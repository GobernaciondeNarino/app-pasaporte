// Configuración del evento — /admin/festival
//
// Todo lo que hace que la plataforma sirva para un evento distinto sin tocar
// código: el nombre, el logo y los símbolos, los colores y la letra, las
// preguntas del voto (y si se pregunta por la compra), qué se pide en la
// inscripción de espacios y si está abierta, los textos del pasaporte y la
// política de datos.
//
// Se edita un BORRADOR y se guarda de una vez. Cada pestaña toca su parte del
// borrador; el botón de guardar manda el borrador entero, el servidor lo
// valida (api/lib/Evento.php) y devuelve lo guardado, que pasa a ser lo que
// usa el resto de la aplicación en ese mismo momento.
//
// Todo tiene un valor de fábrica que funciona: quien no entre aquí ve el
// evento genérico con la identidad de la Gobernación de Nariño.

/**
 * Miniatura que dice si la imagen se ve DE VERDAD.
 *
 * Un <img> roto no distingue «no se subió» de «se subió y el servidor no la
 * sirve», y las dos cosas se ven igual: un icono gris. La segunda es el caso
 * real —permisos de fichero en hosting compartido— y hasta ahora obligaba a
 * abrir la consola del navegador para enterarse.
 */
const Miniatura = ({ url, alto = 130, vacio = "Diseño del sistema", contener = false }) => {
  const [estado, setEstado] = React.useState("cargando");
  React.useEffect(() => { setEstado(url ? "cargando" : "vacio"); }, [url]);

  return (
    <div>
      <div style={{
        height: alto, borderRadius: "var(--r-sm)", overflow: "hidden",
        border: "1px dashed var(--line-2)",
        background: estado === "error" ? "color-mix(in oklch, var(--bad) 8%, var(--paper))" : "var(--paper-2)",
        display: "flex", alignItems: "center", justifyContent: "center",
      }}>
        {url
          ? <img src={url} alt="" onLoad={() => setEstado("ok")} onError={() => setEstado("error")}
              style={{ width: "100%", height: "100%", objectFit: contener ? "contain" : "cover", display: estado === "ok" ? "block" : "none", padding: contener ? 10 : 0 }}/>
          : <span className="mono" style={{ color: "var(--ink-3)", textAlign: "center", padding: 10 }}>{vacio}</span>}
        {url && estado === "cargando" && <span className="mono" style={{ color: "var(--ink-3)" }}>Cargando…</span>}
        {url && estado === "error" && (
          <span className="mono" style={{ color: "var(--bad)", textAlign: "center", padding: 10, lineHeight: 1.5 }}>
            Subida, pero el servidor no la entrega
          </span>
        )}
      </div>
      {estado === "error" && (
        <p style={{ fontSize: 12, color: "var(--bad)", lineHeight: 1.5, marginTop: 6 }}>
          El archivo está guardado pero el navegador recibe un error al pedirlo. Suele ser
          permisos: usa «Corregir permisos de las imágenes» al final de la pestaña Pasaporte.
        </p>
      )}
    </div>
  );
};

const FondoRanura = ({ titulo, ayuda, url, onSubir, onQuitar, subiendo, contener = false, vacio }) => (
  <div style={{ border: "1px solid var(--line-2)", borderRadius: "var(--r-md)", padding: 14 }}>
    <div className="mono" style={{ marginBottom: 8 }}>{titulo}</div>
    <div style={{ marginBottom: 10 }}><Miniatura url={url} contener={contener} vacio={vacio}/></div>
    {ayuda && <p className="ayuda" style={{ marginBottom: 8 }}>{ayuda}</p>}
    <div style={{ display: "flex", gap: 8, flexWrap: "wrap" }}>
      <label className="btn btn-ghost" style={{ cursor: subiendo ? "wait" : "pointer", opacity: subiendo ? 0.6 : 1 }}>
        {subiendo ? "Subiendo…" : (url ? "Cambiar" : "Subir imagen")}
        <input type="file" accept="image/jpeg,image/png,image/webp" hidden disabled={subiendo}
          onChange={(e) => { const f = e.target.files && e.target.files[0]; e.target.value = ""; if (f) onSubir(f); }}/>
      </label>
      {url && onQuitar && (
        <button type="button" className="btn btn-ghost" onClick={onQuitar}
          style={{ color: "var(--bad)" }}>Quitar</button>
      )}
    </div>
  </div>
);

// ---------------------------------------------------------------------------
// Utilidades del borrador
// ---------------------------------------------------------------------------

const clonarAjustes = (o) => JSON.parse(JSON.stringify(o || {}));

/** Copia de `obj` con `valor` puesto en la ruta «a.b.c». */
const ponerEn = (obj, ruta, valor) => {
  const copia = clonarAjustes(obj);
  const partes = ruta.split(".");
  let t = copia;
  partes.slice(0, -1).forEach((k) => {
    if (t[k] === undefined || t[k] === null || typeof t[k] !== "object") t[k] = {};
    t = t[k];
  });
  t[partes[partes.length - 1]] = valor;
  return copia;
};
const leerDe = (obj, ruta) => ruta.split(".").reduce((t, k) => (t == null ? undefined : t[k]), obj);

/** Lo que de verdad se guarda: sin lo que calcula el servidor al leer. */
const sinCalculados = (aj) => {
  const c = clonarAjustes(aj);
  delete c.estado; delete c.tema_css;
  return c;
};

const nuevaId = () => "p_" + Math.random().toString(36).slice(2, 10).replace(/[^a-z0-9]/g, "x").padEnd(8, "x");

// ---------------------------------------------------------------------------
// Controles
// ---------------------------------------------------------------------------

const CampoCfg = ({ id, etiqueta, valor, onCambio, max = 120, ayuda, largo = false, filas = 3, placeholder, tipo = "text" }) => (
  <div className="field">
    <label htmlFor={id}>{etiqueta}</label>
    {largo
      ? <textarea id={id} rows={filas} maxLength={max} value={valor || ""} placeholder={placeholder}
          onChange={(e) => onCambio(e.target.value)}
          style={{ border: "1px solid var(--line-2)", borderRadius: "var(--r-md)", padding: 12, lineHeight: 1.6, resize: "vertical" }}/>
      : <input id={id} type={tipo} maxLength={max} value={valor || ""} placeholder={placeholder}
          onChange={(e) => onCambio(e.target.value)}/>}
    {ayuda && <span className="ayuda">{ayuda}</span>}
  </div>
);

/** Casilla grande, con su explicación. Para los interruptores del evento. */
const Interruptor = ({ id, etiqueta, valor, onCambio, ayuda, deshabilitado = false }) => (
  <label htmlFor={id} style={{
    display: "flex", gap: 12, alignItems: "flex-start", cursor: deshabilitado ? "not-allowed" : "pointer",
    padding: "10px 12px", borderRadius: "var(--r-md)", border: "1px solid var(--line-2)",
    background: valor ? "color-mix(in oklch, var(--grano) 6%, var(--paper))" : "var(--paper)",
    opacity: deshabilitado ? 0.6 : 1,
  }}>
    <input id={id} type="checkbox" checked={!!valor} disabled={deshabilitado}
      onChange={(e) => onCambio(e.target.checked)} style={{ marginTop: 3, width: 18, height: 18, flex: "0 0 auto" }}/>
    <span style={{ minWidth: 0 }}>
      <span style={{ fontSize: 14, fontWeight: 600, display: "block" }}>{etiqueta}</span>
      {ayuda && <span className="ayuda" style={{ display: "block", marginTop: 2 }}>{ayuda}</span>}
    </span>
  </label>
);

/** Interruptor pequeño, para filas de tablas (activo / obligatorio). */
const Casilla = ({ etiqueta, valor, onCambio, deshabilitado = false, titulo }) => (
  <label title={titulo} style={{ display: "inline-flex", alignItems: "center", gap: 6, fontSize: 13, cursor: deshabilitado ? "not-allowed" : "pointer", opacity: deshabilitado ? 0.55 : 1, whiteSpace: "nowrap" }}>
    <input type="checkbox" checked={!!valor} disabled={deshabilitado} onChange={(e) => onCambio(e.target.checked)}/>
    {etiqueta}
  </label>
);

/** Un color: selector, código y «volver al de fábrica». */
const hex6 = (v) => {
  const s = String(v || "").trim().toLowerCase();
  if (/^#[0-9a-f]{6}$/.test(s)) return s;
  if (/^#[0-9a-f]{3}$/.test(s)) return "#" + s.slice(1).split("").map((c) => c + c).join("");
  return "";
};

const SelectorColor = ({ id, etiqueta, ayuda, valor, base, onCambio }) => {
  const efectivo = hex6(valor) || hex6(base) || "#000000";
  const [texto, setTexto] = React.useState(valor || "");
  React.useEffect(() => { setTexto(valor || ""); }, [valor]);
  return (
    <div style={{ display: "grid", gridTemplateColumns: "44px minmax(0, 1fr)", gap: 10, alignItems: "center" }}>
      <input id={id} type="color" value={efectivo} onChange={(e) => onCambio(e.target.value)}
        aria-label={etiqueta}
        style={{ width: 44, height: 44, padding: 0, border: "1px solid var(--line-2)", borderRadius: "var(--r-sm)", background: "none", cursor: "pointer" }}/>
      <div style={{ minWidth: 0 }}>
        <div style={{ display: "flex", alignItems: "baseline", gap: 8, flexWrap: "wrap" }}>
          <label htmlFor={id} style={{ fontSize: 13, fontWeight: 600 }}>{etiqueta}</label>
          <input value={texto} placeholder={hex6(base) || ""} maxLength={7} aria-label={etiqueta + " (código)"}
            onChange={(e) => { setTexto(e.target.value); if (hex6(e.target.value) || e.target.value === "") onCambio(e.target.value === "" ? "" : hex6(e.target.value)); }}
            className="ruta" style={{ width: 78, border: "none", borderBottom: "1px solid var(--line-2)", background: "transparent", padding: "2px 0" }}/>
          {valor && (
            <button type="button" onClick={() => onCambio("")} className="mono"
              style={{ background: "none", border: 0, color: "var(--ink-3)", textDecoration: "underline", cursor: "pointer" }}>
              base
            </button>
          )}
        </div>
        {ayuda && <div className="ayuda">{ayuda}</div>}
      </div>
    </div>
  );
};

// ---------------------------------------------------------------------------
// Tema: las mismas variables que calcula el servidor (Evento::cssTema), para
// la vista previa. Lo que se guarda lo vuelve a calcular el servidor.
// ---------------------------------------------------------------------------

const ESQUINAS_TEMA = { recta: [2, 3, 4], normal: [4, 8, 14], redondeada: [8, 14, 22] };

const GRUPOS_COLOR = [
  ["Fondo y secciones", [["paper", "Fondo", "El papel de toda la aplicación."], ["paper-2", "Secciones", "Bloques, tarjetas y franjas."], ["paper-3", "Secciones resaltadas", "Texto claro sobre fondo oscuro, rayados."]]],
  ["Texto", [["ink", "Texto principal", "Títulos, botones y fondos oscuros."], ["ink-2", "Texto secundario", "Párrafos y explicaciones."], ["ink-3", "Metadatos", "Etiquetas en mayúsculas, fechas, notas."]]],
  ["Líneas", [["line", "Divisores", ""], ["line-2", "Bordes de campos", ""]]],
  ["Acentos", [["grano", "Acento principal", "Enlaces y botón al pasar el ratón."], ["galeras", "Palabra destacada", "La última palabra del lema y el mapa."], ["cafeto", "Acento secundario", "Avisos y estados intermedios."]]],
  ["Calificaciones", [["good", "Excelente / correcto", ""], ["meh", "Regular / estrellas", ""], ["bad", "Mejorable / error", ""]]],
];

const variablesTema = (tema, catalogo) => {
  const t = tema || {};
  const base = (((catalogo || {}).paletas || {}).institucional || {}).colores || {};
  const fuentes = (catalogo || {}).fuentes || {};
  const v = {};
  Object.keys(base).forEach((k) => { v["--" + k] = hex6((t.colores || {})[k]) || base[k]; });
  const f = t.fuentes || {};
  if (fuentes[f.display]) v["--font-display"] = fuentes[f.display].pila;
  if (fuentes[f.texto]) v["--font-sans"] = fuentes[f.texto].pila;
  if (fuentes[f.meta]) v["--font-mono"] = fuentes[f.meta].pila;
  v["--display-style"] = t.titulos_cursiva ? "italic" : "normal";
  v["--display-weight"] = String(t.titulos_peso || 400);
  const r = ESQUINAS_TEMA[t.esquinas] || ESQUINAS_TEMA.normal;
  v["--r-sm"] = r[0] + "px"; v["--r-md"] = r[1] + "px"; v["--r-lg"] = r[2] + "px";
  return v;
};

/** Una muestra de la interfaz con el tema del borrador, sin tocar el panel. */
const VistaTema = ({ tema, catalogo, nombre, lema }) => (
  <div style={{
    ...variablesTema(tema, catalogo),
    background: "var(--paper)", color: "var(--ink)", fontFamily: "var(--font-sans)",
    border: "1px solid var(--line)", borderRadius: "var(--r-md)", overflow: "hidden",
  }}>
    <div style={{ padding: "12px 16px", borderBottom: "1px solid var(--line)", display: "flex", justifyContent: "space-between", alignItems: "center", gap: 10 }}>
      <div style={{ lineHeight: 1 }}>
        <div style={{ fontFamily: "var(--font-display)", fontStyle: "var(--display-style)", fontWeight: "var(--display-weight)", fontSize: 17 }}>{nombre}</div>
        <div className="mono" style={{ fontSize: 9, marginTop: 3 }}>Metadatos · etiquetas</div>
      </div>
      <span className="mono" style={{ color: "var(--good)" }}>● En vivo</span>
    </div>
    <div style={{ padding: 16 }}>
      <div className="mono">Ranking público</div>
      <div style={{ fontFamily: "var(--font-display)", fontStyle: "var(--display-style)", fontWeight: "var(--display-weight)", fontSize: 30, lineHeight: 1, margin: "6px 0 8px" }}>
        <LemaDestacado texto={lema}/>
      </div>
      <p style={{ fontSize: 13, color: "var(--ink-2)", lineHeight: 1.55, margin: "0 0 12px" }}>
        Texto secundario de un párrafo. <a href="#vista" onClick={(e) => e.preventDefault()} style={{ color: "var(--grano)" }}>Un enlace</a>.
      </p>
      <div style={{ background: "var(--paper-2)", border: "1px solid var(--line)", borderRadius: "var(--r-md)", padding: 12, marginBottom: 12 }}>
        <div style={{ display: "flex", gap: 3, marginBottom: 8 }}>
          {[1, 2, 3, 4, 5].map((n) => <Estrella key={n} tam={18} relleno={n <= 4 ? 1 : 0} color={n <= 4 ? "var(--meh)" : "var(--line-2)"}/>)}
        </div>
        <div style={{ display: "flex", height: 5, borderRadius: 999, overflow: "hidden", background: "var(--paper)" }}>
          <div style={{ width: "62%", background: "var(--good)" }}/>
          <div style={{ width: "25%", background: "var(--meh)" }}/>
          <div style={{ width: "13%", background: "var(--bad)" }}/>
        </div>
      </div>
      <div style={{ display: "flex", gap: 8, flexWrap: "wrap" }}>
        <span className="btn btn-primary" style={{ fontSize: 13, padding: "8px 14px" }}>Botón principal</span>
        <span className="btn btn-ghost" style={{ fontSize: 13, padding: "8px 14px" }}>Secundario</span>
      </div>
    </div>
    <div style={{ background: "var(--ink)", color: "var(--paper)", padding: "12px 16px" }}>
      <div className="mono" style={{ color: "var(--paper-3)" }}>Franja oscura</div>
      <div style={{ fontSize: 13 }}>Texto claro sobre la tinta del tema.</div>
    </div>
  </div>
);

// ---------------------------------------------------------------------------
// Editor de preguntas propias (encuesta e inscripción)
// ---------------------------------------------------------------------------

const EditorPreguntas = ({ preguntas, onCambio, tipos, conPublica = false, idBase }) => {
  const lista = Array.isArray(preguntas) ? preguntas : [];
  const cambiar = (i, k, v) => onCambio(lista.map((p, j) => (j === i ? { ...p, [k]: v } : p)));
  const mover = (i, d) => {
    const j = i + d;
    if (j < 0 || j >= lista.length) return;
    const c = lista.slice();
    [c[i], c[j]] = [c[j], c[i]];
    onCambio(c);
  };
  const quitar = (i) => onCambio(lista.filter((_, j) => j !== i));
  const anadir = () => onCambio(lista.concat([{
    id: nuevaId(), tipo: tipos[0], etiqueta: "", ayuda: "", activa: true, requerida: false, opciones: [], ...(conPublica ? { publica: false } : {}),
  }]));

  return (
    <div style={{ display: "flex", flexDirection: "column", gap: 10 }}>
      {lista.map((p, i) => {
        const conOpciones = p.tipo === "opcion" || p.tipo === "multiple";
        const opciones = Array.isArray(p.opciones) ? p.opciones : [];
        return (
          <div key={p.id} style={{
            border: "1px solid var(--line-2)", borderRadius: "var(--r-md)", padding: 14,
            background: p.activa === false ? "var(--paper-2)" : "var(--paper)",
          }}>
            <div style={{ display: "flex", gap: 8, alignItems: "center", flexWrap: "wrap", marginBottom: 10 }}>
              <span className="mono" style={{ color: "var(--ink-3)" }}>{String(i + 1).padStart(2, "0")}</span>
              {p.sistema
                ? <span className="mono">Estrellas · del sistema</span>
                : (
                  <select value={p.tipo} aria-label="Tipo de pregunta"
                    onChange={(e) => cambiar(i, "tipo", e.target.value)}
                    style={{ border: "1px solid var(--line-2)", borderRadius: "var(--r-sm)", padding: "6px 8px", background: "var(--paper)" }}>
                    {tipos.map((t) => <option key={t} value={t}>{TIPOS_PREGUNTA[t] || t}</option>)}
                  </select>
                )}
              <span style={{ flex: 1 }}/>
              <button type="button" className="mono" onClick={() => mover(i, -1)} disabled={i === 0}
                aria-label="Subir" style={{ background: "none", border: "1px solid var(--line-2)", borderRadius: "var(--r-sm)", padding: "4px 8px", cursor: "pointer" }}>↑</button>
              <button type="button" className="mono" onClick={() => mover(i, 1)} disabled={i === lista.length - 1}
                aria-label="Bajar" style={{ background: "none", border: "1px solid var(--line-2)", borderRadius: "var(--r-sm)", padding: "4px 8px", cursor: "pointer" }}>↓</button>
              {!p.sistema && (
                <button type="button" className="mono" onClick={() => quitar(i)}
                  style={{ background: "none", border: "1px solid var(--bad)", color: "var(--bad)", borderRadius: "var(--r-sm)", padding: "4px 8px", cursor: "pointer" }}>Eliminar</button>
              )}
            </div>
            <div className="field">
              <label htmlFor={idBase + "-et-" + p.id}>Pregunta</label>
              <input id={idBase + "-et-" + p.id} value={p.etiqueta || ""} maxLength={p.sistema ? 40 : 200}
                placeholder="Escribe la pregunta" onChange={(e) => cambiar(i, "etiqueta", e.target.value)}/>
            </div>
            {!p.sistema && (
              <div className="field" style={{ marginTop: 10 }}>
                <label htmlFor={idBase + "-ay-" + p.id}>Ayuda (opcional)</label>
                <input id={idBase + "-ay-" + p.id} value={p.ayuda || ""} maxLength={300}
                  onChange={(e) => cambiar(i, "ayuda", e.target.value)}/>
              </div>
            )}
            {conOpciones && (
              <div className="field" style={{ marginTop: 10 }}>
                <label htmlFor={idBase + "-op-" + p.id}>Opciones · una por línea</label>
                <textarea id={idBase + "-op-" + p.id} rows={Math.max(3, opciones.length + 1)}
                  value={opciones.join("\n")}
                  onChange={(e) => cambiar(i, "opciones", e.target.value.split("\n").map((x) => x.slice(0, 120)))}
                  style={{ border: "1px solid var(--line-2)", borderRadius: "var(--r-md)", padding: 10, lineHeight: 1.6 }}/>
                <span className="ayuda">Al menos dos. Cambiar el texto de una opción no cambia lo ya respondido con el texto anterior.</span>
              </div>
            )}
            <div style={{ display: "flex", gap: 16, flexWrap: "wrap", marginTop: 12 }}>
              <Casilla etiqueta="Activa" valor={p.activa !== false} onCambio={(v) => cambiar(i, "activa", v)}
                titulo="Desactivada no se pregunta; lo respondido antes se conserva."/>
              {!p.sistema && (
                <Casilla etiqueta="Obligatoria" valor={!!p.requerida} onCambio={(v) => cambiar(i, "requerida", v)}/>
              )}
              {conPublica && !p.sistema && (
                <Casilla etiqueta="Se ve en la ficha pública" valor={!!p.publica} onCambio={(v) => cambiar(i, "publica", v)}/>
              )}
            </div>
          </div>
        );
      })}
      <button type="button" className="btn btn-ghost" onClick={anadir} style={{ alignSelf: "flex-start" }}>
        + Añadir pregunta
      </button>
    </div>
  );
};

/** Lo que el servidor descartaría en silencio: mejor decirlo antes de guardar. */
const problemasPreguntas = (preguntas, donde) => (preguntas || []).flatMap((p, i) => {
  if (p.sistema) return [];
  const n = `${donde}: pregunta ${i + 1}`;
  const out = [];
  if (!(p.etiqueta || "").trim()) out.push(`${n} no tiene texto.`);
  if ((p.tipo === "opcion" || p.tipo === "multiple") && (p.opciones || []).filter((o) => (o || "").trim()).length < 2) {
    out.push(`${n} («${(p.etiqueta || "").trim() || "sin texto"}») necesita al menos dos opciones.`);
  }
  return out;
});

// ---------------------------------------------------------------------------
// Editor de las opciones de un catálogo del sistema (organización, actividad…)
// ---------------------------------------------------------------------------

const EditorOpciones = ({ opciones, onCambio, claveOtro = "otro" }) => {
  const lista = Array.isArray(opciones) ? opciones : [];
  const normales = lista.filter((o) => o[0] !== claveOtro);
  const otro = lista.find((o) => o[0] === claveOtro);
  const poner = (normalesNuevas, otroNuevo) => onCambio(normalesNuevas.concat(otroNuevo ? [otroNuevo] : []));
  return (
    <div style={{ display: "flex", flexDirection: "column", gap: 6 }}>
      {normales.map((o, i) => (
        <div key={(o[0] || "nueva") + i} style={{ display: "flex", gap: 8, alignItems: "center" }}>
          <input value={o[1]} maxLength={300} aria-label={"Opción " + (i + 1)}
            onChange={(e) => poner(normales.map((x, j) => (j === i ? [x[0], e.target.value] : x)), otro)}
            style={{ flex: 1, minWidth: 0, border: "none", borderBottom: "1px solid var(--line-2)", padding: "6px 0", background: "transparent", fontSize: 14 }}/>
          <button type="button" className="mono" onClick={() => poner(normales.filter((_, j) => j !== i), otro)}
            style={{ background: "none", border: 0, color: "var(--bad)", cursor: "pointer" }}>quitar</button>
        </div>
      ))}
      <div style={{ display: "flex", gap: 12, alignItems: "center", flexWrap: "wrap", marginTop: 4 }}>
        <button type="button" className="mono" onClick={() => poner(normales.concat([["", ""]]), otro)}
          style={{ background: "none", border: "1px dashed var(--line-2)", borderRadius: "var(--r-sm)", padding: "6px 10px", cursor: "pointer" }}>
          + Opción
        </button>
        <Casilla etiqueta={"Incluir «" + (otro ? otro[1] : "Otro") + "» con campo para escribir"} valor={!!otro}
          onCambio={(v) => poner(normales, v ? [claveOtro, claveOtro === "otros" ? "Otros" : "Otro"] : null)}/>
      </div>
      <span className="ayuda">
        Cambiar el texto de una opción cambia cómo se lee en todos los informes: se guarda su clave, no la frase.
      </span>
    </div>
  );
};

// Los campos de la inscripción, agrupados como salen en el formulario.
const GRUPOS_INSCRIPCION = [
  ["persona", "Quién eres"],
  ["espacio", "Tu espacio"],
  ["ubicacion", "Ubicación"],
  ["participa", "Quién participa"],
  ["linea", "Línea"],
  ["detalle", "Información detallada"],
  ["final", "Al final"],
];

const FilaCampo = ({ clave, campo, onCambio }) => {
  const [abierto, setAbierto] = React.useState(false);
  const fijo = !!campo.fijo;
  const conOpciones = Array.isArray(campo.opciones);
  return (
    <div style={{ borderBottom: "1px solid var(--line)", padding: "10px 0", opacity: campo.activo === false ? 0.65 : 1 }}>
      <div style={{ display: "grid", gridTemplateColumns: "minmax(0, 1fr) auto", gap: 10, alignItems: "center" }}>
        <input value={campo.etiqueta || ""} maxLength={200} aria-label={"Texto del campo " + clave}
          onChange={(e) => onCambio({ ...campo, etiqueta: e.target.value })}
          style={{ minWidth: 0, border: "none", borderBottom: "1px solid var(--line-2)", padding: "6px 0", background: "transparent", fontSize: 14, fontWeight: 500 }}/>
        <div style={{ display: "flex", gap: 12, alignItems: "center", flexWrap: "wrap", justifyContent: "flex-end" }}>
          <Casilla etiqueta="Se pide" valor={campo.activo !== false} deshabilitado={fijo}
            titulo={fijo ? "El sistema lo necesita: no se puede quitar." : ""}
            onCambio={(v) => onCambio({ ...campo, activo: v })}/>
          <Casilla etiqueta="Obligatorio" valor={!!campo.requerido} deshabilitado={fijo || campo.tipo === "mapa" || campo.activo === false}
            onCambio={(v) => onCambio({ ...campo, requerido: v })}/>
          <button type="button" className="mono" onClick={() => setAbierto((x) => !x)} aria-expanded={abierto}
            style={{ background: "none", border: 0, color: "var(--grano)", cursor: "pointer", textDecoration: "underline" }}>
            {abierto ? "cerrar" : (conOpciones ? "ayuda y opciones" : "ayuda")}
          </button>
        </div>
      </div>
      {fijo && <div className="mono" style={{ color: "var(--ink-3)", marginTop: 4 }}>Lo necesita el sistema</div>}
      {abierto && (
        <div style={{ marginTop: 10, paddingLeft: 12, borderLeft: "2px solid var(--line-2)", display: "flex", flexDirection: "column", gap: 12 }}>
          <CampoCfg id={"fc-ay-" + clave} etiqueta="Texto de ayuda bajo el campo" valor={campo.ayuda} max={400}
            onCambio={(v) => onCambio({ ...campo, ayuda: v })}/>
          {conOpciones && (
            <div>
              <div className="mono" style={{ marginBottom: 6 }}>Opciones de la lista</div>
              <EditorOpciones opciones={campo.opciones} claveOtro={clave === "presentacion" ? "otros" : "otro"}
                onCambio={(ops) => onCambio({ ...campo, opciones: ops })}/>
            </div>
          )}
        </div>
      )}
    </div>
  );
};

// ---------------------------------------------------------------------------
// La página
// ---------------------------------------------------------------------------

const PESTANAS_EVENTO = [
  ["evento", "Evento"],
  ["marca", "Marca y símbolos"],
  ["apariencia", "Colores y letra"],
  ["encuesta", "Encuesta del voto"],
  ["inscripcion", "Inscripción"],
  ["pasaporte", "Pasaporte"],
  ["datos", "Datos personales"],
];

const FestivalPage = () => {
  const [aj, setAj] = React.useState(null);          // lo guardado
  const [borrador, setBorrador] = React.useState(null);
  const [catalogo, setCatalogo] = React.useState(null);
  const [pestana, setPestana] = React.useState(() => {
    try { return sessionStorage.getItem("lmt.cfg.pestana") || "evento"; } catch (_) { return "evento"; }
  });
  const [error, setError] = React.useState("");
  const [ok, setOk] = React.useState("");
  const [subiendo, setSubiendo] = React.useState("");
  const [guardando, setGuardando] = React.useState(false);
  const [verAviso, setVerAviso] = React.useState(false);

  React.useEffect(() => {
    let cancelado = false;
    const cargar = async () => {
      if (cancelado || !window.LMTApi || !window.LMTApi.enabled) return;
      try {
        const [d, c] = await Promise.all([window.LMTApi.festivalAjustes(), window.LMTApi.festivalCatalogo()]);
        if (cancelado) return;
        setAj(d); setBorrador(sinCalculados(d)); setCatalogo(c);
      } catch (e) { if (!cancelado) setError(mensajeError(e)); }
    };
    cargar();
    window.addEventListener("lmt:auth", cargar);
    return () => { cancelado = true; window.removeEventListener("lmt:auth", cargar); };
  }, []);

  React.useEffect(() => {
    try { sessionStorage.setItem("lmt.cfg.pestana", pestana); } catch (_) {}
  }, [pestana]);

  const sucio = React.useMemo(
    () => !!(aj && borrador && JSON.stringify(sinCalculados(aj)) !== JSON.stringify(borrador)),
    [aj, borrador]
  );

  // Con cambios sin guardar, cerrar la pestaña del navegador lo advierte.
  React.useEffect(() => {
    if (!sucio) return undefined;
    const avisar = (e) => { e.preventDefault(); e.returnValue = ""; };
    window.addEventListener("beforeunload", avisar);
    return () => window.removeEventListener("beforeunload", avisar);
  }, [sucio]);

  if (!aj || !borrador || !catalogo) {
    return <AdminShell active="festival"><div className="admin-page">
      {error ? <Aviso>{error}</Aviso> : <p className="mono" style={{ color: "var(--ink-3)" }}>Cargando…</p>}
    </div></AdminShell>;
  }

  const b = borrador;
  const v = (ruta) => leerDe(b, ruta);
  const p = (ruta) => (valor) => { setOk(""); setBorrador((x) => ponerEn(x, ruta, valor)); };

  const problemas = [
    ...problemasPreguntas(((b.encuesta || {}).preguntas), "Encuesta"),
    ...problemasPreguntas(((b.inscripcion || {}).preguntas), "Inscripción"),
  ];

  /** Lo guardado pasa a ser lo que usa el resto de la aplicación ya mismo. */
  const aplicar = (d) => {
    setAj(d);
    if (window.LMTFestival && window.LMTFestival.poner) window.LMTFestival.poner(d);
  };

  const guardar = async () => {
    setError(""); setOk("");
    if (problemas.length) { setError(problemas[0]); return; }
    setGuardando(true);
    try {
      const d = await window.LMTApi.guardarFestivalAjustes(borrador);
      aplicar(d);
      setBorrador(sinCalculados(d));
      setOk("Guardado. Los cambios ya se ven en toda la plataforma.");
    } catch (e) { setError(mensajeError(e)); }
    finally { setGuardando(false); }
  };

  const descartar = () => { setBorrador(sinCalculados(aj)); setError(""); setOk(""); };

  /** Imágenes: se guardan al subirlas. Al borrador sólo pasa la imagen. */
  const mezclarImagenes = (d) => setBorrador((x) => ({
    ...x,
    marca: { ...(x.marca || {}), logo: (d.marca || {}).logo || "" },
    pasaporte: { ...(x.pasaporte || {}),
      portada: (d.pasaporte || {}).portada || "", contraportada: (d.pasaporte || {}).contraportada || "",
      hojas: (d.pasaporte || {}).hojas || [] },
  }));

  const subirImagen = async (destino, archivo) => {
    setError(""); setOk(""); setSubiendo(destino);
    try {
      const d = await window.LMTApi.subirFondoPasaporte(destino, archivo);
      aplicar(d); mezclarImagenes(d);
      setOk("Imagen subida.");
    } catch (e) { setError(mensajeError(e)); }
    finally { setSubiendo(""); }
  };
  const quitarImagen = async (destino, indice) => {
    setError(""); setOk("");
    try {
      const d = await window.LMTApi.quitarFondoPasaporte(destino, indice);
      aplicar(d); mezclarImagenes(d);
    } catch (e) { setError(mensajeError(e)); }
  };

  /**
   * Cargar una plantilla rellena el borrador y nada más: hay que revisarlo y
   * guardar. Las imágenes subidas no se tocan, porque se perderían.
   */
  const cargarPlantilla = (clave) => {
    const pl = (catalogo.plantillas || {})[clave];
    if (!pl) return;
    if (!window.confirm("Se reemplazarán los textos, colores, preguntas y campos del borrador por los de «" + pl.titulo + "». Las imágenes subidas se conservan. No se guarda nada hasta que pulses «Guardar cambios». ¿Continuar?")) return;
    const nuevo = clonarAjustes(pl.ajustes);
    nuevo.marca = { ...(nuevo.marca || {}), logo: (b.marca || {}).logo || "" };
    nuevo.pasaporte = { ...(nuevo.pasaporte || {}),
      portada: (b.pasaporte || {}).portada || "", contraportada: (b.pasaporte || {}).contraportada || "",
      hojas: (b.pasaporte || {}).hojas || [] };
    setBorrador(nuevo);
    setOk("Plantilla cargada en el borrador. Revísala y pulsa «Guardar cambios».");
  };

  const aplicarPaleta = (clave) => {
    const pal = (catalogo.paletas || {})[clave];
    if (!pal) return;
    setOk("");
    setBorrador((x) => ({
      ...x,
      tema: {
        ...(x.tema || {}),
        preset: clave,
        colores: { ...pal.colores },
        fuentes: { ...pal.fuentes },
        titulos_cursiva: !!pal.titulos_cursiva,
        titulos_peso: pal.titulos_peso,
      },
    }));
  };

  const fuentes = catalogo.fuentes || {};
  const basePaleta = ((catalogo.paletas || {}).institucional || {}).colores || {};
  const campos = (b.inscripcion || {}).campos || {};
  const hojas = (b.pasaporte && b.pasaporte.hojas) || [];

  return (
    <AdminShell active="festival">
      <div className="admin-page" style={{ maxWidth: 1080, paddingBottom: 120 }}>
        <div className="mono">Configuración del evento</div>
        <h1 className="titulo-xl">{nombreEvento(b)}</h1>
        <p style={{ fontSize: 14, color: "var(--ink-2)", lineHeight: 1.6, maxWidth: 680, margin: "10px 0 18px" }}>
          Todo lo que identifica a este evento: su nombre y sus datos, el logo, los colores y la
          letra, lo que se pregunta al votar y al inscribir un espacio, y los textos del pasaporte.
          Los cambios se preparan en un borrador y se aplican al pulsar «Guardar cambios».
        </p>

        {/* Plantillas: un punto de partida, no un modo. */}
        <details style={{ border: "1px solid var(--line)", borderRadius: "var(--r-md)", padding: "12px 16px", marginBottom: 20, background: "var(--paper-2)" }}>
          <summary style={{ cursor: "pointer", fontSize: 14, fontWeight: 600 }}>Empezar desde una plantilla</summary>
          <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fit, minmax(min(100%, 260px), 1fr))", gap: 12, marginTop: 12 }}>
            {Object.entries(catalogo.plantillas || {}).map(([clave, pl]) => (
              <div key={clave} style={{ border: "1px solid var(--line-2)", borderRadius: "var(--r-md)", padding: 14, background: "var(--paper)" }}>
                <div style={{ fontWeight: 600, fontSize: 15 }}>{pl.titulo}</div>
                <p className="ayuda" style={{ margin: "6px 0 10px" }}>{pl.descripcion}</p>
                <button type="button" className="btn btn-ghost" onClick={() => cargarPlantilla(clave)}>Cargar en el borrador</button>
              </div>
            ))}
          </div>
        </details>

        <div role="tablist" aria-label="Secciones de la configuración" style={{
          display: "flex", gap: 6, overflowX: "auto", paddingBottom: 4, marginBottom: 22,
          borderBottom: "1px solid var(--line)", scrollbarWidth: "thin",
        }}>
          {PESTANAS_EVENTO.map(([id, texto]) => (
            <button key={id} type="button" role="tab" aria-selected={pestana === id}
              onClick={() => setPestana(id)}
              style={{
                padding: "10px 14px", minHeight: 44, whiteSpace: "nowrap", fontSize: 14,
                borderBottom: "2px solid " + (pestana === id ? "var(--ink)" : "transparent"),
                fontWeight: pestana === id ? 700 : 400, color: pestana === id ? "var(--ink)" : "var(--ink-2)",
              }}>{texto}</button>
          ))}
        </div>

        <Aviso>{error}</Aviso>
        <Aviso tipo="ok">{ok}</Aviso>
        <div style={{ height: 14 }}/>

        {/* ── Evento ─────────────────────────────────────────────────── */}
        {pestana === "evento" && (
          <div style={{ display: "flex", flexDirection: "column", gap: 20 }}>
            <BloqueForm titulo="Nombre y portada" nota="El nombre sale junto al logo en toda la aplicación, en el título de la pestaña del navegador y en los correos.">
              <CampoCfg id="ev-nombre" etiqueta="Nombre del evento" valor={v("evento.nombre")} max={60} onCambio={p("evento.nombre")}/>
              <CampoCfg id="ev-lema" etiqueta="Lema del tablero público" valor={v("evento.lema")} max={140} onCambio={p("evento.lema")}
                ayuda="El titular grande del ranking. Su última palabra sale en el color destacado."/>
              <CampoCfg id="ev-desc" etiqueta="Descripción" valor={v("evento.descripcion")} max={600} largo onCambio={p("evento.descripcion")}
                ayuda="El párrafo bajo el lema: qué es y cómo se participa."/>
              <div className="grid-2">
                <CampoCfg id="ev-ptit" etiqueta="Título de la portada (/)" valor={v("evento.portada_titulo")} max={140} onCambio={p("evento.portada_titulo")}/>
                <CampoCfg id="ev-ptxt" etiqueta="Texto de la portada" valor={v("evento.portada_texto")} max={600} largo onCambio={p("evento.portada_texto")}/>
              </div>
            </BloqueForm>

            <BloqueForm titulo="Datos básicos" nota="Se muestran en la portada y en el tablero principal. Lo que dejes vacío no aparece.">
              <div className="grid-2">
                <CampoCfg id="ev-fi" tipo="date" etiqueta="Fecha de inicio" valor={v("evento.fecha_inicio")} max={10} onCambio={p("evento.fecha_inicio")}/>
                <CampoCfg id="ev-ff" tipo="date" etiqueta="Fecha de cierre" valor={v("evento.fecha_fin")} max={10} onCambio={p("evento.fecha_fin")}/>
              </div>
              <div className="grid-2">
                <CampoCfg id="ev-hor" etiqueta="Horario" valor={v("evento.horario")} max={120} placeholder="9:00 a. m. a 6:00 p. m." onCambio={p("evento.horario")}/>
                <CampoCfg id="ev-lug" etiqueta="Lugar" valor={v("evento.lugar")} max={160} placeholder="Plaza de Nariño" onCambio={p("evento.lugar")}/>
              </div>
              <div className="grid-2">
                <CampoCfg id="ev-ciu" etiqueta="Ciudad" valor={v("evento.ciudad")} max={80} onCambio={p("evento.ciudad")}/>
                <CampoCfg id="ev-org" etiqueta="Organiza" valor={v("evento.organizador")} max={160} onCambio={p("evento.organizador")}/>
              </div>
              <div className="grid-2">
                <CampoCfg id="ev-web" tipo="url" etiqueta="Sitio web" valor={v("evento.sitio_web")} max={255} placeholder="https://…" onCambio={p("evento.sitio_web")}/>
                <CampoCfg id="ev-con" etiqueta="Contacto" valor={v("evento.contacto")} max={160} placeholder="Correo o teléfono" onCambio={p("evento.contacto")}/>
              </div>
              <CampoCfg id="ev-pie" etiqueta="Pie de página del tablero" valor={v("evento.pie_pagina")} max={160} onCambio={p("evento.pie_pagina")}/>
              <Interruptor id="ev-mostrar" etiqueta="Mostrar los datos del evento en la portada y el tablero"
                valor={v("evento.mostrar_datos") !== false} onCambio={p("evento.mostrar_datos")}/>
              <div style={{ border: "1px dashed var(--line-2)", borderRadius: "var(--r-md)", padding: 16 }}>
                <div className="mono" style={{ marginBottom: 10 }}>Así se verán</div>
                {filasDatosEvento(b).length
                  ? <div style={{ display: "grid", gap: 12, gridTemplateColumns: "repeat(auto-fit, minmax(min(100%, 170px), 1fr))" }}>
                      {filasDatosEvento(b).map(([k, val]) => (
                        <div key={k}><div className="mono">{k}</div><div style={{ fontSize: 14 }}>{val}</div></div>
                      ))}
                    </div>
                  : <p className="ayuda">Todavía no hay datos que mostrar.</p>}
              </div>
            </BloqueForm>
          </div>
        )}

        {/* ── Marca y símbolos ───────────────────────────────────────── */}
        {pestana === "marca" && (
          <div style={{ display: "flex", flexDirection: "column", gap: 20 }}>
            <BloqueForm titulo="Logo" nota="Va a la izquierda del nombre en toda la aplicación, en la portada del pasaporte y como icono de la pestaña. PNG con fondo transparente es lo mejor. Se guarda al subirlo.">
              <div className="grid-2">
                <FondoRanura titulo="Logo del evento" url={urlImagen((b.marca || {}).logo)} contener
                  vacio="Sin logo: se usa el símbolo" subiendo={subiendo === "logo"}
                  onSubir={(f) => subirImagen("logo", f)} onQuitar={() => quitarImagen("logo")}/>
                <div style={{ display: "flex", flexDirection: "column", gap: 14 }}>
                  <div className="field">
                    <label htmlFor="mc-simbolo">Símbolo cuando no hay logo</label>
                    <div style={{ display: "flex", gap: 8, flexWrap: "wrap" }}>
                      {SIMBOLOS_MARCA.map(([k, t]) => (
                        <button key={k} type="button" onClick={() => p("marca.simbolo")(k)} title={t} aria-pressed={((b.marca || {}).simbolo || "pasaporte") === k}
                          style={{
                            padding: 8, borderRadius: "var(--r-md)",
                            border: ((b.marca || {}).simbolo || "pasaporte") === k ? "2px solid var(--ink)" : "1px solid var(--line-2)",
                            background: "var(--paper)",
                          }}>
                          <SimboloSvg simbolo={k} size={36}/>
                        </button>
                      ))}
                    </div>
                  </div>
                  <Interruptor id="mc-nombre" etiqueta="Escribir el nombre junto al logo"
                    ayuda="Desactívalo si el logo ya lleva el nombre escrito."
                    valor={(b.marca || {}).mostrar_nombre !== false} onCambio={p("marca.mostrar_nombre")}/>
                  <CampoCfg id="mc-pie" etiqueta="Texto bajo el nombre" valor={v("marca.pie")} max={60} onCambio={p("marca.pie")}
                    ayuda="El renglón pequeño bajo el nombre. También encabeza los correos."/>
                </div>
              </div>
              <div style={{ border: "1px dashed var(--line-2)", borderRadius: "var(--r-sm)", padding: 16, background: "var(--paper-2)" }}>
                <div className="mono" style={{ color: "var(--ink-3)", marginBottom: 10 }}>Así se verá</div>
                <div style={{ display: "flex", alignItems: "center", gap: 10 }}>
                  {(b.marca || {}).logo
                    ? <img src={urlImagen(b.marca.logo)} alt="" style={{ height: 28, width: "auto", maxWidth: 90, objectFit: "contain" }}/>
                    : <SimboloSvg simbolo={(b.marca || {}).simbolo || "pasaporte"} size={28}/>}
                  {(b.marca || {}).mostrar_nombre !== false && (
                    <div style={{ lineHeight: 1 }}>
                      <div style={{ fontFamily: "var(--font-display)", fontSize: 20, fontStyle: "var(--display-style)", fontWeight: "var(--display-weight)" }}>{nombreEvento(b)}</div>
                      <div className="mono" style={{ fontSize: 9, marginTop: 2 }}>{pieDeMarca(b)}</div>
                    </div>
                  )}
                </div>
              </div>
            </BloqueForm>

            <BloqueForm titulo="Símbolos del voto" nota="Los tres botones con los que se califica un espacio. El orden y su significado no cambian (peor, intermedio, mejor); cambian el dibujo y la palabra.">
              <div className="grid-3">
                {[["malo", "Peor"], ["regular", "Intermedio"], ["bueno", "Mejor"]].map(([k, orden]) => (
                  <div key={k} style={{ border: "1px solid var(--line-2)", borderRadius: "var(--r-md)", padding: 12, textAlign: "center" }}>
                    <div className="mono" style={{ marginBottom: 6 }}>{orden}</div>
                    <input value={v("simbolos.votos." + k + ".emoji") || ""} maxLength={8} aria-label={"Emoji " + orden}
                      onChange={(e) => p("simbolos.votos." + k + ".emoji")(e.target.value)}
                      style={{ width: 70, fontSize: 34, textAlign: "center", border: "none", borderBottom: "1px solid var(--line-2)", background: "transparent" }}/>
                    <input value={v("simbolos.votos." + k + ".texto") || ""} maxLength={24} aria-label={"Palabra " + orden}
                      onChange={(e) => p("simbolos.votos." + k + ".texto")(e.target.value)}
                      style={{ width: "100%", textAlign: "center", marginTop: 8, border: "none", borderBottom: "1px solid var(--line-2)", background: "transparent", fontSize: 14 }}/>
                  </div>
                ))}
              </div>
              <span className="ayuda">Pega un emoji en cada casilla (en el teléfono, desde el teclado de emoji).</span>
            </BloqueForm>

            <BloqueForm titulo="Sello y podio">
              <div className="grid-2">
                <div style={{ display: "flex", flexDirection: "column", gap: 14 }}>
                  <CampoCfg id="mc-sello" etiqueta="Palabra dentro del sello" valor={v("simbolos.sello")} max={18} onCambio={p("simbolos.sello")}
                    ayuda="Se escribe en mayúsculas. De fábrica: VISITADO."/>
                  <CampoCfg id="mc-primero" etiqueta="Rótulo del primer lugar" valor={v("simbolos.primer_lugar")} max={40} onCambio={p("simbolos.primer_lugar")}
                    ayuda="Junto al «#1» del podio en el tablero."/>
                </div>
                <div style={{ display: "flex", justifyContent: "center", alignItems: "center", background: "var(--paper-2)", borderRadius: "var(--r-md)", padding: 16 }}>
                  <div style={{ position: "relative" }}>
                    <SelloVista palabra={(v("simbolos.sello") || "VISITADO").toUpperCase()}/>
                  </div>
                </div>
              </div>
            </BloqueForm>
          </div>
        )}

        {/* ── Colores y letra ────────────────────────────────────────── */}
        {pestana === "apariencia" && (
          <div className="editor-2col">
            <div style={{ display: "flex", flexDirection: "column", gap: 20 }}>
              <BloqueForm titulo="Paletas" nota="Un punto de partida: aplica los colores y la letra de una vez. Después puedes cambiar cada uno.">
                <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fit, minmax(min(100%, 200px), 1fr))", gap: 10 }}>
                  {Object.entries(catalogo.paletas || {}).map(([clave, pal]) => (
                    <button key={clave} type="button" onClick={() => aplicarPaleta(clave)}
                      style={{
                        textAlign: "left", padding: 12, borderRadius: "var(--r-md)",
                        border: ((b.tema || {}).preset === clave) ? "2px solid var(--ink)" : "1px solid var(--line-2)",
                        background: "var(--paper)",
                      }}>
                      <div style={{ display: "flex", gap: 0, height: 22, borderRadius: 4, overflow: "hidden", marginBottom: 8 }}>
                        {["paper", "paper-2", "ink", "grano", "galeras", "meh"].map((k) => (
                          <span key={k} style={{ flex: 1, background: pal.colores[k] }}/>
                        ))}
                      </div>
                      <div style={{ fontSize: 13, fontWeight: 600 }}>{pal.nombre}</div>
                      <div className="ayuda">{(fuentes[pal.fuentes.display] || {}).nombre}</div>
                    </button>
                  ))}
                </div>
              </BloqueForm>

              <BloqueForm titulo="Tipografía">
                {[["display", "Títulos"], ["texto", "Texto"], ["meta", "Metadatos y etiquetas"]].map(([rol, et]) => (
                  <div className="field" key={rol}>
                    <label htmlFor={"tp-" + rol}>{et}</label>
                    <select id={"tp-" + rol} value={((b.tema || {}).fuentes || {})[rol] || ""}
                      onChange={(e) => { p("tema.fuentes." + rol)(e.target.value); p("tema.preset")("personalizado"); }}>
                      {Object.entries(fuentes).map(([k, f]) => <option key={k} value={k}>{f.nombre}</option>)}
                    </select>
                    <span style={{ fontFamily: (fuentes[((b.tema || {}).fuentes || {})[rol]] || {}).pila, fontSize: rol === "display" ? 22 : 15, marginTop: 4 }}>
                      Pasaporte de Nariño · ÁÉÍÓÚ ñ 0123
                    </span>
                  </div>
                ))}
                <div className="grid-2">
                  <div className="field">
                    <label htmlFor="tp-peso">Peso de los títulos</label>
                    <select id="tp-peso" value={(b.tema || {}).titulos_peso || 400}
                      onChange={(e) => { p("tema.titulos_peso")(Number(e.target.value)); p("tema.preset")("personalizado"); }}>
                      {[[300, "Fino"], [400, "Normal"], [500, "Medio"], [600, "Seminegrita"], [700, "Negrita"]].map(([n, t]) => (
                        <option key={n} value={n}>{t}</option>
                      ))}
                    </select>
                  </div>
                  <div className="field">
                    <label htmlFor="tp-esq">Esquinas</label>
                    <select id="tp-esq" value={(b.tema || {}).esquinas || "normal"} onChange={(e) => p("tema.esquinas")(e.target.value)}>
                      <option value="recta">Casi rectas</option>
                      <option value="normal">Normales</option>
                      <option value="redondeada">Redondeadas</option>
                    </select>
                  </div>
                </div>
                <Interruptor id="tp-cursiva" etiqueta="Títulos en cursiva"
                  ayuda={(fuentes[((b.tema || {}).fuentes || {}).display] || {}).cursiva === false
                    ? "La letra elegida para los títulos no tiene cursiva: el navegador la inclinaría a la fuerza."
                    : "Luce con letras editoriales como Instrument Serif."}
                  valor={!!(b.tema || {}).titulos_cursiva}
                  onCambio={(x) => { p("tema.titulos_cursiva")(x); p("tema.preset")("personalizado"); }}/>
              </BloqueForm>

              {GRUPOS_COLOR.map(([grupo, lista]) => (
                <BloqueForm key={grupo} titulo={grupo}>
                  {lista.map(([k, et, ay]) => (
                    <SelectorColor key={k} id={"col-" + k} etiqueta={et} ayuda={ay}
                      valor={((b.tema || {}).colores || {})[k] || ""} base={basePaleta[k]}
                      onCambio={(x) => { p("tema.colores." + k)(x); p("tema.preset")("personalizado"); }}/>
                  ))}
                </BloqueForm>
              ))}
              <p className="ayuda">
                «base» devuelve el color al del tema institucional. Comprueba que el texto se lea bien
                sobre el fondo: la vista previa usa exactamente estos valores.
              </p>
            </div>
            <aside style={{ position: "sticky", top: 24, alignSelf: "start" }}>
              <div className="mono" style={{ marginBottom: 10 }}>Vista previa</div>
              <VistaTema tema={b.tema} catalogo={catalogo} nombre={nombreEvento(b)}
                lema={textoCfg("evento", "lema", "¿Cuál es el mejor espacio?", b)}/>
            </aside>
          </div>
        )}

        {/* ── Encuesta ───────────────────────────────────────────────── */}
        {pestana === "encuesta" && (
          <div style={{ display: "flex", flexDirection: "column", gap: 20 }}>
            <BloqueForm titulo="Textos del formulario de voto" nota="Lo que ve quien escanea el QR de un espacio.">
              <CampoCfg id="en-tit" etiqueta="Título" valor={v("encuesta.titulo")} max={120} onCambio={p("encuesta.titulo")}/>
              <CampoCfg id="en-ins" etiqueta="Instrucción" valor={v("encuesta.instrucciones")} max={200} onCambio={p("encuesta.instrucciones")}/>
              <CampoCfg id="en-emo" etiqueta="Pregunta sobre los emoji" valor={v("encuesta.pregunta_emoji")} max={120} onCambio={p("encuesta.pregunta_emoji")}
                ayuda="Tocar un emoji envía el voto, así que todo lo demás va antes."/>
              <CampoCfg id="en-aviso" etiqueta="Aviso de datos al pie" valor={v("encuesta.aviso_datos")} max={200} onCambio={p("encuesta.aviso_datos")}
                ayuda="Vacío = no se muestra."/>
            </BloqueForm>

            <BloqueForm titulo="¿Compraste algo?" nota="Alimenta el informe de Actividad económica. Desactívala si en el evento no se vende nada.">
              <Interruptor id="en-compra" etiqueta="Preguntar si compró"
                valor={v("encuesta.compra.activa") !== false} onCambio={p("encuesta.compra.activa")}/>
              {v("encuesta.compra.activa") !== false && (
                <>
                  <CampoCfg id="en-cp" etiqueta="Pregunta" valor={v("encuesta.compra.pregunta")} max={120} onCambio={p("encuesta.compra.pregunta")}/>
                  <div className="grid-2">
                    <CampoCfg id="en-csi" etiqueta="Botón «sí»" valor={v("encuesta.compra.si")} max={40} onCambio={p("encuesta.compra.si")}/>
                    <CampoCfg id="en-cno" etiqueta="Botón «no»" valor={v("encuesta.compra.no")} max={40} onCambio={p("encuesta.compra.no")}/>
                  </div>
                  <Interruptor id="en-cval" etiqueta="Preguntar cuánto gastó"
                    valor={v("encuesta.compra.pide_valor") !== false} onCambio={p("encuesta.compra.pide_valor")}/>
                  {v("encuesta.compra.pide_valor") !== false && (
                    <CampoCfg id="en-cvp" etiqueta="Pregunta del importe" valor={v("encuesta.compra.pregunta_valor")} max={120} onCambio={p("encuesta.compra.pregunta_valor")}/>
                  )}
                </>
              )}
            </BloqueForm>

            <BloqueForm titulo="Comentario">
              <Interruptor id="en-com" etiqueta="Permitir un comentario"
                ayuda="Sale en el tablero público y en la ficha del espacio."
                valor={v("encuesta.comentario.activo") !== false} onCambio={p("encuesta.comentario.activo")}/>
              {v("encuesta.comentario.activo") !== false && (
                <div className="grid-2">
                  <CampoCfg id="en-cet" etiqueta="Texto del enlace" valor={v("encuesta.comentario.etiqueta")} max={120} onCambio={p("encuesta.comentario.etiqueta")}/>
                  <CampoCfg id="en-cph" etiqueta="Ejemplo dentro del campo" valor={v("encuesta.comentario.placeholder")} max={160} onCambio={p("encuesta.comentario.placeholder")}/>
                </div>
              )}
            </BloqueForm>

            <BloqueForm titulo="Preguntas" nota="Las tres valoraciones con estrellas son del sistema: se pueden renombrar y desactivar, no borrar. Añade las tuyas debajo; las respuestas se ven en Encuesta y en el CSV de votos.">
              <EditorPreguntas idBase="en" tipos={["estrellas", "si_no", "opcion", "texto"]}
                preguntas={v("encuesta.preguntas")} onCambio={p("encuesta.preguntas")}/>
              <a href="/admin/encuesta" data-route className="mono" style={{ color: "var(--grano)" }}>Ver los resultados de la encuesta →</a>
            </BloqueForm>
          </div>
        )}

        {/* ── Inscripción ────────────────────────────────────────────── */}
        {pestana === "inscripcion" && (
          <div style={{ display: "flex", flexDirection: "column", gap: 20 }}>
            <BloqueForm titulo="Formulario abierto" nota="Con la inscripción cerrada, /inscripcion muestra el mensaje de abajo y el servidor rechaza solicitudes nuevas. Los espacios se siguen pudiendo crear desde el panel.">
              <Interruptor id="in-abierta" etiqueta="Inscripción pública abierta"
                valor={!!v("inscripcion.abierta")} onCambio={p("inscripcion.abierta")}/>
              <div className="grid-2">
                <CampoCfg id="in-cierre" tipo="date" etiqueta="Cierre automático (opcional)" valor={v("inscripcion.cierre")} max={10}
                  onCambio={p("inscripcion.cierre")} ayuda="Se cierra sola al terminar ese día (hora de Colombia)."/>
                <div/>
              </div>
              <CampoCfg id="in-msgc" etiqueta="Mensaje con la inscripción cerrada" valor={v("inscripcion.mensaje_cerrada")} max={400} largo onCambio={p("inscripcion.mensaje_cerrada")}/>
            </BloqueForm>

            <BloqueForm titulo="Encabezado del formulario">
              <CampoCfg id="in-tit" etiqueta="Título" valor={v("inscripcion.titulo")} max={120} onCambio={p("inscripcion.titulo")}/>
              <CampoCfg id="in-intro" etiqueta="Introducción" valor={v("inscripcion.intro")} max={800} largo onCambio={p("inscripcion.intro")}/>
            </BloqueForm>

            <BloqueForm titulo="Campos" nota="Qué se pide, con qué texto y si es obligatorio. El editor de espacios del panel pide lo mismo. Un campo que dejes de pedir conserva los datos que ya tenía.">
              {GRUPOS_INSCRIPCION.map(([grupo, titulo]) => {
                const claves = Object.keys(campos).filter((k) => campos[k].grupo === grupo);
                if (!claves.length) return null;
                const seccion = ["ubicacion", "participa", "linea", "detalle"].includes(grupo) ? grupo : null;
                return (
                  <div key={grupo}>
                    <div className="mono" style={{ margin: "6px 0 2px" }}>{titulo}</div>
                    {seccion && (
                      <div className="grid-2" style={{ margin: "6px 0 8px" }}>
                        <CampoCfg id={"sec-t-" + grupo} etiqueta="Título del bloque" valor={v("inscripcion.secciones." + seccion + ".titulo")} max={80}
                          onCambio={p("inscripcion.secciones." + seccion + ".titulo")}/>
                        <CampoCfg id={"sec-n-" + grupo} etiqueta="Nota del bloque" valor={v("inscripcion.secciones." + seccion + ".nota")} max={400}
                          onCambio={p("inscripcion.secciones." + seccion + ".nota")}/>
                      </div>
                    )}
                    {claves.map((k) => (
                      <FilaCampo key={k} clave={k} campo={campos[k]}
                        onCambio={(c) => p("inscripcion.campos." + k)(c)}/>
                    ))}
                  </div>
                );
              })}
            </BloqueForm>

            <BloqueForm titulo="Preguntas adicionales" nota="Preguntas propias de este evento. Salen en un bloque antes de «Cómo vas a entrar». Las marcadas como públicas se ven en la ficha del espacio.">
              <div className="grid-2">
                <CampoCfg id="sec-t-extra" etiqueta="Título del bloque" valor={v("inscripcion.secciones.extra.titulo")} max={80} onCambio={p("inscripcion.secciones.extra.titulo")}/>
                <CampoCfg id="sec-n-extra" etiqueta="Nota del bloque" valor={v("inscripcion.secciones.extra.nota")} max={400} onCambio={p("inscripcion.secciones.extra.nota")}/>
              </div>
              <EditorPreguntas idBase="ip" conPublica tipos={["texto", "parrafo", "numero", "fecha", "si_no", "opcion", "multiple"]}
                preguntas={v("inscripcion.preguntas")} onCambio={p("inscripcion.preguntas")}/>
            </BloqueForm>
            <a href="/inscripcion" data-route className="mono" style={{ color: "var(--grano)" }}>Ver el formulario público (con lo guardado) →</a>
          </div>
        )}

        {/* ── Pasaporte ──────────────────────────────────────────────── */}
        {pestana === "pasaporte" && (
          <div style={{ display: "flex", flexDirection: "column", gap: 20 }}>
            <BloqueForm titulo="Portada y hoja de datos">
              <div className="grid-2">
                <CampoCfg id="pp-eti" etiqueta="Rótulo de la portada" valor={v("pasaporte.etiqueta")} max={40} onCambio={p("pasaporte.etiqueta")}/>
                <CampoCfg id="pp-tit" etiqueta="Título de la portada" valor={v("pasaporte.titulo_portada")} max={60} onCambio={p("pasaporte.titulo_portada")}
                  placeholder={nombreEvento(b)} ayuda="Vacío = el nombre del evento."/>
              </div>
              <div className="grid-2">
                <CampoCfg id="pp-l1" etiqueta="Lugar (línea 1)" valor={v("pasaporte.lugar_1")} max={30} onCambio={p("pasaporte.lugar_1")}/>
                <CampoCfg id="pp-l2" etiqueta="Lugar (línea 2)" valor={v("pasaporte.lugar_2")} max={30} onCambio={p("pasaporte.lugar_2")}/>
              </div>
              <div className="grid-2">
                <CampoCfg id="pp-e1" etiqueta="Encabezado de la hoja de datos (1)" valor={v("pasaporte.encabezado_1")} max={40} onCambio={p("pasaporte.encabezado_1")}/>
                <CampoCfg id="pp-e2" etiqueta="Encabezado de la hoja de datos (2)" valor={v("pasaporte.encabezado_2")} max={40} onCambio={p("pasaporte.encabezado_2")}/>
              </div>
              <CampoCfg id="pp-pre" etiqueta="Prefijo del número de pasaporte" valor={v("pasaporte.prefijo")} max={4}
                onCambio={(x) => p("pasaporte.prefijo")(x.toUpperCase().replace(/[^A-Z]/g, ""))}
                ayuda={"Hasta 4 letras. Así: " + ((v("pasaporte.prefijo") || "NAR")) + "-K7Q2-M4XP. Cambiarlo cambia el número que ve todo el mundo."}/>
            </BloqueForm>

            <BloqueForm titulo="Textos">
              <CampoCfg id="pp-inv" etiqueta="Invitación (pasaporte vacío)" valor={v("pasaporte.invitacion")} max={120} onCambio={p("pasaporte.invitacion")}/>
              <CampoCfg id="pp-ct" etiqueta="Cierre: título" valor={v("pasaporte.cierre_titulo")} max={120} onCambio={p("pasaporte.cierre_titulo")}/>
              <div className="grid-2">
                <CampoCfg id="pp-cb" etiqueta="Cierre: botón" valor={v("pasaporte.cierre_boton")} max={60} onCambio={p("pasaporte.cierre_boton")}/>
                <CampoCfg id="pp-cf" etiqueta="Contraportada: firma" valor={v("pasaporte.cierre_firma")} max={60} onCambio={p("pasaporte.cierre_firma")}/>
              </div>
            </BloqueForm>

            <BloqueForm titulo="Rejilla de «Mi recorrido»">
              <div className="grid-2">
                {[["columnas_pc", "Columnas en computador", 6], ["columnas_movil", "Columnas en móvil", 3]].map(([k, etiqueta, max]) => (
                  <div className="field" key={k}>
                    <label htmlFor={"fr-" + k}>{etiqueta}</label>
                    <select id={"fr-" + k} value={v("recorrido." + k) || 1}
                      onChange={(e) => p("recorrido." + k)(Number(e.target.value))}>
                      {Array.from({ length: max }, (_, i) => i + 1).map((n) => (
                        <option key={n} value={n}>{n}</option>
                      ))}
                    </select>
                  </div>
                ))}
              </div>
            </BloqueForm>

            <BloqueForm titulo="Fondos del pasaporte" nota="Si no subes nada, el pasaporte usa el papel del sistema. Las imágenes se ven detrás del texto: mejor claras y sin mucho detalle. Se guardan al subirlas.">
              <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fit, minmax(min(100%, 240px), 1fr))", gap: 12 }}>
                <FondoRanura titulo="Portada" url={urlImagen((b.pasaporte || {}).portada)}
                  subiendo={subiendo === "portada"}
                  ayuda="La tapa del pasaporte. Sobre ella va el nombre del portador en claro."
                  onSubir={(f) => subirImagen("portada", f)}
                  onQuitar={() => quitarImagen("portada")}/>
                <FondoRanura titulo="Contraportada" url={urlImagen((b.pasaporte || {}).contraportada)}
                  subiendo={subiendo === "contraportada"}
                  ayuda="La tapa de atrás, la que cierra el libro."
                  onSubir={(f) => subirImagen("contraportada", f)}
                  onQuitar={() => quitarImagen("contraportada")}/>
              </div>
              <div className="mono">Hojas internas · {hojas.length} {hojas.length === 1 ? "imagen" : "imágenes"}</div>
              <p className="ayuda" style={{ margin: 0 }}>
                Se reparten en orden por las hojas de dentro y se repiten cuando se acaban.
              </p>
              <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fill, minmax(140px, 1fr))", gap: 10 }}>
                {hojas.map((h, i) => (
                  <div key={h + i} style={{ border: "1px solid var(--line-2)", borderRadius: "var(--r-sm)", overflow: "hidden" }}>
                    <Miniatura url={urlImagen(h)} alto={96} vacio="sin imagen"/>
                    <div style={{ display: "flex", alignItems: "center", justifyContent: "space-between", padding: "6px 8px" }}>
                      <span className="mono" style={{ color: "var(--ink-3)" }}>{i + 1}</span>
                      <button type="button" onClick={() => quitarImagen("hojas", i)}
                        className="mono" style={{ background: "none", border: "none", color: "var(--bad)", cursor: "pointer" }}>quitar</button>
                    </div>
                  </div>
                ))}
                <label style={{
                  border: "1px dashed var(--line-2)", borderRadius: "var(--r-sm)", minHeight: 128,
                  display: "flex", alignItems: "center", justifyContent: "center",
                  cursor: subiendo === "hojas" ? "wait" : "pointer", color: "var(--ink-3)",
                }} className="mono">
                  {subiendo === "hojas" ? "Subiendo…" : "+ Añadir hoja"}
                  <input type="file" accept="image/jpeg,image/png,image/webp" hidden disabled={subiendo === "hojas"}
                    onChange={(e) => { const f = e.target.files && e.target.files[0]; e.target.value = ""; if (f) subirImagen("hojas", f); }}/>
                </label>
              </div>
              <a href="/pasaporte" data-route className="mono" style={{ color: "var(--grano)" }}>Ver el pasaporte →</a>
              <EstadoAlmacen/>
            </BloqueForm>
          </div>
        )}

        {/* ── Datos personales ───────────────────────────────────────── */}
        {/* El texto legal se edita aquí porque quien lo tiene que poder
            cambiar es el área jurídica, no quien despliega. Un aviso de habeas
            data desactualizado no es un detalle de redacción. */}
        {pestana === "datos" && (
          <div style={{ display: "flex", flexDirection: "column", gap: 20 }}>
            <BloqueForm titulo="Política de tratamiento de datos" nota="Es lo que lee quien se inscribe al pulsar «Leer la política de tratamiento de datos», junto a la casilla de autorización. Deja una línea en blanco entre párrafos.">
              <CampoCfg id="fd-resp" etiqueta="Responsable (en la casilla de autorización)" valor={v("datos.responsable")} max={120}
                onCambio={p("datos.responsable")} ayuda="«Autorizo a … a tratar mis datos personales…»"/>
              <CampoCfg id="fd-texto" etiqueta="Texto del aviso" valor={v("datos.texto")} max={8000} largo filas={12}
                onCambio={p("datos.texto")}
                ayuda={((v("datos.texto") || "").length).toLocaleString() + " de 8.000 caracteres. Vacío = vuelve al texto que trae el sistema."}/>
              <div className="grid-2">
                <CampoCfg id="fd-enlace" tipo="url" etiqueta="Enlace a la política completa" valor={v("datos.enlace")} max={400}
                  placeholder="https://…" onCambio={p("datos.enlace")} ayuda="Vacío = no se muestra ningún enlace."/>
                <CampoCfg id="fd-enlace-t" etiqueta="Texto del enlace" valor={v("datos.enlace_texto")} max={160} onCambio={p("datos.enlace_texto")}/>
              </div>
              <button type="button" className="btn btn-ghost" onClick={() => setVerAviso(true)} style={{ alignSelf: "flex-start" }}>
                Ver cómo queda el aviso
              </button>
            </BloqueForm>
            {/* Con el borrador, no con lo guardado: se está mirando para
                decidir si se guarda. */}
            <AvisoDatos abierto={verAviso} onCerrar={() => setVerAviso(false)} datos={b.datos}/>
          </div>
        )}
      </div>

      {/* Barra de guardado: siempre a la vista, porque el borrador abarca
          todas las pestañas y no se guarda solo. */}
      <div style={{
        position: "sticky", bottom: 0, zIndex: 15, borderTop: "1px solid var(--line)",
        background: "color-mix(in oklch, var(--paper) 94%, transparent)", backdropFilter: "blur(6px)",
        padding: "12px 24px", display: "flex", gap: 12, alignItems: "center", flexWrap: "wrap",
      }}>
        <span className="mono" style={{ flex: 1, minWidth: 180, color: problemas.length ? "var(--bad)" : (sucio ? "var(--cafeto)" : "var(--ink-3)") }}>
          {problemas.length ? problemas[0] : (sucio ? "Hay cambios sin guardar" : "Todo guardado")}
        </span>
        {sucio && <button type="button" className="btn btn-ghost" onClick={descartar} disabled={guardando}>Descartar</button>}
        <button type="button" className="btn btn-primary" onClick={guardar} disabled={guardando || !sucio || problemas.length > 0}
          style={{ opacity: (guardando || !sucio || problemas.length) ? 0.55 : 1 }}>
          {guardando ? "Guardando…" : "Guardar cambios"}
        </button>
      </div>
    </AdminShell>
  );
};

/** El sello con la palabra del borrador, para verlo antes de guardar. */
const SelloVista = ({ palabra }) => (
  <svg width={150} height={150} viewBox="0 0 110 110" style={{ transform: "rotate(-8deg)", mixBlendMode: "multiply" }} aria-label={"Sello: " + palabra}>
    <circle cx="55" cy="55" r="48" stroke="var(--grano)" strokeWidth="2" fill="none"/>
    <circle cx="55" cy="55" r="42" stroke="var(--grano)" strokeWidth="1" fill="none"/>
    <text x="55" y="48" textAnchor="middle" fill="var(--grano)" fontSize="8" fontFamily="var(--font-mono)" letterSpacing="2">{palabra}</text>
    <text x="55" y="62" textAnchor="middle" fill="var(--grano)" fontSize="16" fontFamily="var(--font-display)"
      style={{ fontStyle: "var(--display-style)", fontWeight: "var(--display-weight)" }}>Espacio</text>
    <text x="55" y="74" textAnchor="middle" fill="var(--grano)" fontSize="7" fontFamily="var(--font-mono)" letterSpacing="1">12 NOV 2026</text>
  </svg>
);

Object.assign(window, { FestivalPage, Miniatura, FondoRanura, VistaTema, EditorPreguntas });
