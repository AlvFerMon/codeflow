<?php

namespace App\Support;

use App\Models\User;

/**
 * Preferencias de accesibilidad y apariencia de cada usuario (users.preferences, JSON).
 * Se guardan en la cuenta para que se apliquen igual en cualquier navegador y en las apps.
 */
final class Preferences
{
    public const THEMES = ['system', 'light', 'dark'];

    public const FONT_SIZES = ['normal', 'large', 'larger'];

    public const DEFAULTS = [
        'theme' => 'system',          // claro / oscuro / el del sistema
        'font_size' => 'normal',      // normal (100 %), large (112,5 %), larger (125 %)
        'high_contrast' => false,     // textos y bordes con más contraste
        'reduce_motion' => false,     // sin animaciones aunque el sistema no lo pida
        'underline_links' => false,   // enlaces siempre subrayados
        'wrap_code' => false,         // el código largo salta de línea en vez de desplazarse
    ];

    /** Preferencias del usuario completadas con los valores por defecto. */
    public static function for(?User $user): array
    {
        $saved = is_array($user?->preferences) ? $user->preferences : [];

        return array_merge(self::DEFAULTS, array_intersect_key($saved, self::DEFAULTS));
    }

    /** Reglas de validación (todas opcionales: se puede cambiar solo una). */
    public static function rules(): array
    {
        return [
            'theme' => 'sometimes|in:'.implode(',', self::THEMES),
            'font_size' => 'sometimes|in:'.implode(',', self::FONT_SIZES),
            'high_contrast' => 'sometimes|boolean',
            'reduce_motion' => 'sometimes|boolean',
            'underline_links' => 'sometimes|boolean',
            'wrap_code' => 'sometimes|boolean',
        ];
    }

    /** Guarda los cambios (solo claves conocidas) y devuelve las preferencias completas. */
    public static function update(User $user, array $changes): array
    {
        $changes = array_intersect_key($changes, self::DEFAULTS);
        foreach (['high_contrast', 'reduce_motion', 'underline_links', 'wrap_code'] as $flag) {
            if (array_key_exists($flag, $changes)) {
                $changes[$flag] = filter_var($changes[$flag], FILTER_VALIDATE_BOOLEAN);
            }
        }

        $user->forceFill(['preferences' => array_merge(self::for($user), $changes)])->save();

        return self::for($user);
    }

    /** Atributos para <html> (los usa el CSS de resources/css/preferences.css). */
    public static function htmlAttributes(?User $user): array
    {
        $p = self::for($user);

        return array_filter([
            'data-font-size' => $p['font_size'] !== 'normal' ? $p['font_size'] : null,
            'data-contrast' => $p['high_contrast'] ? 'high' : null,
            'data-motion' => $p['reduce_motion'] ? 'reduce' : null,
            'data-links' => $p['underline_links'] ? 'underline' : null,
            'data-wrap-code' => $p['wrap_code'] ? 'true' : null,
        ]);
    }

    /** Los mismos atributos ya escapados, para escribirlos en la etiqueta <html>. */
    public static function htmlAttributeString(?User $user): string
    {
        $attributes = self::htmlAttributes($user);
        if ($user) {
            $attributes['data-preferences-url'] = route('profile.preferences');
        }

        return collect($attributes)->map(fn ($v, $k) => $k.'="'.e($v).'"')->implode(' ');
    }

    /** Tema guardado en la cuenta, solo si el usuario lo eligió (si no, manda el del navegador). */
    public static function savedTheme(?User $user): ?string
    {
        $theme = is_array($user?->preferences) ? ($user->preferences['theme'] ?? null) : null;

        return in_array($theme, self::THEMES, true) ? $theme : null;
    }
}
