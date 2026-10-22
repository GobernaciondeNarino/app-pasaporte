<?php
namespace LMT;
defined('LMT_GUARD') || exit('forbidden');

use PDO;

final class RateLimit
{
    /**
     * Límites de reserva, por si el bucket no está en api/config.php.
     *
     * Hace falta porque config.php es del administrador y NO gana claves al
     * actualizar: un límite nuevo que sólo estuviera en config.example.php
     * quedaría sin efecto en toda instalación ya existente —y sin efecto aquí
     * significa sin límite ninguno, que es peor que no haberlo añadido—. Lo que
     * ponga el administrador manda; esto sólo cubre el hueco.
     */
    private const RESERVA = [
        'login'                 => ['window' => 600,  'max' => 5],
        'vote'                  => ['window' => 60,   'max' => 1],
        'vote_email'            => ['window' => 600,  'max' => 12],
        'pasaporte'             => ['window' => 60,   'max' => 120],
        // Consultas SIN testigo de un mismo correo: quien no es su dueño.
        'pasaporte_correo'      => ['window' => 3600, 'max' => 30],
        // Consultas del propio titular (traen su testigo). Sólo frena un bucle
        // desbocado: el dueño abre su pasaporte y su recorrido todas las veces
        // que quiera, que es justo lo que hace en una feria.
        'pasaporte_titular'     => ['window' => 3600, 'max' => 1200],
        'promotor_registro'     => ['window' => 3600, 'max' => 5],
        'promotor_login'        => ['window' => 900,  'max' => 15],
        'promotor_upload'       => ['window' => 3600, 'max' => 60],
        'promotor_logo_publico' => ['window' => 3600, 'max' => 10],
        'perfil_visitante'      => ['window' => 600,  'max' => 30],
        'foto_visitante'        => ['window' => 3600, 'max' => 12],
        'perfil_enlace'         => ['window' => 3600, 'max' => 10],
        'perfil_enlace_correo'  => ['window' => 3600, 'max' => 3],
        // Puerta del ciudadano (correo -> testigo). Generoso por IP, porque en
        // la feria una familia entera entra desde el mismo wifi; estrecho por
        // correo, que es lo que frena a quien prueba claves de una persona.
        'perfil_acceso'         => ['window' => 600,  'max' => 30],
        'perfil_acceso_correo'  => ['window' => 900,  'max' => 8],
        'correo_prueba'         => ['window' => 600,  'max' => 10],
        'global'                => ['window' => 60,   'max' => 120],
    ];

    /**
     * Mínimos para una feria. Un límite por IP que esté por DEBAJO de éstos no
     * se aplica: se usa el mínimo.
     *
     * Por qué existen
     * ---------------
     * En la 1ª Feria de Atención al Ciudadano los visitantes empezaron a ver
     * «No fue posible cargar tu pasaporte» y el recorrido vacío al cabo de unos
     * cuantos sellos. Eran estos límites: el instalador escribía en config.php
     * 120 peticiones por minuto por IP, 1 voto por minuto por IP y espacio, 20
     * consultas del pasaporte por minuto por IP… y en una feria cientos de
     * personas salen a internet por la MISMA IP —el wifi del recinto, el NAT de
     * los operadores móviles y, sobre todo, Cloudflare, que el servidor veía
     * como único cliente—. Diez móviles con el tablero abierto bastaban para
     * agotar el cupo de todo el mundo.
     *
     * Como esos valores viven en el config.php de cada instalación, cambiar sólo
     * los de reserva no arreglaba nada: config.php manda. El mínimo sí. Quien
     * quiera más holgura la pone en config.php; menos que esto, no.
     */
    private const MINIMOS = [
        'global'           => ['window' => 60,   'max' => 600],
        'vote'             => ['window' => 60,   'max' => 30],
        'vote_email'       => ['window' => 600,  'max' => 40],
        'pasaporte'        => ['window' => 60,   'max' => 120],
        'pasaporte_correo' => ['window' => 3600, 'max' => 30],
        'perfil_acceso'    => ['window' => 600,  'max' => 200],
        'perfil_visitante' => ['window' => 600,  'max' => 200],
        'foto_visitante'   => ['window' => 3600, 'max' => 60],
        'perfil_enlace'    => ['window' => 3600, 'max' => 40],
        'promotor_login'   => ['window' => 900,  'max' => 60],
    ];

    /**
     * Como hit(), pero si se pasa del límite responde 429 diciendo CUÁL fue.
     *
     * El nombre del límite viaja en `message` («limite:pasaporte_correo») y la
     * pantalla lo pone en el código interno del error. Sin él, un «demasiadas
     * solicitudes» en la feria no decía si era el visitante, su wifi o todo el
     * evento. No es información sensible: son nombres de reglas.
     */
    public static function exigir(string $bucket, string $subject): void
    {
        if (!self::hit($bucket, $subject)) Response::error(429, 'rate_limited', 'limite:' . $bucket);
    }

    /** La regla que se aplica de verdad: la de config.php, con su mínimo. */
    public static function regla(string $bucket): ?array
    {
        $cfg  = Config::get('rate_limits', []);
        $rule = $cfg[$bucket] ?? self::RESERVA[$bucket] ?? null;
        if (!$rule) return null;
        $min = self::MINIMOS[$bucket] ?? null;
        if ($min !== null) {
            // Se comparan como ritmo (peticiones por segundo): config.php puede
            // usar otra ventana.
            $ritmo    = (int) $rule['max'] / max(1, (int) $rule['window']);
            $ritmoMin = $min['max'] / $min['window'];
            if ($ritmo < $ritmoMin) $rule = $min;
        }
        return ['window' => (int) $rule['window'], 'max' => (int) $rule['max']];
    }

    /**
     * Rate limit por (clave, ventana). Devuelve true si la solicitud es permitida.
     * Implementación: contador en DB con ventana fija. Suficiente para feria/POS.
     */
    public static function hit(string $bucket, string $subject): bool
    {
        $rule = self::regla($bucket);
        if (!$rule) return true;

        $window = $rule['window'];
        $max    = $rule['max'];
        $now    = time();
        $key    = hash('sha256', $bucket . '|' . $subject);

        $pdo = Db::pdo();

        try {
            // Incremento ATÓMICO: se escribe primero y se decide después con el
            // valor ya persistido.
            //
            // La versión anterior hacía SELECT, decidía en PHP y luego UPDATE.
            // Entre el SELECT y el UPDATE no había nada: N peticiones
            // simultáneas leían todas el mismo contador y todas pasaban. Con
            // 200 intentos de login en paralelo se probaban 200 contraseñas en
            // la ventana que debía permitir 5.
            $driver = $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
            if ($driver === 'mysql') {
                $sql = 'INSERT INTO rate_limits (id, bucket, hits, window_start)
                        VALUES (:id, :b, 1, :ws)
                        ON DUPLICATE KEY UPDATE
                          hits = IF(:now1 - window_start >= :win1, 1, hits + 1),
                          window_start = IF(:now2 - window_start >= :win2, :now3, window_start)';
                $pdo->prepare($sql)->execute([
                    ':id' => $key, ':b' => $bucket, ':ws' => $now,
                    ':now1' => $now, ':win1' => $window,
                    ':now2' => $now, ':win2' => $window, ':now3' => $now,
                ]);
            } else {
                $sql = 'INSERT INTO rate_limits (id, bucket, hits, window_start)
                        VALUES (:id, :b, 1, :ws)
                        ON CONFLICT(id) DO UPDATE SET
                          hits = CASE WHEN :now1 - rate_limits.window_start >= :win1 THEN 1 ELSE rate_limits.hits + 1 END,
                          window_start = CASE WHEN :now2 - rate_limits.window_start >= :win2 THEN :now3 ELSE rate_limits.window_start END';
                $pdo->prepare($sql)->execute([
                    ':id' => $key, ':b' => $bucket, ':ws' => $now,
                    ':now1' => $now, ':win1' => $window,
                    ':now2' => $now, ':win2' => $window, ':now3' => $now,
                ]);
            }

            $sel = $pdo->prepare('SELECT hits FROM rate_limits WHERE id = :id');
            $sel->execute([':id' => $key]);
            $hits = (int) $sel->fetchColumn();

            return $hits <= $max;
        } catch (\Throwable $e) {
            // Antes devolvía Config::debug(), es decir false en producción: un
            // fallo de permisos sobre la tabla convertía el 100% del tráfico en
            // 429, incluido el login del administrador, y el mensaje de error
            // apuntaba al sitio equivocado. Ahora se distingue el bucket:
            // en los que protegen credenciales se sigue cerrando, y en el resto
            // se deja pasar para no tumbar la feria entera por una tabla rota.
            error_log('[lmt][ratelimit][' . $bucket . '] ' . $e->getMessage());
            return !in_array($bucket, ['login', 'promotor_login', 'promotor_registro'], true);
        }
    }

    public static function ipHash(): string
    {
        return hash_hmac('sha256', self::ipCliente(), (string) Config::get('app_secret', ''));
    }

    /**
     * Rangos de Cloudflare (https://www.cloudflare.com/ips/, octubre de 2026).
     *
     * Cuando la conexión llega desde uno de ellos, quien está al otro lado es
     * Cloudflare y no el visitante: la IP del visitante viene en la cabecera
     * CF-Connecting-IP, que Cloudflare escribe siempre y sobrescribe si el
     * cliente la manda. Si Cloudflare publica rangos nuevos, se añaden aquí o en
     * 'trusted_proxies' de config.php.
     */
    /** Redes privadas, de loopback y CGNAT: donde vive un proxy interno. */
    private const PRIVADAS = [
        '10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16', '127.0.0.0/8', '100.64.0.0/10',
        'fc00::/7', '::1/128',
    ];

    private const CLOUDFLARE = [
        '173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22', '103.31.4.0/22',
        '141.101.64.0/18', '108.162.192.0/18', '190.93.240.0/20', '188.114.96.0/20',
        '197.234.240.0/22', '198.41.128.0/17', '162.158.0.0/15', '104.16.0.0/13',
        '104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22',
        '2400:cb00::/32', '2606:4700::/32', '2803:f800::/32', '2405:b500::/32',
        '2405:8100::/32', '2a06:98c0::/29', '2c0f:f248::/32',
    ];

    /**
     * La IP del visitante, no la del último salto.
     *
     * El servidor de producción está detrás de Cloudflare, y hasta la v3.0.0
     * los límites se contaban por REMOTE_ADDR: es decir, por el nodo de
     * Cloudflare. Todos los asistentes de la feria compartían un puñado de IP
     * y, con ellas, el mismo cupo de peticiones.
     *
     * Las cabeceras con la IP del cliente se pueden falsear, así que sólo se
     * leen cuando quien se conecta es de confianza:
     *   - CF-Connecting-IP, si REMOTE_ADDR está en los rangos de Cloudflare;
     *   - X-Forwarded-For / X-Real-IP, si REMOTE_ADDR está en
     *     'trusted_proxies' de config.php o si 'trust_proxy' es true.
     * Quien se salte el proxy y hable directamente con el servidor no está en
     * esos rangos, y sus cabeceras se ignoran.
     */
    public static function ipCliente(): string
    {
        return self::resolverIp()['ip'];
    }

    /**
     * Cómo se averiguó la IP: 'cloudflare', 'proxy' o 'directa'. Lo enseña
     * Panel → Sistema para comprobar, tras instalar, que el servidor ve la IP
     * de cada visitante y no la de Cloudflare o la de la red interna.
     */
    public static function origenIp(): string
    {
        return self::resolverIp()['via'];
    }

    /** @return array{ip:string, via:string} */
    private static function resolverIp(): array
    {
        $remota = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
        if (!filter_var($remota, FILTER_VALIDATE_IP)) return ['ip' => '0.0.0.0', 'via' => 'directa'];

        $cf = trim((string) ($_SERVER['HTTP_CF_CONNECTING_IP'] ?? ''));
        if ($cf !== '' && filter_var($cf, FILTER_VALIDATE_IP) && self::enRangos($remota, self::CLOUDFLARE)) {
            return ['ip' => $cf, 'via' => 'cloudflare'];
        }

        // Sin lista explícita, 'trust_proxy' sólo vale para un proxy de la red
        // interna. Si la conexión llega desde una IP pública que no es de
        // Cloudflare, es el propio visitante —o alguien que entra directo al
        // servidor saltándose Cloudflare— y su X-Forwarded-For no se cree: si
        // no, bastaría inventarlo para esquivar el límite del login.
        $confiables = array_values(array_filter(array_map('strval', (array) Config::get('trusted_proxies', []))));
        $hayProxy = $confiables
            ? self::enRangos($remota, $confiables)
            : ((bool) Config::get('trust_proxy', false) && self::enRangos($remota, self::PRIVADAS));
        if (!$hayProxy) return ['ip' => $remota, 'via' => 'directa'];

        // X-Forwarded-For se lee de derecha a izquierda: el último valor lo
        // añadió el proxy de confianza; los de la izquierda los puede escribir
        // cualquiera. Se salta lo que sea también un proxy conocido.
        $saltos = array_reverse(array_map('trim', explode(',', (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''))));
        foreach ($saltos as $ip) {
            if (!filter_var($ip, FILTER_VALIDATE_IP)) break;
            if (self::enRangos($ip, array_merge($confiables, self::CLOUDFLARE))) continue;
            return ['ip' => $ip, 'via' => 'proxy'];
        }
        $real = trim((string) ($_SERVER['HTTP_X_REAL_IP'] ?? ''));
        return filter_var($real, FILTER_VALIDATE_IP)
            ? ['ip' => $real, 'via' => 'proxy']
            : ['ip' => $remota, 'via' => 'directa'];
    }

    /** ¿Está la IP en alguno de los rangos CIDR? IPv4 e IPv6. */
    public static function enRangos(string $ip, array $rangos): bool
    {
        $bin = @inet_pton($ip);
        if ($bin === false) return false;
        foreach ($rangos as $rango) {
            [$red, $bits] = array_pad(explode('/', (string) $rango, 2), 2, null);
            $redBin = @inet_pton((string) $red);
            if ($redBin === false || strlen($redBin) !== strlen($bin)) continue;
            $bits = $bits === null ? strlen($bin) * 8 : max(0, min(strlen($bin) * 8, (int) $bits));
            $bytes = intdiv($bits, 8);
            $resto = $bits % 8;
            if ($bytes && substr($bin, 0, $bytes) !== substr($redBin, 0, $bytes)) continue;
            if ($resto) {
                $mascara = (0xFF << (8 - $resto)) & 0xFF;
                if ((ord($bin[$bytes]) & $mascara) !== (ord($redBin[$bytes]) & $mascara)) continue;
            }
            return true;
        }
        return false;
    }
}
