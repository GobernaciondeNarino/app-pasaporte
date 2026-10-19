# Configuración del evento

Guía para quien organiza. Todo lo de este documento se hace desde el navegador,
en **Panel → Configuración del evento** (`/admin/festival`), sin tocar código ni
pedirle nada a quien administra el servidor.

> **Cómo se guarda.** Los cambios de todas las pestañas se preparan en un
> **borrador**. La barra de abajo dice si hay cambios sin guardar; nada cambia
> para el público hasta pulsar **Guardar cambios**, y en ese momento se aplica en
> toda la plataforma, incluidos los visitantes que la tienen abierta. Las
> **imágenes** (logo y fondos del pasaporte) son la excepción: se guardan al
> subirlas.

---

## Contenido

1. [Antes de empezar: las plantillas](#1-antes-de-empezar-las-plantillas)
2. [Evento: nombre y datos básicos](#2-evento-nombre-y-datos-básicos)
3. [Marca y símbolos](#3-marca-y-símbolos)
4. [Colores y letra](#4-colores-y-letra)
5. [Encuesta del voto](#5-encuesta-del-voto)
6. [Inscripción de espacios](#6-inscripción-de-espacios)
7. [Pasaporte](#7-pasaporte)
8. [Datos personales](#8-datos-personales)
9. [Dónde se ve cada cosa](#9-dónde-se-ve-cada-cosa)
10. [Lista de comprobación antes de abrir al público](#10-lista-de-comprobación-antes-de-abrir-al-público)

---

## 1. Antes de empezar: las plantillas

Arriba de la página, **Empezar desde una plantilla** carga una configuración
completa en el borrador. Es un punto de partida: después se cambia lo que haga
falta y se guarda.

| Plantilla | Para qué |
| --- | --- |
| **Evento genérico · Gobernación de Nariño** | La configuración de fábrica: identidad institucional (verde, amarillo y azul; Hind Madurai) y una inscripción sin preguntas de un sector concreto. |
| **La Mejor Taza · Festival del Café** | Los textos, la paleta «Mercado» y las preguntas del café (SIC, Cámara de Comercio, INVIMA, presentación del producto…) con las que nació la plataforma. |

Cargar una plantilla **conserva las imágenes ya subidas** (logo y fondos del
pasaporte) y no guarda nada hasta que se pulse *Guardar cambios*.

---

## 2. Evento: nombre y datos básicos

**Nombre y portada**

| Campo | Dónde aparece |
| --- | --- |
| Nombre del evento | Junto al logo en toda la aplicación, título de la pestaña del navegador, asunto y cabecera de los correos, remitente del correo si no se configuró otro. |
| Lema del tablero público | El titular grande de `/festival`. **Su última palabra sale en el color destacado** (como «Nariño» en «¿Cuál es la mejor taza de *Nariño*?»). |
| Descripción | El párrafo bajo el lema. |
| Título y texto de la portada | La columna oscura de la página de inicio (`/`). |

**Datos básicos** — fecha de inicio y de cierre, horario, lugar, ciudad, quién
organiza, sitio web y contacto. Se muestran en la **portada** y en un bloque
«Datos del evento» del **tablero principal**. Lo que se deja vacío no aparece.
El interruptor *Mostrar los datos del evento* los oculta todos de una vez.

Las fechas se escriben solas en español: *12 al 14 de noviembre de 2026*. La
fecha y la ciudad salen también al pie del cartel del código QR de cada espacio.

---

## 3. Marca y símbolos

- **Logo.** PNG con fondo transparente es lo mejor. Se usa junto al nombre, en
  la portada del pasaporte (en claro) y como icono de la pestaña del navegador.
- **Símbolo cuando no hay logo.** Libreta con sello (de fábrica), taza de café,
  Estrella del Sur o montaña y sol. Se dibuja en el color del tema.
- **Escribir el nombre junto al logo.** Desactívalo si el logo ya lleva el
  nombre escrito.
- **Texto bajo el nombre.** El renglón pequeño (por ejemplo «Gobernación de
  Nariño · 2026»). También encabeza los correos.
- **Símbolos del voto.** Los tres botones con los que se califica: el dibujo
  (un emoji) y la palabra de cada uno. El significado no cambia —peor,
  intermedio, mejor— y los votos anteriores siguen contando igual.
- **Palabra dentro del sello** (de fábrica, VISITADO) y **rótulo del primer
  lugar** del podio.

---

## 4. Colores y letra

- **Paletas.** *Institucional*, *Mercado* (La Mejor Taza) y *Sobrio* aplican de
  una vez los colores y la letra. Después se puede cambiar cada valor.
- **Tipografía**, por separado para **títulos**, **texto** y **metadatos y
  etiquetas** (los rótulos pequeños en mayúsculas). Las tres institucionales
  —Hind Madurai, Nunito Sans y Source Sans 3— vienen incluidas en el servidor:
  no dependen de Google ni de internet.
- **Peso y cursiva de los títulos**, y **esquinas** (casi rectas, normales,
  redondeadas).
- **Colores**, agrupados por función:

| Grupo | Colores |
| --- | --- |
| Fondo y secciones | fondo, secciones, secciones resaltadas |
| Texto | principal (también botones y franjas oscuras), secundario, metadatos |
| Líneas | divisores, bordes de los campos |
| Acentos | acento principal (enlaces), palabra destacada, acento secundario |
| Calificaciones | excelente, regular (también las estrellas), mejorable |

Cada color se elige con el selector o escribiendo su código (`#10A13B`).
**«base»** lo devuelve al del tema institucional. La **vista previa** de la
derecha usa exactamente los valores del borrador: compruebe ahí que el texto se
lee bien sobre el fondo antes de guardar.

> Identidad de la Gobernación de Nariño (Manual de Identidad Visual 2024 y
> Manual de Sitios Web 2025): verde `#10A13B`, amarillo `#FFD500`, azul
> `#003366`, grises de texto `#4A4A4A`; Hind Madurai como tipografía principal
> y Source Sans como secundaria. Es lo que trae la paleta *Institucional*.

---

## 5. Encuesta del voto

Es lo que ve quien escanea el QR de un espacio. **Tocar un emoji envía el
voto**, así que todas las preguntas van antes de los emoji.

- **Textos**: título, instrucción, pregunta sobre los emoji y aviso de datos al
  pie (vacío = no se muestra).
- **¿Compraste algo?** Se activa o se desactiva. Activa, se pueden cambiar sus
  textos y decidir si además se pregunta **cuánto gastó**. Desactivada, el
  formulario no la muestra, el servidor no guarda compras nuevas y *Actividad
  económica* lo advierte.
- **Comentario**: se permite o no, y con qué texto.
- **Preguntas.** Las tres valoraciones con estrellas (Innovación, Atención y
  Calidad de fábrica) son del sistema: se **renombran** y se **desactivan**, no
  se borran. Debajo se **añaden** las propias, de cuatro tipos:

| Tipo | Ejemplo |
| --- | --- |
| Estrellas (1 a 5) | «¿Cómo valoras la atención?» |
| Sí / No | «¿Lo recomendarías?» |
| Una opción de una lista | «¿Volverías el próximo año?» — Sí / Tal vez / No |
| Texto corto | «¿Qué mejorarías?» |

Cada pregunta propia se puede **reordenar** (↑ ↓), **desactivar** (deja de
preguntarse; lo respondido se conserva), marcar como **obligatoria** (el voto no
se envía sin ella) o **eliminar**.

**Resultados:** Panel → **Encuesta** (`/admin/encuesta`) resume cada pregunta
—media y reparto de estrellas, sí/no, cuántos eligieron cada opción, últimas
respuestas abiertas— para todo el evento o un espacio concreto. Todas las
respuestas salen también en **Descargar todos los votos (CSV)**, una columna por
pregunta.

---

## 6. Inscripción de espacios

### Abierta o cerrada

- **Inscripción pública abierta**: el interruptor general.
- **Cierre automático**: una fecha; la inscripción se cierra sola al terminar
  ese día (hora de Colombia).
- **Mensaje con la inscripción cerrada**: lo que ve quien entra a `/inscripcion`.

Cerrada, el formulario no se muestra, el enlace «Inscribirme como promotor»
desaparece de la portada y el servidor rechaza solicitudes nuevas. Los espacios
se siguen pudiendo crear desde *Panel → Espacios*, y los promotores ya inscritos
siguen entrando a su portal. *Panel → Promotores* muestra arriba si está abierta
o cerrada.

### Campos

Cada campo del formulario tiene:

- **Se pide**: si aparece en el formulario.
- **Obligatorio**: si hay que llenarlo para enviar.
- **Texto** de la pregunta y **ayuda** bajo el campo.
- En las listas (tipo de organización, actividad, población, línea,
  presentación): las **opciones**, que se pueden reescribir, añadir y quitar, y
  la opción **«Otro»** con su campo para escribir.

Nombre, correo, empresa y municipio **los necesita el sistema** y no se pueden
quitar. Los campos del café (certificaciones, orgánico, especial, promedio de
taza, INVIMA, manipulación de alimentos, presentación) vienen **desactivados**
en la plantilla genérica y **activos** en la de La Mejor Taza.

> Cambiar el texto de una opción cambia cómo se lee **en todos los informes**:
> en la base se guarda una clave, no la frase. Quitar una opción no borra lo que
> ya eligió alguien: el editor del panel lo sigue mostrando.

Un campo que se deja de pedir **conserva los datos** de los espacios que ya lo
tenían, y el editor de espacios del panel lo sigue mostrando para ellos.

### Preguntas adicionales

Preguntas propias del evento, en un bloque antes de «Cómo vas a entrar». Siete
tipos: texto corto, texto largo, número, fecha, sí/no, una opción y varias
opciones. Además de obligatoria y activa, cada una puede marcarse como **«Se ve
en la ficha pública»**: sus respuestas aparecen en la ficha del espacio que ve
el público. Las demás sólo las ve el panel (revisión de la inscripción, editor
del espacio y `stands.csv`).

---

## 7. Pasaporte

- **Portada y hoja de datos**: rótulo y título de la portada (vacío = el nombre
  del evento), las dos líneas de lugar (NARIÑO / COLOMBIA), los dos renglones de
  encabezado de la hoja de datos y el **prefijo del número** de pasaporte
  (`NAR-K7Q2-M4XP`; hasta 4 letras).
- **Textos**: invitación con el pasaporte vacío, título y botón de la hoja de
  cierre, y firma de la contraportada.
- **Rejilla de «Mi recorrido»**: columnas en computador y en móvil.
- **Fondos**: portada, contraportada y hojas internas (se reparten en orden y se
  repiten).

Las dos vistas del pasaporte —el libro 3D y la versión sin 3D— usan los mismos
textos, colores y tipografías.

---

## 8. Datos personales

El aviso de la **Ley 1581 de 2012** que se lee desde la casilla de autorización
de la inscripción: el **responsable** que aparece en la casilla («Autorizo a …»),
el **texto** del aviso (una línea en blanco entre párrafos), el **enlace** a la
política completa y su texto. *Ver cómo queda el aviso* lo enseña con el
borrador, antes de guardar.

---

## 9. Dónde se ve cada cosa

| Pantalla | Qué usa de la configuración |
| --- | --- |
| Portada `/` | nombre, pie, logo, título y texto de portada, datos del evento, inscripción abierta |
| Tablero `/festival` | nombre, lema, descripción, datos del evento, emoji, primer lugar, pie de página |
| Ficha de un espacio | valoraciones activas, emoji, respuestas públicas de la inscripción |
| Voto `/s/{id}` | textos, valoraciones, preguntas propias, compra, comentario, emoji |
| Pasaporte | textos del pasaporte, emblema, sello, colores y letra |
| Inscripción `/inscripcion` | abierta/cerrada, encabezado, campos, preguntas, política de datos |
| Cartel del QR | nombre, pie, emoji, sello, fechas y ciudad |
| Correos | nombre, pie, organizador, color principal |

---

## 10. Lista de comprobación antes de abrir al público

1. Cargar la plantilla adecuada y revisar **Evento** (nombre, fechas, lugar).
2. Subir el **logo** y comprobar cómo queda en *Marca y símbolos*.
3. Revisar **Colores y letra** en la vista previa.
4. Revisar la **Encuesta**: ¿se pregunta la compra?, ¿qué preguntas propias?
5. Revisar la **Inscripción**: campos, opciones, obligatorios, y abrirla.
6. Revisar la **política de datos** con el área jurídica.
7. Guardar y abrir `/`, `/festival`, `/inscripcion` y el voto de un espacio
   (`/s/{id}`) en un teléfono.
8. Imprimir los carteles QR (*Panel → Códigos QR*): llevan el nombre, las fechas
   y la ciudad del evento.
9. Borrar los datos de prueba en *Panel → Empezar de cero*.
