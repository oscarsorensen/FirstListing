// Spanish translations — key is a short ID, value is the Spanish text.
// English text lives in the HTML itself (not here).
var es = {
    // Navigation (shared across all pages)
    "nav-how":      "Cómo funciona",
    "nav-helps":    "Por qué ayuda",
    "nav-login":    "Iniciar sesión",
    "nav-register": "Registrarse",
    "nav-home":     "Inicio",
    "nav-logout":   "Cerrar sesión",
    "nav-user":     "Usuario",
    "back-home":    "Volver al inicio",

    // index.php
    "idx-eyebrow":      "Proyecto escolar · Detección de duplicados inmobiliarios",
    "idx-h1":           "Rastrea anuncios duplicados y compara quién apareció primero.",
    "idx-lead":         "FirstListing guarda evidencia bruta de anuncios (HTML, texto y JSON-LD), organiza campos clave con IA y ayuda a comparar anuncios duplicados entre agencias.",
    "idx-btn-search":   "Buscar duplicados",
    "idx-btn-how":      "Ver cómo funciona",
    "idx-meta-poc":     "Prueba de concepto",
    "idx-meta-crawler": "Primera vez visto por nuestro rastreador (no es una afirmación legal de propiedad)",
    "idx-stat-raw":     "Evidencia bruta",
    "idx-stat-extract": "Extracción",
    "idx-stat-ai":      "Campos extraídos por IA",
    "idx-stat-match":   "Comparación",
    "idx-card-title":   "Candidato a duplicado",
    "idx-card-firstseen": "Primera vez visto",
    "idx-card-source":  "Fuente",
    "idx-card-matches": "Coincidencias",
    "idx-note-title":   "Qué guarda el sistema",
    "idx-note-raw":     "Contenido bruto de la página para trazabilidad",
    "idx-note-ai":      "Campos organizados por IA en tabla separada",
    "idx-note-ts":      "Marcas de tiempo del rastreador para primera/última visita",
    "idx-step1-title":  "Rastrear y guardar",
    "idx-step1-desc":   "Guardamos HTML bruto, texto y JSON-LD estructurado en MySQL.",
    "idx-step2-title":  "La IA organiza",
    "idx-step2-desc":   "La IA extrae precio, habitaciones, m² y dirección con confianza.",
    "idx-step3-title":  "Encontrar duplicados",
    "idx-step3-desc":   "SQL filtra candidatos y la IA clasifica las mejores coincidencias.",
    "idx-trust1-h2":    "Diseñado para demostrar, no para ser perfecto.",
    "idx-trust1-p":     "El MVP favorece la exhaustividad. Mostramos duplicados probables con una puntuación de confianza y una marca de tiempo de \"primera vez visto\".",
    "idx-trust1-tag1":  "5 sitios objetivo",
    "idx-trust1-tag2":  "Extracción asistida por IA",
    "idx-trust1-tag3":  "Similitud vectorial",
    "idx-trust2-h2":    "Qué ves",
    "idx-trust2-p":     "Una lista limpia de candidatos, con el anuncio más antiguo destacado y evidencia de los datos fuente brutos.",
    "idx-trust2-tag1":  "Primera vez visto",
    "idx-trust2-tag2":  "Puntuación de coincidencia",
    "idx-trust2-tag3":  "Transparencia de la fuente",
    "idx-cta-h2":       "Crea una cuenta y prueba el flujo de búsqueda de duplicados",
    "idx-cta-lead":     "Empieza con la página de usuario y una entrada de ejemplo. La versión actual se centra en la evidencia del rastreador, la extracción por IA y la visibilidad del administrador.",
    "footer-mvp":       "FirstListing — Proyecto escolar",
    "footer-privacy":   "Política de Privacidad",
    "footer-legal":     "Aviso Legal",

    // login.php
    "login-h1":      "Iniciar sesión",
    "login-sub":     "Introduce tus credenciales de usuario.",
    "label-username":"Usuario",
    "label-password":"Contraseña",
    "login-btn":     "Iniciar sesión",
    "login-create":  "Crear cuenta",

    // register.php
    "reg-h1":           "Crear cuenta",
    "reg-sub":          "Registro sencillo para usuarios de FirstListing.",
    "reg-hint":         "Usa letras/números más . _ -",
    "label-email":      "Email (opcional)",
    "label-role":       "Rol",
    "reg-btn":          "Registrarse",
    "reg-have-account": "¿Ya tienes una cuenta?",

    // user.php
    "usr-eyebrow":      "Área de usuario · Comprobador de duplicados",
    "usr-h1":           "Pega la URL de un anuncio para encontrar copias en otros portales.",
    "usr-lead":         "El rastreador obtiene la página, la IA extrae los campos estructurados y los comparamos con todos los anuncios de nuestra base de datos para encontrar posibles duplicados.",
    "usr-meta-user":    "Usuario:",
    "usr-meta-role":    "Rol:",
    "usr-note-title":   "Cómo funciona",
    "usr-note-1":       "1. El rastreador obtiene la URL",
    "usr-note-2":       "2. La IA extrae precio, m², habitaciones…",
    "usr-note-3":       "3. SQL puntúa cada anuncio de la BD",
    "usr-note-4":       "4. La IA compara descripciones",
    "usr-card1-top":    "Comprobación de duplicados",
    "usr-card1-h2":     "Pega la URL del anuncio",
    "usr-card1-sub":    "Pega la URL de una propiedad. La comprobación tarda unos 10–20 segundos.",
    "usr-label-url":    "URL del anuncio",
    "usr-btn-check":    "Comprobar duplicados",
    "usr-btn-back":     "Volver al inicio",
    "usr-card2-top":    "Estado de la búsqueda",
    "usr-status-crawled": "URL rastreada",
    "usr-status-parsed":  "IA analizada",
    "usr-status-dupes":   "Candidatos a duplicado encontrados",
    "usr-card3-top":    "Datos extraídos del anuncio",
    "usr-card3-h2":     "Campos estructurados",
    "usr-field-url":    "URL",
    "usr-field-domain": "Dominio",
    "usr-field-title":  "Título",
    "usr-field-price":  "Precio",
    "usr-field-sqm":    "M²",
    "usr-field-plotsqm":"M² parcela",
    "usr-field-rooms":  "Habitaciones",
    "usr-field-baths":  "Baños",
    "usr-field-type":   "Tipo",
    "usr-field-listing":"Anuncio",
    "usr-field-address":"Dirección",
    "usr-field-ref":    "Referencia",
    "usr-field-agent":  "Agente",
    "usr-field-firstseen": "Primera vez visto",
    "usr-field-lastseen":  "Última vez visto",
    "usr-card4-top":    "Posibles duplicados",
    "usr-th-score":     "Puntuación",
    "usr-th-domain":    "Dominio",
    "usr-th-title":     "Título",
    "usr-th-price":     "Precio",
    "usr-th-sqm":       "M²",
    "usr-th-rooms":     "Habitaciones",
    "usr-th-firstseen": "Primera vez visto",
    "usr-th-ai":        "Resultado IA",
    "usr-th-url":       "URL",
    "usr-hint-score":   "Puntuación = suma de campos coincidentes (máx. 17). Pasa el ratón sobre la insignia de IA para ver el motivo.",
    "usr-no-dupes":     "Ningún candidato superó 10 puntos. El anuncio puede ser único en nuestra base de datos.",
    "usr-hint-submit":  "Envía una URL arriba para ver candidatos a duplicado aquí.",
    "usr-account-top":  "Cuenta",
    "usr-account-sub":  "Área de usuario para comprobar duplicados.",
    "usr-field-userid": "ID de usuario",
    "usr-field-email":  "Email",
    "usr-field-role":   "Rol",
    "usr-notes-top":    "Notas",
    "usr-notes-h2":     "Recordatorio del alcance del MVP",
    "usr-note-li1":     "\"Primera vez visto\" = primera vez rastreado, no cuándo se publicó",
    "usr-note-li2":     "No es una afirmación legal de propiedad original",
    "usr-note-li3":     "Los mejores resultados dependen de la cobertura del rastreador",
    "usr-note-li4":     "Las descripciones de IA solo se comparan para las 5 mejores coincidencias SQL",
    "usr-footer":       "FirstListing — Área de usuario",

    // how.php
    "how-eyebrow":      "Cómo funciona el MVP",
    "how-h1":           "Del rastreo bruto de datos a la detección de duplicados organizada por IA.",
    "how-lead":         "Esta página muestra el flujo técnico del proyecto: rastrear, guardar, organizar, filtrar y comparar. El objetivo es la transparencia y un pipeline claro de prueba de concepto.",
    "how-meta1":        "Almacenamiento bruto MySQL",
    "how-meta2":        "Extracción de campos por IA",
    "how-meta3":        "Comparación de descripciones por IA",
    "how-note-title":   "Enfoque del pipeline",
    "how-note-1":       "Evidencia bruta trazable primero",
    "how-note-2":       "La IA ayuda a organizar, no a inventar",
    "how-note-3":       "\"Primera vez visto\" se basa en el rastreador",
    "how-step1-title":  "Rastrear y guardar",
    "how-step1-desc":   "Rastreamos (leemos) un pequeño conjunto de sitios y guardamos HTML, texto y JSON-LD en MySQL.",
    "how-step2-title":  "La IA organiza",
    "how-step2-desc":   "La IA extrae campos estructurados (precio, m², habitaciones, dirección).",
    "how-step3-title":  "Filtro SQL",
    "how-step3-desc":   "Generamos pares candidatos usando reglas simples como área + rango de precio.",
    "how-step4-title":  "Comparación IA",
    "how-step4-desc":   "La IA compara descripciones de anuncios para confirmar duplicados reales.",
    "how-step5-title":  "Primera vez visto",
    "how-step5-desc":   "Guardamos la marca de tiempo más antigua como aproximación al anuncio original.",
    "how-cta-eyebrow":  "Siguiente paso",
    "how-cta-h2":       "Descubre por qué este flujo es útil en la práctica",
    "how-cta-lead":     "El valor no es solo la extracción por IA. Es la combinación de evidencia, marcas de tiempo y lógica de coincidencia.",
    "how-cta-btn":      "Por qué ayuda",

    // helps.php
    "hlp-eyebrow":      "Por qué ayuda",
    "hlp-h1":           "Señal útil, datos más limpios y mejor transparencia en un solo flujo.",
    "hlp-lead":         "FirstListing ayuda a reducir el ruido de duplicados y hacer las comparaciones más fáciles de auditar. Es especialmente útil como proyecto escolar porque el rastro de evidencia es visible.",
    "hlp-meta1":        "Reducción de duplicados",
    "hlp-meta2":        "Confianza + visibilidad de la fuente",
    "hlp-meta3":        "Señal de marca de tiempo del rastreador",
    "hlp-note-title":   "Qué explica esta página",
    "hlp-note-1":       "Por qué importa agrupar duplicados",
    "hlp-note-2":       "Por qué los datos brutos mejoran la confianza",
    "hlp-note-3":       "Por qué \"primera vez visto\" es útil en un MVP",
    "hlp-card1-h2":     "Reducir el ruido de duplicados",
    "hlp-card1-p":      "La misma propiedad aparece en varios agentes. Agrupamos esos anuncios para que veas un resultado limpio.",
    "hlp-card1-tag1":   "Menos duplicados",
    "hlp-card1-tag2":   "Búsqueda más limpia",
    "hlp-card1-tag3":   "Decisiones más rápidas",
    "hlp-card2-h2":     "Transparencia por diseño",
    "hlp-card2-p":      "Cada coincidencia está respaldada por datos brutos y una puntuación de confianza. Puedes inspeccionar las fuentes para entender por qué se vincularon los elementos.",
    "hlp-card2-tag1":   "Coincidencias explicables",
    "hlp-card2-tag2":   "Rastro de auditoría",
    "hlp-card2-tag3":   "Acceso a datos brutos",
    "hlp-card3-h2":     "Señal de primera vez visto",
    "hlp-card3-p":      "Rastreamos cuándo nuestro rastreador vio un anuncio por primera vez. Es una aproximación práctica a la publicación más antigua en un proyecto escolar.",
    "hlp-card3-tag1":   "Marca de tiempo de primera vez visto",
    "hlp-card3-tag2":   "Aproximación al origen",
    "hlp-card3-tag3":   "Apto para MVP",
    "hlp-card4-h2":     "Camino escalable",
    "hlp-card4-p":      "Comienza con sitios fiables, luego escala usando una combinación de datos estructurados, extracción por API y clasificación por IA.",
    "hlp-card4-tag1":   "Extracción híbrida",
    "hlp-card4-tag2":   "Organización por IA",
    "hlp-card4-tag3":   "Preparado para el futuro",
    "hlp-cta-eyebrow":  "Pruébalo",
    "hlp-cta-h2":       "Crea una cuenta y prueba el flujo de usuario",
    "hlp-cta-lead":     "El MVP actual es más fuerte como demostración técnica: rastreo, almacenamiento de evidencia, extracción por IA y revisión del administrador.",

    // legal.php
    "legal-eyebrow":    "Información legal",
    "legal-h1":         "Aviso Legal",
    "legal-lead":       "Identificación del titular, condiciones de uso, propiedad intelectual y legislación aplicable.",
    "legal-s1-title":   "1. Identificación del titular",
    "legal-s1-intro":   "En cumplimiento del artículo 10 de la Ley 34/2002 de Servicios de la Sociedad de la Información (LSSI-CE), se facilitan los siguientes datos identificativos:",
    "legal-s1-li1":     "Nombre del sitio web: FirstListing",
    "legal-s1-li2":     "Titular: Oscar (estudiante de DAW — Desarrollo de Aplicaciones Web)",
    "legal-s1-li3":     "Naturaleza: Proyecto escolar — no es un servicio comercial",
    "legal-s1-li4":     "Contacto: contact@firstlisting.es",
    "legal-s2-title":   "2. Finalidad del sitio web",
    "legal-s2-p":       "FirstListing es una aplicación web de demostración desarrollada como proyecto escolar MVP. Su finalidad es demostrar la detección de anuncios inmobiliarios duplicados mediante rastreo web, extracción de campos asistida por IA y puntuación de similitud. No es un servicio comercial ni está destinado a uso en producción.",
    "legal-s3-title":   "3. Condiciones de uso",
    "legal-s3-p":       "Al acceder y utilizar este sitio web, el usuario se compromete a hacerlo únicamente con fines lícitos y de manera que no vulnere los derechos de terceros. Está prohibido el rastreo automatizado sin autorización previa. El desarrollador se reserva el derecho de modificar, suspender o cancelar el acceso al sitio en cualquier momento y sin previo aviso.",
    "legal-s4-title":   "4. Propiedad intelectual",
    "legal-s4-p":       "Todo el contenido, código fuente, diseño y materiales de este sitio web son propiedad intelectual del desarrollador, salvo indicación contraria. Las bibliotecas y herramientas de terceros se utilizan bajo sus respectivas licencias de código abierto. Queda prohibida la reproducción, distribución o comunicación pública de cualquier parte de este sitio sin autorización previa por escrito.",
    "legal-s5-title":   "5. Limitación de responsabilidad",
    "legal-s5-p":       "Este sitio web es un proyecto de estudiante proporcionado únicamente con fines de demostración educativa. El desarrollador no ofrece garantías sobre la exactitud, integridad o idoneidad del contenido para ningún fin concreto. El desarrollador no será responsable de los daños derivados del uso o la imposibilidad de uso de este sitio web.",
    "legal-s6-title":   "6. Enlaces a sitios de terceros",
    "legal-s6-p":       "Este sitio web puede contener enlaces a sitios web de terceros. El desarrollador no es responsable del contenido ni de las prácticas de privacidad de dichos sitios. Los enlaces se proporcionan únicamente por conveniencia.",
    "legal-s7-title":   "7. Legislación aplicable y jurisdicción",
    "legal-s7-p":       "Este sitio web y sus condiciones se rigen por la legislación española. Para cualquier controversia relacionada con este sitio, las partes se someten a la jurisdicción de los tribunales españoles, salvo que resulte aplicable otra jurisdicción por imperativo legal.",
    "legal-updated":    "Última actualización: marzo de 2026",

    // privacy.php
    "priv-eyebrow":     "Privacidad",
    "priv-h1":          "Política de Privacidad",
    "priv-lead":        "Cómo FirstListing recoge, usa y protege tus datos personales.",
    "priv-s1-title":    "1. Responsable del tratamiento",
    "priv-s1-p":        "FirstListing está desarrollado por Oscar, estudiante de primer año de DAW (Desarrollo de Aplicaciones Web). Se trata de un proyecto escolar, no de una entidad comercial. Contacto: contact@firstlisting.es",
    "priv-s2-title":    "2. Datos que recogemos",
    "priv-s2-intro":    "Al registrarte o utilizar FirstListing, podemos recoger los siguientes datos personales:",
    "priv-s2-li1":      "Nombre de usuario (obligatorio para crear la cuenta)",
    "priv-s2-li2":      "Dirección de correo electrónico (opcional, solo para recuperación de cuenta)",
    "priv-s2-li3":      "Contraseña (almacenada como hash bcrypt — nunca en texto plano)",
    "priv-s2-li4":      "Historial de búsquedas (URLs enviadas para comprobar duplicados)",
    "priv-s2-li5":      "Datos de uso (número de búsquedas realizadas por mes)",
    "priv-s3-title":    "3. Cómo recogemos tus datos",
    "priv-s3-p":        "Recogemos tus datos directamente a través de los formularios que rellenas en nuestra web (registro, inicio de sesión). No utilizamos cookies de seguimiento ni perfilado. No recogemos datos de fuentes de terceros.",
    "priv-s4-title":    "4. Finalidad y base legal",
    "priv-s4-intro":    "Tratamos tus datos personales con las siguientes finalidades:",
    "priv-s4-li1":      "Gestión de cuentas — base legal: art. 6.1.b RGPD (ejecución de contrato)",
    "priv-s4-li2":      "Prestación del servicio (comprobación de duplicados) — base legal: art. 6.1.b RGPD (ejecución de contrato)",
    "priv-s4-li3":      "Límites de uso (cuota mensual de búsquedas) — base legal: art. 6.1.b RGPD (ejecución de contrato)",
    "priv-s5-title":    "5. Conservación de datos",
    "priv-s5-p":        "Tus datos se conservan mientras tu cuenta esté activa. Si solicitas la eliminación de tu cuenta, todos tus datos personales serán suprimidos en un plazo de 30 días. El historial de búsquedas (URLs) se conserva para el funcionamiento del pipeline de detección de duplicados.",
    "priv-s6-title":    "6. Cesión de datos a terceros",
    "priv-s6-p1":       "No vendemos, alquilamos ni compartimos tus datos personales con terceros.",
    "priv-s6-p2":       "El único servicio de terceros que utilizamos es la API de OpenAI, para la extracción de campos asistida por IA y la comparación de descripciones. Las URLs que envías para la comprobación de duplicados se transmiten a OpenAI como parte de este procesamiento. Se aplica la política de privacidad de OpenAI (openai.com/policies/privacy-policy).",
    "priv-s7-title":    "7. Tus derechos",
    "priv-s7-intro":    "En virtud del RGPD y la LOPD-GDD tienes los siguientes derechos:",
    "priv-s7-li1":      "Derecho de acceso (art. 15 RGPD)",
    "priv-s7-li2":      "Derecho de rectificación (art. 16 RGPD)",
    "priv-s7-li3":      "Derecho de supresión / derecho al olvido (art. 17 RGPD)",
    "priv-s7-li4":      "Derecho a la limitación del tratamiento (art. 18 RGPD)",
    "priv-s7-li5":      "Derecho a la portabilidad de los datos (art. 20 RGPD)",
    "priv-s7-li6":      "Derecho de oposición (art. 21 RGPD)",
    "priv-s7-contact":  "Para ejercer cualquiera de estos derechos, contacta con nosotros en:",
    "priv-s8-title":    "8. Derecho a reclamar ante la autoridad de control",
    "priv-s8-p":        "Si consideras que tus derechos en materia de protección de datos han sido vulnerados, puedes presentar una reclamación ante la Agencia Española de Protección de Datos (AEPD) en www.aepd.es.",
    "priv-s9-title":    "9. Seguridad",
    "priv-s9-p":        "Aplicamos medidas técnicas y organizativas adecuadas para proteger tus datos frente a pérdidas accidentales, accesos no autorizados, divulgación, alteración o destrucción. Las contraseñas se almacenan con hash bcrypt.",
    "priv-s10-title":   "10. Cambios en esta política",
    "priv-s10-p":       "Podemos actualizar esta política periódicamente. La fecha que aparece al final de esta página indica cuándo fue revisada por última vez. Te recomendamos consultar esta página con regularidad.",
    "priv-updated":     "Última actualización: marzo de 2026"
}

// Read the saved language from localStorage, default to English
var currentLang = localStorage.getItem('lang') || 'en'

// Save each translatable element's current (English) text before touching anything.
// This snapshot is used when switching back to English.
function saveEnText() {
    document.querySelectorAll('[lang-change]').forEach(function(el) {
        el.setAttribute('data-en', el.textContent.trim())
    })
}

// Swap text on all translatable elements to the chosen language
function applyLang(lang) {
    document.querySelectorAll('[lang-change]').forEach(function(el) {
        var key = el.getAttribute('lang-change')
        if (lang === 'es' && es[key]) {
            el.textContent = es[key]
        } else {
            // Restore the original English text from the snapshot
            el.textContent = el.getAttribute('data-en')
        }
    })

    // Show the OTHER language on the toggle button
    var btn = document.getElementById('lang-toggle')
    if (btn) {
        btn.textContent = lang === 'es' ? 'EN' : 'ES'
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Step 1: snapshot English text (must happen before any swap)
    saveEnText()

    // Step 2: apply saved language
    applyLang(currentLang)

    // Toggle button switches language and saves preference
    var btn = document.getElementById('lang-toggle')
    if (btn) {
        btn.addEventListener('click', function() {
            currentLang = currentLang === 'en' ? 'es' : 'en'
            localStorage.setItem('lang', currentLang)
            applyLang(currentLang)
        })
    }
})
