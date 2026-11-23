<?php
namespace App\Core;

/**
 * Protección CSRF basada en un token por sesión (synchronizer token pattern).
 *
 * - Csrf::field()  → imprime el <input type="hidden"> para incluir en cada <form method="POST">.
 * - Csrf::verify() → lo usa el Router para validar todas las peticiones POST.
 */
class Csrf
{
    private const SESSION_KEY = 'csrf_token';
    private const FIELD_NAME  = '_csrf';

    public static function token(): string
    {
        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::SESSION_KEY];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="' . self::FIELD_NAME . '" value="'
            . htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function verify(): bool
    {
        $sent     = $_POST[self::FIELD_NAME] ?? '';
        $expected = $_SESSION[self::SESSION_KEY] ?? '';

        return is_string($sent) && $expected !== '' && hash_equals($expected, $sent);
    }
}
