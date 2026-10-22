<?php
namespace LMT;
defined('LMT_GUARD') || exit('forbidden');

final class Response
{
    public static function json($data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function ok($data = null): void
    {
        self::json(['ok' => true, 'data' => $data], 200);
    }

    /**
     * Error con código estable y referencia.
     *
     * `ref` es un identificador corto de ESTA respuesta. La pantalla lo enseña
     * dentro del código interno (p. ej. «PAS-429-global-#k3f9q2») y el servidor
     * lo escribe en su registro junto a la ruta, así que quien reciba el código
     * de un visitante puede encontrar la línea exacta en el log de PHP.
     */
    public static function error(int $status, string $code, ?string $message = null): void
    {
        $ref = self::referencia();
        // Un 429 dice cuándo volver: el cliente espera eso y reintenta solo, en
        // vez de dar por perdido el pasaporte de la persona.
        if ($status === 429 && !headers_sent()) header('Retry-After: 10');
        // Se registran los que dicen algo del sistema —límites y fallos del
        // servidor—, no los errores de validación de un formulario.
        if ($status === 429 || $status >= 500) {
            $ruta = (string) ($_GET['path'] ?? ($_SERVER['REQUEST_URI'] ?? ''));
            // La ruta del pasaporte lleva el correo: al log va enmascarado.
            $ruta = preg_replace('/([^\/])[^\/@]*@/', '$1***@', $ruta) ?? '';
            error_log(sprintf('[lmt][error] ref=%s %d %s %s %s', $ref, $status, $code,
                $message ?? '', substr(preg_replace('/[^\w\/\-.@%*]/', '', $ruta) ?? '', 0, 120)));
        }
        self::json([
            'ok' => false,
            'error' => $code,
            'message' => $message,
            'ref' => $ref,
        ], $status);
    }

    /** Seis caracteres en minúscula, sin los que se confunden al dictarlos. */
    public static function referencia(): string
    {
        static $ref = null;
        if ($ref !== null) return $ref;
        $abc = 'abcdefghjkmnpqrstuvwxyz23456789';
        $ref = '';
        for ($i = 0; $i < 6; $i++) $ref .= $abc[random_int(0, strlen($abc) - 1)];
        return $ref;
    }
}
