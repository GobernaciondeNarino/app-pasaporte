<?php
namespace LMT;
defined('LMT_GUARD') || exit('forbidden');

/**
 * Configuración del evento: todo lo que hace que la plataforma sirva para un
 * festival del café, una feria artesanal o un congreso sin tocar código.
 *
 * Vive en un solo sitio porque la leen cuatro capas distintas —el shell de
 * app.php (colores y título antes de pintar nada), las rutas de la API (qué se
 * pregunta y qué se valida), los correos y el navegador— y cada copia de los
 * valores por defecto que existiera en otra parte acabaría diciendo otra cosa.
 *
 * Se guarda en la tabla `ajustes` bajo la clave `festival`, la misma que ya
 * usaba la personalización de La Mejor Taza: una instalación anterior conserva
 * lo que tenía configurado (títulos de las estrellas, fondos del pasaporte,
 * política de datos) y gana todo lo nuevo con sus valores de fábrica.
 *
 * Grupos:
 *   evento      nombre, lema, fechas, lugar, organizador, textos de portada
 *   marca       logo subido, pie bajo el nombre
 *   simbolos    los tres emoji del voto, la palabra del sello, el «#1»
 *   tema        colores, tipografías, cursiva y peso de los títulos, esquinas
 *   encuesta    preguntas del voto, compra sí/no, comentario
 *   inscripcion abierta/cerrada, campos activos y obligatorios, preguntas propias
 *   pasaporte   textos de la libreta y fondos
 *   datos       política de tratamiento de datos (Ley 1581 de 2012)
 */
final class Evento
{
    public const GRUPO = 'festival';

    /** Las tres valoraciones de sistema: son columnas de `votos`, no se borran. */
    public const ESTRELLAS_SISTEMA = ['est_innovacion' => 'innovacion', 'est_atencion' => 'atencion', 'est_calidad' => 'calidad'];

    /** Tipos de pregunta que admite cada formulario. */
    public const TIPOS_ENCUESTA    = ['estrellas', 'si_no', 'opcion', 'texto'];
    public const TIPOS_INSCRIPCION = ['texto', 'parrafo', 'numero', 'fecha', 'si_no', 'opcion', 'multiple'];

    /** Símbolos que trae el sistema para cuando no hay logo (ver SimboloSvg). */
    public const SIMBOLOS = ['pasaporte', 'taza', 'estrella', 'montana'];

    /** Colores que se pueden cambiar, en el orden en que los presenta el panel. */
    public const COLORES = [
        'paper', 'paper-2', 'paper-3', 'ink', 'ink-2', 'ink-3', 'line', 'line-2',
        'grano', 'galeras', 'cafeto', 'good', 'meh', 'bad',
    ];

    private static ?array $cache = null;

    // =====================================================================
    // Valores de fábrica y plantillas
    // =====================================================================

    /**
     * Lo que trae el sistema si nadie configuró nada: un evento genérico de la
     * Gobernación de Nariño con la identidad institucional.
     */
    public static function porDefecto(): array
    {
        return [
            'evento' => [
                'nombre'         => 'Pasaporte del Evento',
                'lema'           => '¿Cuál es el mejor espacio del evento?',
                'descripcion'    => 'El público decide. Escanea el código QR de cada espacio, califica tu visita y sella tu pasaporte.',
                'portada_titulo' => 'Tu pasaporte del evento.',
                'portada_texto'  => 'Recorre los espacios, conoce sus propuestas y califica tu experiencia. Tu pasaporte se va sellando con cada visita.',
                'fecha_inicio'   => '',
                'fecha_fin'      => '',
                'horario'        => '',
                'lugar'          => '',
                'ciudad'         => 'Pasto, Nariño',
                'organizador'    => 'Gobernación de Nariño',
                'sitio_web'      => 'https://narino.gov.co/',
                'contacto'       => '',
                'pie_pagina'     => 'Gobernación de Nariño · 2026',
                'mostrar_datos'  => true,
            ],
            'marca' => [
                'pie'            => 'Gobernación de Nariño · 2026',
                'logo'           => '',
                // El símbolo que se dibuja cuando no hay logo subido.
                'simbolo'        => 'pasaporte',
                'mostrar_nombre' => true,
            ],
            'simbolos' => [
                'votos' => [
                    'malo'    => ['emoji' => '😞', 'texto' => 'Mejorable'],
                    'regular' => ['emoji' => '😐', 'texto' => 'Regular'],
                    'bueno'   => ['emoji' => '😍', 'texto' => 'Excelente'],
                ],
                'sello'        => 'VISITADO',
                'primer_lugar' => 'Primer lugar',
            ],
            'tema' => [
                'preset'          => 'institucional',
                // Vacío = el color del tema base (styles/tokens.css).
                'colores'         => array_fill_keys(self::COLORES, ''),
                'fuentes'         => ['display' => 'hind', 'texto' => 'nunito', 'meta' => 'source'],
                'titulos_cursiva' => false,
                'titulos_peso'    => 700,
                'esquinas'        => 'normal',
            ],
            'encuesta' => [
                'titulo'         => '¿Cómo te fue en este espacio?',
                'instrucciones'  => 'Responde lo que quieras y toca un emoji para enviar.',
                'pregunta_emoji' => '¿Cómo calificas tu visita?',
                'aviso_datos'    => 'Al votar aceptas el tratamiento de datos del evento.',
                'compra' => [
                    'activa'         => true,
                    'pregunta'       => '¿Compraste algo?',
                    'si'             => 'Sí, compré',
                    'no'             => 'No esta vez',
                    'pide_valor'     => true,
                    'pregunta_valor' => '¿Cuánto gastaste? (opcional)',
                ],
                'comentario' => [
                    'activo'      => true,
                    'etiqueta'    => 'Comentario (opcional)',
                    'placeholder' => '¿Qué destacarías del espacio?',
                ],
                'preguntas' => [
                    ['id' => 'est_innovacion', 'tipo' => 'estrellas', 'etiqueta' => 'Innovación', 'activa' => true, 'sistema' => true],
                    ['id' => 'est_atencion',   'tipo' => 'estrellas', 'etiqueta' => 'Atención',   'activa' => true, 'sistema' => true],
                    ['id' => 'est_calidad',    'tipo' => 'estrellas', 'etiqueta' => 'Calidad',    'activa' => true, 'sistema' => true],
                ],
            ],
            'inscripcion' => [
                'abierta'         => true,
                'cierre'          => '',
                'mensaje_cerrada' => 'Las inscripciones de espacios están cerradas. Si necesitas ayuda, comunícate con el equipo organizador.',
                'titulo'          => 'Inscribe tu espacio en el evento.',
                'intro'           => 'Completa los datos de tu espacio. Un organizador revisará la solicitud y te enviará por correo tu acceso al portal y el código QR de tu espacio, listo para imprimir.',
                'secciones' => [
                    'ubicacion' => ['titulo' => '¿Dónde estás?', 'nota' => 'Toca el mapa de Nariño donde queda tu negocio u organización. Al marcarlo se completa solo el municipio.'],
                    'participa' => ['titulo' => 'Quién participa', 'nota' => 'Sirve para saber a quién está llegando el evento. Nadie queda fuera por lo que responda aquí.'],
                    'linea'     => ['titulo' => 'Línea en la cual participa', 'nota' => 'Puedes marcar más de una.'],
                    'detalle'   => ['titulo' => 'Información detallada', 'nota' => 'Detalles sobre tu emprendimiento y tu producto. Nos ayudan a conocer mejor tu propuesta.'],
                    'extra'     => ['titulo' => 'Preguntas adicionales', 'nota' => ''],
                ],
                'campos'    => self::camposPorDefecto(),
                'preguntas' => [],
            ],
            'estrellas' => ['innovacion' => 'Innovación', 'atencion' => 'Atención', 'calidad' => 'Calidad'],
            'recorrido' => ['columnas_pc' => 3, 'columnas_movil' => 2],
            'pasaporte' => [
                'etiqueta'       => 'Pasaporte del evento',
                'titulo_portada' => '',
                'lugar_1'        => 'NARIÑO',
                'lugar_2'        => 'COLOMBIA',
                'encabezado_1'   => 'REPÚBLICA DE COLOMBIA',
                'encabezado_2'   => 'DEPARTAMENTO DE NARIÑO',
                'prefijo'        => 'NAR',
                'invitacion'     => 'Empieza tu recorrido por el evento.',
                'cierre_titulo'  => 'Gracias por recorrer el evento con nosotros.',
                'cierre_boton'   => 'Te esperamos en la próxima edición',
                'cierre_firma'   => 'GOBERNACIÓN DE NARIÑO · 2026',
                'portada'        => '',
                'contraportada'  => '',
                'hojas'          => [],
            ],
            'datos' => [
                'texto'          => self::POLITICA,
                'enlace'         => 'https://narino.gov.co/',
                'enlace_texto'   => 'Política de Tratamiento de Datos Personales de la Gobernación de Nariño',
                'responsable'    => 'la Gobernación de Nariño',
            ],
        ];
    }

    /**
     * Campos de la inscripción que trae el sistema.
     *
     * `fijo` = no se puede desactivar (los necesita el propio sistema: sin
     * nombre, correo, empresa o municipio no hay espacio que crear). Las
     * opciones de los catálogos guardan [clave, frase]: se guarda la CLAVE en
     * la base, para que corregir una tilde no parta un tipo en dos.
     */
    public static function camposPorDefecto(): array
    {
        $si = fn(string $etiqueta, bool $activo = false, bool $req = false, string $ayuda = '', string $grupo = 'detalle')
            => ['tipo' => 'si_no', 'grupo' => $grupo, 'etiqueta' => $etiqueta, 'ayuda' => $ayuda, 'activo' => $activo, 'requerido' => $req];
        return [
            'nombre'   => ['tipo' => 'texto',  'grupo' => 'persona', 'etiqueta' => 'Nombre completo del responsable', 'ayuda' => '', 'activo' => true, 'requerido' => true, 'fijo' => true],
            'documento'=> ['tipo' => 'numero', 'grupo' => 'persona', 'etiqueta' => 'Documento de identidad', 'ayuda' => 'Cédula del responsable, sólo números.', 'activo' => true, 'requerido' => false],
            'telefono' => ['tipo' => 'numero', 'grupo' => 'persona', 'etiqueta' => 'Teléfono', 'ayuda' => 'Sólo números, sin espacios ni guiones.', 'activo' => true, 'requerido' => false],
            'email'    => ['tipo' => 'texto',  'grupo' => 'persona', 'etiqueta' => 'Correo electrónico', 'ayuda' => 'Aquí llegarán tu acceso y el QR de tu espacio.', 'activo' => true, 'requerido' => true, 'fijo' => true],
            'empresa'  => ['tipo' => 'texto',  'grupo' => 'espacio', 'etiqueta' => 'Empresa, organización o marca', 'ayuda' => '', 'activo' => true, 'requerido' => true, 'fijo' => true],
            'stand_nombre' => ['tipo' => 'texto', 'grupo' => 'espacio', 'etiqueta' => 'Nombre del espacio', 'ayuda' => 'Déjalo vacío para usar el nombre de la empresa.', 'activo' => true, 'requerido' => false],
            'municipio'=> ['tipo' => 'municipio', 'grupo' => 'espacio', 'etiqueta' => 'Municipio', 'ayuda' => '', 'activo' => true, 'requerido' => true, 'fijo' => true],
            'direccion'=> ['tipo' => 'texto',  'grupo' => 'espacio', 'etiqueta' => 'Dirección', 'ayuda' => 'Dónde queda tu negocio u organización. El punto del mapa señala la zona; la dirección es la que lleva a la puerta.', 'activo' => true, 'requerido' => true],
            'tipo_organizacion' => ['tipo' => 'catalogo', 'grupo' => 'espacio', 'etiqueta' => '¿Qué tipo de organización eres?', 'ayuda' => '', 'activo' => true, 'requerido' => true, 'opciones' => Catalogos::frases('organizacion')],
            'actividad_cafe'    => ['tipo' => 'catalogo', 'grupo' => 'espacio', 'etiqueta' => '¿Cuál es tu actividad principal?', 'ayuda' => '', 'activo' => true, 'requerido' => true, 'opciones' => Catalogos::frases('actividad_generica')],
            'nit'        => ['tipo' => 'numero', 'grupo' => 'espacio', 'etiqueta' => 'NIT o RUT', 'ayuda' => 'Si estás constituido como empresa. Sólo números, con el dígito de verificación al final.', 'activo' => true, 'requerido' => false],
            'sitio_web'  => ['tipo' => 'texto',  'grupo' => 'espacio', 'etiqueta' => 'Sitio web o red social', 'ayuda' => '', 'activo' => true, 'requerido' => false],
            'descripcion'=> ['tipo' => 'parrafo','grupo' => 'espacio', 'etiqueta' => 'Descripción del espacio', 'ayuda' => 'Se muestra en la ficha pública de tu espacio.', 'activo' => true, 'requerido' => false],
            'logo'       => ['tipo' => 'imagen', 'grupo' => 'espacio', 'etiqueta' => 'Logo de tu espacio', 'ayuda' => 'Con él se te reconoce en el pasaporte de los visitantes y en la lista del evento.', 'activo' => true, 'requerido' => true],
            'ubicacion'  => ['tipo' => 'mapa',   'grupo' => 'ubicacion', 'etiqueta' => 'Ubicación en el mapa', 'ayuda' => '', 'activo' => true, 'requerido' => false],
            'poblacion'  => ['tipo' => 'catalogo', 'grupo' => 'participa', 'etiqueta' => '¿A cuál de los siguientes grupos o tipos de población pertenece usted principalmente?', 'ayuda' => 'Selecciona una sola opción.', 'activo' => true, 'requerido' => true, 'opciones' => Catalogos::frases('poblacion')],
            'linea_productiva' => ['tipo' => 'multiple', 'grupo' => 'linea', 'etiqueta' => 'Línea en la cual participa', 'ayuda' => '', 'activo' => false, 'requerido' => false, 'opciones' => Catalogos::frases('linea_productiva')],
            'cert_internacional'     => $si('¿Su producto cuenta con certificaciones internacionales?'),
            'organico'               => $si('¿Su producto es orgánico?'),
            'especial'               => $si('¿Su café es especial?'),
            'promedio_taza'          => ['tipo' => 'texto', 'grupo' => 'detalle', 'etiqueta' => '¿Cuál es el promedio de taza de su café?', 'ayuda' => 'Ej: 84,5 puntos SCA', 'activo' => false, 'requerido' => false],
            'marca_registrada'       => $si('¿Su marca se encuentra registrada ante la Superintendencia de Industria y Comercio?', true),
            'camara_comercio'        => $si('¿Cuenta con Certificado de Existencia y Representación Legal (Cámara de Comercio)?', true, false, 'Para personas jurídicas, asociaciones o cooperativas, con fecha de expedición no mayor a noventa (90) días.'),
            'invima'                 => $si('¿Su marca cuenta con acreditación sanitaria (INVIMA)?'),
            'manipulacion_alimentos' => $si('¿El o la expositora cuenta con certificado de manipulación de alimentos vigente?'),
            'presentacion' => ['tipo' => 'multiple', 'grupo' => 'detalle', 'etiqueta' => 'Presentación del producto', 'ayuda' => 'Puedes marcar más de una.', 'activo' => false, 'requerido' => false, 'opciones' => Catalogos::frases('presentacion')],
            'mensaje'    => ['tipo' => 'parrafo', 'grupo' => 'final', 'etiqueta' => 'Mensaje para el organizador (opcional)', 'ayuda' => '', 'activo' => true, 'requerido' => false],
        ];
    }

    /**
     * Plantillas que se cargan de un toque desde el panel.
     *
     * Una plantilla no es un modo del sistema: es un punto de partida. Cargarla
     * rellena el borrador del panel y el organizador sigue cambiando lo que
     * quiera antes de guardar.
     */
    public static function plantillas(): array
    {
        $base = self::porDefecto();

        // La Mejor Taza: la configuración con la que nació la plataforma.
        $taza = self::combinar($base, [
            'evento' => [
                'nombre'         => 'La Mejor Taza',
                'lema'           => '¿Cuál es la mejor taza de Nariño?',
                'descripcion'    => 'El festival lo decide el público. Escanea el QR de cada stand, vota con un emoji y sella tu pasaporte.',
                'portada_titulo' => 'El pasaporte del café nariñense.',
                'portada_texto'  => 'Recorre los stands, prueba los cafés y vota. Tu pasaporte se va sellando con cada visita, y entre todos decidimos cuál es la mejor taza de Nariño.',
                'pie_pagina'     => 'Comité del Café · Gobernación de Nariño · 2026',
            ],
            'marca'    => ['pie' => 'Festival · Nariño 2026', 'simbolo' => 'taza'],
            'simbolos' => ['primer_lugar' => 'La Mejor Taza', 'votos' => ['malo' => ['emoji' => '😞', 'texto' => 'Malo']]],
            'tema' => [
                'preset'          => 'mercado',
                'colores'         => self::presetsTema()['mercado']['colores'],
                'fuentes'         => ['display' => 'instrument', 'texto' => 'geist', 'meta' => 'jetbrains'],
                'titulos_cursiva' => true,
                'titulos_peso'    => 400,
            ],
            'encuesta' => [
                'titulo'         => '¿Cómo estuvo el café?',
                'instrucciones'  => 'Puntúa lo que quieras y toca un emoji para enviar.',
                'pregunta_emoji' => '¿Cómo te pareció nuestro espacio?',
                'aviso_datos'    => 'Al votar aceptas el tratamiento de datos del festival.',
            ],
            'inscripcion' => [
                'titulo' => 'Inscribe tu espacio en el festival.',
                'secciones' => [
                    'ubicacion' => ['titulo' => '¿Dónde estás?', 'nota' => 'Toca el mapa de Nariño donde queda tu finca o tu negocio. Al marcarlo se completa solo el municipio. Es opcional, pero ayuda a los visitantes a encontrarte y al festival a saber de qué zonas viene el café.'],
                    'participa' => ['titulo' => 'Quién participa', 'nota' => 'Sirve para saber a quién está llegando el festival. Nadie queda fuera por lo que responda aquí.'],
                    'linea'     => ['titulo' => 'Línea productiva en la cual participa', 'nota' => 'Puedes marcar más de una.'],
                    'detalle'   => ['titulo' => 'Información detallada', 'nota' => 'Detalles sobre tu emprendimiento, la presentación del producto, el origen y tu propuesta de valor. Esta información nos ayuda a conocer mejor tu papel en la cadena productiva del café.'],
                ],
            ],
            'pasaporte' => [
                'etiqueta'      => 'Pasaporte del Café',
                'invitacion'    => 'Empieza tu travesía del café.',
                'cierre_titulo' => 'Gracias por caminar el café con nosotros.',
                'cierre_boton'  => 'Vuelve el próximo festival',
            ],
            'datos' => ['texto' => self::POLITICA_TAZA],
        ]);
        // Los campos del café se activan uno a uno: la lista se reemplaza
        // entera en combinar() sólo si es una lista, y `campos` es un mapa.
        $c = &$taza['inscripcion']['campos'];
        $c['empresa']['etiqueta'] = 'Empresa, finca o marca';
        $c['stand_nombre']['etiqueta'] = 'Nombre del producto';
        $c['descripcion']['etiqueta'] = 'Descripción del producto';
        $c['logo']['etiqueta'] = 'Logo de tu producto';
        $c['nombre']['etiqueta'] = 'Nombre completo del propietario';
        $c['actividad_cafe']['etiqueta'] = '¿Cuál es tu actividad o vínculo con la cadena de valor del café?';
        $c['actividad_cafe']['opciones'] = Catalogos::frases('actividad');
        $c['linea_productiva'] = array_merge($c['linea_productiva'], ['activo' => true, 'etiqueta' => 'Línea productiva']);
        foreach (['cert_internacional' => '¿Su café cuenta con certificaciones internacionales?',
                  'organico' => '¿Su café es orgánico?', 'especial' => '¿Su café es especial?'] as $k => $txt) {
            $c[$k]['activo'] = true; $c[$k]['etiqueta'] = $txt;
        }
        $c['promedio_taza']['activo'] = true;
        foreach (['marca_registrada', 'camara_comercio', 'invima', 'manipulacion_alimentos'] as $k) {
            $c[$k]['activo'] = true; $c[$k]['requerido'] = true;
        }
        $c['camara_comercio']['ayuda'] = 'Para personas jurídicas, asociaciones o cooperativas, con fecha de expedición no mayor a noventa (90) días. Para pequeños productores individuales se acepta la certificación de la UMATA, la Secretaría de Agricultura Municipal o el Comité de Cafeteros.';
        $c['presentacion'] = array_merge($c['presentacion'], ['activo' => true, 'requerido' => true]);
        unset($c);
        $taza['estrellas'] = ['innovacion' => 'Innovación', 'atencion' => 'Atención', 'calidad' => 'Calidad'];

        return [
            'generica' => [
                'titulo' => 'Evento genérico · Gobernación de Nariño',
                'descripcion' => 'Identidad institucional (verde, amarillo y azul; Hind Madurai). Inscripción sin preguntas de un sector concreto.',
                'ajustes' => $base,
            ],
            'lamejortaza' => [
                'titulo' => 'La Mejor Taza · Festival del Café',
                'descripcion' => 'Los textos, la paleta «Mercado» y las preguntas del café (INVIMA, SIC, presentación…) con las que nació la plataforma.',
                'ajustes' => $taza,
            ],
        ];
    }

    // =====================================================================
    // Tema visual
    // =====================================================================

    /**
     * Tipografías elegibles. La pila es del servidor y no del cliente: va a
     * parar dentro de un <style>, y aceptar texto libre ahí sería abrir la
     * puerta a inyectar CSS.
     */
    public static function fuentes(): array
    {
        return [
            'hind'         => ['nombre' => 'Hind Madurai · institucional', 'pila' => '"Hind Madurai", "Segoe UI", Arial, sans-serif', 'cursiva' => false],
            'nunito'       => ['nombre' => 'Nunito Sans · institucional web', 'pila' => '"Nunito Sans", "Segoe UI", Arial, sans-serif', 'cursiva' => true],
            'source'       => ['nombre' => 'Source Sans 3 · institucional secundaria', 'pila' => '"Source Sans 3", "Segoe UI", Arial, sans-serif', 'cursiva' => true],
            'instrument'   => ['nombre' => 'Instrument Serif · editorial', 'pila' => '"Instrument Serif", "Cormorant Garamond", Georgia, serif', 'cursiva' => true],
            'geist'        => ['nombre' => 'Geist · moderna', 'pila' => '"Geist", "Inter", -apple-system, BlinkMacSystemFont, sans-serif', 'cursiva' => false],
            'jetbrains'    => ['nombre' => 'JetBrains Mono · monoespaciada', 'pila' => '"JetBrains Mono", ui-monospace, "SF Mono", monospace', 'cursiva' => false],
            'georgia'      => ['nombre' => 'Georgia · serif del sistema', 'pila' => 'Georgia, "Times New Roman", serif', 'cursiva' => true],
            'sistema'      => ['nombre' => 'Sans del sistema', 'pila' => 'system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif', 'cursiva' => true],
            'mono_sistema' => ['nombre' => 'Monoespaciada del sistema', 'pila' => 'ui-monospace, "SF Mono", Menlo, Consolas, monospace', 'cursiva' => false],
        ];
    }

    /** Paletas completas. `institucional` coincide con styles/tokens.css. */
    public static function presetsTema(): array
    {
        return [
            'institucional' => [
                'nombre' => 'Institucional · Gobernación de Nariño',
                'colores' => [
                    'paper' => '#ffffff', 'paper-2' => '#f3f6f4', 'paper-3' => '#e3eae5',
                    'ink' => '#003366', 'ink-2' => '#4a4a4a', 'ink-3' => '#6b6b6b',
                    'line' => '#dde3e8', 'line-2' => '#c3cdd5',
                    'grano' => '#0b7a2c', 'galeras' => '#10a13b', 'cafeto' => '#1f6f9f',
                    'good' => '#10a13b', 'meh' => '#e8b800', 'bad' => '#c62828',
                ],
                'fuentes' => ['display' => 'hind', 'texto' => 'nunito', 'meta' => 'source'],
                'titulos_cursiva' => false, 'titulos_peso' => 700,
            ],
            'mercado' => [
                'nombre' => 'Mercado · La Mejor Taza',
                'colores' => [
                    'paper' => '#fbf0e0', 'paper-2' => '#f2e6d6', 'paper-3' => '#e3d5c2',
                    'ink' => '#26160e', 'ink-2' => '#50453d', 'ink-3' => '#84786f',
                    'line' => '#d2cdc7', 'line-2' => '#bdb6ad',
                    'grano' => '#7c271c', 'galeras' => '#cf5604', 'cafeto' => '#5c7e34',
                    'good' => '#419547', 'meh' => '#c79e41', 'bad' => '#cf4040',
                ],
                'fuentes' => ['display' => 'instrument', 'texto' => 'geist', 'meta' => 'jetbrains'],
                'titulos_cursiva' => true, 'titulos_peso' => 400,
            ],
            'sobrio' => [
                'nombre' => 'Sobrio · gris y azul',
                'colores' => [
                    'paper' => '#f7f8fa', 'paper-2' => '#eceff3', 'paper-3' => '#dde2e8',
                    'ink' => '#1c2430', 'ink-2' => '#46505e', 'ink-3' => '#6b7685',
                    'line' => '#d9dee5', 'line-2' => '#c2c9d3',
                    'grano' => '#1d4ed8', 'galeras' => '#2563eb', 'cafeto' => '#0f766e',
                    'good' => '#15803d', 'meh' => '#d97706', 'bad' => '#b91c1c',
                ],
                'fuentes' => ['display' => 'source', 'texto' => 'source', 'meta' => 'source'],
                'titulos_cursiva' => false, 'titulos_peso' => 600,
            ],
        ];
    }

    /** Radios de las esquinas: sm, md, lg. */
    private const ESQUINAS = [
        'recta'      => [2, 3, 4],
        'normal'     => [4, 8, 14],
        'redondeada' => [8, 14, 22],
    ];

    /**
     * El CSS que aplica el tema: variables sobre :root y nada más.
     *
     * Todo lo que entra aquí está validado por sanear() —hex de 3 o 6 cifras,
     * claves de un catálogo del servidor, números con tope—, porque el
     * resultado se imprime dentro de un <style> en app.php.
     */
    public static function cssTema(array $tema): string
    {
        $vars = [];
        foreach (self::COLORES as $k) {
            $v = (string) ($tema['colores'][$k] ?? '');
            if (self::esHex($v)) $vars['--' . $k] = $v;
        }
        $f = self::fuentes();
        $map = ['display' => '--font-display', 'texto' => '--font-sans', 'meta' => '--font-mono'];
        foreach ($map as $rol => $var) {
            $clave = (string) ($tema['fuentes'][$rol] ?? '');
            if (isset($f[$clave])) $vars[$var] = $f[$clave]['pila'];
        }
        $vars['--display-style']  = !empty($tema['titulos_cursiva']) ? 'italic' : 'normal';
        $vars['--display-weight'] = (string) self::peso($tema['titulos_peso'] ?? 400);
        $r = self::ESQUINAS[(string) ($tema['esquinas'] ?? 'normal')] ?? self::ESQUINAS['normal'];
        $vars['--r-sm'] = $r[0] . 'px';
        $vars['--r-md'] = $r[1] . 'px';
        $vars['--r-lg'] = $r[2] . 'px';

        $css = ':root{';
        foreach ($vars as $k => $v) $css .= $k . ':' . $v . ';';
        return $css . '}';
    }

    /** Color de la barra del navegador en el móvil: la tinta del tema. */
    public static function colorTema(array $tema): string
    {
        $ink = (string) ($tema['colores']['ink'] ?? '');
        return self::esHex($ink) ? $ink : self::presetsTema()['institucional']['colores']['ink'];
    }

    public static function esHex(string $v): bool
    {
        return (bool) preg_match('/\A#(?:[0-9a-f]{3}|[0-9a-f]{6})\z/i', $v);
    }

    private static function peso($v): int
    {
        $n = (int) $v;
        return in_array($n, [300, 400, 500, 600, 700], true) ? $n : 400;
    }

    // =====================================================================
    // Lectura
    // =====================================================================

    /** Los ajustes vigentes: lo guardado sobre lo de fábrica, ya normalizado. */
    public static function ajustes(): array
    {
        if (self::$cache !== null) return self::$cache;
        $aj = Ajustes::grupo(self::GRUPO, self::porDefecto());
        return self::$cache = self::normalizar($aj, Ajustes::origen(self::GRUPO));
    }

    public static function olvidarCache(): void
    {
        self::$cache = null;
    }

    /**
     * Completa lo que una versión anterior no guardaba.
     *
     * Dos cosas importan aquí. Las tres valoraciones de sistema existen siempre
     * en la encuesta, aunque se hayan desactivado, porque son columnas de la
     * base. Y una instalación de La Mejor Taza que sólo había cambiado los
     * títulos de las estrellas (grupo `estrellas`) los conserva: si la encuesta
     * nunca se guardó, sus etiquetas salen de ahí.
     */
    public static function normalizar(array $aj, array $origen = []): array
    {
        $def = self::porDefecto();
        $encuestaGuardada = false;
        foreach ($origen as $clave) {
            if (strpos($clave, 'encuesta.preguntas') === 0) { $encuestaGuardada = true; break; }
        }

        $preguntas = is_array($aj['encuesta']['preguntas'] ?? null) ? array_values($aj['encuesta']['preguntas']) : [];
        $ids = array_column($preguntas, 'id');
        foreach ($def['encuesta']['preguntas'] as $p) {
            if (!in_array($p['id'], $ids, true)) array_unshift($preguntas, $p);
        }
        foreach ($preguntas as &$p) {
            if (isset(self::ESTRELLAS_SISTEMA[$p['id'] ?? ''])) {
                $p['tipo'] = 'estrellas';
                $p['sistema'] = true;
                $corta = self::ESTRELLAS_SISTEMA[$p['id']];
                if (!$encuestaGuardada && !empty($aj['estrellas'][$corta])) $p['etiqueta'] = (string) $aj['estrellas'][$corta];
            }
        }
        unset($p);
        $aj['encuesta']['preguntas'] = $preguntas;
        // Y al revés: `estrellas` sigue existiendo para quien lo lea (el
        // recorrido, el pasaporte, los CSV), siempre igual a la encuesta.
        foreach ($preguntas as $p) {
            if (isset(self::ESTRELLAS_SISTEMA[$p['id']])) {
                $aj['estrellas'][self::ESTRELLAS_SISTEMA[$p['id']]] = (string) $p['etiqueta'];
            }
        }

        // Campos de la inscripción: los del sistema siempre están, con su tipo
        // y su grupo de fábrica (el panel no los puede cambiar).
        $campos = is_array($aj['inscripcion']['campos'] ?? null) ? $aj['inscripcion']['campos'] : [];
        foreach ($def['inscripcion']['campos'] as $k => $c) {
            $guardado = is_array($campos[$k] ?? null) ? $campos[$k] : [];
            $campos[$k] = array_merge($c, $guardado, ['tipo' => $c['tipo'], 'grupo' => $c['grupo']]);
            if (!empty($c['fijo'])) {
                $campos[$k]['fijo'] = true;
                $campos[$k]['activo'] = true;
                $campos[$k]['requerido'] = true;
            }
        }
        $aj['inscripcion']['campos'] = array_intersect_key($campos, $def['inscripcion']['campos']);
        if (!is_array($aj['inscripcion']['preguntas'] ?? null)) $aj['inscripcion']['preguntas'] = [];

        return $aj;
    }

    public static function nombre(): string
    {
        $n = trim((string) (self::ajustes()['evento']['nombre'] ?? ''));
        return $n !== '' ? $n : 'Pasaporte del Evento';
    }

    public static function pie(): string
    {
        $p = trim((string) (self::ajustes()['marca']['pie'] ?? ''));
        return $p !== '' ? $p : self::porDefecto()['marca']['pie'];
    }

    /** ¿Se aceptan inscripciones ahora mismo? Interruptor y fecha de cierre. */
    public static function inscripcionAbierta(?array $aj = null): bool
    {
        $i = ($aj ?? self::ajustes())['inscripcion'] ?? [];
        if (empty($i['abierta'])) return false;
        $cierre = (string) ($i['cierre'] ?? '');
        if ($cierre === '') return true;
        try {
            $zona = new \DateTimeZone('America/Bogota');
            $fin = new \DateTimeImmutable($cierre . ' 23:59:59', $zona);
            return new \DateTimeImmutable('now', $zona) <= $fin;
        } catch (\Throwable $e) {
            return true;
        }
    }

    /** Configuración de un campo de la inscripción. */
    public static function campo(string $clave, ?array $aj = null): array
    {
        return (($aj ?? self::ajustes())['inscripcion']['campos'][$clave] ?? []) + ['activo' => false, 'requerido' => false];
    }

    /** Claves válidas de un catálogo configurado. */
    public static function clavesCampo(string $clave, ?array $aj = null): array
    {
        $ops = self::campo($clave, $aj)['opciones'] ?? [];
        return array_values(array_filter(array_map(fn($o) => is_array($o) ? (string) ($o[0] ?? '') : '', $ops)));
    }

    public static function compraActiva(?array $aj = null): bool
    {
        return !empty(($aj ?? self::ajustes())['encuesta']['compra']['activa']);
    }

    /** Valoraciones de sistema activas: ['est_innovacion', …]. */
    public static function estrellasActivas(?array $aj = null): array
    {
        $out = [];
        foreach ((($aj ?? self::ajustes())['encuesta']['preguntas'] ?? []) as $p) {
            if (!empty($p['sistema']) && !empty($p['activa'])) $out[] = (string) $p['id'];
        }
        return $out;
    }

    /** Preguntas propias de la encuesta que están activas. */
    public static function preguntasEncuesta(?array $aj = null, bool $soloActivas = true): array
    {
        return array_values(array_filter(
            (($aj ?? self::ajustes())['encuesta']['preguntas'] ?? []),
            fn($p) => empty($p['sistema']) && (!$soloActivas || !empty($p['activa']))
        ));
    }

    public static function preguntasInscripcion(?array $aj = null, bool $soloActivas = true): array
    {
        return array_values(array_filter(
            (($aj ?? self::ajustes())['inscripcion']['preguntas'] ?? []),
            fn($p) => !$soloActivas || !empty($p['activa'])
        ));
    }

    // =====================================================================
    // Respuestas a preguntas propias
    // =====================================================================

    /**
     * Valida las respuestas a las preguntas propias. Devuelve [id => valor]
     * sólo con lo contestado, listo para guardarse como JSON.
     *
     * Las opciones se guardan por su TEXTO: es lo que lee quien abre el CSV, y
     * una pregunta propia no tiene informes por clave como los catálogos del
     * sistema. `$exigir` distingue el formulario público (donde una pregunta
     * obligatoria se exige) del editor del panel (donde no se inventa nada).
     *
     * Lanza RuntimeException('respuesta_requerida' | 'respuesta_invalida').
     */
    public static function validarRespuestas(array $preguntas, $entrada, bool $exigir): array
    {
        $entrada = is_array($entrada) ? $entrada : [];
        $out = [];
        foreach ($preguntas as $p) {
            $id = (string) ($p['id'] ?? '');
            if ($id === '' || empty($p['activa'])) continue;
            $v = $entrada[$id] ?? null;
            $valor = self::valorRespuesta($p, $v);
            if ($valor === null) {
                $vacio = $v === null || $v === '' || $v === [] ;
                if (!$vacio) throw new \RuntimeException('respuesta_invalida');
                if ($exigir && !empty($p['requerida'])) throw new \RuntimeException('respuesta_requerida');
                continue;
            }
            $out[$id] = $valor;
        }
        return $out;
    }

    private static function valorRespuesta(array $p, $v)
    {
        $tipo = (string) ($p['tipo'] ?? 'texto');
        if ($v === null || $v === '' || $v === []) return null;
        switch ($tipo) {
            case 'estrellas':
                if (!is_numeric($v)) return null;
                $n = (int) $v;
                return ($n >= 1 && $n <= 5) ? $n : null;
            case 'si_no':
                if ($v === true || $v === 1 || $v === '1' || $v === 'true') return 1;
                if ($v === false || $v === 0 || $v === '0' || $v === 'false') return 0;
                return null;
            case 'numero':
                if (!is_scalar($v)) return null;
                $s = str_replace(',', '.', trim((string) $v));
                if (!preg_match('/\A-?\d{1,12}(\.\d{1,4})?\z/', $s)) return null;
                return $s;
            case 'fecha':
                if (!is_string($v) || !preg_match('/\A\d{4}-\d{2}-\d{2}\z/', $v)) return null;
                [$y, $m, $d] = array_map('intval', explode('-', $v));
                return checkdate($m, $d, $y) ? $v : null;
            case 'opcion':
                if (!is_string($v)) return null;
                return in_array($v, self::opcionesTexto($p), true) ? $v : null;
            case 'multiple':
                if (!is_array($v)) return null;
                $ops = self::opcionesTexto($p);
                $sel = array_values(array_filter($ops, fn($o) => in_array($o, $v, true)));
                return $sel === [] ? null : $sel;
            case 'parrafo':
                $t = Validate::parrafos($v, 1000);
                return $t === '' ? null : $t;
            case 'texto':
            default:
                $t = Validate::texto($v, 200);
                return $t === '' ? null : $t;
        }
    }

    private static function opcionesTexto(array $p): array
    {
        return array_values(array_filter(array_map('strval', (array) ($p['opciones'] ?? [])), fn($s) => $s !== ''));
    }

    /** Lee de vuelta un JSON de respuestas. Siempre un mapa. */
    public static function respuestasDe($json): array
    {
        if (!is_string($json) || $json === '') return [];
        $v = json_decode($json, true);
        return is_array($v) ? $v : [];
    }

    // =====================================================================
    // Escritura: lo que llega del panel
    // =====================================================================

    /**
     * Valida un borrador del panel contra los ajustes actuales.
     *
     * Sólo se toca lo que llega: un PUT con `{tema: {...}}` deja el resto como
     * estaba. Todo lo que no cumple se descarta en silencio salvo lo que tiene
     * un error con nombre (enlace inválido, color mal escrito), que se
     * devuelve para que el panel lo pueda decir.
     */
    public static function sanear(array $b, array $act): array
    {
        $def = self::porDefecto();

        if (isset($b['evento']) && is_array($b['evento'])) {
            $e = $b['evento'];
            $txt = ['nombre' => 60, 'lema' => 140, 'portada_titulo' => 140, 'horario' => 120, 'lugar' => 160,
                    'ciudad' => 80, 'organizador' => 160, 'contacto' => 160, 'pie_pagina' => 160];
            foreach ($txt as $k => $max) {
                if (array_key_exists($k, $e)) $act['evento'][$k] = Validate::texto($e[$k], $max);
            }
            foreach (['descripcion' => 600, 'portada_texto' => 600] as $k => $max) {
                if (array_key_exists($k, $e)) $act['evento'][$k] = Validate::parrafos($e[$k], $max);
            }
            if ($act['evento']['nombre'] === '') $act['evento']['nombre'] = $def['evento']['nombre'];
            foreach (['fecha_inicio', 'fecha_fin'] as $k) {
                if (array_key_exists($k, $e)) $act['evento'][$k] = self::fecha($e[$k]);
            }
            if (array_key_exists('sitio_web', $e)) $act['evento']['sitio_web'] = self::enlace($e['sitio_web']);
            if (array_key_exists('mostrar_datos', $e)) $act['evento']['mostrar_datos'] = (bool) $e['mostrar_datos'];
        }

        if (isset($b['marca']) && is_array($b['marca'])) {
            if (array_key_exists('pie', $b['marca'])) {
                $pie = Validate::texto($b['marca']['pie'], 60);
                $act['marca']['pie'] = $pie !== '' ? $pie : $def['marca']['pie'];
            }
            if (array_key_exists('mostrar_nombre', $b['marca'])) $act['marca']['mostrar_nombre'] = (bool) $b['marca']['mostrar_nombre'];
            if (in_array($b['marca']['simbolo'] ?? '', self::SIMBOLOS, true)) $act['marca']['simbolo'] = (string) $b['marca']['simbolo'];
            // El logo sólo se cambia por su ruta de subida o se quita con '':
            // aceptar cualquier ruta permitiría apuntar a otro fichero.
            if (array_key_exists('logo', $b['marca']) && (string) $b['marca']['logo'] === '') $act['marca']['logo'] = '';
        }

        if (isset($b['simbolos']) && is_array($b['simbolos'])) {
            $s = $b['simbolos'];
            foreach (['malo', 'regular', 'bueno'] as $k) {
                if (!isset($s['votos'][$k]) || !is_array($s['votos'][$k])) continue;
                $emoji = self::emoji($s['votos'][$k]['emoji'] ?? '');
                if ($emoji !== '') $act['simbolos']['votos'][$k]['emoji'] = $emoji;
                $t = Validate::texto($s['votos'][$k]['texto'] ?? '', 24);
                if ($t !== '') $act['simbolos']['votos'][$k]['texto'] = $t;
            }
            if (array_key_exists('sello', $s)) {
                $t = mb_strtoupper(Validate::texto($s['sello'], 18), 'UTF-8');
                $act['simbolos']['sello'] = $t !== '' ? $t : $def['simbolos']['sello'];
            }
            if (array_key_exists('primer_lugar', $s)) {
                $t = Validate::texto($s['primer_lugar'], 40);
                $act['simbolos']['primer_lugar'] = $t !== '' ? $t : $def['simbolos']['primer_lugar'];
            }
        }

        if (isset($b['tema']) && is_array($b['tema'])) {
            $t = $b['tema'];
            if (isset($t['colores']) && is_array($t['colores'])) {
                foreach (self::COLORES as $k) {
                    if (!array_key_exists($k, $t['colores'])) continue;
                    $v = strtolower(trim((string) $t['colores'][$k]));
                    if ($v !== '' && !self::esHex($v)) throw new \RuntimeException('color_invalido');
                    $act['tema']['colores'][$k] = $v;
                }
            }
            $fuentes = self::fuentes();
            foreach (['display', 'texto', 'meta'] as $rol) {
                $v = (string) ($t['fuentes'][$rol] ?? '');
                if ($v !== '' && isset($fuentes[$v])) $act['tema']['fuentes'][$rol] = $v;
            }
            if (array_key_exists('titulos_cursiva', $t)) $act['tema']['titulos_cursiva'] = (bool) $t['titulos_cursiva'];
            if (array_key_exists('titulos_peso', $t)) $act['tema']['titulos_peso'] = self::peso($t['titulos_peso']);
            if (isset($t['esquinas']) && isset(self::ESQUINAS[$t['esquinas']])) $act['tema']['esquinas'] = (string) $t['esquinas'];
            if (isset($t['preset']) && (isset(self::presetsTema()[$t['preset']]) || $t['preset'] === 'personalizado')) {
                $act['tema']['preset'] = (string) $t['preset'];
            }
        }

        if (isset($b['encuesta']) && is_array($b['encuesta'])) {
            $act['encuesta'] = self::sanearEncuesta($b['encuesta'], $act['encuesta']);
            // `estrellas` acompaña siempre a las valoraciones de sistema.
            foreach ($act['encuesta']['preguntas'] as $p) {
                if (isset(self::ESTRELLAS_SISTEMA[$p['id']])) $act['estrellas'][self::ESTRELLAS_SISTEMA[$p['id']]] = $p['etiqueta'];
            }
        }
        // Compatibilidad con el panel anterior: {estrellas: {innovacion: '…'}}.
        if (isset($b['estrellas']) && is_array($b['estrellas']) && !isset($b['encuesta'])) {
            foreach (self::ESTRELLAS_SISTEMA as $id => $corta) {
                $v = Validate::texto($b['estrellas'][$corta] ?? null, 40);
                if ($v === '') continue;
                $act['estrellas'][$corta] = $v;
                foreach ($act['encuesta']['preguntas'] as &$p) {
                    if ($p['id'] === $id) $p['etiqueta'] = $v;
                }
                unset($p);
            }
        }

        if (isset($b['inscripcion']) && is_array($b['inscripcion'])) {
            $act['inscripcion'] = self::sanearInscripcion($b['inscripcion'], $act['inscripcion']);
        }

        if (isset($b['recorrido']) && is_array($b['recorrido'])) {
            foreach (['columnas_pc' => 6, 'columnas_movil' => 3] as $k => $max) {
                if (isset($b['recorrido'][$k])) $act['recorrido'][$k] = max(1, min($max, (int) $b['recorrido'][$k]));
            }
        }

        if (isset($b['pasaporte']) && is_array($b['pasaporte'])) {
            $p = $b['pasaporte'];
            $txt = ['etiqueta' => 40, 'titulo_portada' => 60, 'lugar_1' => 30, 'lugar_2' => 30,
                    'encabezado_1' => 40, 'encabezado_2' => 40, 'invitacion' => 120,
                    'cierre_titulo' => 120, 'cierre_boton' => 60, 'cierre_firma' => 60];
            foreach ($txt as $k => $max) {
                if (!array_key_exists($k, $p)) continue;
                $v = Validate::texto($p[$k], $max);
                // Los textos vacíos vuelven al de fábrica, salvo los que pueden
                // quedar en blanco a propósito (el título cae al nombre).
                $act['pasaporte'][$k] = ($v === '' && !in_array($k, ['titulo_portada', 'lugar_1', 'lugar_2', 'encabezado_1', 'encabezado_2', 'cierre_firma'], true))
                    ? $def['pasaporte'][$k] : $v;
            }
            if (array_key_exists('prefijo', $p)) {
                $pre = strtoupper(preg_replace('/[^A-Za-z]/', '', (string) $p['prefijo']) ?? '');
                $act['pasaporte']['prefijo'] = $pre !== '' ? substr($pre, 0, 4) : $def['pasaporte']['prefijo'];
            }
        }

        if (isset($b['datos']) && is_array($b['datos'])) {
            $d = $b['datos'];
            if (array_key_exists('texto', $d)) {
                $t = Validate::parrafos($d['texto'], 8000);
                $act['datos']['texto'] = $t !== '' ? $t : $def['datos']['texto'];
            }
            if (array_key_exists('enlace', $d)) {
                $enlace = trim((string) ($d['enlace'] ?? ''));
                if ($enlace === '') $act['datos']['enlace'] = '';
                else {
                    $u = Validate::url($enlace, 400);
                    if ($u === null) throw new \RuntimeException('enlace_invalido');
                    $act['datos']['enlace'] = $u;
                }
            }
            if (array_key_exists('enlace_texto', $d)) {
                $t = Validate::texto($d['enlace_texto'], 160);
                $act['datos']['enlace_texto'] = $t !== '' ? $t : $def['datos']['enlace_texto'];
            }
            if (array_key_exists('responsable', $d)) {
                $t = Validate::texto($d['responsable'], 120);
                $act['datos']['responsable'] = $t !== '' ? $t : $def['datos']['responsable'];
            }
        }

        return self::normalizar($act);
    }

    private static function sanearEncuesta(array $e, array $act): array
    {
        $def = self::porDefecto()['encuesta'];
        foreach (['titulo' => 120, 'instrucciones' => 200, 'pregunta_emoji' => 120, 'aviso_datos' => 200] as $k => $max) {
            if (!array_key_exists($k, $e)) continue;
            $v = Validate::texto($e[$k], $max);
            $act[$k] = ($v === '' && $k !== 'aviso_datos') ? $def[$k] : $v;
        }
        if (isset($e['compra']) && is_array($e['compra'])) {
            $c = $e['compra'];
            foreach (['activa', 'pide_valor'] as $k) if (array_key_exists($k, $c)) $act['compra'][$k] = (bool) $c[$k];
            foreach (['pregunta' => 120, 'si' => 40, 'no' => 40, 'pregunta_valor' => 120] as $k => $max) {
                if (!array_key_exists($k, $c)) continue;
                $v = Validate::texto($c[$k], $max);
                $act['compra'][$k] = $v !== '' ? $v : $def['compra'][$k];
            }
        }
        if (isset($e['comentario']) && is_array($e['comentario'])) {
            $c = $e['comentario'];
            if (array_key_exists('activo', $c)) $act['comentario']['activo'] = (bool) $c['activo'];
            foreach (['etiqueta' => 120, 'placeholder' => 160] as $k => $max) {
                if (!array_key_exists($k, $c)) continue;
                $v = Validate::texto($c[$k], $max);
                $act['comentario'][$k] = $v !== '' ? $v : $def['comentario'][$k];
            }
        }
        if (isset($e['preguntas']) && is_array($e['preguntas'])) {
            $act['preguntas'] = self::sanearPreguntas($e['preguntas'], self::TIPOS_ENCUESTA, true);
        }
        return $act;
    }

    private static function sanearInscripcion(array $i, array $act): array
    {
        $def = self::porDefecto()['inscripcion'];
        if (array_key_exists('abierta', $i)) $act['abierta'] = (bool) $i['abierta'];
        if (array_key_exists('cierre', $i)) $act['cierre'] = self::fecha($i['cierre']);
        foreach (['titulo' => 120, 'mensaje_cerrada' => 400] as $k => $max) {
            if (!array_key_exists($k, $i)) continue;
            $v = Validate::texto($i[$k], $max);
            $act[$k] = $v !== '' ? $v : $def[$k];
        }
        if (array_key_exists('intro', $i)) {
            $v = Validate::parrafos($i['intro'], 800);
            $act['intro'] = $v !== '' ? $v : $def['intro'];
        }
        if (isset($i['secciones']) && is_array($i['secciones'])) {
            foreach ($def['secciones'] as $k => $_) {
                if (!isset($i['secciones'][$k]) || !is_array($i['secciones'][$k])) continue;
                $s = $i['secciones'][$k];
                if (array_key_exists('titulo', $s)) {
                    $v = Validate::texto($s['titulo'], 80);
                    $act['secciones'][$k]['titulo'] = $v !== '' ? $v : $def['secciones'][$k]['titulo'];
                }
                if (array_key_exists('nota', $s)) $act['secciones'][$k]['nota'] = Validate::texto($s['nota'], 400);
            }
        }
        if (isset($i['campos']) && is_array($i['campos'])) {
            foreach ($def['campos'] as $k => $c) {
                if (!isset($i['campos'][$k]) || !is_array($i['campos'][$k])) continue;
                $n = $i['campos'][$k];
                $cur = $act['campos'][$k] ?? $c;
                if (empty($c['fijo'])) {
                    if (array_key_exists('activo', $n)) $cur['activo'] = (bool) $n['activo'];
                    if (array_key_exists('requerido', $n) && $c['tipo'] !== 'mapa') $cur['requerido'] = (bool) $n['requerido'];
                }
                if (array_key_exists('etiqueta', $n)) {
                    $v = Validate::texto($n['etiqueta'], 200);
                    $cur['etiqueta'] = $v !== '' ? $v : $c['etiqueta'];
                }
                if (array_key_exists('ayuda', $n)) $cur['ayuda'] = Validate::texto($n['ayuda'], 400);
                if (isset($n['opciones']) && is_array($n['opciones']) && isset($c['opciones'])) {
                    $cur['opciones'] = self::sanearOpcionesCatalogo($n['opciones'], $c['tipo'] === 'multiple' ? 'otros' : 'otro');
                    if ($cur['opciones'] === []) $cur['opciones'] = $c['opciones'];
                }
                $act['campos'][$k] = $cur;
            }
        }
        if (isset($i['preguntas']) && is_array($i['preguntas'])) {
            $act['preguntas'] = self::sanearPreguntas($i['preguntas'], self::TIPOS_INSCRIPCION, false);
        }
        return $act;
    }

    /**
     * Opciones de un catálogo del sistema: [[clave, frase], …].
     *
     * Las claves existentes se respetan —son lo que hay guardado en la base—;
     * una opción nueva llega sin clave y aquí se le inventa una a partir de la
     * frase. «Otro» (u «Otros») se reconoce por su clave y abre el campo libre.
     */
    private static function sanearOpcionesCatalogo(array $ops, string $claveOtro): array
    {
        $out = [];
        $usadas = [];
        foreach (array_slice($ops, 0, 40) as $o) {
            if (!is_array($o)) continue;
            $texto = Validate::texto($o[1] ?? '', 300);
            if ($texto === '') continue;
            $clave = (string) ($o[0] ?? '');
            if (!preg_match('/\A[a-z0-9_]{1,40}\z/', $clave)) {
                $base = trim((string) preg_replace('/[^a-z0-9]+/', '_', Validate::plegarAscii($texto)), '_');
                $clave = substr($base !== '' ? $base : 'opcion', 0, 32);
            }
            $c = $clave; $n = 2;
            while (in_array($c, $usadas, true)) $c = substr($clave, 0, 30) . '_' . $n++;
            $usadas[] = $c;
            $out[] = [$c, $texto];
        }
        // «Otro» siempre al final, que es donde se busca.
        usort($out, fn($a, $b) => (int) ($a[0] === $claveOtro) <=> (int) ($b[0] === $claveOtro));
        return $out;
    }

    /** Preguntas propias (o las de sistema de la encuesta, sólo etiqueta y activa). */
    private static function sanearPreguntas(array $lista, array $tipos, bool $esEncuesta): array
    {
        $out = [];
        $ids = [];
        foreach (array_slice($lista, 0, 40) as $p) {
            if (!is_array($p)) continue;
            $id = (string) ($p['id'] ?? '');
            $sistema = $esEncuesta && isset(self::ESTRELLAS_SISTEMA[$id]);
            if (!$sistema && !preg_match('/\Ap_[a-z0-9]{4,16}\z/', $id)) continue;
            if (in_array($id, $ids, true)) continue;
            $ids[] = $id;
            $etiqueta = Validate::texto($p['etiqueta'] ?? '', 200);
            if ($sistema) {
                $out[] = ['id' => $id, 'tipo' => 'estrellas', 'etiqueta' => $etiqueta !== '' ? $etiqueta : self::porDefecto()['estrellas'][self::ESTRELLAS_SISTEMA[$id]],
                          'activa' => !empty($p['activa']), 'sistema' => true];
                continue;
            }
            $tipo = (string) ($p['tipo'] ?? '');
            if (!in_array($tipo, $tipos, true) || $etiqueta === '') continue;
            $q = [
                'id' => $id, 'tipo' => $tipo, 'etiqueta' => $etiqueta,
                'ayuda' => Validate::texto($p['ayuda'] ?? '', 300),
                'activa' => !array_key_exists('activa', $p) || !empty($p['activa']),
                'requerida' => !empty($p['requerida']),
            ];
            if (!$esEncuesta) $q['publica'] = !empty($p['publica']);
            if (in_array($tipo, ['opcion', 'multiple'], true)) {
                $ops = [];
                foreach (array_slice((array) ($p['opciones'] ?? []), 0, 30) as $o) {
                    $t = Validate::texto(is_scalar($o) ? (string) $o : '', 120);
                    if ($t !== '' && !in_array($t, $ops, true)) $ops[] = $t;
                }
                if (count($ops) < 2) continue;   // una «opción» de una no es una pregunta
                $q['opciones'] = $ops;
            }
            $out[] = $q;
        }
        // Las de sistema no se pueden borrar desde el panel: si no llegan, se
        // conservan desactivadas en vez de desaparecer.
        if ($esEncuesta) {
            foreach (self::porDefecto()['encuesta']['preguntas'] as $p) {
                if (!in_array($p['id'], $ids, true)) $out[] = array_merge($p, ['activa' => false]);
            }
        }
        return $out;
    }

    private static function fecha($v): string
    {
        $v = trim((string) $v);
        if (!preg_match('/\A\d{4}-\d{2}-\d{2}\z/', $v)) return '';
        [$y, $m, $d] = array_map('intval', explode('-', $v));
        return checkdate($m, $d, $y) ? $v : '';
    }

    private static function enlace($v): string
    {
        $v = trim((string) $v);
        if ($v === '') return '';
        $u = Validate::url($v, 255);
        if ($u === null) throw new \RuntimeException('enlace_invalido');
        return $u;
    }

    /** Un emoji o símbolo corto: hasta 8 caracteres, sin letras ASCII ni marcas. */
    private static function emoji($v): string
    {
        $t = Validate::texto($v, 8);
        if ($t === '' || preg_match('/[<>&"\'`]/', $t)) return '';
        return $t;
    }

    /** Combinación recursiva: los mapas se mezclan, las listas se reemplazan. */
    public static function combinar(array $base, array $encima): array
    {
        foreach ($encima as $k => $v) {
            if (is_array($v) && isset($base[$k]) && is_array($base[$k]) && !array_is_list($v)) {
                $base[$k] = self::combinar($base[$k], $v);
            } else {
                $base[$k] = $v;
            }
        }
        return $base;
    }

    // =====================================================================
    // Política de tratamiento de datos
    // =====================================================================

    /**
     * Texto por defecto. Lo tiene que poder cambiar el área jurídica sin un
     * despliegue: la finalidad y el responsable cambian de un evento a otro.
     */
    public const POLITICA = <<<'TXT'
La Gobernación de Nariño, en calidad de responsable del tratamiento de los datos personales, realizará el tratamiento de la información suministrada mediante este formulario, de conformidad con la Ley 1581 de 2012 y demás normas aplicables en materia de protección de datos personales.

Los datos personales serán tratados para las finalidades relacionadas con la inscripción, selección, organización, comunicación, participación y desarrollo del evento, así como para identificar y caracterizar a los participantes y sus emprendimientos.

De igual manera, la información podrá ser utilizada para actividades de difusión y comunicación institucional relacionadas con el evento, incluyendo la publicación de información sobre los emprendimientos participantes y la captura, uso y divulgación de fotografías, videos y demás material audiovisual obtenido durante las actividades desarrolladas en el evento, a través de los canales institucionales de la Gobernación de Nariño.

El titular declara que conoce los derechos que le asisten respecto del tratamiento de sus datos personales, entre ellos, conocer, actualizar y rectificar su información y, cuando sea procedente, solicitar su supresión o revocar la autorización otorgada.
TXT;

    public const POLITICA_TAZA = <<<'TXT'
La Gobernación de Nariño, a través de la Secretaría de Agricultura y Desarrollo Rural, en calidad de responsable del tratamiento de los datos personales, realizará el tratamiento de la información suministrada mediante este formulario, de conformidad con la Ley 1581 de 2012 y demás normas aplicables en materia de protección de datos personales.

Los datos personales serán tratados para las finalidades relacionadas con la inscripción, selección, organización, comunicación, participación y desarrollo de la muestra comercial "La Mejor Taza Nariño 2026", así como para identificar y caracterizar a los participantes y sus emprendimientos dentro de la cadena de valor del café.

De igual manera, la información podrá ser utilizada para actividades de difusión y comunicación institucional relacionadas con la muestra, incluyendo la publicación de información sobre los emprendimientos participantes y la captura, uso y divulgación de fotografías, videos y demás material audiovisual obtenido durante las actividades desarrolladas en el evento, a través de los canales institucionales de la Gobernación de Nariño y de la Secretaría de Agricultura y Desarrollo Rural y demás dependencias.

El titular declara que conoce los derechos que le asisten respecto del tratamiento de sus datos personales, entre ellos, conocer, actualizar y rectificar su información y, cuando sea procedente, solicitar su supresión o revocar la autorización otorgada.
TXT;
}
