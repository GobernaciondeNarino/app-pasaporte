// Mi recorrido — /recorrido
//
// Todos los stands del festival en una rejilla. Los que ya sellé salen a color
// y con su valoración; el resto quedan apagados, como una colección a medio
// llenar. Es la vista que responde a «¿cuáles me faltan?», que el ranking no
// contesta porque ahí lo que manda es quién va ganando.
//
// El número de columnas lo pone el organizador (PC y móvil por separado) desde
// el panel: en una pantalla de sala grande caben cuatro y en un teléfono a
// veces conviene una sola.

const RECORRIDO_COLUMNAS = { pc: 3, movil: 2 };

const columnasConfiguradas = () => {
  const a = (window.LMTFestival && window.LMTFestival.ajustes()) || {};
  const r = a.recorrido || {};
  return {
    pc:    Math.max(1, Math.min(6, Number(r.columnas_pc)    || RECORRIDO_COLUMNAS.pc)),
    movil: Math.max(1, Math.min(3, Number(r.columnas_movil) || RECORRIDO_COLUMNAS.movil)),
  };
};

/** Tarjeta de un stand. `visitado` es lo que la enciende. */
const TarjetaRecorrido = ({ stand, visitado, valoracion, titulos }) => {
  const logo = urlImagen(stand.logo);
  const inicial = (stand.nombre || "?").trim().charAt(0).toUpperCase();

  return (
    <a href={"/festival/" + stand.id} data-route
      aria-label={`${stand.nombre}, ${stand.municipio}${visitado ? " — visitado" : " — sin visitar"}`}
      style={{
        display: "flex", flexDirection: "column", gap: 10,
        padding: 14, textDecoration: "none", color: "inherit",
        border: visitado ? "1px solid var(--line-2)" : "1px dashed var(--line-2)",
        borderRadius: "var(--r-md)",
        background: visitado ? "var(--paper)" : "transparent",
        // Apagar y no esconder: el stand sin visitar se ve, pero se nota que le
        // falta algo. Con `filter` basta y no hay que duplicar la tarjeta.
        filter: visitado ? "none" : "grayscale(1)",
        opacity: visitado ? 1 : 0.55,
        transition: "opacity 0.2s, filter 0.2s",
        minWidth: 0,
      }}>
      <div style={{ display: "flex", alignItems: "center", gap: 10, minWidth: 0 }}>
        <div style={{
          width: 44, height: 44, borderRadius: "50%", flexShrink: 0, overflow: "hidden",
          border: "1px solid var(--line-2)", background: stand.color || "var(--grano)",
          display: "flex", alignItems: "center", justifyContent: "center",
        }}>
          {logo
            ? <img src={logo} alt="" style={{ width: "100%", height: "100%", objectFit: "cover" }}/>
            : <span style={{ fontFamily: "var(--font-display)", fontWeight: "var(--display-weight)", fontStyle: "var(--display-style)", fontSize: 20, color: "var(--paper)" }}>{inicial}</span>}
        </div>
        <div style={{ minWidth: 0, flex: 1 }}>
          <div style={{ fontSize: 14, fontWeight: 500, lineHeight: 1.2, overflow: "hidden", textOverflow: "ellipsis", whiteSpace: "nowrap" }}>
            {stand.nombre}
          </div>
          <div className="mono" style={{ fontSize: 9, color: "var(--ink-3)", marginTop: 2 }}>{stand.municipio}</div>
        </div>
      </div>

      <div style={{ display: "flex", flexDirection: "column", gap: 3 }}>
        {estrellasActivas().map((campo) => {
          const clave = ESTRELLA_CLAVES[campo];
          // Mi puntuación si la di; si no, la media del festival. Un stand que
          // no visité nunca tendrá «mi» valoración, y enseñar la media es lo
          // que ayuda a decidir a cuál ir.
          const mia = valoracion ? valoracion[clave] : null;
          const media = (stand.estrellas || {})[clave];
          return (
            <EstrellasLectura key={campo} etiqueta={titulos[campo]}
              valor={mia != null ? mia : media} tam={12}/>
          );
        })}
      </div>

      <div className="mono" style={{ fontSize: 9, color: visitado ? "var(--cafeto)" : "var(--ink-3)" }}>
        {visitado ? "✓ Sellado" : "Sin sellar"}
      </div>
    </a>
  );
};

const RecorridoPage = ({ stands: standsTablero }) => {
  // Si el tablero no llegó, la copia de la lista guardada en este teléfono.
  const stands = window.LMTApi && window.LMTApi.standsVisitante
    ? window.LMTApi.standsVisitante(standsTablero) : (standsTablero || []);
  const [pasaporte, setPasaporte] = React.useState(null);
  const [cargando, setCargando] = React.useState(true);
  // El último fallo al pedir el pasaporte ("OFF" = la API no arrancó) y, si se
  // está enseñando la copia del teléfono, cuándo se guardó.
  const [fallo, setFallo] = React.useState(null);
  const [copiaDe, setCopiaDe] = React.useState(0);
  const pasaporteRef = React.useRef(null);
  pasaporteRef.current = pasaporte;
  const [intento, setIntento] = React.useState(0);
  const [cols, setCols] = React.useState(columnasConfiguradas);
  const [titulos, setTitulos] = React.useState(() => window.titulosEstrellas());

  React.useEffect(() => {
    const refrescar = () => { setCols(columnasConfiguradas()); setTitulos(window.titulosEstrellas()); };
    window.addEventListener("lmt:festival", refrescar);
    return () => window.removeEventListener("lmt:festival", refrescar);
  }, []);

  // Sin correo no hay «mi» recorrido: es de esta persona y hay que saber quién
  // es. Se pide con la misma puerta que el pasaporte y el perfil.
  const [correo, setCorreo] = React.useState(() => (window.LMTPerfil && window.LMTPerfil.correoConocido()) || "");

  // Antes un fallo se tragaba en silencio y la rejilla salía toda apagada:
  // la persona creía que había perdido su recorrido. Ahora se enseña la copia
  // del teléfono si la hay, se dice qué pasó con su código y se reintenta.
  React.useEffect(() => {
    if (!correo) { setCargando(false); return; }
    let cancelado = false;
    const usarCopia = () => {
      if (pasaporteRef.current) return;
      const copia = window.LMTApi && window.LMTApi.pasaporteGuardado && window.LMTApi.pasaporteGuardado(correo);
      if (copia) { setPasaporte(copia.data); setCopiaDe(copia.t || 1); }
    };
    const intentar = async () => {
      if (cancelado || !window.LMTApi) return;
      if (!window.LMTApi.enabled) {
        if (window.LMTApi.bootstrapDone && window.LMTApi.bootstrapDone()) {
          setFallo("OFF"); usarCopia(); setCargando(false);
        }
        return;
      }
      try {
        const t = (window.LMTPerfil && window.LMTPerfil.testigoDe(correo)) || "";
        const res = await window.LMTApi.getPasaporte(correo, t);
        if (!cancelado) { setPasaporte(res); setFallo(null); setCopiaDe(0); }
      } catch (e) {
        if (!cancelado) { setFallo(e); usarCopia(); }
      }
      finally { if (!cancelado) setCargando(false); }
    };
    intentar();
    window.addEventListener("lmt:auth", intentar);
    return () => { cancelado = true; window.removeEventListener("lmt:auth", intentar); };
  }, [correo, intento]);

  // Reintento solo mientras haya un fallo («OFF» lo reintenta js/api.js y
  // avisa con lmt:auth).
  React.useEffect(() => {
    if (!fallo || fallo === "OFF") return;
    if (String((fallo && fallo.code) || "").includes("bad_email")) return;
    const t = setTimeout(() => setIntento((n) => n + 1), pasaporte ? 20000 : 10000);
    return () => clearTimeout(t);
  }, [fallo, pasaporte]);

  const reintentarYa = () => {
    if (window.LMTApi && window.LMTApi.enabled) setIntento((n) => n + 1);
    else window.dispatchEvent(new CustomEvent("lmt:reintentar"));
  };

  const visitados = (pasaporte && pasaporte.visitados) || [];
  const misEstrellas = (pasaporte && pasaporte.estrellas_mias) || {};
  const setVisitados = React.useMemo(() => new Set(visitados), [visitados]);

  // Los sellados primero: es lo que la persona quiere ver de un vistazo.
  const ordenados = React.useMemo(() => {
    const copia = [...stands];
    copia.sort((a, b) => {
      const va = setVisitados.has(a.id) ? 0 : 1;
      const vb = setVisitados.has(b.id) ? 0 : 1;
      return va !== vb ? va - vb : a.nombre.localeCompare(b.nombre, "es");
    });
    return copia;
  }, [stands, setVisitados]);

  if (!correo) {
    return (
      <PuertaCorreo
        titulo="Tu recorrido por el evento."
        nota="Escribe el correo con el que votas en los espacios y verás cuáles llevas sellados. No hace falta contraseña."
        volverA="/festival" volverTexto="← Volver al ranking"
        onListo={(c) => setCorreo(c)}/>
    );
  }

  return (
    <div style={{ minHeight: "100dvh", background: "var(--paper)" }}>
      <PublicHeader/>
      <section className="seccion" style={{ paddingTop: 28, paddingBottom: 40 }}>
        <div className="mono">Mi recorrido</div>
        <h1 style={{ fontFamily: "var(--font-display)", fontStyle: "var(--display-style)", fontSize: "min(56px, 11vw)", fontWeight: "var(--display-weight)", margin: "6px 0 8px", lineHeight: 1 }}>
          Los espacios del evento.
        </h1>
        <p style={{ fontSize: 14, color: "var(--ink-2)", lineHeight: 1.6, maxWidth: 560 }}>
          {visitados.length
            ? <>Llevas <strong style={{ fontWeight: 500 }}>{visitados.length} de {stands.length}</strong> sellados. Los que están a color ya los visitaste.</>
            : <>Escanea el QR de cualquier espacio y vota: a partir de ahí, los que visites se van encendiendo aquí.</>}
        </p>

        {cargando && (
          <p className="mono" style={{ marginTop: 20, color: "var(--ink-3)" }}>Cargando tu recorrido…</p>
        )}

        {!cargando && fallo && (
          <div role={pasaporte ? "status" : "alert"} style={{
            marginTop: 18, padding: "10px 12px", fontSize: 13, lineHeight: 1.5, maxWidth: 640,
            border: `1px solid ${pasaporte ? "var(--line)" : "var(--bad)"}`, borderRadius: "var(--r-sm)",
            color: pasaporte ? "var(--ink-2)" : "var(--bad)",
          }}>
            {copiaDe
              ? <>El servidor no respondió: ves la copia guardada en este teléfono. Tus sellos no se pierden y la lista se actualiza sola.</>
              : pasaporte
                ? <>No se pudo actualizar tu recorrido; lo reintentamos solos.</>
                : <>No pudimos cargar tu recorrido ahora mismo. Tus sellos están guardados en el servidor: lo reintentamos solos.</>}
            <CodigoError codigo={codigoDiagnostico(fallo, "REC")}/>
            <button type="button" className="btn btn-ghost" onClick={reintentarYa}
              style={{ marginTop: 8, padding: "6px 12px", fontSize: 13 }}>Reintentar ahora</button>
          </div>
        )}

        <div className="recorrido-grid"
          style={{
            marginTop: 22,
            // Las columnas configuradas viajan como variables CSS y una media
            // query decide cuál de las dos manda (ver styles/tokens.css).
            "--cols-pc": cols.pc,
            "--cols-movil": cols.movil,
          }}>
          {ordenados.map((s) => (
            <TarjetaRecorrido key={s.id} stand={s} titulos={titulos}
              visitado={setVisitados.has(s.id)}
              valoracion={misEstrellas[s.id] || null}/>
          ))}
        </div>

        {/* Sin lista no siempre es «no hay espacios»: puede que el tablero
            aún no haya respondido. Decir lo primero con sellos ganados era
            lo que hacía pensar que se había perdido el recorrido. */}
        {!stands.length && (window.LMTApi && window.LMTApi.ultimoTableroOk
          ? <p style={{ marginTop: 24, color: "var(--ink-2)" }}>Todavía no hay espacios registrados.</p>
          : <div style={{ marginTop: 24, color: "var(--ink-2)" }}>
              <p style={{ margin: 0 }}>
                {visitados.length ? `Tienes ${visitados.length} ${visitados.length === 1 ? "sello" : "sellos"}. ` : ""}
                Cargando los espacios del evento…
              </p>
              {window.LMTApi && window.LMTApi.errorTablero && (
                <CodigoError codigo={codigoDiagnostico(window.LMTApi.errorTablero, "REC-TAB")}/>
              )}
            </div>)}
      </section>
    </div>
  );
};

Object.assign(window, { RecorridoPage, TarjetaRecorrido });
