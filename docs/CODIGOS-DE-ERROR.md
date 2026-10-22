# Códigos internos de error

Desde la v3.0.1, cada mensaje de error que ve una persona lleva debajo, en letra
pequeña, un **código interno**:

```
Código PAS-429-pasaporte_correo-#k3f9q2
```

Quien reciba una captura o un dictado de ese código sabe, sin pedir más datos,
**en qué pantalla** pasó, **qué respondió el servidor** y **por qué**. Si el
error quedó registrado, la **referencia** lleva a la línea exacta del log de PHP.

El código se puede copiar con una pulsación larga sobre él.

---

## Cómo se lee

```
PAS - 429 - pasaporte_correo - #k3f9q2
 │     │          │               └── referencia (sólo en 429 y 5xx)
 │     │          └── motivo: el límite que saltó o el código del servidor
 │     └── estado: HTTP, o RED / OFF / CLI
 └── pantalla
```

### 1. Pantalla

| Prefijo | Pantalla | Ruta |
| --- | --- | --- |
| `PAS` | Pasaporte | `/pasaporte` |
| `REC` | Mi recorrido | `/recorrido` |
| `VOT` | Voto desde el QR | `/s/<espacio>` |
| `INS` | Inscripción de espacios | `/inscripcion` |
| `PER` | Perfil del visitante | `/perfil` |
| `PTA` | Portal del promotor | `/promotor` |
| `ADM` | Panel de administración | `/admin/…` |
| `TAB` | Tablero y ficha pública | `/festival/…` |
| `INI` | Portada y cualquier otra | `/` |

`PAS-TAB`, `REC-TAB` y `VOT-TAB` significan que en esa pantalla lo que falló
fue **la lista de espacios** (`/dashboard`), no el pasaporte.

### 2. Estado

| Estado | Qué significa |
| --- | --- |
| `400`, `422` | Datos no válidos (un campo mal escrito). Lo corrige la persona. |
| `401`, `403` | Sin sesión, sin permiso o token caducado. |
| `404` | No existe (espacio borrado, enlace viejo). |
| `409` | Ya hecho antes (`ya_votaste`, `ya_verificado`). |
| `413` | Lo enviado pesa demasiado (imagen, configuración). |
| `429` | **Un límite de uso saltó.** El motivo dice cuál (tabla 3). |
| `500`, `503` | Fallo del servidor o de la base de datos. Mirar el log con la referencia. |
| `RED` | El teléfono no llegó al servidor: sin señal, wifi caído o el servidor no respondió. |
| `OFF` | La aplicación no logró arrancar su conexión con el servidor (`/auth/me`). Lo que sigue es la causa, p. ej. `PAS-OFF-429-global-#…` o `VOT-OFF-RED`. |
| `CLI` | Lo decidió el propio teléfono sin preguntar al servidor (p. ej. `VOT-CLI-rate_limited`: tocó dos veces el mismo espacio en menos de un minuto). |

### 3. Motivo cuando el estado es 429

El motivo es el nombre del límite que saltó. Los valores son los efectivos: lo
que diga `api/config.php`, pero **nunca por debajo del mínimo** de
`api/lib/RateLimit.php` (marcado con *).

| Motivo | Cuenta | Límite | Qué hacer si aparece en la feria |
| --- | --- | --- | --- |
| `global` | Todas las peticiones de una IP | 600/min* | Si sale a muchos a la vez: el servidor no está viendo la IP real. Ver «Comprobar la IP» abajo. |
| `pasaporte` | Consultas de pasaportes por IP | 120/min* | Igual que `global`. |
| `pasaporte_correo` | Consultas a un correo **sin** su testigo | 30/hora* | Normal si alguien prueba correos ajenos. Si es el dueño: le falta el testigo; que entre por la puerta del pasaporte con su correo. |
| `pasaporte_titular` | El dueño abriendo su pasaporte y su recorrido | 1200/hora | No debería verse. |
| `vote` | Votos por IP y espacio | 30/min* | Muchos votando el mismo espacio desde el mismo wifi; esperar unos segundos. |
| `vote_email` | Votos por correo | 40 cada 10 min* | Alguien votando muy rápido con el mismo correo. |
| `perfil_acceso` | Puerta del pasaporte/recorrido/perfil por IP | 200 cada 10 min* | Igual que `global`. |
| `perfil_acceso_correo` | Intentos de entrar a un perfil **con clave** | 8 cada 15 min | Alguien probando la clave de otra persona. |
| `perfil_visitante` | Lecturas y cambios del perfil por IP | 200 cada 10 min* | Igual que `global`. |
| `foto_visitante` | Fotos de perfil por IP | 60/hora* | |
| `perfil_enlace` | Enlaces de perfil por correo enviados por IP | 40/hora* | |
| `perfil_enlace_correo` | Enlaces de perfil a un mismo correo | 3/hora | Evita usar el formulario para llenar el buzón de alguien. |
| `login` | Inicio de sesión del panel por IP | 5 cada 10 min | Esperar 10 minutos. |
| `promotor_login` | Inicio de sesión de promotores por IP | 60 cada 15 min* | |
| `promotor_registro` | Inscripciones por IP | 5/hora | |
| `promotor_upload` | Imágenes por promotor | 60/hora | |
| `promotor_logo_publico` | Logos en la inscripción, sin sesión | 10/hora | |
| `correo_prueba` | Pruebas de envío desde el panel | 10 cada 10 min | |

`429 cuenta_bloqueada` (sin `limite:`) es otra cosa: la cuenta de un promotor
quedó bloqueada por intentos fallidos.

### 4. Motivo con otros estados

Es el código que devolvió el servidor, tal cual. Los más frecuentes:

| Código | Significa |
| --- | --- |
| `ya_votaste` | Ese correo ya votó en ese espacio (no se enseña código: no es un fallo). |
| `invalid_credentials` | Correo o contraseña del panel equivocados. |
| `unauthorized` | La sesión del panel caducó. |
| `csrf_invalid` | La página llevaba mucho tiempo abierta; recargar. |
| `origin_not_allowed` | El dominio no está en `allowed_origins` de `api/config.php`. |
| `token_invalido` | El testigo o el enlace del perfil ya no vale. |
| `inscripciones_cerradas` | La inscripción está cerrada en la configuración del evento. |
| `stand_no_existe` | El QR apunta a un espacio borrado. |
| `bad_email`, `correo_invalido` | Correo mal escrito. |
| `payload_too_large` | Imagen o configuración demasiado grande. |
| `internal_error` | Excepción no controlada en PHP: la referencia está en el log. |
| `db_unavailable` | No hay conexión con la base de datos. |
| `api_off` | (sólo `VOT-OFF-…`) El voto no se envió porque no había conexión con el servidor: **no quedó registrado**. |
| `espera` | (`PAS-TAB-espera`, `VOT-TAB-espera`) La lista de espacios aún no ha llegado; se reintenta sola. |

### 5. Referencia (`#k3f9q2`)

Seis caracteres que identifican **esa respuesta concreta**. El servidor los
escribe en el log de errores de PHP para todos los 429 y 5xx:

```
[lmt][error] ref=k3f9q2 429 rate_limited limite:pasaporte_correo pasaportes/v***@correo.co
[lmt][unhandled] ref=a8m2xp SQLSTATE[42S02]: Base table or view not found … @ /api/routes/votos.php:140
```

El correo de la ruta va enmascarado. Para encontrar la línea, buscar la
referencia en el log del hosting (cPanel → *Errores* o el `error_log` de la
carpeta `api/`):

```sh
grep "ref=k3f9q2" error_log
```

Los errores 4xx que no son 429 no se registran (son datos mal escritos, no
fallos del sistema), así que su código no lleva referencia.

---

## Comprobar la IP (lo primero si salen muchos 429)

Detrás de Cloudflare todas las peticiones llegan al servidor desde unas pocas
IP de Cloudflare. Si el servidor no leyera la IP real de cada visitante, toda
la feria compartiría un solo cupo y se quedaría sin pasaporte a los pocos
minutos. Eso fue lo que pasó antes de la v3.0.1.

1. Desde un teléfono **con datos móviles**, entrar al panel → **Sistema**.
2. El recuadro **«Cómo te ve el servidor»** enseña la IP y por dónde llegó:
   - `vía Cloudflare` con la IP del teléfono: **correcto**.
   - `vía conexión directa` con una IP **interna** (10.x, 172.16–31.x,
     192.168.x): hay un proxy delante que no está configurado. Añadir su IP a
     `'trusted_proxies'` en `api/config.php` (ver `api/config.example.php`).
   - `vía conexión directa` con la IP pública del teléfono: correcto si no hay
     ni Cloudflare ni proxy delante.

## Qué hace la aplicación sola ante un fallo

- Las **lecturas** (pasaporte, recorrido, lista de espacios) se reintentan dos
  veces seguidas esperando lo que pida el servidor (`Retry-After`), y después
  cada pocos segundos mientras la pantalla esté abierta.
- El **pasaporte y el recorrido** enseñan la **copia guardada en el teléfono**
  si el servidor no responde, con un aviso y su código. Los sellos no
  desaparecen.
- Si la aplicación no arrancó (`OFF`), lo reintenta sola con esperas
  crecientes (2 s, 4 s, 8 s… hasta 30 s) y, al conectar, las pantallas se
  cargan solas.
- El **voto nunca se da por hecho** si no llegó al servidor: la persona ve
  «tu voto NO se registró» y el código `VOT-OFF-…` o `VOT-RED`, y vuelve a
  tocar su calificación.
