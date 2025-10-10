<?php
/**
 * single-amenity.php
 */
get_header();

$post_id = get_the_ID();

/* ============================================================
   Helpers para construir el "Volver" determinístico
   ============================================================ */
if (!function_exists('tt_resolve_post_id')) {
  function tt_resolve_post_id($maybe) {
    if (is_numeric($maybe)) return (int)$maybe;
    if (is_object($maybe) && isset($maybe->ID)) return (int)$maybe->ID;
    if (is_array($maybe)) {
      $out = [];
      foreach ($maybe as $x) {
        if (is_numeric($x)) { $out[] = (int)$x; continue; }
        if (is_object($x) && isset($x->ID)) { $out[] = (int)$x->ID; continue; }
        if (is_array($x) && isset($x['ID'])) { $out[] = (int)$x['ID']; continue; }
      }
      return $out;
    }
    return 0;
  }
}

if (!function_exists('tt_get_amenity_cluster_ids')) {
  function tt_get_amenity_cluster_ids($post_id) {
    $candidates = ['cluster_perteneciente', 'cluster_asociado', 'cluster', 'clusters'];
    $ids = [];
    foreach ($candidates as $key) {
      $raw = get_field($key, $post_id);
      if (empty($raw)) continue;

      $res = tt_resolve_post_id($raw);
      if (is_array($res)) $ids = array_merge($ids, $res);
      elseif (is_int($res) && $res > 0) $ids[] = $res;

      if (!empty($ids)) break;
    }
    $ids = array_filter(array_map('intval', $ids));
    $ids = array_values(array_unique($ids));
    return $ids;
  }
}

if (!function_exists('tt_get_amenity_archive_url')) {
  function tt_get_amenity_archive_url() {
    $url = get_post_type_archive_link('amenity');
    if (!$url) $url = home_url('/amenities/');
    return $url;
  }
}

if (!function_exists('tt_build_amenities_back_url')) {
  function tt_build_amenities_back_url($post_id) {
    $base = tt_get_amenity_archive_url();

    // 1) Prioridad a querystring
    $qs_ids = [];
    if (isset($_GET['cluster_ids'])) {
      $qs_ids = (array) $_GET['cluster_ids'];
      $qs_ids = array_filter(array_map('intval', $qs_ids));
    } elseif (isset($_GET['cluster_id'])) {
      $cid = intval($_GET['cluster_id']);
      if ($cid > 0) $qs_ids = [$cid];
    }

    // 2) Si no llega nada, mirar ACF
    if (empty($qs_ids)) {
      $qs_ids = tt_get_amenity_cluster_ids($post_id);
    }

    // 3) Sin clusters → archivo a secas
    if (empty($qs_ids)) return $base;

    // 4) Un solo cluster → cluster_id
    if (count($qs_ids) === 1) {
      return add_query_arg('cluster_id', $qs_ids[0], $base);
    }

    // 5) Varios clusters → cluster_ids[]
    $sep = (strpos($base, '?') !== false) ? '&' : '?';
    $pairs = [];
    foreach ($qs_ids as $id) {
      $pairs[] = 'cluster_ids[]=' . rawurlencode($id);
    }
    return $base . $sep . implode('&', $pairs);
  }
}

$amenities_back_url = tt_build_amenities_back_url($post_id);

/* ============================================================
   → Campos ACF existentes
   ============================================================ */
$galeria_posts     = get_field('imagenes');
$planos_posts      = get_field('imagenes_planos');
$tour360           = get_field('tour_360');
$descripcion_corta = get_field('descripcion_corta');

// Extraer imágenes
$gallery_images = [];
if ($galeria_posts) {
  foreach ($galeria_posts as $gal_id) {
    $imgs = get_field('galeria', $gal_id);
    if ($imgs) $gallery_images = array_merge($gallery_images, $imgs);
  }
}
$planos_images = [];
if ($planos_posts) {
  foreach ($planos_posts as $g_post) {
    $imgs = get_field('galeria', $g_post->ID);
    if ($imgs) $planos_images = array_merge($planos_images, $imgs);
  }
}

// Helpers: nombre del nivel
function tt_get_nivel_name($post_id){
  $raw = get_field('nivel', $post_id);
  if (is_object($raw) && isset($raw->name)) return $raw->name;
  if (is_array($raw) && !empty($raw)) {
    $first = $raw[0];
    if (is_object($first) && isset($first->name)) return $first->name;
    $term = get_term((int)$first, 'nivel');
    return ($term && !is_wp_error($term)) ? $term->name : null;
  }
  if (!empty($raw)) {
    $term = get_term((int)$raw, 'nivel');
    return ($term && !is_wp_error($term)) ? $term->name : null;
  }
  $terms = get_the_terms($post_id, 'nivel');
  return (is_array($terms) && !empty($terms)) ? $terms[0]->name : null;
}

// Imagen destacada para el popover
$featured_id  = get_post_thumbnail_id();
$featured_url = $featured_id ? wp_get_attachment_image_url($featured_id, 'large') : '';
if (!$featured_url) {
  if (!empty($gallery_images)) {
    $first = $gallery_images[0];
    $featured_url = is_array($first) ? ($first['url'] ?? '') : '';
  } elseif (!empty($planos_images)) {
    $first = $planos_images[0];
    $featured_url = is_array($first) ? ($first['url'] ?? '') : '';
  }
}

$nivel_name  = tt_get_nivel_name(get_the_ID());
$floor_label = $nivel_name ? mb_strtoupper($nivel_name) : 'SIN NIVEL';
$desc_modal  = $descripcion_corta ?: get_field('descripcion') ?: '';
?>

<!-- Swiper CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css"/>

<style>
  .swiper-button-next::after,
  .swiper-button-prev::after { content: none !important; }

  /* Fallback progresivo: vh → svh → dvh */
  #amenity {
    height: 100vh;
    height: 100svh;
    height: 100dvh;
  }

  /* Controles inferiores: safe-area iOS */
  .amenity-bottom { bottom: calc(1.5rem + env(safe-area-inset-bottom, 0px)); }

  /* Swiper ocupa todo el alto */
  #amenity .swiper,
  #amenity .swiper-wrapper,
  #amenity .swiper-slide { height: 100%; }

  /* ===== Contenedor de imagen (base) ===== */
  .image-slide-container {
    width: 100%;
    height: 100vh;
    height: 100svh;
    height: 100dvh;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    background: transparent;
    overflow: hidden;
  }
  .full-screen-image {
    width: auto;
    height: auto;
    display: block;
    object-fit: contain;
    object-position: center center;
  }

  /* Topbar Amenity: ocultar/mostrar con transición */
  #amenity-topbar{
    transition: opacity .25s ease, transform .25s ease;
  }
  #amenity-topbar.nav-hidden{
    opacity: 0;
    transform: translateY(-8px);
    pointer-events: none;
    visibility: hidden;
  }

  /* ===== Desktop (≥1024px): fullscreen cover ===== */
  @media (min-width: 1024px) {
    .image-slide-container {
      width: 100vw !important;
      height: 100vh !important;
      height: 100svh !important;
      height: 100dvh !important;
      overflow: hidden !important;
      background: transparent !important;
    }
    .full-screen-image {
      width: 100vw !important;
      height: 100vh !important;
      height: 100svh !important;
      height: 100dvh !important;
      object-fit: cover !important;
      object-position: center center !important;
    }
    html, body { margin: 0; padding: 0; }
  }

  /* ===== Mobile & Tablet (≤1023px): contenidas ===== */
  @media (max-width: 1023px) {
    .image-slide-container {
      overflow: hidden !important;
      -webkit-overflow-scrolling: auto !important;
      background: transparent !important;
      padding: 0 !important;
      justify-content: center !important;
      align-items: center !important;
    }
    .full-screen-image {
      max-width: 100%;
      max-height: 100%;
      width: auto;
      height: auto;
      min-width: 0 !important;
      min-height: 0 !important;
      object-fit: contain !important;
      object-position: center center !important;
    }
    .btn-slide-arrow { transform: scale(0.9); }
  }

  /* PLANOS — pan horizontal (solo mobile/tablet) */
  @media (max-width: 1023px) {
    #tab-planos .panogram{
      height: 100vh; height: 100svh; height: 100dvh;
      overflow-x: auto;
      overflow-y: hidden;
      display: block;                 /* no grid/flex */
      -webkit-overflow-scrolling: touch;
      overscroll-behavior-x: contain;
      touch-action: pan-x pinch-zoom;
      background: #000;               /* marco negro */
    }
    #tab-planos .panogram-img{
      height: 100vh; height: 100svh; height: 100dvh;  /* ocupa todo el alto */
      width: auto;                    /* respeta 1920x1080 */
      max-width: none;
      display: block;
      object-fit: contain;
      object-position: center;
    }
  }
</style>

<div id="amenity" class="relative w-full overflow-hidden font-fuente_primaria text-text">
  <nav id="amenity-topbar" class="absolute inset-x-0 top-0 h-auto flex flex-col items-start p-0 gap-2 z-50 bg-black/90">
    <div class="flex items-center w-full text-white px-4 py-2 lg:py-4">
      <div class="flex items-center gap-2">
        <!-- Botón volver determinístico -->
        <a href="<?php echo esc_url($amenities_back_url); ?>"
           class="btn-fourth"
           id="btn-back-amenity"
           title="Volver a Amenidades">
          <i class="material-symbols-outlined">arrow_back</i>
        </a>

        <!-- POPUP INFO (popover ≥lg, bottom-sheet en mobile) -->
        <div class="relative inline-block" id="info-wrapper">
          <button id="btn-info" class="btn-fourth" aria-haspopup="true" aria-expanded="false">
            <i class="material-symbols-outlined">info</i>
          </button>

          <!-- OVERLAY solo mobile -->
          <div id="info-overlay" class="hidden fixed inset-0 bg-black/40 backdrop-blur-[1px] z-[90] lg:hidden"></div>

          <!-- Card flotante (responsive) -->
          <div id="info-popover"
               class="hidden
                      fixed inset-x-0 bottom-0 w-screen
                      lg:absolute lg:inset-auto lg:left-0 lg:mt-4 lg:w-[282px] lg:max-w-xs
                      bg-white border border-[#EDECF1]
                      rounded-t-[24px] lg:rounded-[24px]
                      shadow-[0_12px_12px_rgba(0,0,0,0.08)]
                      pt-8 pb-4 px-3 text-gray-900 z-[100]"
               style="--safeBottom: env(safe-area-inset-bottom, 0px); padding-bottom: calc(1rem + var(--safeBottom));">
            <button id="close-info"
                    class="absolute top-1 right-1 w-8 h-8 rounded-full grid place-items-center
                           text-gray-700 hover:text-gray-400"
                    aria-label="Cerrar">
              <i class="fa-solid fa-xmark"></i>
            </button>

            <div class="relative w-full h-[300px] lg:h-[200px] bg-gray-200 rounded-[18px] overflow-hidden mb-4">
              <?php if (!empty($featured_id)) : ?>
                <?php
                  echo wp_get_attachment_image(
                    $featured_id,
                    'large',
                    false,
                    [
                      'class' => 'block w-full h-full object-cover object-center',
                      'alt'   => get_the_title()
                    ]
                  );
                ?>
              <?php elseif (!empty($featured_url)) : ?>
                <img src="<?php echo esc_url($featured_url); ?>"
                     alt="<?php echo esc_attr(get_the_title()); ?>"
                     class="block w-full h-full object-cover object-center">
              <?php else : ?>
                <div class="w-full h-full flex items-center justify-center text-gray-400">
                  Sin imagen
                </div>
              <?php endif; ?>
            </div>

            <p class="text-xs font-extrabold tracking-wider text-gray-400">
              <?php echo esc_html($floor_label); ?>
            </p>
            <h3 class="text-lg font-semibold leading-snug mb-3"><?php the_title(); ?></h3>
            <hr class="border-gray-200 my-3">

            <?php if (!empty($desc_modal)): ?>
              <p class="text-base leading-[1.2] font-normal text-sm">
                <?php echo esc_html($desc_modal); ?>
              </p>
            <?php endif; ?>
          </div>
        </div>

        <div class="flex flex-col items-start lg:gap-2 lg:flex-row lg:items-center">
          <!-- Título -->
          <span class="text-base lg:text-lg font-medium text-white ml-0 lg:ml-2 order-last lg:order-none">
            <?php the_title(); ?>
          </span>

          <!-- Piso -->
          <span class="text-[11px] leading-none lg:px-2 py-1 rounded-full
                      order-first lg:order-none 
                      bg-transparent text-white lg:bg-white lg:text-gray-900">
            <?php echo esc_html($floor_label); ?>
          </span>
        </div>
      </div>

      <!-- Fullscreen SOLO desktop -->
      <div class="ml-auto hidden lg:block">
        <button class="btn-fourth" title="Pantalla completa">
          <i class="material-symbols-outlined">fullscreen</i>
        </button>
      </div>
    </div>
  </nav>

  <!-- Tabs contenedores -->
  <div id="tab-galeria" class="hidden absolute inset-0 z-20"></div>
  <div id="tab-planos"  class="hidden absolute inset-0 z-20"></div>
  <div id="tab-tour"    class="hidden absolute inset-0 z-20"></div>

  <div class="fixed lg:absolute left-0 right-0 top-auto z-30 flex justify-center pointer-events-auto amenity-bottom">
    <div class="relative">
      <button
        class="btn-share absolute -top-[55px] left-1/2 -translate-x-1/2 w-10 h-10 bg-white text-gray-700 shadow-md rounded-full flex items-center justify-center z-10 transition"
        title="Compartir"
        data-url="<?= esc_url(get_permalink(get_the_ID())) ?>"
        data-title="<?= esc_attr(get_the_title()) ?>"
        data-img="<?= esc_url($featured_url) ?>"
      >
        <i class="fa-solid fa-share-nodes text-gray-600 text-xs"></i>
      </button>

      <div class="bg-black/90 backdrop-blur-sm rounded-full px-3 py-2 flex flex-col items-center space-y-2 shadow-lg text-center max-w-md mx-auto">
        <div class="flex flex-wrap justify-center gap-3">
          <?php if ($gallery_images): ?>
            <button data-tab="tab-galeria" class="tab-btn btn-tertiary">
              <i class="material-symbols-outlined">photo_camera</i>
              <span class="hidden sm:inline">Galería</span>
            </button>
          <?php endif; ?>
          <?php if ($planos_images): ?>
            <button data-tab="tab-planos" class="tab-btn btn-tertiary">
              <i class="material-symbols-outlined">architecture</i>
              <span class="hidden sm:inline">Planos</span>
            </button>
          <?php endif; ?>
          <?php if ($tour360): ?>
            <button data-tab="tab-tour" class="tab-btn btn-tertiary">
              <i class="material-symbols-outlined">simulation</i>
              <span class="hidden sm:inline">Tour 360</span>
            </button>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Swiper JS -->
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  /**********************
   * Config / helpers
   **********************/
  const ANIM_IN_DESK  = 'animate-fade-in';
  const ANIM_OUT_DESK = 'animate-fade-out';
  const isMobile = () => window.matchMedia('(max-width: 1023px)').matches;

  // Topbar del detalle de amenity
  const amenityTopbar = document.getElementById('amenity-topbar');
  function toggleAmenityTopbarForTab(tabId){
    if (!amenityTopbar) return;
    if (tabId === 'tab-tour') amenityTopbar.classList.add('nav-hidden');
    else amenityTopbar.classList.remove('nav-hidden');
  }

  /**********************
   * PLANOS: datos PHP → JS y generadores
   **********************/
  const PLANOS = <?= wp_json_encode($planos_images, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); ?>;

  function srcFrom(img){
    return (img && (img.url || (img.sizes && (img.sizes.full || img.sizes.large)))) ? (img.url || img.sizes.full || img.sizes.large) : '';
  }

  function generatePlanosSlides(){
    if (!PLANOS || !PLANOS.length) return '';
    if (isMobile()){
      // Mobile/Tablet: pan horizontal nativo
      return PLANOS.map(img => `
        <div class="swiper-slide">
          <div class="panogram" data-pan="1">
            <img src="${srcFrom(img)}" alt="Plano" class="panogram-img" loading="lazy">
          </div>
        </div>`).join('');
    }
    // Desktop: cover como el resto
    return PLANOS.map(img => `
      <div class="swiper-slide">
        <div class="image-slide-container">
          <img src="${srcFrom(img)}" alt="Plano" class="full-screen-image" loading="lazy">
        </div>
      </div>`).join('');
  }

  function generatePlanosMarkup(){
    return `
      <div class="swiper h-full w-full">
        <div class="swiper-wrapper">
          ${generatePlanosSlides()}
        </div>
        <div class="swiper-button-prev btn-slide-arrow"><i class="fa-solid fa-chevron-left"></i></div>
        <div class="swiper-button-next btn-slide-arrow"><i class="fa-solid fa-chevron-right"></i></div>
      </div>`;
  }

  /**********************
   * Plantillas de tabs (Galería/Tour fijas)
   **********************/
  const tabs = {
    'tab-galeria': `
      <div class="swiper h-full w-full">
        <div class="swiper-wrapper">
          <?php foreach ($gallery_images as $img): ?>
            <div class="swiper-slide">
              <div class="image-slide-container">
                <?= wp_get_attachment_image($img['ID'], 'full', false, ['class' => 'full-screen-image']); ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="swiper-button-prev btn-slide-arrow"><i class="fa-solid fa-chevron-left"></i></div>
        <div class="swiper-button-next btn-slide-arrow"><i class="fa-solid fa-chevron-right"></i></div>
      </div>
    `,
    'tab-planos': null, /* se genera dinámicamente según viewport */
    'tab-tour': `
      <iframe src="<?= esc_url($tour360) ?>"
              class="w-full h-full object-cover bg-black"
              frameborder="0"
              allowfullscreen></iframe>
    `
  };

  const btns = document.querySelectorAll('.tab-btn');
  const allTabs = Array.from(document.querySelectorAll('#amenity > div[id^="tab-"]'));
  let activeTab = null;

  function centerPlanoActive(){
    if (!isMobile()) return;
    const scroller = document.querySelector('#tab-planos .swiper-slide-active .panogram');
    if (!scroller) return;
    const mid = Math.max(0, Math.floor((scroller.scrollWidth - scroller.clientWidth) / 2));
    scroller.scrollTo({ left: mid, behavior: 'auto' });
  }

  function initSwiperFor(tabId) {
    if (tabId === 'tab-tour') return;

    const loopFlag =
      tabId === 'tab-galeria' ? (<?= count($gallery_images) > 1 ? 'true' : 'false' ?>) :
      tabId === 'tab-planos'  ? (<?= count($planos_images)  > 1 ? 'true' : 'false' ?>) : false;

    new Swiper(`#${tabId} .swiper`, {
      slidesPerView: 1,
      loop: loopFlag,
      allowTouchMove: false,          // gesto queda para panear el plano en mobile
      effect: 'fade',
      fadeEffect: { crossFade: true },
      speed: 450,
      observer: true,
      observeParents: true,
      preloadImages: true,
      updateOnImagesReady: true,
      watchSlidesProgress: true,
      navigation: {
        nextEl: `#${tabId} .swiper-button-next`,
        prevEl: `#${tabId} .swiper-button-prev`
      },
      keyboard: { enabled: true },
      on: {
    init(s) {
      requestAnimationFrame(() => {
        if (s.slides && s.slides.length > 1) {
          s.slideTo(1, 0, false);
          s.slideTo(0, 0, false);
          s.update();
        }
        // Centrar el plano inicial
        if (tabId === 'tab-planos') {
          setTimeout(() => centerPlanoActive(), 100);
        }
      });
    },
    
    imagesReady(s) {
      s.update();
      // Centrar cuando las imágenes estén listas
      if (tabId === 'tab-planos') {
        setTimeout(() => centerPlanoActive(), 100);
      }
    },
    
    slideChange(s) {
      // Centrar al cambiar de slide
      if (tabId === 'tab-planos') {
        setTimeout(() => centerPlanoActive(), 50);
      }
    },
    
    slideChangeTransitionEnd(s) {
      // Centrar cuando termine la transición
      if (tabId === 'tab-planos') {
        centerPlanoActive();
      }
    }
  },
});
  }

  function activarTab(tabId) {
    toggleAmenityTopbarForTab(tabId);

    const next = document.getElementById(tabId);
    const current = allTabs.find(d => !d.classList.contains('hidden') && d.id !== tabId);

    // 1) contenido
    next.innerHTML = (tabId === 'tab-planos') ? generatePlanosMarkup() : tabs[tabId];

    // 2) swiper
    initSwiperFor(tabId);

    // 3) fade-out actual
    if (current) {
      current.classList.add(ANIM_OUT_DESK);
      current.addEventListener('animationend', () => {
        current.classList.add('hidden');
        current.classList.remove(ANIM_OUT_DESK);
        current.innerHTML = '';
      }, { once: true });
    }

    // 4) fade-in siguiente
    next.classList.remove('hidden');
    next.classList.add(ANIM_IN_DESK);
    next.addEventListener('animationend', () => {
      next.classList.remove(ANIM_IN_DESK);
      if (tabId === 'tab-planos') centerPlanoActive();
    }, { once: true });

    // 5) UI y hash
    btns.forEach(b => b.classList.remove('active'));
    document.querySelector(`[data-tab="${tabId}"]`)?.classList.add('active');
    history.replaceState(null, null, `#${tabId.replace('tab-','')}`);

    // 6) limpiar el resto
    allTabs.forEach(div => {
      if (div.id !== tabId && div !== current) {
        div.classList.add('hidden');
        div.innerHTML = '';
      }
    });

    activeTab = tabId;
  }

  btns.forEach(btn => btn.addEventListener('click', () => activarTab(btn.dataset.tab)));

  // Init por hash o fallback
  const initHash = window.location.hash.replace('#','');
  if (['galeria','planos','tour'].includes(initHash)) activarTab(`tab-${initHash}`);
  else if (<?php echo json_encode(!empty($gallery_images)); ?>) activarTab('tab-galeria');
  else if (<?php echo json_encode(!empty($planos_images)); ?>) activarTab('tab-planos');
  else if ('<?= $tour360 ?>') activarTab('tab-tour');

  // Recentrado en eventos de ventana
  window.addEventListener('load', () => { if (activeTab === 'tab-planos') centerPlanoActive(); });
  window.addEventListener('resize', () => { if (activeTab === 'tab-planos') setTimeout(centerPlanoActive, 50); });
  window.addEventListener('orientationchange', () => { if (activeTab === 'tab-planos') setTimeout(centerPlanoActive, 100); });
});
</script>

<script>
/* Popover de info (igual UX que en tu otra hoja) */
document.addEventListener('DOMContentLoaded', () => {
  const wrapper  = document.getElementById('info-wrapper');
  const btn      = document.getElementById('btn-info');
  const popover  = document.getElementById('info-popover');
  const closeBtn = document.getElementById('close-info');
  const overlay  = document.getElementById('info-overlay');

  const ANIM_IN_MOBILE  = 'animate-fade-in-up';
  const ANIM_OUT_MOBILE = 'animate-fade-out-down';
  const ANIM_IN_DESK    = 'animate-fade-in';
  const ANIM_OUT_DESK   = 'animate-fade-out';

  const OPEN_GUARD_MS = 400;
  const SQUELCH_MS    = 260;
  const isMobile = () => window.matchMedia('(max-width: 1023px)').matches;
  const now = () => performance.now();

  const state = { open:false, openingAt:0, squelchUntil:0 };
  const inSquelch = () => now() < state.squelchUntil;
  const setSquelch = (ms=SQUELCH_MS) => state.squelchUntil = now() + ms;

  if (!wrapper || !btn || !popover) return;

  function showMobile() {
    overlay?.classList.remove('hidden');
    popover.classList.remove('hidden');
    popover.classList.remove(ANIM_OUT_MOBILE, ANIM_IN_DESK, ANIM_OUT_DESK);
    void popover.offsetWidth;
    popover.classList.add(ANIM_IN_MOBILE);
    btn.setAttribute('aria-expanded', 'true');
    state.open = true;
    state.openingAt = now();
    setSquelch();
  }
  function hideMobile() {
    if (popover.classList.contains('hidden')) return;
    popover.classList.remove(ANIM_IN_MOBILE, ANIM_IN_DESK);
    popover.classList.add(ANIM_OUT_MOBILE);
    popover.addEventListener('animationend', () => {
      popover.classList.add('hidden');
      popover.classList.remove(ANIM_OUT_MOBILE);
    }, { once: true });
    overlay?.classList.add('hidden');
    btn.setAttribute('aria-expanded', 'false');
    state.open = false;
  }

  function showDesk() {
    overlay?.classList.add('hidden');
    popover.classList.remove('hidden');
    popover.classList.remove(ANIM_OUT_DESK, ANIM_IN_MOBILE, ANIM_OUT_MOBILE);
    void popover.offsetWidth;
    popover.classList.add(ANIM_IN_DESK);
    popover.addEventListener('animationend', () => {
      popover.classList.remove(ANIM_IN_DESK);
    }, { once: true });
    btn.setAttribute('aria-expanded', 'true');
    state.open = true;
    state.openingAt = now();
    setSquelch();
  }
  function hideDesk() {
    popover.classList.remove(ANIM_IN_DESK, ANIM_IN_MOBILE);
    popover.classList.add(ANIM_OUT_DESK);
    popover.addEventListener('animationend', () => {
      popover.classList.add('hidden');
      popover.classList.remove(ANIM_OUT_DESK);
    }, { once: true });
    btn.setAttribute('aria-expanded', 'false');
    state.open = false;
  }

  function openInfo()  { isMobile() ? showMobile() : showDesk(); }
  function closeInfo() { isMobile() ? hideMobile() : hideDesk(); }

  function toggleInfo(e){
    e?.preventDefault?.();
    e?.stopPropagation?.();
    if (inSquelch()) return;
    state.open ? closeInfo() : openInfo();
  }

  btn.addEventListener('pointerdown', toggleInfo);
  btn.addEventListener('click', (e) => e.preventDefault());

  closeBtn?.addEventListener('click', (e) => { e.stopPropagation(); closeInfo(); });

  function onOverlayTap(e){
    if ((now() - state.openingAt) < OPEN_GUARD_MS) return;
    e?.preventDefault?.();
    e?.stopPropagation?.();
    closeInfo();
  }
  overlay?.addEventListener('pointerdown', onOverlayTap);
  overlay?.addEventListener('click', onOverlayTap);

  document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && state.open) closeInfo(); });

  document.addEventListener('click', (e) => {
    if (isMobile()) return;
    if (!state.open) return;
    if ((now() - state.openingAt) < OPEN_GUARD_MS) return;
    if (!wrapper.contains(e.target)) hideDesk();
  });

  window.addEventListener('resize', () => {
    if (!isMobile()) {
      overlay?.classList.add('hidden');
      popover?.classList.remove(ANIM_IN_MOBILE, ANIM_OUT_MOBILE, ANIM_IN_DESK, ANIM_OUT_DESK);
    }
  });
});
</script>

<?php get_footer(); ?>
