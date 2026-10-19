// Resultados de la encuesta — /admin/encuesta
//
// Cada pregunta del voto con su resumen: la media y el reparto de las
// estrellas, el sí y el no, cuántos eligieron cada opción y las últimas
// respuestas abiertas. Las preguntas salen de la configuración del evento, así
// que lo que se añade en el panel aparece aquí sin tocar nada más.
//
// Las barras son una sola serie por pregunta y van en un único tono (el acento
// del tema): miden cantidad, no identifican nada, así que no necesitan leyenda
// ni colores distintos. Los números se leen siempre al lado, en tinta.

const BarraResultado = ({ etiqueta, n, total, nota }) => {
  const pct = total > 0 ? (n / total) * 100 : 0;
  const texto = `${etiqueta}: ${n} de ${total} (${pct.toFixed(0)}%)`;
  return (
    <div title={texto} style={{ display: "grid", gridTemplateColumns: "minmax(80px, 34%) 1fr auto", gap: 10, alignItems: "center", padding: "5px 0" }}>
      <span style={{ fontSize: 13, color: "var(--ink)", overflow: "hidden", textOverflow: "ellipsis", whiteSpace: "nowrap" }}>
        {etiqueta}{nota && <span className="mono" style={{ marginLeft: 6, color: "var(--ink-3)" }}>{nota}</span>}
      </span>
      <div role="img" aria-label={texto} style={{ height: 8, background: "var(--paper-2)", borderRadius: 4, overflow: "hidden" }}>
        <div style={{ width: pct + "%", minWidth: n > 0 ? 4 : 0, height: "100%", background: "var(--grano)", borderRadius: 4, transition: "width 0.4s" }}/>
      </div>
      <span className="mono" style={{ color: "var(--ink-2)", minWidth: 74, textAlign: "right" }}>{n} · {pct.toFixed(0)}%</span>
    </div>
  );
};

const TarjetaPregunta = ({ p }) => {
  const tipo = (window.TIPOS_PREGUNTA || {})[p.tipo] || p.tipo;
  return (
    <div style={{ border: "1px solid var(--line)", borderRadius: "var(--r-md)", padding: 18, opacity: p.activa ? 1 : 0.7 }}>
      <div style={{ display: "flex", justifyContent: "space-between", gap: 10, flexWrap: "wrap", alignItems: "baseline" }}>
        <div style={{ fontSize: 16, fontWeight: 600, lineHeight: 1.3, flex: "1 1 220px" }}>{p.etiqueta}</div>
        <div className="mono" style={{ color: "var(--ink-3)" }}>
          {tipo}{p.sistema ? " · del sistema" : ""}{!p.activa ? " · desactivada" : ""}
        </div>
      </div>
      <div className="mono" style={{ margin: "6px 0 12px", color: "var(--ink-3)" }}>
        {p.n} {p.n === 1 ? "respuesta" : "respuestas"}
      </div>

      {p.tipo === "estrellas" && (
        <div style={{ display: "grid", gridTemplateColumns: "auto 1fr", gap: 18, alignItems: "center" }}>
          <div style={{ textAlign: "center", minWidth: 84 }}>
            <div style={{ fontFamily: "var(--font-display)", fontStyle: "var(--display-style)", fontWeight: "var(--display-weight)", fontSize: 40, lineHeight: 1 }}>
              {p.promedio !== null && p.promedio !== undefined ? p.promedio.toFixed(1) : "—"}
            </div>
            <div className="mono" style={{ marginTop: 4 }}>de 5</div>
          </div>
          <div>
            {[5, 4, 3, 2, 1].map((k) => (
              <BarraResultado key={k} etiqueta={"★".repeat(k)} n={(p.distribucion || [])[k - 1] || 0} total={p.n}/>
            ))}
          </div>
        </div>
      )}

      {p.tipo === "si_no" && (
        <div>
          <BarraResultado etiqueta="Sí" n={p.si || 0} total={p.n}/>
          <BarraResultado etiqueta="No" n={p.no || 0} total={p.n}/>
        </div>
      )}

      {(p.tipo === "opcion" || p.tipo === "multiple") && (
        <div>
          {(p.opciones || []).map((o) => (
            <BarraResultado key={o.texto} etiqueta={o.texto} n={o.n} total={p.n}
              nota={o.retirada ? "ya no está en la lista" : ""}/>
          ))}
          {p.tipo === "multiple" && (
            <p className="ayuda" style={{ marginTop: 6 }}>Se podía marcar más de una: los porcentajes no suman 100.</p>
          )}
        </div>
      )}

      {p.recientes && (
        p.recientes.length
          ? <ul style={{ margin: 0, paddingLeft: 18, display: "grid", gap: 6 }}>
              {p.recientes.map((t, i) => <li key={i} style={{ fontSize: 13, color: "var(--ink-2)", lineHeight: 1.5 }}>{t}</li>)}
            </ul>
          : <p className="ayuda">Aún no hay respuestas.</p>
      )}
    </div>
  );
};

const EncuestaResultadosPage = ({ user, stands }) => {
  const [stand, setStand] = React.useState("");
  const [datos, setDatos] = React.useState(null);
  const [error, setError] = React.useState("");

  React.useEffect(() => {
    let vivo = true;
    const cargar = async () => {
      if (!window.LMTApi || !window.LMTApi.enabled) return;
      try { const d = await window.LMTApi.resultadosEncuesta(stand); if (vivo) { setDatos(d); setError(""); } }
      catch (e) { if (vivo) setError(mensajeError(e)); }
    };
    cargar();
    window.addEventListener("lmt:auth", cargar);
    return () => { vivo = false; window.removeEventListener("lmt:auth", cargar); };
  }, [stand]);

  const simbolos = simbolosVoto().slice().reverse();
  const csv = window.LMTApi && window.LMTApi.urlFor ? window.LMTApi.urlFor("/export/votos.csv") : "";

  return (
    <AdminShell active="encuesta" user={user}>
      <div className="admin-page">
      <div className="mono">Encuesta</div>
      <h1 className="titulo-xl">Resultados del voto.</h1>
      <p style={{ fontSize: 14, color: "var(--ink-2)", lineHeight: 1.6, maxWidth: 640, margin: "10px 0 20px" }}>
        Lo que respondió el público al votar en cada espacio. Las preguntas se cambian en{" "}
        <a href="/admin/festival" data-route style={{ color: "var(--grano)" }}>Configuración del evento → Encuesta</a>;
        una pregunta desactivada sigue aquí con lo que se respondió mientras estuvo activa.
      </p>

      <div style={{ display: "flex", gap: 12, flexWrap: "wrap", alignItems: "flex-end", marginBottom: 22 }}>
        <div className="field" style={{ minWidth: 240, flex: "0 1 320px" }}>
          <label htmlFor="enc-stand">Espacio</label>
          <select id="enc-stand" value={stand} onChange={(e) => setStand(e.target.value)}>
            <option value="">Todos los espacios</option>
            {(stands || []).map((s) => <option key={s.id} value={s.id}>{s.nombre} · {s.municipio}</option>)}
          </select>
        </div>
        {csv && <a href={csv} className="btn btn-ghost">↓ Descargar todos los votos (CSV)</a>}
      </div>

      <Aviso>{error}</Aviso>
      {!datos && !error && <p className="mono" style={{ color: "var(--ink-3)" }}>Cargando…</p>}

      {datos && (
        <>
          <div style={{ border: "1px solid var(--line)", borderRadius: "var(--r-md)", padding: 18, marginBottom: 16, background: "var(--paper-2)" }}>
            <div style={{ display: "flex", alignItems: "baseline", gap: 10, marginBottom: 10 }}>
              <span style={{ fontFamily: "var(--font-display)", fontStyle: "var(--display-style)", fontWeight: "var(--display-weight)", fontSize: 36, lineHeight: 1 }}>{datos.total}</span>
              <span className="mono">{datos.total === 1 ? "voto" : "votos"}{stand ? " en este espacio" : " en todo el evento"}</span>
            </div>
            {simbolos.map((x) => (
              <BarraResultado key={x.id} etiqueta={x.emoji + " " + x.label} n={(datos.emojis || {})[x.id] || 0} total={datos.total}/>
            ))}
          </div>

          <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fit, minmax(min(100%, 380px), 1fr))", gap: 14 }}>
            {(datos.preguntas || []).map((p) => <TarjetaPregunta key={p.id} p={p}/>)}
          </div>
          {(datos.preguntas || []).length === 0 && (
            <p style={{ color: "var(--ink-2)" }}>La encuesta no tiene preguntas además del emoji.</p>
          )}
        </>
      )}
      </div>
    </AdminShell>
  );
};

Object.assign(window, { EncuestaResultadosPage });
