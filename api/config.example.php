<?php
defined('LMT_GUARD') || exit('forbidden');
// api/config.example.php
// Copia a `config.php` y rellena los valores. `config.php` está en .gitignore.

return [
    // Base de datos: MySQL/MariaDB recomendado en producción.
    // Para desarrollo rápido puedes usar SQLite con DSN sqlite:/ruta/db.sqlite
    'db' => [
        'dsn'      => 'mysql:host=127.0.0.1;dbname=la_mejor_taza;charset=utf8mb4',
        'user'     => 'lmt_app',
        'password' => 'CAMBIAR_EN_PRODUCCION',
        // Para SQLite descomentar y comentar las anteriores:
        // 'dsn'      => 'sqlite:' . __DIR__ . '/../db/la-mejor-taza.sqlite',
        // 'user'     => null,
        // 'password' => null,
    ],

    // Pimienta para hashes adicionales (NUNCA cambiar en caliente sin migrar).
    // Genera con: php -r "echo bin2hex(random_bytes(32));"
    'pepper' => 'CAMBIAR_POR_64_HEX_CARACTERES',

    // Secret para HMAC de tokens y CSRF cuando no hay sesión.
    'app_secret' => 'CAMBIAR_POR_64_HEX_CARACTERES',

    // Cookie / sesión.
    'session' => [
        'name'     => 'lmt_sid',
        'lifetime' => 60 * 60 * 8,   // 8 horas
        'secure'   => true,           // requiere HTTPS en producción
        'samesite' => 'Strict',
        'path'     => '/',
        'domain'   => '',             // dejar vacío = host actual
    ],

    // Origen permitido (para chequeo de Origin/Referer en POST/PUT/DELETE).
    // Lista blanca; usa el dominio real en producción.
    'allowed_origins' => [
        'https://lamejortaza.co',
        'http://localhost:8000',
        'http://127.0.0.1:8000',
    ],

    // URL pública base del sitio, usada por api/qr/{id}.png para el enlace del
    // QR ({base}/s/{id}).
    // RECOMENDADO: déjalo VACÍO. Así se deriva automáticamente del request e
    // incluye el subdirectorio donde vive la app (p. ej. /lamejortaza), igual
    // que el frontend. Si lo defines, DEBE ser la URL completa CON subdirectorio
    // (p. ej. 'https://tic.narino.gov.co/lamejortaza'), sin barra final.
    'public_base_url' => '',

    // Envío de correo — lo usa el módulo de promotores para entregar la
    // contraseña temporal cuando el administrador verifica una inscripción.
    //
    //   transport = 'smtp'  RECOMENDADO en producción. El correo sale
    //                       autenticado desde el dominio institucional y no
    //                       acaba en spam.
    //   transport = 'mail'  Usa la función mail() de PHP. Sirve si el hosting
    //                       tiene un MTA local, pero muchos proveedores la
    //                       tienen capada o marcan el correo como spam.
    //   transport = 'log'   No envía nada: escribe el mensaje en `log_file`.
    //                       Para desarrollo y para probar plantillas.
    'mail' => [
        'transport' => 'mail',
        'from'      => 'hosting@narino.gov.co',
        'from_name' => '',                    // vacío = el nombre del evento (Panel → Configuración del evento)
        'reply_to'  => '',                    // vacío = sin Reply-To
        // Déjalo VACÍO: el fichero se crea entonces en el directorio temporal
        // del sistema, FUERA del document root. Con la ruta anterior
        // (db/correo-salida.log) cualquiera podía descargarlo por HTTP y leer
        // las contraseñas temporales de los promotores en claro.
        'log_file'  => '',
        'smtp' => [
            // El buzón institucional funciona sobre Gmail (Google Workspace).
            // OJO: 'password' NO es la contraseña de la cuenta, sino una
            // «contraseña de aplicación» de 16 caracteres que se genera en
            // cuenta de Google → Seguridad → Verificación en dos pasos →
            // Contraseñas de aplicaciones. Con la normal, Gmail responde 535.
            // Y 'user' debe ser la MISMA dirección que 'from': si no, Gmail
            // reescribe el remitente.
            'host'     => 'smtp.gmail.com',
            'port'     => 587,
            'secure'   => 'tls',              // 'tls' (STARTTLS), 'ssl' (puerto 465) o '' (sin cifrar)
            'user'     => 'hosting@narino.gov.co',
            'password' => '',
            'timeout'  => 15,
        ],
    ],

    // Imágenes que suben los promotores (logo de empresa, fotos de producto).
    // `dir` debe ser escribible por PHP. Si lo dejas por defecto, la carpeta
    // uploads/ se crea en la raíz del sitio con un .htaccess que impide
    // ejecutar código dentro.
    'uploads' => [
        'dir'       => __DIR__ . '/../uploads',
        'max_bytes' => 3 * 1024 * 1024,       // 3 MB por archivo
        'max_dim'   => 1600,                  // se redimensiona a este lado máximo
    ],

    // Rate limits (segundos / max hits). Cuentan por IP del visitante —la real,
    // también detrás de Cloudflare (ver 'trusted_proxies')— o por correo.
    // Los de uso público tienen un MÍNIMO en api/lib/RateLimit.php: un valor
    // más bajo aquí se sube a ese mínimo. En una feria cientos de teléfonos
    // comparten el wifi del recinto o la misma antena, y con los de antes
    // (120/min global, 10 consultas/hora del propio pasaporte) la gente se
    // quedaba sin pasaporte a los pocos sellos.
    'rate_limits' => [
        'login'             => ['window' => 600, 'max' => 5],   // 5 intentos / 10 min
        'vote'              => ['window' => 60,  'max' => 30],  // votos / min / IP / stand (wifi compartido)
        'vote_email'        => ['window' => 600, 'max' => 40],  // votos / 10 min / correo
        'pasaporte'         => ['window' => 60,  'max' => 120], // consultas / min / IP (anti-enumeración)
        'pasaporte_correo'  => ['window' => 3600, 'max' => 30], // consultas / hora a un correo SIN su testigo
        'pasaporte_titular' => ['window' => 3600, 'max' => 1200], // el dueño abriendo su pasaporte y su recorrido
        'promotor_registro' => ['window' => 3600, 'max' => 5],  // 5 inscripciones / hora / IP
        'promotor_login'    => ['window' => 900, 'max' => 60],  // intentos / 15 min / IP
        'promotor_upload'   => ['window' => 3600, 'max' => 60], // 60 imágenes / hora / promotor
        'promotor_logo_publico' => ['window' => 3600, 'max' => 10], // logos en la inscripción (sin sesión)
        'perfil_acceso'     => ['window' => 600, 'max' => 200], // puerta del pasaporte / 10 min / IP
        'perfil_visitante'  => ['window' => 600, 'max' => 200], // perfil del visitante / 10 min / IP
        'foto_visitante'    => ['window' => 3600, 'max' => 60], // fotos de perfil / hora / IP (sin sesión)
        'perfil_enlace'     => ['window' => 3600, 'max' => 40], // enlaces de perfil / hora / IP
        'perfil_enlace_correo' => ['window' => 3600, 'max' => 3], // por correo, para no usarlo de arma
        'correo_prueba'     => ['window' => 600, 'max' => 10],  // pruebas de envío desde el panel
        'global'            => ['window' => 60,  'max' => 600], // anti-flood por IP
    ],

    // Token para el endpoint de diagnóstico api/diag.php. Déjalo vacío para
    // MANTENER DIAG CERRADO en producción (recomendado). Para diagnosticar,
    // pon aquí un valor secreto y llama a: api/diag.php?token=EL_TOKEN
    // Genera con: php -r "echo bin2hex(random_bytes(16));"
    'diag_token' => '',

    // Forzar HTTPS (envía 301 a https://). Apaga si haces dev local sin TLS.
    'force_https' => false,

    // Pon true SÓLO si hay un proxy inverso o balanceador TLS delante (es el
    // caso habitual en la infraestructura de la Gobernación). Entonces se hace
    // caso a X-Forwarded-Proto para detectar HTTPS y emitir HSTS. Con false,
    // esa cabecera se ignora: si no hay proxy, cualquiera podría enviarla y
    // hacer creer al servidor que la conexión es segura.
    'trust_proxy' => false,

    // IP de cada visitante. Detrás de Cloudflare se reconoce sola (cabecera
    // CF-Connecting-IP, sólo si la conexión llega desde los rangos publicados
    // por Cloudflare). Si delante hay OTRO proxy o balanceador, pon aquí su IP
    // o su red (CIDR), p. ej. ['10.0.0.5', '10.20.0.0/16']: entonces se lee
    // X-Forwarded-For de ese proxy. Vacío y con 'trust_proxy' => true, se cree
    // a cualquier proxy de la red interna (10.x, 172.16–31.x, 192.168.x,
    // 127.x), nunca a una IP pública. Panel → Sistema enseña cómo te ve el
    // servidor; si sale una IP interna, falta configurar esto.
    'trusted_proxies' => [],

    // Modo debug (NUNCA true en producción).
    'debug' => false,
];
