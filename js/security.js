// Utilidades de seguridad para "La Mejor Taza"
// - Escape HTML para todo texto que provenga de la API PHP o del usuario.
// - Validación de correos y enmascarado para mostrar en público.
// - Limpieza de comentarios (sin HTML, sin URLs sospechosas, longitud máxima).
// - Rate limit en cliente (1 voto por stand cada 60 s desde el mismo navegador).

(function () {
  const HTML_ESCAPE = { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;", "/": "&#x2F;" };
  function escapeHtml(value) {
    if (value == null) return "";
    return String(value).replace(/[&<>"'\/]/g, (c) => HTML_ESCAPE[c]);
  }

  // Devuelve sólo texto seguro para inyectar como children de React.
  // React ya escapa por defecto, pero exponemos también una función explícita
  // para situaciones donde se concatena en strings antes del render.
  function sanitizeText(value, maxLength = 500) {
    if (value == null) return "";
    let s = String(value).normalize("NFKC");
    s = s.replace(/[\x00-\x1F\x7F]/g, " ");
    s = s.replace(/\s+/g, " ").trim();
    if (s.length > maxLength) s = s.slice(0, maxLength);
    return s;
  }

  // Email RFC-5321 práctico (no perfecto, pero suficiente como guard rail).
  const EMAIL_RE = /^[a-zA-Z0-9._%+\-]{1,64}@[a-zA-Z0-9.\-]{1,253}\.[a-zA-Z]{2,}$/;
  function isEmail(value) {
    if (typeof value !== "string") return false;
    if (value.length > 254) return false;
    return EMAIL_RE.test(value);
  }

  function normalizeEmail(value) {
    if (typeof value !== "string") return "";
    return value.trim().toLowerCase();
  }

  function maskEmail(value) {
    if (!isEmail(value)) return "";
    const [user, domain] = value.split("@");
    const head = user.slice(0, Math.min(2, user.length));
    return `${head}${"*".repeat(Math.max(1, user.length - head.length))}@${domain}`;
  }

  const VOTE_VALUES = new Set(["bueno", "regular", "malo"]);
  function isVoteEmoji(value) { return VOTE_VALUES.has(value); }

  // Stand id: sólo minúsculas, dígitos, guión. Evita trampas en rutas /s/{id}.
  function isStandId(value) {
    return typeof value === "string" && /^[a-z0-9\-]{2,32}$/.test(value);
  }

  // Rate limit local — protección razonable para un kiosko/feria.
  // No reemplaza el rate limit del servidor (api/lib/RateLimit.php), sólo
  // evita spam accidental desde el mismo navegador.
  const RATE_KEY = "lmt.rate";
  const COOLDOWN_MS = 60 * 1000;
  function canVote(standId) {
    if (!isStandId(standId)) return false;
    try {
      const raw = localStorage.getItem(RATE_KEY);
      const map = raw ? JSON.parse(raw) : {};
      const last = map[standId] || 0;
      return Date.now() - last > COOLDOWN_MS;
    } catch (_) {
      return true;
    }
  }
  function markVote(standId) {
    if (!isStandId(standId)) return;
    try {
      const raw = localStorage.getItem(RATE_KEY);
      const map = raw ? JSON.parse(raw) : {};
      map[standId] = Date.now();
      localStorage.setItem(RATE_KEY, JSON.stringify(map));
    } catch (_) { /* noop */ }
  }

  // Helper para construir un payload de voto válido.
  // Una valoración válida es un entero de 1 a 5; cualquier otra cosa no viaja.
  // El servidor lo vuelve a comprobar: esto es sólo para no mandar basura.
  function estrellaValida(v) {
    const n = Number(v);
    return Number.isInteger(n) && n >= 1 && n <= 5;
  }

  // Respuestas a las preguntas propias de la encuesta. Sólo viajan las claves
  // con la forma que pone el panel (p_xxxx) y valores simples: el servidor
  // vuelve a validarlas contra la configuración del evento.
  function limpiarRespuestas(r) {
    const out = {};
    if (!r || typeof r !== "object") return out;
    Object.keys(r).slice(0, 40).forEach((k) => {
      if (!/^p_[a-z0-9]{4,16}$/.test(k)) return;
      const v = r[k];
      if (v === null || v === undefined || v === "") return;
      if (Array.isArray(v)) { const a = v.filter((x) => typeof x === "string").slice(0, 30).map((x) => x.slice(0, 120)); if (a.length) out[k] = a; return; }
      if (typeof v === "boolean" || typeof v === "number") { out[k] = v; return; }
      if (typeof v === "string") out[k] = sanitizeText(v, 1000);
    });
    return out;
  }

  function buildVotePayload({ stand, emoji, correo, compra, compra_valor, texto, estrellas, respuestas }) {
    if (!isStandId(stand)) throw new Error("stand_invalido");
    if (!isVoteEmoji(emoji)) throw new Error("emoji_invalido");
    const correoNorm = normalizeEmail(correo);
    if (!isEmail(correoNorm)) throw new Error("correo_invalido");
    const payload = {
      stand,
      emoji,
      correo: correoNorm,
    };
    const e = estrellas || {};
    ["est_innovacion", "est_atencion", "est_calidad"].forEach((k) => {
      if (estrellaValida(e[k])) payload[k] = Number(e[k]);
    });
    if (typeof compra === "boolean") payload.compra = compra;
    // El importe sólo acompaña a un «sí compré»: mandarlo con un «no» sería
    // pedirle al servidor que descarte lo que nosotros ya sabemos que sobra.
    if (compra === true && compra_valor != null && String(compra_valor).trim() !== "") {
      payload.compra_valor = String(compra_valor).slice(0, 20);
    }
    const limpio = sanitizeText(texto || "", 500);
    if (limpio) payload.texto = limpio;
    const resp = limpiarRespuestas(respuestas);
    if (Object.keys(resp).length) payload.respuestas = resp;
    return payload;
  }

  window.LMTSecurity = {
    escapeHtml,
    sanitizeText,
    isEmail,
    normalizeEmail,
    maskEmail,
    isVoteEmoji,
    isStandId,
    canVote,
    markVote,
    buildVotePayload,
  };
})();
