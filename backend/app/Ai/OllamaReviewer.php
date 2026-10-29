<?php

namespace App\Ai;

use App\Models\AiFinding;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Revisión con un modelo local servido por Ollama (POST /api/chat).
 * Se pide la respuesta en JSON con un esquema fijo y después se valida: los hallazgos de
 * archivos que no están en el lote, con gravedad o categoría desconocidas, se descartan.
 */
class OllamaReviewer implements AiReviewer
{
    public function __construct(
        private readonly string $url,
        private readonly string $model,
        private readonly int $timeout = 300,
        private readonly int $context = 12288,
        private readonly int $maxOutput = 1024,
        private readonly string $keepAlive = '30m',
    ) {}

    public function model(): string
    {
        return $this->model;
    }

    public function review(string $diff, array $paths): ReviewResult
    {
        try {
            $response = Http::timeout($this->timeout)
                ->connectTimeout(10)
                ->acceptJson()
                ->post($this->url.'/api/chat', [
                    'model' => $this->model,
                    'stream' => false,
                    'format' => self::schema(),
                    // El modelo se queda cargado entre lotes y análisis (cargarlo cuesta segundos)
                    'keep_alive' => $this->keepAlive,
                    'options' => [
                        'temperature' => 0.1,
                        'num_ctx' => $this->context,
                        // Tope de la respuesta: los modelos pequeños a veces no paran de escribir
                        'num_predict' => $this->maxOutput,
                    ],
                    'messages' => [
                        ['role' => 'system', 'content' => self::SYSTEM_PROMPT],
                        ['role' => 'user', 'content' => "Revisa este diff de un pull request:\n\n".$diff],
                    ],
                ]);
        } catch (ConnectionException $e) {
            throw new AiUnavailableException('No se pudo conectar con el servicio de IA (Ollama). Comprueba que está arrancado.');
        }

        if ($response->status() === 404) {
            throw new AiUnavailableException("El modelo {$this->model} no está descargado. Ejecuta: docker compose exec ollama ollama pull {$this->model}");
        }
        if ($response->failed()) {
            throw new AiUnavailableException('El servicio de IA respondió con un error ('.$response->status().').');
        }

        $content = (string) data_get($response->json(), 'message.content', '');
        $data = json_decode($content, true);
        if (! is_array($data) && data_get($response->json(), 'done_reason') === 'length') {
            throw new InvalidAiResponseException('La respuesta de la IA era demasiado larga y se cortó. Prueba de nuevo o sube CODEFLOW_AI_MAX_OUTPUT.');
        }
        if (! is_array($data)) {
            throw new InvalidAiResponseException('La IA no devolvió una respuesta válida. Prueba de nuevo.');
        }

        return new ReviewResult(
            summary: self::clean((string) ($data['summary'] ?? ''), 1500),
            findings: self::validFindings($data['findings'] ?? [], $paths),
        );
    }

    /**
     * @return array<int,array{path:string,line:?int,severity:string,category:string,title:string,explanation:string,suggestion:?string}>
     */
    public static function validFindings(mixed $findings, array $paths): array
    {
        if (! is_array($findings)) {
            return [];
        }
        $allowed = array_flip($paths);
        $valid = [];

        foreach (array_slice($findings, 0, 50) as $f) {
            if (! is_array($f)) {
                continue;
            }
            $path = ltrim(trim((string) ($f['path'] ?? '')), '/');
            $severity = strtolower(trim((string) ($f['severity'] ?? '')));
            $category = strtolower(trim((string) ($f['category'] ?? '')));
            $title = self::clean((string) ($f['title'] ?? ''), 200);
            $explanation = self::clean((string) ($f['explanation'] ?? ''), 2000);

            if (! isset($allowed[$path]) || ! in_array($severity, AiFinding::SEVERITIES, true)
                || ! in_array($category, AiFinding::CATEGORIES, true) || $title === '' || $explanation === '') {
                continue;
            }

            $line = filter_var($f['line'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $suggestion = self::clean((string) ($f['suggestion'] ?? ''), 1000);

            $valid[] = [
                'path' => $path,
                'line' => $line === false ? null : $line,
                'severity' => $severity,
                'category' => $category,
                'title' => $title,
                'explanation' => $explanation,
                'suggestion' => $suggestion !== '' ? $suggestion : null,
            ];
        }

        return $valid;
    }

    /** Quita bloques de código (no se guarda código en la base de datos), espacios y longitud. */
    private static function clean(string $text, int $max): string
    {
        $text = preg_replace('/```.*?(```|$)/s', '[fragmento de código omitido]', $text) ?? '';

        return Str::limit(trim(preg_replace('/[ \t]+/', ' ', $text) ?? ''), $max, '…');
    }

    /** Esquema JSON de la respuesta (Ollama "structured outputs"). */
    public static function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'summary' => ['type' => 'string'],
                'findings' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'path' => ['type' => 'string'],
                            'line' => ['type' => 'integer'],
                            'severity' => ['type' => 'string', 'enum' => AiFinding::SEVERITIES],
                            'category' => ['type' => 'string', 'enum' => AiFinding::CATEGORIES],
                            'title' => ['type' => 'string'],
                            'explanation' => ['type' => 'string'],
                            'suggestion' => ['type' => 'string'],
                        ],
                        'required' => ['path', 'severity', 'category', 'title', 'explanation'],
                    ],
                ],
            ],
            'required' => ['summary', 'findings'],
        ];
    }

    private const SYSTEM_PROMPT = <<<'TXT'
Eres un revisor de código senior. Revisas el diff de un pull request y respondes SIEMPRE en español.

Formato del diff:
- "### archivo: ruta (estado)" empieza cada archivo.
- Cada línea lleva el número de línea del archivo nuevo, una marca y el texto:
  "+" línea añadida, "-" línea borrada (sin número), " " línea de contexto sin cambios.
- "… (N líneas más de este archivo sin revisar)" indica que el resto del archivo no se muestra:
  no comentes nada sobre esa parte.

Qué debes hacer:
- Busca solo problemas reales en las líneas añadidas o modificadas: errores (bug), fallos de
  seguridad (security), rendimiento (performance), estilo (style) y mantenibilidad (maintainability).
- No inventes problemas. Si el código está bien, devuelve "findings" vacío.
- "line" es el número de línea del archivo nuevo donde está el problema.
- "severity": "high" si rompe algo o es un riesgo de seguridad, "medium" si conviene arreglarlo, "low" si es menor.
- "title": una frase corta. "explanation": por qué es un problema. "suggestion": cómo arreglarlo, en palabras.
- No copies código en las respuestas: descríbelo con palabras.
- Como máximo 6 hallazgos, los más importantes. Sé breve: explicaciones de 1 o 2 frases.
- "summary": 1 o 2 frases sobre qué hace el cambio y su calidad general.
TXT;
}
