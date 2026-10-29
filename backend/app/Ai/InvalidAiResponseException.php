<?php

namespace App\Ai;

/** El modelo respondió, pero no con un JSON válido (solo se pierde ese lote, no todo el análisis). */
class InvalidAiResponseException extends AiUnavailableException {}
