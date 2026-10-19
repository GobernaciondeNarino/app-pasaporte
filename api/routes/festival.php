<?php
defined('LMT_GUARD') || exit('forbidden');

use LMT\Ajustes;
use LMT\Response;
use LMT\Security;

/**
 * Configuración del evento.
 *
 * El esquema, los valores de fábrica, las plantillas y la validación viven en
 * api/lib/Evento.php: aquí sólo están las rutas.
 *
 * La lectura es PÚBLICA porque el navegador de cualquier visitante necesita
 * estos valores para pintar el evento: el nombre, los colores, las preguntas
 * del voto y si la inscripción está abierta. No hay nada personal ni secreto
 * aquí. La escritura es de administración.
 */

/** Los ajustes vigentes. Se conserva el nombre: lo usan otras rutas. */
function festival_ajustes(): array
{
    return \LMT\Evento::ajustes();
}

/** Lo que devuelve la lectura: los ajustes y el estado calculado a partir de ellos. */
function festival_respuesta(): array
{
    $aj = \LMT\Evento::ajustes();
    $aj['estado'] = [
        'inscripcion_abierta' => \LMT\Evento::inscripcionAbierta($aj),
    ];
    // El CSS del tema ya calculado: el navegador lo aplica tal cual al
    // recargar los ajustes, sin necesitar el catálogo de tipografías.
    $aj['tema_css'] = \LMT\Evento::cssTema($aj['tema'] ?? []);
    return $aj;
}

/** Guarda y devuelve lo guardado, ya releído de la base. */
function festival_guardar(array $act): void
{
    Ajustes::guardar(\LMT\Evento::GRUPO, $act);
    \LMT\Evento::olvidarCache();
}

function register_routes_festival(\LMT\Router $r): void
{
    $r->get('/festival/ajustes', function () {
        Response::ok(festival_respuesta());
    });

    /**
     * Lo que el panel necesita para editar y que el público no: tipografías,
     * paletas y plantillas. Va aparte para no engordar la lectura pública,
     * que se hace en cada visita.
     */
    $r->get('/admin/festival/catalogo', function () {
        Security::requireAdmin();
        Response::ok([
            'fuentes'    => \LMT\Evento::fuentes(),
            'paletas'    => \LMT\Evento::presetsTema(),
            'plantillas' => \LMT\Evento::plantillas(),
            'por_defecto'=> \LMT\Evento::porDefecto(),
        ]);
    });

    $r->put('/admin/festival/ajustes', function () {
        Security::requireAdmin();
        $b = Security::jsonBodyConfig();
        try {
            $act = \LMT\Evento::sanear($b, \LMT\Evento::ajustes());
        } catch (\RuntimeException $e) {
            Response::error(422, $e->getMessage());
        }
        festival_guardar($act);
        Response::ok(festival_respuesta());
    });

    /**
     * Imágenes del evento: el logo y los fondos del pasaporte. Se suben como
     * cualquier otra imagen del sistema (se validan y recodifican) y se guarda
     * su ruta en los ajustes.
     *
     * `destino` dice para qué es: el logo, la portada, la contraportada o una
     * más del montón de hojas internas.
     */
    $r->post('/admin/festival/fondo', function () {
        Security::requireAdmin();
        $destino = (string) ($_POST['destino'] ?? 'hojas');
        if (!in_array($destino, ['logo', 'portada', 'contraportada', 'hojas'], true)) {
            Response::error(422, 'destino_invalido');
        }
        if (empty($_FILES['archivo']) || !is_array($_FILES['archivo'])) {
            Response::error(422, 'archivo_ausente');
        }
        try {
            $ruta = \LMT\Uploads::imagen($_FILES['archivo'], $destino === 'logo' ? 'marca' : 'pasaporte');
        } catch (\RuntimeException $e) {
            Response::error(422, $e->getMessage());
        }

        $act = festival_ajustes();
        if ($destino === 'logo') {
            $act['marca']['logo'] = $ruta;
        } elseif ($destino === 'hojas') {
            $hojas = $act['pasaporte']['hojas'];
            // Un tope sensato: más de 20 fondos no aporta variedad y sí peso
            // de descarga en el móvil de un visitante.
            if (count($hojas) >= 20) Response::error(422, 'demasiadas_hojas');
            $hojas[] = $ruta;
            $act['pasaporte']['hojas'] = $hojas;
        } else {
            $act['pasaporte'][$destino] = $ruta;
        }
        festival_guardar($act);
        Response::ok(festival_respuesta());
    });

    /** Quita una imagen. Volver a cero es volver al diseño que trae el sistema. */
    $r->delete('/admin/festival/fondo', function () {
        Security::requireAdmin();
        $b       = Security::jsonBody();
        $destino = (string) ($b['destino'] ?? '');
        $act     = festival_ajustes();

        if ($destino === 'logo') {
            $act['marca']['logo'] = '';
        } elseif ($destino === 'portada' || $destino === 'contraportada') {
            $act['pasaporte'][$destino] = '';
        } elseif ($destino === 'hojas') {
            $i = isset($b['indice']) ? (int) $b['indice'] : -1;
            $hojas = array_values($act['pasaporte']['hojas']);
            if ($i < 0 || $i >= count($hojas)) Response::error(422, 'indice_invalido');
            array_splice($hojas, $i, 1);
            $act['pasaporte']['hojas'] = $hojas;
        } else {
            Response::error(422, 'destino_invalido');
        }
        festival_guardar($act);
        Response::ok(festival_respuesta());
    });

    /**
     * Resultados de la encuesta: cada pregunta con su resumen.
     *
     * Sólo agregados, como la actividad económica: quién contestó qué está en
     * la exportación de votos y no hace falta aquí. `?stand=` filtra por un
     * espacio. Las preguntas desactivadas también salen —marcadas—, porque sus
     * respuestas de antes siguen contando.
     */
    $r->get('/admin/encuesta', function () {
        Security::requireAdmin();
        $aj = \LMT\Evento::ajustes();
        $stand = \LMT\Validate::standId($_GET['stand'] ?? null);
        $sql = 'SELECT emoji, est_innovacion, est_atencion, est_calidad, compra, respuestas FROM votos';
        $st = \LMT\Db::pdo()->prepare($sql . ($stand ? ' WHERE stand_id = :s' : ''));
        $st->execute($stand ? [':s' => $stand] : []);

        $preguntas = $aj['encuesta']['preguntas'] ?? [];
        $acc = [];
        foreach ($preguntas as $p) $acc[$p['id']] = ['n' => 0, 'suma' => 0, 'dist' => [], 'opciones' => [], 'textos' => []];
        $total = 0;
        $emojis = ['bueno' => 0, 'regular' => 0, 'malo' => 0];
        while ($v = $st->fetch(\PDO::FETCH_ASSOC)) {
            $total++;
            if (isset($emojis[$v['emoji']])) $emojis[$v['emoji']]++;
            $resp = \LMT\Evento::respuestasDe($v['respuestas'] ?? null);
            foreach ($preguntas as $p) {
                $id = $p['id'];
                $val = !empty($p['sistema']) ? ($v[$id] ?? null) : ($resp[$id] ?? null);
                if ($val === null || $val === '' || $val === []) continue;
                $a = &$acc[$id];
                $a['n']++;
                switch ($p['tipo']) {
                    case 'estrellas':
                        $n = (int) $val;
                        $a['suma'] += $n;
                        $a['dist'][$n] = ($a['dist'][$n] ?? 0) + 1;
                        break;
                    case 'si_no':
                        $k = ((int) $val === 1) ? 'si' : 'no';
                        $a['dist'][$k] = ($a['dist'][$k] ?? 0) + 1;
                        break;
                    case 'opcion':
                    case 'multiple':
                        foreach ((array) $val as $o) {
                            $o = (string) $o;
                            $a['opciones'][$o] = ($a['opciones'][$o] ?? 0) + 1;
                        }
                        break;
                    default:
                        // Las respuestas abiertas: las últimas, para leerlas.
                        if (count($a['textos']) < 30) $a['textos'][] = (string) $val;
                }
                unset($a);
            }
        }

        $salida = [];
        foreach ($preguntas as $p) {
            $a = $acc[$p['id']];
            $fila = [
                'id' => $p['id'], 'tipo' => $p['tipo'], 'etiqueta' => $p['etiqueta'],
                'activa' => !empty($p['activa']), 'sistema' => !empty($p['sistema']), 'n' => $a['n'],
            ];
            if ($p['tipo'] === 'estrellas') {
                $fila['promedio'] = $a['n'] ? round($a['suma'] / $a['n'], 2) : null;
                $fila['distribucion'] = array_map(fn($k) => $a['dist'][$k] ?? 0, [1, 2, 3, 4, 5]);
            } elseif ($p['tipo'] === 'si_no') {
                $fila['si'] = $a['dist']['si'] ?? 0;
                $fila['no'] = $a['dist']['no'] ?? 0;
            } elseif (in_array($p['tipo'], ['opcion', 'multiple'], true)) {
                // Las opciones configuradas primero y en su orden; lo que se
                // respondió con una opción que ya no existe, al final.
                $ops = [];
                foreach ((array) ($p['opciones'] ?? []) as $o) $ops[] = ['texto' => (string) $o, 'n' => $a['opciones'][$o] ?? 0];
                foreach ($a['opciones'] as $o => $n) {
                    if (!in_array((string) $o, (array) ($p['opciones'] ?? []), true)) $ops[] = ['texto' => (string) $o, 'n' => $n, 'retirada' => true];
                }
                $fila['opciones'] = $ops;
            } else {
                $fila['recientes'] = array_reverse($a['textos']);
            }
            $salida[] = $fila;
        }

        Response::ok([
            'total'     => $total,
            'emojis'    => $emojis,
            'stand'     => $stand,
            'preguntas' => $salida,
        ]);
    });

    /**
     * Actividad económica del festival.
     *
     * Lo que de verdad quiere saber quien organiza: cuánto se movió, en qué
     * stands y qué proporción de visitantes acabó comprando. Sólo agregados —el
     * detalle de quién compró qué está en los votos y no hace falta aquí.
     */
    $r->get('/admin/economia', function () {
        Security::requireAdmin();
        $pdo = \LMT\Db::pdo();

        $total = $pdo->query(
            'SELECT COUNT(*) AS votos,
                    SUM(CASE WHEN compra = 1 THEN 1 ELSE 0 END) AS compras,
                    SUM(CASE WHEN compra = 0 THEN 1 ELSE 0 END) AS sin_compra,
                    SUM(CASE WHEN compra = 1 THEN compra_valor ELSE 0 END) AS valor,
                    COUNT(compra_valor) AS con_valor
             FROM votos'
        )->fetch(\PDO::FETCH_ASSOC) ?: [];

        $filas = $pdo->query(
            'SELECT s.id, s.nombre, s.municipio,
                    COUNT(v.id) AS votos,
                    SUM(CASE WHEN v.compra = 1 THEN 1 ELSE 0 END) AS compras,
                    SUM(CASE WHEN v.compra = 1 THEN v.compra_valor ELSE 0 END) AS valor,
                    COUNT(v.compra_valor) AS con_valor
             FROM stands s
             LEFT JOIN votos v ON v.stand_id = s.id
             GROUP BY s.id, s.nombre, s.municipio
             ORDER BY valor DESC, compras DESC, s.nombre'
        )->fetchAll(\PDO::FETCH_ASSOC);

        Response::ok([
            'total' => economia_fila($total),
            'stands' => array_map(function (array $f) {
                return economia_fila($f) + [
                    'id'        => (string) $f['id'],
                    'nombre'    => (string) $f['nombre'],
                    'municipio' => (string) ($f['municipio'] ?? ''),
                ];
            }, $filas),
        ]);
    });
}

/**
 * Cifras derivadas de una fila de compras.
 *
 * `ticket` sale de dividir entre los votos QUE TRAEN IMPORTE, no entre todas
 * las compras: quien dice «sí compré» pero no escribe cuánto hundiría la media
 * si contara como una compra de cero pesos.
 */
function economia_fila(array $f): array
{
    $votos    = (int) ($f['votos'] ?? 0);
    $compras  = (int) ($f['compras'] ?? 0);
    $valor    = (int) ($f['valor'] ?? 0);
    $conValor = (int) ($f['con_valor'] ?? 0);
    return [
        'votos'      => $votos,
        'compras'    => $compras,
        'con_valor'  => $conValor,
        'valor'      => $valor,
        'ticket'     => $conValor > 0 ? (int) round($valor / $conValor) : 0,
        'conversion' => $votos > 0 ? round($compras * 100 / $votos, 1) : 0.0,
    ];
}
