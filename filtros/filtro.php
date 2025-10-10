<?php
/**
 * Template Part: Filtro (modal + chips)
 * Uso: get_template_part('filtros/filtro');
 */
defined('ABSPATH') || exit;

// ---- 1) Contexto: cluster actual ----
$cluster_id = intval($_GET['cluster_id'] ?? 0);

// IDs de 'apartamento' para acotar términos
$cluster_posts = get_posts([
  'post_type'      => 'apartamento',
  'posts_per_page' => -1,
  'fields'         => 'ids',
  'meta_key'       => 'cluster_asociado',
  'meta_value'     => $cluster_id,
]);

// Términos usados por esas unidades
$niveles    = get_terms([ 'taxonomy' => 'nivel',     'hide_empty' => false, 'object_ids' => $cluster_posts ]);
$vistas     = get_terms([ 'taxonomy' => 'vista',     'hide_empty' => false, 'object_ids' => $cluster_posts ]);
$tipologias = get_terms([ 'taxonomy' => 'tipologia', 'hide_empty' => false, 'object_ids' => $cluster_posts ]);

// ---- 2) Derivar "Habitaciones" y "Superficie" (metrajes) desde ACF en tipologia ----
$habitaciones_disponibles = [];
$superficies_disponibles  = [];

foreach ($tipologias as $tip) {
  $term_key = $tip->taxonomy . '_' . $tip->term_id; // ACF: taxonomy_termId
  $habit = get_field('cantidad_de_habitaciones', $term_key);
  $sup   = get_field('superficie',                $term_key);

  if ($habit !== false && $habit !== '') $habitaciones_disponibles[] = intval($habit);
  if ($sup   !== false && $sup   !== '') $superficies_disponibles[]  = floatval($sup);
}
$habitaciones_disponibles = array_values(array_unique($habitaciones_disponibles));
sort($habitaciones_disponibles);

$superficies_disponibles = array_values(array_unique($superficies_disponibles));
sort($superficies_disponibles);

// límites para el stepper (si no hay datos, fallback 1–6)
$habit_min = $habitaciones_disponibles ? min($habitaciones_disponibles) : 1;
$habit_max = $habitaciones_disponibles ? max($habitaciones_disponibles) : 6;

// límites para metraje
$metrajes = $superficies_disponibles ?: [20.0, 2000.0]; // fallback
$met_min  = min($metrajes);
$met_max  = max($metrajes);
?>

<!-- ===================== MODAL FILTRO ===================== -->
<div id="filtro-popup" class="fixed inset-0 z-50 hidden bg-black/20 backdrop-blur-sm font-fuente_primaria">
  <div class="min-h-screen w-full flex items-center justify-center p-4">
    <div id="modal-card" class="bg-white rounded-2xl shadow-2xl w-[420px] max-w-[90vw] p-6 relative border border-gray-300/50 shadow-xl backdrop-blur-sm">
      <button id="cerrar-filtro" class="absolute top-2 right-4 text-gray-400 hover:text-black text-2xl font-bold fa-solid fa-xmark"></button>

      <h2 class="text-center text-xl font-bold text-primary mb-1">Filtrar unidades</h2>
      <p class="text-center text-sm text-gray-500 mb-8">¡Encontrá la unidad ideal que se adapte a tu gusto!</p>

      <form id="form-filtro" method="POST" action="<?= esc_url(admin_url('admin-ajax.php')) ?>" class="filtro-form">
        <input type="hidden" name="action" value="filtrar_apartamentos_ajax">
        <input type="hidden" name="cluster_id" value="<?= esc_attr($cluster_id) ?>">
   <hr class="my-2">

        <!-- Recámaras -->
<div class="space-y-2">
  
  <div id="habit-stepper"
       class="stepper w-full  rounded  py-2 flex items-center justify-between"
       data-min="<?= esc_attr($habit_min) ?>"
       data-max="<?= esc_attr($habit_max) ?>">
    <div class="flex items-center gap-2">
      <span class="material-symbols-outlined text-[18px] text-gray-500">bed</span>
      <label class="block text-sm font-medium text-gray-700">Recámaras</label>
    </div>
    <div class="flex items-center">
      <button type="button" class="stepper-btn minus rounded-full" aria-label="Disminuir" title="Disminuir">
        <span class="material-symbols-outlined">remove</span>
      </button>
      <div class="stepper-value" aria-live="polite" data-empty="1">
        <span class="text-sm text-gray-400">/</span>
      </div>
      <button type="button" class="stepper-btn plus rounded-full " aria-label="Aumentar" title="Aumentar">
        <span class="material-symbols-outlined">add</span>
      </button>
    </div>
    <!-- input oculto que viaja al servidor -->
    <input type="hidden" name="habitaciones" value="">
  </div>
</div>
   <hr class="my-2">


        <!-- Metraje: Rango discreto (min–max exactos) -->
        <div class="space-y-3" id="metraje-block"
             data-points='<?= json_encode($metrajes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>'>
        <div class="flex items-center gap-2">
            <span class="material-symbols-outlined text-[18px] text-gray-500">crop_free</span>
            <label class="block text-sm font-medium text-gray-700">Metraje</label>
          </div>
          <div class="range" id="metraje-range">
            <div class="range-track">
              <div class="range-fill"></div>
              <!-- marks y handles los inyecta JS -->
            </div>

            <div class="range-side-labels">
              <div class="range-side range-side-min">
                <span class="range-side-title">Min</span>
                <span class="range-pill" id="metraje-pill-min"><?= esc_html($met_min) ?> m²</span>
              </div>
              <div class="range-side range-side-max">
                <span class="range-side-title">Máx</span>
                <span class="range-pill" id="metraje-pill-max"><?= esc_html($met_max) ?> m²</span>
              </div>
            </div>
          </div>

          <!-- inputs reales (por defecto: rango completo) -->
          <input type="hidden" name="superficie_min" id="superficie-min" value="<?= esc_attr($met_min) ?>">
          <input type="hidden" name="superficie_max" id="superficie-max" value="<?= esc_attr($met_max) ?>">
          <!-- IMPORTANTE: muchos backends esperan 'superficie' → mandamos CSV min,max en el request JS -->
          <input type="hidden" name="superficie" id="superficie-csv" value="<?= esc_attr("{$met_min},{$met_max}") ?>">
        </div>

   <hr class="my-2">
      
   <!-- Piso -->
<div class="space-y-2">
  <div class="flex items-center justify-between">
    <div class="flex items-center gap-2">
      <span class="material-symbols-outlined text-[18px] text-gray-500">swap_vert</span>
      <label class="block text-sm font-medium text-gray-700">Piso</label>
    </div>
    <select name="nivel" class="short-dd w-auto min-w-[140px] text-gray-500">
      <option value="">Sin asignar</option>
      <?php foreach ($niveles as $nivel): ?>
        <option value="<?= esc_attr($nivel->slug) ?>"><?= esc_html($nivel->name) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
</div>

 <hr class="my-2">



        <!-- Vista: chips multiselección (sin chips resumen externos) -->
        <div class="space-y-2">
        <div class="flex items-center gap-2">
              <span class="material-symbols-outlined text-[18px] text-gray-500">landscape_2</span>
              <label class="block text-sm font-medium text-gray-700">Vistas</label>
            </div>
          <!-- hidden sincronizado con chips (CSV: slug,slug,...) -->
          <input type="hidden" name="vista" id="vista-hidden" value="">
          <div id="vista-chips" class="flex flex-wrap gap-2">
            <?php foreach ($vistas as $vista): ?>
              <button type="button"
                      class="chip-vista"
                      data-slug="<?= esc_attr($vista->slug) ?>"
                      aria-pressed="false">
                <?= esc_html($vista->name) ?>
              </button>
            <?php endforeach; ?>
          </div>
        </div>

        <hr class="my-2">

 

     <div class="flex items-center gap-3 mt-4">
  <!-- Botón limpiar (izquierda) -->
  <button type="button" id="limpiar-filtros" class="w-auto   text-text p-3 rounded text-sm hover:bg-white/90 transition hidden">
    <i class="fa-solid fa-broom"></i> Limpiar filtros
  </button>

  <!-- Botón buscar (siempre pegado a la derecha) -->
  <button type="submit" class="w-auto ml-auto text-text p-3 rounded border border-gray-200  text-sm hover:bg-white/90 transition" id="btn-encontrar">
    <i class="fa-solid fa-magnifying-glass"></i> Encontrar unidades
  </button>
</div>
      </form>
    </div>
  </div>
</div>

<style>
  /* Caja del modal */
  #filtro-popup .bg-white {
    width: 420px !important;
    max-width: 90vw !important;
    box-shadow:
      0 25px 50px -12px rgba(0, 0, 0, 0.15),
      0 0 0 1px rgba(255, 255, 255, 0.05) !important;
  }
  .filtro-form > * { margin-bottom: 15px !important; }
  .filtro-form > *:last-child { margin-bottom: 0 !important; }

  /* Configuración inicial del modal para animaciones */
  #modal-card {
    transform-origin: center;
    position: relative;
    overflow: visible;
  }

  /* Animaciones para mobile */
  @media (max-width: 767px) {
    #modal-card.animate-fade-in-up {
      animation: fade-in-up 0.5s ease-out both;
    }
    
    #modal-card.animate-fade-out-down {
      animation: fade-out-down 0.5s ease-out both;
    }
  }

  /* Animaciones para desktop */
  @media (min-width: 768px) {
    #modal-card.animate-fade-in {
      animation: fade-in .3s linear both;
    }
    
    #modal-card.animate-fade-out {
      animation: fade-out .3s linear both;
    }
  }

  /* ===================== CUSTOM SELECT (UI) ===================== */
  .cs { position: relative; }
  .cs-hidden-select { display: none !important; }
  .cs-button{
    width: 100%; background:#fff; border:1px solid #d1d5db7c; border-radius:.375rem;
    padding:.5rem .75rem; font-size:.875rem; display:flex; align-items:center; justify-content:space-between; gap:.75rem; cursor:pointer;
  }
  .cs-button:focus{ outline:2px solid #3B82F6; outline-offset:2px; }
  .cs-leading{ display:flex; align-items:center; gap:.5rem; min-width:0; }
  .cs-label{ white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
  .cs-arrow{ opacity:.7; }
  .cs-menu{
    position:absolute; left:0; right:0; top:calc(100% + 6px); background:#fff; border:1px solid rgba(0,0,0,.26);
    border-radius:.5rem; box-shadow:0 10px 20px rgba(0,0,0,.08); padding:6px; z-index:60; max-height:168px; overflow:auto; display:none;
  }
  .cs.open .cs-menu{ display:block; }
  .cs-opt{ width:100%; text-align:left; background:transparent; border:0; padding:8px 10px; border-radius:8px; font-size:.875rem; cursor:pointer; display:flex; align-items:center; justify-content:space-between; }
  .cs-opt:hover{ background:#EEF2FF; }
  .cs-opt[aria-selected="true"]{ background:#F3F4F6; }

  /* ===================== STEPPER Recámaras ===================== */
  .stepper { user-select: none; }
  .stepper .stepper-btn {
    width: 33px; height: 33px;
    display: inline-flex; align-items: center; justify-content: center;
    border-radius: 100%; border: 1px solid #E5E7EB; background: #FFF;
  }
  .stepper .stepper-value { min-width: 44px; text-align: center; margin: 0 6px; font-weight: 600; }

  /* ===================== Chips de Vista ===================== */
  .chip-vista{
    background:#fff; color:#111827;
    border:1px solid #D1D5DB; border-radius:9999px;
    padding:.25rem .6rem; font-size:.875rem; line-height:1.25rem;
    display:inline-flex; align-items:center; gap:.25rem;
    transition: box-shadow .15s ease, border-color .15s ease, transform .05s ease;
  }
  .chip-vista:hover{ box-shadow:0 1px 2px rgba(0,0,0,.08); }
  .chip-vista[aria-pressed="true"]{
    border-color:#0c0f13b5; background:#0c0f13b5; color:#fff;
    box-shadow:0 0 0 2px rgba(17,24,39,.08); transform: translateY(-1px);
  }

  /* ===================== Rango discreto Metraje ===================== */
  .range { width: 100%; }
  .range-track{
    position: relative; width: 100%; height: 8px; border-radius: 9999px;
    background: #E5E7EB; overflow: visible;
  }
  .range-fill{
    position: absolute; height: 100%; left: 0; right: 0; background: #60A5FA; border-radius: 9999px;
    transform-origin: left center; pointer-events: none;
  }
  .range-mark{
    position:absolute; top:50%; transform: translate(-50%,-50%);
    width: 8px; height: 8px; border-radius: 50%; background: #D1D5DB;
    border: 2px solid #fff; box-shadow: 0 0 0 1px rgba(0,0,0,.08);
    cursor: pointer;
  }
  .range-mark.active{ background:#60A5FA; }
  .range-handle{
    position:absolute; top:50%; transform: translate(-50%,-50%);
    width: 24px; height: 24px; border-radius: 50%; background: #fff;
    border: 1px solid #D1D5DB; box-shadow: 0 2px 6px rgba(0,0,0,.12);
    cursor: grab;
  }
  .range-handle:active{ cursor: grabbing; }
  .range-side-labels{
    margin-top: 12px; display:flex; justify-content:space-between; align-items:center;
  }
  .range-side{ display:flex; align-items:center; gap:8px; }
  .range-side-title{ color:#6B7280; font-size: .85rem; }
  .range-pill{
    display:inline-flex; align-items:center; justify-content:center;
    min-width: 68px; padding: .2rem .6rem; border:1px solid #D1D5DB; border-radius: 9999px; background:#fff;
    font-size:.85rem;
  }
</style>

<script>
document.addEventListener("DOMContentLoaded", () => {
  // --- Constantes / contexto ---
  const FILTER_KEYS = ['nivel','habitaciones','superficie','vista']; // en URL usaremos 'superficie' = "min,max"

  // --- DOM refs ---
  const popup              = document.getElementById("filtro-popup");
  const modalCard          = document.getElementById("modal-card");
  const form               = document.getElementById("form-filtro");
  const btnBuscar          = document.getElementById("btn-encontrar");
  const btnLimpiar         = document.getElementById("limpiar-filtros");
  const btnCerrar          = document.getElementById("cerrar-filtro");
  const resultado          = document.getElementById("grid-unidades");

  // Stepper
  const habitWrap   = document.getElementById('habit-stepper');
  const habitInput  = form?.querySelector('input[name="habitaciones"]');
  const habitValEl  = habitWrap?.querySelector('.stepper-value');
  const habitBtnMin = habitWrap?.querySelector('.stepper-btn.minus');
  const habitBtnPls = habitWrap?.querySelector('.stepper-btn.plus');
  const HABIT_MIN   = parseInt(habitWrap?.dataset.min || '1', 10);
  const HABIT_MAX   = parseInt(habitWrap?.dataset.max || '6', 10);

  // Rango Metraje
  const metBlock     = document.getElementById('metraje-block');
  const POINTS       = JSON.parse(metBlock?.dataset.points || '[]'); // valores exactos
  const rangeEl      = document.getElementById('metraje-range');
  const pillMin      = document.getElementById('metraje-pill-min');
  const pillMax      = document.getElementById('metraje-pill-max');
  const inputSMin    = document.getElementById('superficie-min');
  const inputSMax    = document.getElementById('superficie-max');
  const inputSCsv    = document.getElementById('superficie-csv');

  // Vistas (chips)
  const vistaHidden = document.getElementById('vista-hidden');
  const chipsWrap   = document.getElementById('vista-chips');
  const chipNodes   = [...(chipsWrap?.querySelectorAll('.chip-vista') || [])];
  let   vistaSet    = new Set();

  // Abridores de modal
  const btnAbrirDesktop = document.getElementById("btn-filtrar");
  const btnAbrirMobile  = document.getElementById("btn-filtrar-mobile");

  // Badge contador en el archive (solo se actualiza al aplicar)
  const badgeNodes = [
    document.getElementById("filtros-count"),
    document.getElementById("filtros-count-mobile")
  ].filter(Boolean);

  // Estado aplicado
  let appliedState = null;
  let isApplied = badgeNodes.some(el => !el.classList.contains('hidden'));

  // ===== FUNCIONES DE ANIMACIÓN =====
  
  // Función para detectar si es mobile
  function isMobile() {
    return window.innerWidth < 768; // Ajusta el breakpoint según necesites
  }

  // Función para abrir modal con animación
  function openModal() {
    popup.classList.remove("hidden");
    popup.style.display = "flex";
    
    // Aplicar la animación según el dispositivo
    if (isMobile()) {
      modalCard.classList.remove('animate-fade-out-down', 'animate-fade-in', 'animate-fade-out');
      modalCard.classList.add('animate-fade-in-up');
    } else {
      modalCard.classList.remove('animate-fade-in-up', 'animate-fade-out-down', 'animate-fade-out');
      modalCard.classList.add('animate-fade-in');
    }
    
    if (appliedState) setDraftState(appliedState);
    else {
      renderHabitacionesUI();
      vistaSet = new Set((vistaHidden?.value || '').split(',').map(s=>s.trim()).filter(Boolean));
      chipNodes.forEach(ch => ch.setAttribute('aria-pressed', vistaSet.has(ch.dataset.slug) ? 'true' : 'false'));
      renderDiscreteRange();
      actualizarUIBotones();
    }
    actualizarConteoDinamico();
  }

  // Función para cerrar modal con animación
  function closeModal() {
    // Aplicar animación de salida según el dispositivo
    if (isMobile()) {
      modalCard.classList.remove('animate-fade-in-up', 'animate-fade-in', 'animate-fade-out');
      modalCard.classList.add('animate-fade-out-down');
    } else {
      modalCard.classList.remove('animate-fade-in-up', 'animate-fade-out-down', 'animate-fade-in');
      modalCard.classList.add('animate-fade-out');
    }
    
    // Esperar a que termine la animación antes de ocultar
    setTimeout(() => {
      popup.classList.add("hidden");
      popup.style.display = "none";
      resetDraftFilters();
      // Limpiar clases de animación
      modalCard.classList.remove('animate-fade-in', 'animate-fade-out', 'animate-fade-in-up', 'animate-fade-out-down');
    }, isMobile() ? 500 : 300); // 500ms para mobile, 300ms para desktop
  }

  // Función para cerrar modal después de aplicar filtros
  function closeModalAfterApply() {
    // Aplicar animación de salida según el dispositivo
    if (isMobile()) {
      modalCard.classList.remove('animate-fade-in-up', 'animate-fade-in', 'animate-fade-out');
      modalCard.classList.add('animate-fade-out-down');
    } else {
      modalCard.classList.remove('animate-fade-in-up', 'animate-fade-out-down', 'animate-fade-in');
      modalCard.classList.add('animate-fade-out');
    }
    
    // Esperar a que termine la animación antes de ocultar
    setTimeout(() => {
      popup.classList.add("hidden");
      popup.style.display = "none";
      // Limpiar clases de animación
      modalCard.classList.remove('animate-fade-in', 'animate-fade-out', 'animate-fade-in-up', 'animate-fade-out-down');
    }, isMobile() ? 500 : 300);
  }

  // ===== Helpers de estado =====
  function rangeDefaults() {
    return { smin: POINTS.length ? POINTS[0] : 0, smax: POINTS.length ? POINTS[POINTS.length-1] : 0 };
  }
  const getDraftState = () => {
    const rdef = rangeDefaults();
    const smin = parseFloat(inputSMin?.value || rdef.smin);
    const smax = parseFloat(inputSMax?.value || rdef.smax);
    return {
      nivel:        form?.nivel?.value || "",
      habitaciones: habitInput?.value || "",
      smin: isFinite(smin) ? smin : rdef.smin,
      smax: isFinite(smax) ? smax : rdef.smax,
      vista:        vistaHidden?.value || "" // CSV
    };
  };
  const setDraftState = (st = {}) => {
    const rdef = rangeDefaults();
    if ('nivel'        in st && form.nivel)        form.nivel.value        = st.nivel || "";
    if ('habitaciones' in st && habitInput)        habitInput.value        = st.habitaciones || "";
    const newSMin = ('smin' in st) ? (st.smin ?? rdef.smin) : (inputSMin?.value ?? rdef.smin);
    const newSMax = ('smax' in st) ? (st.smax ?? rdef.smax) : (inputSMax?.value ?? rdef.smax);
    if (inputSMin) inputSMin.value = newSMin;
    if (inputSMax) inputSMax.value = newSMax;
    inputSCsv.value = `${newSMin},${newSMax}`;

    if ('vista' in st && vistaHidden) {
      vistaHidden.value = st.vista || "";
      vistaSet = new Set((st.vista || "").split(',').map(s => s.trim()).filter(Boolean));
      chipNodes.forEach(ch => ch.setAttribute('aria-pressed', vistaSet.has(ch.dataset.slug) ? 'true' : 'false'));
    }
    // refrescar selects UI
    document.querySelectorAll('.cs').forEach(cs=>{
      const sel = cs.querySelector('select');
      if (sel) {
        const btnLbl = cs.querySelector('.cs-label');
        if (btnLbl) btnLbl.textContent = sel.selectedOptions?.[0]?.textContent || sel.options[0]?.textContent || 'Seleccionar';
        cs.querySelectorAll('.cs-opt').forEach(b=>{
          b.setAttribute('aria-selected', (b.dataset.value===sel.value) ? 'true' : 'false');
        });
      }
    });
    renderHabitacionesUI();
    renderDiscreteRange();
    actualizarUIBotones();
  };

  function countActiveFilters(stateObj) {
    const rdef = rangeDefaults();
    const st = stateObj || getDraftState();
    let n = 0;
    if (st.nivel) n++;
    if (st.habitaciones) n++;
    if (POINTS.length && (parseFloat(st.smin) > rdef.smin || parseFloat(st.smax) < rdef.smax)) n++;
    if (st.vista) n++;
    return n;
  }

  function actualizarUIBotones() {
    btnBuscar.disabled = false; // SIEMPRE habilitado
    btnBuscar.style.opacity = "1";

    const st = getDraftState();
    const rdef = rangeDefaults();
    const hasAny =
      !!st.nivel || !!st.habitaciones || !!st.vista ||
      (POINTS.length && (st.smin > rdef.smin || st.smax < rdef.smax));
    btnLimpiar.classList.toggle("hidden", !hasAny);
  }

  // ====== Construir FormData coherente para AJAX (incluye superficie CSV) ======
  function buildFormData() {
    const fd = new FormData(form);
    // forzar CSV de metraje
    const smin = inputSMin.value, smax = inputSMax.value;
    fd.set('superficie_min', smin);
    fd.set('superficie_max', smax);
    fd.set('superficie', `${smin},${smax}`); // compatibilidad con backend
    // vista CSV ya está en vistaHidden
    return fd;
  }

  async function actualizarConteoDinamico() {
    if (!form || !btnBuscar) return;
    try {
      const res = await fetch(form.getAttribute("action"), { method: "POST", body: buildFormData() });
      const json = await res.json();
      btnBuscar.innerHTML = `<i class="fa-solid fa-magnifying-glass"></i> Encontrar unidades (${json.total})`;
    } catch (err) { console.error("Error al actualizar el total dinámico:", err); }
  }

  function updateArchiveFilterBadgeApplied(stateApplied) {
    const n = countActiveFilters(stateApplied);
    badgeNodes.forEach(el => {
      if (!el) return;
      if (n > 0) { el.textContent = n; el.classList.remove("hidden"); }
      else { el.textContent = ""; el.classList.add("hidden"); }
    });
  }

  // URL (solo al aplicar). Superficie como "min,max"
  function syncUrlWithCurrentFilters() {
    const u = new URL(location.href);
    FILTER_KEYS.forEach(k => u.searchParams.delete(k));
    const st = getDraftState();
    if (st.nivel)        u.searchParams.set('nivel', st.nivel);
    if (st.habitaciones) u.searchParams.set('habitaciones', st.habitaciones);
    if (POINTS.length)   u.searchParams.set('superficie', `${st.smin},${st.smax}`);
    if (st.vista)        u.searchParams.set('vista', st.vista);
    u.searchParams.delete('pagina');
    history.replaceState({}, '', u.toString());
  }

  // ===================== Paginado AJAX (solo si ya se aplicaron) =====================
  function buildPaginationHTML(total, page, perPage) {
    const totalPages = Math.max(1, Math.ceil(total / perPage));
    if (totalPages <= 1) return "";

    const btnLink = "inline-flex items-center justify-center w-8 h-8 text-primary hover:bg-primary-100 rounded-full transition-colors focus:outline-none focus:ring-primary js-ajax-page";
    const btnCurr = "inline-flex items-center justify-center w-8 h-8 bg-primary text-white rounded-full transition";
    const btnDis  = "inline-flex items-center justify-center w-8 h-8 text-gray-300 rounded-full border border-gray-200";
    const dotsCls = "inline-flex items-center justify-center w-8 h-8 text-gray-500";

    const currentPage   = page;
    const postsPerPage  = perPage;
    const startItem     = ((currentPage - 1) * postsPerPage) + 1;
    const endItem       = Math.min(currentPage * postsPerPage, total);

    let html = '';
    html += '<nav id="pagination" class="grid w-full items-center gap-3 lg:grid-cols-[1fr_auto_1fr] mt-4 mb-4" aria-label="Navegación de páginas">';
    html += `<div class="order-2 lg:order-1 justify-self-center lg:justify-self-start text-sm text-gray-600 text-center lg:text-left lg:whitespace-nowrap">Mostrando ${startItem}-${endItem} de ${total} unidades</div>`;
    html += '<div class="order-1 lg:order-2 flex items-center space-x-2 justify-self-center max-w-full overflow-x-auto px-2">';

    if (currentPage > 1) {
      html += `<a href="#" class="${btnLink}" data-page="${currentPage-1}" aria-label="Página anterior"><i class="fa-solid fa-chevron-left text-xs" aria-hidden="true"></i></a>`;
    } else {
      html += `<span class="${btnDis}" aria-hidden="true"><i class="fa-solid fa-chevron-left text-xs"></i></span>`;
    }

    const range = 2;
    const start = Math.max(1, currentPage - range);
    const end   = Math.min(totalPages, currentPage + range);

    if (start > 1) {
      html += `<a href="#" class="${btnLink}" data-page="1" aria-label="Ir a la página 1">1</a>`;
      if (start > 2) html += `<span class="${dotsCls}" aria-hidden="true">…</span>`;
    }

    for (let i = start; i <= end; i++) {
      if (i === currentPage) html += `<span class="${btnCurr}" aria-current="page">${i}</span>`;
      else html += `<a href="#" class="${btnLink}" data-page="${i}" aria-label="Ir a la página ${i}">${i}</a>`;
    }

    if (end < totalPages) {
      if (end < totalPages - 1) html += `<span class="${dotsCls}" aria-hidden="true">…</span>`;
      html += `<a href="#" class="${btnLink}" data-page="${totalPages}" aria-label="Ir a la página ${totalPages}">${totalPages}</a>`;
    }

    if (currentPage < totalPages) {
      html += `<a href="#" class="${btnLink}" data-page="${currentPage+1}" aria-label="Página siguiente"><i class="fa-solid fa-chevron-right text-xs" aria-hidden="true"></i></a>`;
    } else {
      html += `<span class="${btnDis}" aria-hidden="true"><i class="fa-solid fa-chevron-right text-xs"></i></span>`;
    }

    html += '</div><div class="hidden lg:block lg:order-3"></div></nav>';
    return html;
  }

  async function applyFiltersPage(page) {
    const datos = buildFormData();
    datos.set('pagina', String(page));
    const res = await fetch(form.getAttribute("action"), { method: "POST", body: datos });
    const json = await res.json();

    if (resultado) resultado.innerHTML = json.html;

    const navOld = document.getElementById('pagination');
    if (json.total > json.per_page) {
      const newNavHTML = buildPaginationHTML(json.total, json.page, json.per_page);
      if (navOld) navOld.outerHTML = newNavHTML;
      else document.getElementById('grid-unidades')?.insertAdjacentHTML('afterend', newNavHTML);
    } else if (navOld) {
      navOld.classList.add('hidden');
    }

    const u = new URL(location.href);
    u.searchParams.set('pagina', String(page));
    history.replaceState({}, '', u.toString());
    document.getElementById('grid-unidades')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  function bindPaginationDelegation() {
    document.addEventListener('click', (e) => {
      const a = e.target.closest('a.js-ajax-page'); if (!a) return;
      if (!isApplied) return;
      const page = parseInt(a.dataset.page || '1', 10);
      if (!isFinite(page) || page < 1) return;
      e.preventDefault();
      applyFiltersPage(page);
    });
  }

  // ===================== Aplicar filtros (único momento que refresca grid + badge + URL) =====================
  async function applyFilters() {
    const datos = buildFormData();
    datos.set('pagina', '1');
    const res = await fetch(form.getAttribute("action"), { method: "POST", body: datos });
    const json = await res.json();

    if (resultado) resultado.innerHTML = json.html;
    btnBuscar.innerHTML = `<i class="fa-solid fa-magnifying-glass"></i> Encontrar unidades (${json.total})`;

    const navOld = document.getElementById('pagination');
    if (json.total > json.per_page) {
      const newNavHTML = buildPaginationHTML(json.total, json.page, json.per_page);
      if (navOld) navOld.outerHTML = newNavHTML;
      else document.getElementById('grid-unidades')?.insertAdjacentHTML('afterend', newNavHTML);
    } else if (navOld) navOld.classList.add('hidden');

    appliedState = getDraftState();
    isApplied = true;
    updateArchiveFilterBadgeApplied(appliedState);
    syncUrlWithCurrentFilters();
  }

  // ===================== Cerrar modal => descarta cambios (vuelve a estado aplicado) =====================
  function resetDraftFilters() {
    if (appliedState) {
      setDraftState(appliedState);
      actualizarConteoDinamico();
    } else {
      form?.querySelectorAll("select").forEach(s => s.value = "");
      if (habitInput) habitInput.value = ""; renderHabitacionesUI();
      vistaSet.clear(); vistaHidden.value = ""; chipNodes.forEach(ch => ch.setAttribute('aria-pressed','false'));
      const rdef = rangeDefaults();
      inputSMin.value = rdef.smin; inputSMax.value = rdef.smax; inputSCsv.value = `${rdef.smin},${rdef.smax}`;
      renderDiscreteRange();
      actualizarConteoDinamico();
    }
    btnLimpiar.classList.add("hidden");
  }

  // ====== Listeners ======
  form?.querySelectorAll("select").forEach(s => {
    s.addEventListener("change", async () => {
      actualizarUIBotones();
      await actualizarConteoDinamico();
    });
  });

  function renderHabitacionesUI(){
    if (!habitValEl || !habitInput) return;
    const v = habitInput.value;
    if (v === "" || isNaN(parseInt(v,10))) {
      habitValEl.dataset.empty = "1";
      habitValEl.innerHTML = '<span class="text-sm text-gray-600">/</span>';
    } else {
      habitValEl.dataset.empty = "0";
      habitValEl.textContent = String(parseInt(v,10));
    }
  }
  async function setHabitacionesValue(nextVal){
    if (!habitInput) return;
    if (nextVal === "" || nextVal === null) habitInput.value = "";
    else {
      const n = Math.max(<?= (int)$habit_min ?>, Math.min(<?= (int)$habit_max ?>, parseInt(nextVal,10)));
      habitInput.value = isFinite(n) ? String(n) : "";
    }
    renderHabitacionesUI();
    actualizarUIBotones();
    await actualizarConteoDinamico();
  }
  habitBtnPls?.addEventListener('click', (e)=>{
    e.preventDefault();
    const curr = habitInput?.value || "";
    if (curr === "") setHabitacionesValue(<?= (int)$habit_min ?>);
    else {
      const n = parseInt(curr,10);
      setHabitacionesValue(isFinite(n) ? Math.min(<?= (int)$habit_max ?>, n + 1) : <?= (int)$habit_min ?>);
    }
  });
  habitBtnMin?.addEventListener('click', (e)=>{
    e.preventDefault();
    const curr = habitInput?.value || "";
    if (curr === "") setHabitacionesValue("");
    else {
      const n = parseInt(curr,10);
      if (!isFinite(n) || n <= <?= (int)$habit_min ?>) setHabitacionesValue("");
      else setHabitacionesValue(n - 1);
    }
  });

  function updateVistaHiddenFromSet(){ vistaHidden.value = [...vistaSet].join(','); }
  chipNodes.forEach(btn=>{
    btn.addEventListener('click', async (e)=>{
      e.preventDefault();
      const slug = btn.dataset.slug;
      const isOn = btn.getAttribute('aria-pressed') === 'true';
      if (isOn) { vistaSet.delete(slug); btn.setAttribute('aria-pressed','false'); }
      else      { vistaSet.add(slug);    btn.setAttribute('aria-pressed','true');  }
      updateVistaHiddenFromSet();
      actualizarUIBotones();
      await actualizarConteoDinamico();
    });
  });

  // ====== Rango Discreto Metraje ======
  let idxMin = 0, idxMax = Math.max(0, POINTS.length - 1);
  function valueToIndex(val){ const i = POINTS.findIndex(v => Number(v) === Number(val)); return (i >= 0 ? i : 0); }
  function indexToPercent(i){ return (POINTS.length <= 1) ? 0 : (i / (POINTS.length - 1)) * 100; }

  function renderDiscreteRange(){
    if (!rangeEl) return;
    
    // indices desde inputs
    idxMin = valueToIndex(parseFloat(inputSMin.value));
    idxMax = valueToIndex(parseFloat(inputSMax.value));
    if (idxMin > idxMax) { const t = idxMin; idxMin = idxMax; idxMax = t; }

    const track = rangeEl.querySelector('.range-track');
    track.innerHTML = '<div class="range-fill"></div>';
    const fill = track.querySelector('.range-fill');

    // marks
    POINTS.forEach((v, i)=>{
      const m = document.createElement('div');
      m.className = 'range-mark';
      m.style.left = `${indexToPercent(i)}%`;
      if (i >= idxMin && i <= idxMax) m.classList.add('active');
      m.title = `${v} m²`;
      
      m.addEventListener('click', ()=>{
        // Si hacemos click exactamente en el índice mínimo o máximo actual, no hacer nada
        if (i === idxMin || i === idxMax) return;
        
        // Calcular a cuál handle está más cerca
        const distToMin = Math.abs(i - idxMin);
        const distToMax = Math.abs(i - idxMax);
        
        // Si está fuera del rango actual, expandir hacia ese lado
        if (i < idxMin) {
          idxMin = i;
        } else if (i > idxMax) {
          idxMax = i;
        } else {
          // Si está dentro del rango, mover el handle más cercano
          if (distToMin < distToMax) {
            idxMin = i;
          } else if (distToMax < distToMin) {
            idxMax = i;
          } else {
            // Si está equidistante, mover según la posición
            if (i < (idxMin + idxMax) / 2) {
              idxMin = i;
            } else {
              idxMax = i;
            }
          }
        }
        
        // Asegurar que min <= max
        if (idxMin > idxMax) {
          const temp = idxMin;
          idxMin = idxMax;
          idxMax = temp;
        }
        
        // Re-renderizar inmediatamente para mostrar el cambio
        renderDiscreteRangeVisuals();
        commitRangeChange();
      });
      
      track.appendChild(m);
    });

    // handles
    const hMin = document.createElement('button');
    hMin.type = 'button'; hMin.className = 'range-handle'; hMin.style.left = `${indexToPercent(idxMin)}%`; hMin.setAttribute('aria-label','Min');
    const hMax = document.createElement('button');
    hMax.type = 'button'; hMax.className = 'range-handle'; hMax.style.left = `${indexToPercent(idxMax)}%`; hMax.setAttribute('aria-label','Max');
    track.appendChild(hMin); track.appendChild(hMax);

    // Actualizar visuales iniciales
    renderDiscreteRangeVisuals();

    // drag functionality
    let dragging = null;
    function onPointerDown(which, ev){
      dragging = which; ev.preventDefault();
      document.addEventListener('pointermove', onPointerMove);
      document.addEventListener('pointerup', onPointerUp, { once:true });
    }
    function onPointerMove(ev){
      if (!dragging) return;
      const rect = track.getBoundingClientRect();
      const relX = Math.min(Math.max(ev.clientX - rect.left, 0), rect.width);
      const pct  = rect.width ? (relX / rect.width) : 0;
      const approxIndex = Math.round(pct * (POINTS.length - 1));
      if (dragging === 'min') idxMin = Math.min(Math.max(0, approxIndex), idxMax);
      else idxMax = Math.max(Math.min(POINTS.length - 1, approxIndex), idxMin);

      renderDiscreteRangeVisuals();
    }
    function onPointerUp(){
      document.removeEventListener('pointermove', onPointerMove);
      dragging = null;
      commitRangeChange();
    }
    hMin.addEventListener('pointerdown', onPointerDown.bind(null,'min'));
    hMax.addEventListener('pointerdown', onPointerDown.bind(null,'max'));

    // keyboard
    function handleKey(which, e){
      const step = (e.shiftKey ? 2 : 1);
      if (e.key === 'ArrowLeft' || e.key === 'ArrowDown') {
        if (which==='min') idxMin = Math.max(0, idxMin - step);
        else idxMax = Math.max(idxMin, idxMax - step);
        renderDiscreteRangeVisuals();
        commitRangeChange(); 
        e.preventDefault();
      } else if (e.key === 'ArrowRight' || e.key === 'ArrowUp') {
        if (which==='min') idxMin = Math.min(idxMax, idxMin + step);
        else idxMax = Math.min(POINTS.length-1, idxMax + step);
        renderDiscreteRangeVisuals();
        commitRangeChange(); 
        e.preventDefault();
      }
    }
    hMin.addEventListener('keydown', handleKey.bind(null,'min'));
    hMax.addEventListener('keydown', handleKey.bind(null,'max'));
  }

  // Nueva función auxiliar para actualizar solo los visuales
  function renderDiscreteRangeVisuals() {
    if (!rangeEl) return;
    
    const track = rangeEl.querySelector('.range-track');
    const fill = track.querySelector('.range-fill');
    const hMin = track.querySelector('.range-handle[aria-label="Min"]');
    const hMax = track.querySelector('.range-handle[aria-label="Max"]');
    
    // Actualizar posiciones de handles
    if (hMin) hMin.style.left = `${indexToPercent(idxMin)}%`;
    if (hMax) hMax.style.left = `${indexToPercent(idxMax)}%`;
    
    // Actualizar fill
    if (fill) {
      fill.style.left = `${indexToPercent(idxMin)}%`;
      fill.style.width = `${indexToPercent(idxMax) - indexToPercent(idxMin)}%`;
    }
    
    // Actualizar pills
    if (pillMin) pillMin.textContent = `${POINTS[idxMin]} m²`;
    if (pillMax) pillMax.textContent = `${POINTS[idxMax]} m²`;
    
    // Actualizar marks activos
    track.querySelectorAll('.range-mark').forEach((el, i) => {
      if (i >= idxMin && i <= idxMax) el.classList.add('active'); 
      else el.classList.remove('active');
    });
  }

  async function commitRangeChange(){
    inputSMin.value = POINTS[idxMin];
    inputSMax.value = POINTS[idxMax];
    inputSCsv.value = `${POINTS[idxMin]},${POINTS[idxMax]}`;
    actualizarUIBotones();
    await actualizarConteoDinamico();
  }

  // ====== botones del modal ======
  btnLimpiar?.addEventListener("click", async () => {
    form?.querySelectorAll("select").forEach(s => s.value = "");
    if (habitInput) { habitInput.value = ""; renderHabitacionesUI(); }

    // limpiar vistas
    vistaSet.clear();
    vistaHidden.value = "";
    chipNodes.forEach(ch => ch.setAttribute('aria-pressed','false'));

    // metraje => rango completo
    const rdef = rangeDefaults();
    inputSMin.value = rdef.smin;
    inputSMax.value = rdef.smax;
    inputSCsv.value = `${rdef.smin},${rdef.smax}`;
    renderDiscreteRange();

    actualizarUIBotones();
    await actualizarConteoDinamico();
  });

  // Event listeners actualizados con animaciones
  [btnAbrirDesktop, btnAbrirMobile].forEach(btn => btn?.addEventListener("click", openModal));

  btnCerrar?.addEventListener("click", closeModal);
  
  popup?.addEventListener("click", e => {
    const path = e.composedPath ? e.composedPath() : [];
    const clickedInside = path.includes(modalCard);
    if (!clickedInside) {
      closeModal();
    }
  });

  form?.addEventListener("submit", async e => {
    e.preventDefault();
    btnBuscar.innerHTML = "Buscando...";
    await applyFilters();
    closeModalAfterApply();
  });

  // Listener para cambios de tamaño de ventana
  window.addEventListener('resize', () => {
    // Solo si el modal está abierto
    if (!popup.classList.contains('hidden')) {
      // Remover todas las clases de animación para evitar conflictos
      modalCard.classList.remove('animate-fade-in', 'animate-fade-out', 'animate-fade-in-up', 'animate-fade-out-down');
      
      // Aplicar la animación correcta según el nuevo tamaño
      setTimeout(() => {
        if (isMobile()) {
          modalCard.classList.add('animate-fade-in-up');
        } else {
          modalCard.classList.add('animate-fade-in');
        }
      }, 50); // Pequeño delay para evitar conflictos
    }
  });

  // ===================== CUSTOM SELECT (enhancer) =====================
  function iconForSelect(name){
    switch(name){
      case 'nivel': return '<span class="material-symbols-outlined text-gray-500 text-[18px] cs-icon"></span>';
      default:      return '';
    }
  }
  function buildCustomSelect(sel){
    if (!sel || sel.dataset.csInit === '1') return;
    sel.dataset.csInit = '1';

    const wrap = document.createElement('div');
    wrap.className = 'cs';
    sel.parentNode.insertBefore(wrap, sel);
    wrap.appendChild(sel);
    sel.classList.add('cs-hidden-select');

    const placeholder = sel.options[0]?.textContent || 'Seleccionar';
    const leadingIcon = iconForSelect(sel.getAttribute('name'));

    const btn = document.createElement('button');
    btn.type = 'button'; btn.className = 'cs-button';
    btn.setAttribute('aria-haspopup','listbox'); btn.setAttribute('aria-expanded','false');
    btn.innerHTML = `<span class="cs-leading">${leadingIcon}<span class="cs-label">${sel.selectedOptions?.[0]?.textContent || placeholder}</span></span><span class="cs-arrow">▾</span>`;
    wrap.appendChild(btn);

    const menu = document.createElement('div');
    menu.className = 'cs-menu'; menu.setAttribute('role','listbox');
    wrap.appendChild(menu);

    function renderOptions(){
      menu.innerHTML = '';
      [...sel.options].forEach((o) => {
        const item = document.createElement('button');
        item.type = 'button'; item.className = 'cs-opt'; item.setAttribute('role','option');
        item.dataset.value = o.value; item.setAttribute('aria-selected', o.selected ? 'true' : 'false');
        item.innerHTML = `<span>${o.textContent}</span>`;
        item.addEventListener('click', ()=>{
          sel.value = o.value;
          sel.dispatchEvent(new Event('change', {bubbles:true}));
          btn.querySelector('.cs-label').textContent = sel.selectedOptions?.[0]?.textContent || placeholder;
          menu.querySelectorAll('.cs-opt').forEach(b=> b.setAttribute('aria-selected', b.dataset.value===sel.value ? 'true' : 'false'));
          closeMenu();
        });
        menu.appendChild(item);
      });
    }
    function openMenu(){ wrap.classList.add('open'); btn.setAttribute('aria-expanded','true'); }
    function closeMenu(){ wrap.classList.remove('open'); btn.setAttribute('aria-expanded','false'); }
    function toggleMenu(){ wrap.classList.contains('open') ? closeMenu() : openMenu(); }
    btn.addEventListener('click', (e)=>{ e.preventDefault(); toggleMenu(); });
    document.addEventListener('click', (e)=>{ if (!wrap.contains(e.target)) closeMenu(); });
    document.addEventListener('keydown', (e)=>{ if (e.key === 'Escape') closeMenu(); });

    const obs = new MutationObserver(renderOptions);
    obs.observe(sel, {childList: true, subtree: true});
    renderOptions();
  }
  function initCustomSelects(){
    document.querySelectorAll('select.short-dd').forEach(buildCustomSelect); // solo "nivel"
  }

  // ===== Init =====
  renderHabitacionesUI();
  renderDiscreteRange();
  actualizarUIBotones();
  actualizarConteoDinamico();
  bindPaginationDelegation();
  initCustomSelects();
});
</script>