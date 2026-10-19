<?php
namespace LMT;
defined('LMT_GUARD') || exit('forbidden');

/**
 * Catálogos cerrados de la inscripción de espacios.
 *
 * Por qué se guarda una clave y no la frase
 * -----------------------------------------
 * «Sociedades por Acciones Simplificadas S.A.S.» es lo que lee quien se
 * inscribe; lo que se guarda es `sas`. Si se guardara la frase, el día que
 * alguien corrija una tilde o cambie un paréntesis la base tendría dos
 * organizaciones distintas que son la misma, y cualquier conteo por tipo
 * dejaría de valer — que es exactamente lo que ya pasó con los municipios
 * antes de cerrarlos contra el catálogo del DANE.
 *
 * Las frases viven en el JSX, que es donde va lo que se lee. Aquí están las
 * claves, que es lo que decide qué entra en la base. Una prueba comprueba que
 * las dos listas tengan las mismas claves: separar presentación de validación
 * sólo funciona si algo avisa cuando se separan de más.
 */
final class Catalogos
{
    /** Qué tipo de organización es quien se inscribe. */
    public const ORGANIZACION = [
        'persona_natural',
        'sas',
        'limitada',
        'civil',
        'unipersonal',
        'comunidades_indigenas',
        'anonima',
        'utilidad_comun',
        'fundacion',
        'comandita_simple',
        'corporacion',
        'no_formalizada',
        'otro',
    ];

    /**
     * Grupo o tipo de población. Una sola opción.
     *
     * Es un dato sensible en el sentido de la Ley 1581 de 2012 —pertenencia
     * étnica, discapacidad, condición de víctima—, así que sale de un catálogo
     * cerrado con salida explícita («Ninguna de las anteriores») y se pide
     * junto a la autorización de tratamiento, nunca antes.
     */
    public const POBLACION = [
        'urbana',
        'rural',
        'indigena',
        'afrodescendiente',
        'rrom',
        'victima',
        'discapacidad',
        'adulto_mayor',
        'estudiante',
        'ninguna',
        'otro',
    ];

    /** Línea productiva. Selección MÚLTIPLE. */
    public const LINEA_PRODUCTIVA = [
        'cafes_especiales',
        'transformacion',
        'economia_circular',
    ];

    /** Presentación del producto. Selección MÚLTIPLE. */
    public const PRESENTACION = [
        'grano',
        'molido',
        'instantaneo',
        'descafeinado',
        'capsulas',
        'otros',
    ];

    /** Vínculo con la cadena de valor del café. */
    public const ACTIVIDAD = [
        'tostado_marca_propia',
        'transformador',
        'distribuidor',
        'barista',
        'proveedor',
        'artesanias',
        'servicios',
        'organizacion',
        'otro',
    ];

    /**
     * Valida contra un catálogo. Devuelve null si no está en la lista.
     *
     * Se compara con `in_array` estricto y no con un `switch` laxo porque el
     * cliente puede mandar `0`, que en PHP se parece a cualquier cadena si uno
     * se descuida con la comparación.
     */
    public static function valor($value, array $catalogo): ?string
    {
        if (!is_string($value)) return null;
        $v = trim($value);
        return in_array($v, $catalogo, true) ? $v : null;
    }

    /**
     * Un campo de catálogo con su «Otro: ____». Devuelve [clave, detalle].
     *
     * El detalle sólo se guarda cuando la clave es 'otro'. Si no, quien elige
     * «Fundaciones» después de haber escrito algo en el campo abierto se
     * llevaría los dos datos a la base y el informe contaría una cosa distinta
     * de la que se eligió. Y «Otro» sin decir cuál tampoco entra: no es una
     * caracterización, es un hueco con otro nombre.
     *
     * `$obligatorio` distingue la inscripción —donde estos campos se exigen—
     * del editor del panel, donde un espacio creado antes de que existieran
     * estos campos se tiene que poder seguir editando.
     *
     * Lanza RuntimeException con el código de error, como Uploads: la clase no
     * conoce el transporte y quien la llama decide cómo responder.
     */
    public static function par($clave, $detalle, array $catalogo, string $error, bool $obligatorio, bool $laxo = false): array
    {
        $v = self::valor($clave, $catalogo);
        // En el editor del panel se acepta además una clave bien formada que
        // ya no esté en el catálogo: el organizador puede haber quitado esa
        // opción después de que alguien la eligiera, y editar el nombre de ese
        // espacio no puede obligar a inventarle otra.
        if ($v === null && $laxo && is_string($clave) && preg_match('/\A[a-z0-9_]{1,40}\z/', trim($clave))) {
            $v = trim($clave);
        }
        if ($v === null) {
            $vacio = $clave === null || (is_string($clave) && trim($clave) === '');
            if ($vacio && !$obligatorio) return [null, null];
            throw new \RuntimeException($error);
        }
        if ($v !== 'otro') return [$v, null];

        $texto = Validate::nombre($detalle, 120);
        if ($texto === null) throw new \RuntimeException('detalle_requerido');
        return [$v, $texto];
    }

    /**
     * Selección múltiple contra un catálogo. Devuelve JSON o null si va vacía.
     *
     * Se guarda como JSON y no como una columna por opción porque las listas
     * cambian entre ediciones del festival —«economía circular» no existía el
     * año pasado— y cada cambio significaría una migración. Y no como texto
     * separado por comas porque entonces buscar 'grano' encontraría también
     * 'grano molido' el día que alguien añada esa opción.
     *
     * El orden se normaliza al del catálogo: así dos respuestas iguales se
     * guardan igual, y comparar o agrupar en un informe funciona.
     */
    public static function multiple($valores, array $catalogo, string $error, bool $obligatorio, bool $laxo = false): ?string
    {
        if (!is_array($valores)) $valores = [];
        $limpio = [];
        foreach ($catalogo as $clave) {
            if (in_array($clave, $valores, true)) $limpio[] = $clave;
        }
        if ($laxo) {
            foreach ($valores as $v) {
                if (is_string($v) && preg_match('/\A[a-z0-9_]{1,40}\z/', $v) && !in_array($v, $limpio, true)) $limpio[] = $v;
            }
        }
        if ($limpio === []) {
            if ($obligatorio) throw new \RuntimeException($error);
            return null;
        }
        return json_encode($limpio, JSON_UNESCAPED_UNICODE);
    }

    /** Lee de vuelta un JSON de selección múltiple. Siempre una lista. */
    public static function listaDe($json): array
    {
        if (!is_string($json) || $json === '') return [];
        $v = json_decode($json, true);
        return is_array($v) ? array_values(array_filter($v, 'is_string')) : [];
    }

    /**
     * Un «sí / no» que distingue el «no» del «no contestó».
     *
     * `Validate::bool()` devuelve null en los dos casos, y eso convierte «este
     * café NO es orgánico» en «no sabemos si es orgánico»: son cosas distintas
     * y la segunda no se puede publicar como la primera. Aquí null sólo
     * sobrevive cuando el campo es opcional y de verdad viene vacío.
     */
    public static function siNo($valor, string $error, bool $obligatorio): ?int
    {
        if ($valor === true  || $valor === 1 || $valor === '1' || $valor === 'true')  return 1;
        if ($valor === false || $valor === 0 || $valor === '0' || $valor === 'false') return 0;
        if ($obligatorio) throw new \RuntimeException($error);
        return null;
    }

    /**
     * La ficha del participante, leída de un cuerpo JSON.
     *
     * Devuelve el array de columna => valor, listo para bindear. Está aquí y no
     * repetido en las dos rutas porque la inscripción pública y el editor del
     * panel guardan EXACTAMENTE los mismos campos: con dos copias, la primera
     * vez que se añada una casilla una de las dos se queda sin ella y el
     * espacio pierde el dato en cuanto un organizador le da a «Guardar».
     *
     * `$exigir` distingue la inscripción —donde cuatro respuestas son
     * obligatorias porque son requisitos de participación— del editor, donde un
     * espacio creado antes de que existieran estos campos se tiene que poder
     * seguir editando sin inventarse nada.
     *
     * Lanza RuntimeException con el código de error; quien llama decide cómo
     * responderlo.
     */
    public static function ficha(array $b, bool $exigir, ?array $aj = null): array
    {
        // Qué se pregunta y qué se exige lo decide la configuración del evento
        // (Panel → Configuración del evento → Inscripción). En la inscripción
        // pública un campo desactivado no se guarda aunque llegue: el
        // formulario no lo enseñó, así que cualquier valor sería inventado. En
        // el editor del panel ($exigir = false) se guarda lo que llegue, para
        // no borrar el dato de un espacio sólo porque hoy ese campo esté
        // apagado.
        $aj = $aj ?? Evento::ajustes();
        $laxo = !$exigir;
        $activo = fn(string $k) => $laxo || !empty(Evento::campo($k, $aj)['activo']);
        $pide   = fn(string $k) => $exigir && !empty(Evento::campo($k, $aj)['activo']) && !empty(Evento::campo($k, $aj)['requerido']);
        $claves = fn(string $k) => Evento::clavesCampo($k, $aj);

        // El orden importa: se valida de arriba abajo, en el MISMO orden en que
        // están los campos en el formulario. Si no, a quien se deja sin marcar
        // la casilla de la marca registrada se le contesta que falta el INVIMA
        // —tres preguntas más abajo— y se pone a buscar el error donde no está.
        [$poblacion, $poblacionOtro] = $activo('poblacion')
            ? self::par($b['poblacion'] ?? null, $b['poblacion_otro'] ?? null, $claves('poblacion'), 'poblacion_invalida', $pide('poblacion'), $laxo)
            : [null, null];
        $linea = $activo('linea_productiva')
            ? self::multiple($b['linea_productiva'] ?? null, $claves('linea_productiva'), 'linea_productiva_requerida', $pide('linea_productiva'), $laxo)
            : null;

        $siNo = fn(string $k, string $error) => $activo($k) ? self::siNo($b[$k] ?? null, $error, $pide($k)) : null;

        $certInternacional = $siNo('cert_internacional', 'cert_internacional_requerida');
        $organico          = $siNo('organico', 'organico_requerido');
        $especial          = $siNo('especial', 'especial_requerido');
        $promedioTaza      = $activo('promedio_taza') ? Validate::texto($b['promedio_taza'] ?? null, 40) : '';
        if ($promedioTaza === '' && $pide('promedio_taza')) throw new \RuntimeException('promedio_taza_requerido');

        $marca  = $siNo('marca_registrada', 'marca_registrada_requerida');

        $camara = $siNo('camara_comercio', 'camara_comercio_requerido');
        // El número sólo se guarda si la respuesta fue que sí. Si no, quien
        // marque «sí», lo escriba y luego rectifique a «no» dejaría en la base
        // un certificado que el propio formulario dice que no tiene.
        $camaraNumero = $camara === 1 ? Validate::texto($b['camara_comercio_numero'] ?? null, 60) : '';

        $invima = $siNo('invima', 'invima_requerido');
        $invimaDetalle = $invima === 1 ? Validate::parrafos($b['invima_detalle'] ?? null, 800) : '';

        $manipulacion = $siNo('manipulacion_alimentos', 'manipulacion_requerida');

        $presentacionJson = $activo('presentacion')
            ? self::multiple($b['presentacion'] ?? null, $claves('presentacion'), 'presentacion_requerida', $pide('presentacion'), $laxo)
            : null;
        $presentacionOtro = in_array('otros', self::listaDe($presentacionJson), true)
            ? Validate::texto($b['presentacion_otro'] ?? null, 120)
            : '';

        return [
            'poblacion'              => $poblacion,
            'poblacion_otro'         => $poblacionOtro,
            'linea_productiva'       => $linea,
            'cert_internacional'     => $certInternacional,
            'organico'               => $organico,
            'especial'               => $especial,
            'promedio_taza'          => self::vacioANull($promedioTaza),
            'marca_registrada'       => $marca,
            'camara_comercio'        => $camara,
            'camara_comercio_numero' => self::vacioANull($camaraNumero),
            'invima'                 => $invima,
            'invima_detalle'         => self::vacioANull($invimaDetalle),
            'manipulacion_alimentos' => $manipulacion,
            'presentacion'           => $presentacionJson,
            'presentacion_otro'      => self::vacioANull($presentacionOtro),
        ];
    }

    /** Las columnas de la ficha, en el orden en que las devuelve ficha(). */
    public static function camposFicha(): array
    {
        return [
            'poblacion', 'poblacion_otro', 'linea_productiva', 'cert_internacional', 'organico',
            'especial', 'promedio_taza', 'marca_registrada', 'camara_comercio', 'camara_comercio_numero',
            'invima', 'invima_detalle', 'manipulacion_alimentos', 'presentacion', 'presentacion_otro',
        ];
    }

    private static function vacioANull(?string $v): ?string
    {
        return ($v === null || $v === '') ? null : $v;
    }

    /**
     * Las frases de fábrica de cada catálogo, como [clave, frase].
     *
     * Antes vivían sólo en el JSX. Ahora el organizador puede reescribirlas
     * desde el panel, así que el valor de fábrica tiene que estar donde se
     * guarda la configuración: aquí.
     */
    public static function frases(string $catalogo): array
    {
        $f = [
            'organizacion' => [
                ['persona_natural', 'Persona Natural'],
                ['sas', 'Sociedades por Acciones Simplificadas S.A.S.'],
                ['limitada', 'Sociedad Limitada'],
                ['civil', 'Las demás organizaciones civiles, corporaciones, fundaciones'],
                ['unipersonal', 'Empresas unipersonales'],
                ['comunidades_indigenas', 'Corporaciones, asociación y fundaciones creadas para adelantar actividades en comunidades indígenas'],
                ['anonima', 'Sociedad Anónima'],
                ['utilidad_comun', 'Asociaciones, corporaciones, fundaciones e instituciones de utilidad común (gremiales, de beneficencia; profesionales, juveniles, sociales, democráticas y participativas, cívicas y comunitarias, de egresados, de rehabilitación social y ayuda a indigentes y clubes sociales)'],
                ['fundacion', 'Fundaciones'],
                ['comandita_simple', 'Sociedad en Comandita Simple'],
                ['corporacion', 'Corporaciones'],
                ['no_formalizada', 'Organización no formalizada'],
                ['otro', 'Otro'],
            ],
            'actividad' => [
                ['tostado_marca_propia', 'Productor de café tostado con marca propia'],
                ['transformador', 'Transformador de productos derivados del café'],
                ['distribuidor', 'Distribuidor de productos de café'],
                ['barista', 'Barista / establecimiento especializado en café'],
                ['proveedor', 'Proveedor de equipos, maquinaria o insumos para café'],
                ['artesanias', 'Artesanías / productos con identidad cafetera'],
                ['servicios', 'Servicios relacionados con el café'],
                ['organizacion', 'Organización o asociación cafetera'],
                ['otro', 'Otro'],
            ],
            // La actividad de un evento cualquiera. Comparte columna con la del
            // café (`actividad_cafe`): la clave dice qué se eligió y el
            // catálogo vigente dice cómo se lee.
            'actividad_generica' => [
                ['productor', 'Productor o fabricante con marca propia'],
                ['transformador', 'Transformación o agroindustria'],
                ['comercio', 'Comercio o distribución'],
                ['gastronomia', 'Gastronomía'],
                ['artesanias', 'Artesanías'],
                ['servicios', 'Servicios'],
                ['cultura', 'Arte, cultura o turismo'],
                ['organizacion', 'Organización, asociación o cooperativa'],
                ['institucion', 'Entidad pública o académica'],
                ['otro', 'Otro'],
            ],
            'poblacion' => [
                ['urbana', 'Urbana (ciudad o zona metropolitana)'],
                ['rural', 'Rural (campo o zona agrícola)'],
                ['indigena', 'Indígena'],
                ['afrodescendiente', 'Afrodescendiente / Negra / Raizal / Palenquera'],
                ['rrom', 'Rrom / Gitana'],
                ['victima', 'Víctima del conflicto armado'],
                ['discapacidad', 'Persona en condición de discapacidad'],
                ['adulto_mayor', 'Adulto mayor (60 años o más)'],
                ['estudiante', 'Estudiante'],
                ['ninguna', 'Ninguna de las anteriores / Población general'],
                ['otro', 'Otro'],
            ],
            'linea_productiva' => [
                ['cafes_especiales', 'Cafés especiales de origen'],
                ['transformacion', 'Transformación agroindustrial y derivados'],
                ['economia_circular', 'Economía circular y subproductos'],
            ],
            'presentacion' => [
                ['grano', 'Grano'],
                ['molido', 'Molido'],
                ['instantaneo', 'Café instantáneo'],
                ['descafeinado', 'Café descafeinado'],
                ['capsulas', 'Cápsulas'],
                ['otros', 'Otros'],
            ],
        ];
        return $f[$catalogo] ?? [];
    }

    /** Los catálogos, para pintarlos o para comprobarlos desde una prueba. */
    public static function todos(): array
    {
        return [
            'organizacion'     => self::ORGANIZACION,
            'actividad'        => self::ACTIVIDAD,
            'poblacion'        => self::POBLACION,
            'linea_productiva' => self::LINEA_PRODUCTIVA,
            'presentacion'     => self::PRESENTACION,
        ];
    }
}
