
<?php  
/**
 * Template Name: Vista Apartamento
 */
get_header();

if (!have_posts()) {
  echo 'Apartamento no encontrado';
  get_footer();
  exit;
}
the_post();
$post_id = get_the_ID();


// --- Resolver ID de clúster de la unidad actual ---
function tt_resolve_post_id($maybe) {
  if (is_numeric($maybe)) return (int) $maybe;
  if (is_object($maybe) && isset($maybe->ID)) return (int) $maybe->ID;
  if (is_array($maybe)) {
    if (isset($maybe['ID'])) return (int) $maybe['ID'];
    if (isset($maybe[0]))   return tt_resolve_post_id($maybe[0]);
  }
  return 0;
}

$cluster_raw         = get_field('cluster_asociado', $post_id); // ACF (ID | post object | array)
$current_cluster_id  = tt_resolve_post_id($cluster_raw);
$cluster_back_url    = $current_cluster_id
  ? add_query_arg('cluster_id', $current_cluster_id, home_url('/unidades/'))
  : home_url('/unidades/'); // Fallback elegante



// Datos principales
$tip_id     = get_field('tipologia'); // ID del término tipología asociado
$title = get_the_title($post_id);
$tipologia_id = get_field('tipologia', $post_id);
$tipologia = get_term($tipologia_id, 'tipologia');
$tip_nombre = $tipologia ? $tipologia->name : '';
$codigo = get_field('codigo', $post_id);
$nivel_id = get_field('nivel', $post_id);
$img   = get_field('imagen_destacada', 'term_' . $tip_id);
$nivel = $nivel_id ? get_term($nivel_id, 'nivel') : null;
$nivel_name = $nivel ? $nivel->name : '';
$vista_ids  = get_field('vista');
$vista_nombres = [];
if (!empty($vista_ids) && is_array($vista_ids)) {
  foreach ($vista_ids as $vista_id) {
    $term = get_term($vista_id, 'vista');
    if ($term && !is_wp_error($term)) {
      $vista_nombres[] = $term->name;
    }
  }
}
$tipo_unidad_terms = get_the_terms(get_the_ID(), 'tipo-de-unidad');
$tipo_unidad_nombre = '';
if ($tipo_unidad_terms && !is_wp_error($tipo_unidad_terms)) {
  $tipo_unidad_nombre = $tipo_unidad_terms[0]->name;
}

// Tipología: campos extendidos
$habitaciones = get_field('cantidad_de_habitaciones', 'term_' . $tipologia_id) ?: '';
$superficie   = get_field('superficie', 'term_' . $tipologia_id) ?: '';
$img_gallery  = get_field('galeria_de_imagenes', 'term_' . $tipologia_id);
$img_planos   = get_field('galeria_de_planos', 'term_' . $tipologia_id);
$tour_url     = get_field('url_de_tipologia', 'term_' . $tipologia_id);

// Unidad: vistas
$galeria_vistas = get_field('galeria_de_vistas', $post_id);

// FUNCIÓN: obtener imágenes desde Relationship (return_format: id)
function get_gallery_images($relationship_ids) {
  $images = [];
  if (empty($relationship_ids) || !is_array($relationship_ids)) return $images;
  foreach ($relationship_ids as $gallery_id) {
    $gallery_images = get_field('galeria', $gallery_id);
    if ($gallery_images && is_array($gallery_images)) {
      foreach ($gallery_images as $img) {
        if (is_array($img) && isset($img['ID'])) $images[] = $img;
      }
    }
  }
  return $images;
}

// Arrays finales
$gallery_images = get_gallery_images($img_gallery);
$vistas_images  = get_gallery_images($galeria_vistas);  
$planos_images  = get_gallery_images($img_planos);

/** ===============================
 * Pisos de la MISMA tipología (para “Comparar Vistas”)
 * =============================== */

function tt_get_nivel_term($pid){
  $raw = get_field('nivel', $pid);
  if (is_object($raw) && isset($raw->term_id)) return ['id'=>$raw->term_id, 'name'=>$raw->name];
  if (is_array($raw) && !empty($raw)) {
    $first = $raw[0];
    if (is_object($first) && isset($first->term_id)) return ['id'=>$first->term_id, 'name'=>$first->name];
    $t = get_term((int)$first, 'nivel');
    return ($t && !is_wp_error($t)) ? ['id'=>$t->term_id, 'name'=>$t->name] : ['id'=>0,'name'=>''];
  }
  if (!empty($raw)) {
    $t = get_term((int)$raw, 'nivel');
    return ($t && !is_wp_error($t)) ? ['id'=>$t->term_id, 'name'=>$t->name] : ['id'=>0,'name'=>''];
  }
  $terms = get_the_terms($pid, 'nivel');
  return (is_array($terms) && !empty($terms)) ? ['id'=>$terms[0]->term_id, 'name'=>$terms[0]->name] : ['id'=>0,'name'=>''];
}

$pisos_map = [];
if ($tipologia_id) {
  $same_tip = new WP_Query([
    'post_type'      => 'apartamento',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'tax_query'      => [[
      'taxonomy' => 'tipologia',
      'field'    => 'term_id',
      'terms'    => $tipologia_id,
    ]],
    'no_found_rows'  => true,
  ]);

  if ($same_tip->have_posts()) {
    while ($same_tip->have_posts()) { $same_tip->the_post();
      $pid         = get_the_ID();
      $nivel_term  = tt_get_nivel_term($pid);
      if ($nivel_term['id']) {
        if (!isset($pisos_map[$nivel_term['id']])) {
          $pisos_map[$nivel_term['id']] = [
            'nivel_id'   => $nivel_term['id'],
            'nivel_name' => $nivel_term['name'],
            'post_id'    => $pid,
            'permalink'  => get_permalink($pid),
            'is_current' => ($pid === $post_id),
          ];
        } else {
          if ($pid === $post_id) {
            $pisos_map[$nivel_term['id']]['post_id']    = $pid;
            $pisos_map[$nivel_term['id']]['permalink']  = get_permalink($pid);
            $pisos_map[$nivel_term['id']]['is_current'] = true;
          }
        }
      }
    }
    wp_reset_postdata();
  }
}
$pisos_list = array_values($pisos_map);
usort($pisos_list, function($a,$b){ return strnatcasecmp($a['nivel_name'],$b['nivel_name']); });
$has_compare = count($pisos_list) > 1;
?>

<main id="vista-apto" class="relative w-full overflow-hidden font-fuente_primaria text-text opacity-0">
  <!-- NAV superior -->
<nav id="apto-topbar" class="absolute inset-x-0 top-0 h-auto flex flex-col items-start p-0 gap-2 z-50 bg-black/90">
    <div class="flex items-center w-full text-white px-4 py-2 lg:py-4">
      <div class="flex items-center gap-2">
        <a href="<?php echo esc_url($cluster_back_url); ?>"
          class="btn-fourth"
          id="btn-back"
          data-back-href="<?php echo esc_url($cluster_back_url); ?>"
          title="Volver a Unidades">
         <i class="material-symbols-outlined">
            arrow_back
            </i>
        </a>

        <!-- POPUP INFO (popover ≥lg, bottom sheet en mobile) -->
        <div class="relative inline-block" id="info-wrapper">
          <button id="btn-info" class="btn-fourth" aria-haspopup="true" aria-expanded="false">
             <i class="material-symbols-outlined">
            info
            </i>
          </button>

          <!-- OVERLAY solo mobile -->
          <div id="info-overlay" class="hidden fixed inset-0 bg-black/40 backdrop-blur-[1px] z-[90] lg:hidden"></div>

          <div id="info-popover"
               class="hidden font-fuente_primaria
                      fixed inset-x-0 bottom-0 w-screen
                      lg:absolute lg:left-0 lg:inset-auto lg:mt-5 lg:w-[282px] lg:max-w-xs
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
              <?php
              if ($img) {
                echo wp_get_attachment_image(
                  $img,
                  'large',
                  false,
                  ['class' => 'object-cover object-center w-full h-full ']
                );
              } else {
                echo '<div class="w-full h-full flex items-center justify-center text-gray-400">Sin imagen</div>';
              }
              ?>
            </div>

            <p class="text-xs font-extrabold tracking-wider text-gray-400">
              <?= esc_html('Piso ' . $nivel_name) ?>
            </p>
            <h3 class="text-lg font-semibold leading-snug mb-3"><?= esc_html('Modelo ' . $tip_nombre) ?></h3>
            <hr class="border-gray-200 my-3">

            <div class="flex flex-col gap-2 text-sm text-gray-600 mb-4">
              <div class="flex items-center gap-1">
                <span class="material-symbols-outlined  text-gray-500 text-[18px]">crop_free</span>
                <span><?= esc_html($superficie) ?> m²</span>
              </div>
              <div class="flex items-center gap-1">
                <span class="material-symbols-outlined  text-gray-500 text-[18px]">bed</span>   
                <span><?= esc_html($habitaciones) ?> Hab.</span>
              </div>
              <?php if (!empty($vista_nombres)): ?>
              <div class="flex items-center gap-1">
                <span class="material-symbols-outlined text-sm">landscape_2</span>
                <span><?= esc_html(implode(', ', $vista_nombres)) ?></span>
              </div>
              <?php endif; ?>
            </div>

            <div class="flex justify-center mt-3">
              <a href="https://wa.me/549XXXXXXXXXX?text=Hola,%20quiero%20consultar%20por%20<?= urlencode(get_the_title()); ?>"
                 target="_blank"
                 class="flex justify-center items-center gap-2 w-full max-w-[260px] h-[44px] px-4
                        bg-white border border-[#C6C8D4] rounded-lg text-gray-800 font-medium
                        hover:bg-gray-50 transition">
                <i class="fa-brands fa-whatsapp text-gray-500"></i>
                Solicitar información
              </a>
            </div>
          </div>
        </div>

            <div class="flex flex-col items-start lg:gap-2 lg:flex-row lg:items-center">
              <!-- Tipología: en mobile queda abajo; en lg vuelve a la posición normal -->
              <span class="text-base lg:text-lg font-medium text-white ml-0 lg:ml-2 order-last lg:order-none">
                <?php echo esc_html("Modelo " . $tip_nombre); ?>
              </span>

              <!-- Nivel: transparente + texto blanco en mobile; en lg vuelve a fondo blanco + texto gris -->
              <span class="text-[11px] leading-none lg:px-2 py-1 rounded-full
                          order-first lg:order-none 
                          bg-transparent text-white lg:bg-white lg:text-gray-900">
                <?php echo esc_html($nivel_name); ?>
              </span>
            </div>

      </div>

      <!-- Acciones derecha: Fullscreen + Comparar Vistas (popover) -->
      <div class="ml-auto flex items-center gap-2" id="compare-wrapper">
        <!-- Comparar Vistas: visible en mobile/tablet también (icon-only en <lg) -->
        <button id="btn-comparar"
                class="btn-tertiary inline-flex items-center justify-center 
                       [&>span]:hidden lg:[&>span]:inline"
                type="button"
                aria-haspopup="dialog"
                aria-expanded="false"
                title="Comparar Vistas">
           <i class="material-symbols-outlined">
            swap_vert
            </i>
          <span clas="hidden sm:inline">Comparar Vistas</span>
        </button>

        <!-- Fullscreen: solo desktop -->
          <div class="ml-auto hidden lg:block">
        <button class="btn-fourth" title="Pantalla completa">
           <i class="material-symbols-outlined">
            fullscreen
            </i>
        </button>
      </div>
       <!-- OVERLAY solo mobile -->
<div id="compare-overlay" class="hidden fixed inset-0 bg-black/40 backdrop-blur-[1px] z-[90] lg:hidden"></div>

<!-- Popover del selector de pisos -->
<div id="compare-popover"
     class="hidden
            fixed inset-x-0 bottom-0 w-screen
            lg:absolute lg:inset-auto lg:right-30 lg:top-full lg:mt-2 lg:w-[150px]
            bg-white border border-[#EDECF1]
            rounded-t-[24px] lg:rounded-[16px]
            shadow-[0_12px_20px_rgba(0,0,0,0.12)] z-[95]"
     style="--safeBottom: env(safe-area-inset-bottom, 0px); padding-bottom: calc(0.75rem + var(--safeBottom));">
<div class="relative flex items-center px-2 py-2 border-b md:justify-between">
  <h3 class="mx-auto text-xs font-semibold text-gray-500 md:mx-0">
    Seleccionar Pisos
  </h3>

  <button
    id="compare-close"
    class="btn-fourth w-7 h-7 text-gray-700 hover:text-gray-400
           absolute right-2 top-1/2 -translate-y-1/2
           md:static md:transform-none md:top-auto md:right-auto"
    aria-label="Cerrar">
    <span class="fa-solid fa-xmark"></span>
  </button>
</div>


  <div id="compare-list" class="p-2 space-y-2 max-h-[60vh] overflow-auto">
    <!-- JS renderiza filas -->
  </div>
</div>

      </div>
    </div>
  </nav>

  <!-- Contenedores de tabs -->
  <div id="tab-galeria" class="hidden absolute inset-x-0 bottom-0 z-10 w-full h-full tabs-area"></div>
  <div id="tab-vistas"  class="hidden absolute inset-x-0 bottom-0 z-10 w-full h-full tabs-area"></div>
  <div id="tab-planos"  class="hidden absolute inset-x-0 bottom-0 z-10 w-full h-full tabs-area"></div>
  <div id="tab-tour"    class="hidden absolute inset-x-0 bottom-0 z-10 w-full h-full tabs-area"></div>

  <!-- Tarjeta controles inferior -->
<div class="fixed lg:absolute inset-x-0 z-30 flex justify-center pointer-events-auto apto-bottom transform-gpu">
    <div class="relative">
      <button
        class="btn-share absolute -top-[55px] left-1/2 -translate-x-1/2 w-10 h-10 bg-white text-gray-700 shadow-md rounded-full flex items-center justify-center z-10 transition"
        title="Compartir"
        data-url="<?= esc_url(get_permalink(get_the_ID())) ?>"
        data-title="<?= esc_attr($tipo_unidad_nombre . ' ' . get_the_title() . ' • ' . $superficie . 'm² • ' . $habitaciones . ' Hab.') ?>"
        data-img="<?= esc_url( wp_get_attachment_image_url($img, 'large') ) ?>"
      >
        <i class="fa-solid fa-share-nodes text-gray-600 text-xs"></i>
      </button>

      <div class="bg-black/90 backdrop-blur-sm rounded-full px-3 py-2 flex flex-col items-center space-y-2 shadow-lg text-center max-w-lg mx-auto">
        <div class="flex items-center gap-2 justify-center">
          <?php if ($gallery_images): ?>
            <button data-tab="tab-galeria" class="tab-btn btn-tertiary">
              <i class="material-symbols-outlined">
          imagesmode
          </i>
          <span class="hidden sm:inline">Galería</span>
            </button>
          <?php endif; ?>

          <?php if ($vistas_images): ?>
            <button data-tab="tab-vistas" class="tab-btn btn-tertiary">
                        <i class="material-symbols-outlined">
            photo_camera
            </i>
              <span class="hidden sm:inline">Vistas</span>
            </button>
          <?php endif; ?>

          <?php if ($planos_images): ?>
            <button data-tab="tab-planos" class="tab-btn btn-tertiary">
                          <i class="material-symbols-outlined">
          architecture
          </i>
              <span class="hidden sm:inline">Planos</span>
            </button>
          <?php endif; ?>

          <?php if ($tour_url): ?>
            <button data-tab="tab-tour" class="tab-btn btn-tertiary">
                          <i class="material-symbols-outlined">
          simulation
          </i>
              <span class="hidden sm:inline">Tour 360</span>
            </button>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</main>

<!-- Swiper -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css"/>
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

<style>
  .swiper-button-next::after,
  .swiper-button-prev::after { content: none !important; }

  #vista-apto { height: 100vh; height: 100svh; height: 100dvh; }
  .apto-bottom { bottom: calc(1.5rem + env(safe-area-inset-bottom, 0px)); }

  #vista-apto .swiper, #vista-apto .swiper-wrapper, #vista-apto .swiper-slide { height: 100%; }

  .image-slide-container {
    width: 100%;
    height: 100vh; height: 100svh; height: 100dvh;
    position: relative;
    background: black;
    display: flex; align-items: center; justify-content: center;
    overflow: hidden;
  }
  .full-screen-image {
    max-width: none; max-height: none;
    width: auto; height: auto;
    display: block; object-fit: contain; object-position: center;
  }

  /* Topbar: animación suave al ocultar/mostrar */
#apto-topbar{
  transition: opacity .25s ease, transform .25s ease;
}
#apto-topbar.nav-hidden{
  opacity: 0;
  transform: translateY(-8px);
  pointer-events: none; /* no tapa el tour mientras está oculto */
  visibility: hidden;   /* por si algún overlay calcula visibilidad */
}


  @media (min-width: 1024px) {
    .image-slide-container {
      width: 100vw !important; height: 100vh !important; height: 100svh !important; height: 100dvh !important;
      overflow: hidden !important; background: transparent !important;
    }
    .full-screen-image {
      width: 100vw !important; height: 100vh !important; height: 100svh !important; height: 100dvh !important;
      object-fit: cover !important; object-position: center center !important;
    }
    html, body { margin: 0; padding: 0; }
  }

  @media (max-width: 1023px) {
    .image-slide-container {
      overflow: hidden !important;
      -webkit-overflow-scrolling: auto !important;
      background: transparent !important;
      padding: 0 !important;
    }
    .full-screen-image {
      max-width: 100%; max-height: 100%;
      width: auto; height: auto;
      min-width: 0 !important; min-height: 0 !important;
      object-fit: contain !important; object-position: center center !important;
    }
    .btn-slide-arrow { transform: scale(0.9); }
  }

  /* ====== Estilo de píldoras del popover (como la foto) ====== */
  .compare-pill{
    display:block; width:100%;
    text-align:center;
    border:1px solid #E5E7EB; /* gray-200 */
    color:#111827;            /* gray-900 */
    background:#FFFFFF;
    padding:8px 10px;
    border-radius:9999px;
    font-size:12px;
    line-height:1;
    transition:background .15s, color .15s, border-color .15s;
  }
  .compare-pill:hover { background:#F9FAFB; } /* gray-50 */
  .compare-pill.active{
    background: rgba(96,98,110,1); /* #60626E */
    color:#FFFFFF;
    border-color: transparent;
  }

  /* (Se deja la clase antigua por compatibilidad, aunque ya no se usa) */
  .compare-row { transition: background-color .15s, box-shadow .15s; }

 

  /* ===== PLANOS: pan horizontal solo en mobile/tablet ===== */
@media (max-width: 1023px) {
  #tab-planos .panogram{
    height: 100vh; height: 100svh; height: 100dvh;
    overflow-x: auto;
    overflow-y: hidden;
    display: block;              /* clave: no grid/flex */
    white-space: normal;
    -webkit-overflow-scrolling: touch;
    overscroll-behavior-x: contain;
    touch-action: pan-x pinch-zoom;
    background: #000;            /* marco negro */
  }
  #tab-planos .panogram-img{
    height: 100vh; height: 100svh; height: 100dvh;  /* ocupa todo el alto */
    width: auto;                 /* mantiene proporción 1920x1080 */
    max-width: none;
    display: block;
    object-fit: contain;         /* se ve completa, sin recortes */
    
  }
}

</style>

<script>
  document.addEventListener('DOMContentLoaded', () => {
  // --- Fade-in al entrar a la página (solo opacidad) ---
const rootApto = document.getElementById('vista-apto');
if (rootApto) {
  // forzar reflow y disparar la animación
  requestAnimationFrame(() => {
    rootApto.classList.add('animate-fade-in');
    // al terminar, limpiamos la clase animate para no acumular
    rootApto.addEventListener('animationend', () => {
      rootApto.classList.remove('animate-fade-in', 'opacity-0');
    }, { once: true });
  });
}
});
</script>


<script>
document.addEventListener('DOMContentLoaded', () => {
  /**********************
   * CONFIG (prod)
   **********************/
  const DEBUG_CMP = false;                   // ponelo true si querés logs
  const SQUELCH_MS = 260;                    // ventana anti-tap fantasma
  const OPEN_GUARD_MS = 400;                 // tiempo mínimo antes de permitir autocierres
  const LOG = (...a) => DEBUG_CMP && console.log('[CMP]', ...a);

  /**********************
   * DOM refs
   **********************/
  const tabsBtns       = document.querySelectorAll('.tab-btn');
  const compareBtn     = document.getElementById('btn-comparar');
  const compareWrap    = document.getElementById('compare-wrapper');
  const comparePopover = document.getElementById('compare-popover');
  const compareClose   = document.getElementById('compare-close');
  const compareList    = document.getElementById('compare-list');
  const compareOverlay = document.getElementById('compare-overlay');

  // Datos PHP → JS
  const PISOS        = <?php echo wp_json_encode($pisos_list, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); ?>;
  const HAS_COMPARE  = <?php echo $has_compare ? 'true' : 'false'; ?>;
  const LOOP_GALERIA = <?= count($gallery_images) > 1 ? 'true' : 'false' ?>;
  const LOOP_VISTAS  = <?= count($vistas_images)  > 1 ? 'true' : 'false' ?>;
  const LOOP_PLANOS  = <?= count($planos_images)  > 1 ? 'true' : 'false' ?>;

  // Animaciones mobile ya existentes (NO tocar)
  const ANIM_IN_MOBILE  = 'animate-fade-in-up';
  const ANIM_OUT_MOBILE = 'animate-fade-out-down';

  // Animaciones desktop/tablet (nuevo fade)
  const ANIM_IN_DESK  = 'animate-fade-in';
  const ANIM_OUT_DESK = 'animate-fade-out';

  const isMobile = () => window.matchMedia('(max-width: 1023px)').matches;

  /**********************
   * STATE
   **********************/
  const state = {
    tab: null,
    open: false,
    openingAt: 0,
    squelchUntil: 0
  };
  const now = () => performance.now();
  const inSquelch = () => now() < state.squelchUntil;
  const setSquelch = (ms=SQUELCH_MS) => state.squelchUntil = now() + ms;

  /**********************
   * SLIDES/TABS
   **********************/
  window.centerImageOnLoad = (img) => { img.style.opacity = '1'; };

function generateSlides(images, altText, contained = false) {
  return images.map((img) => {
    const width  = img.width || 1920;
    const height = img.height || 1080;
    const aspect = width / height;
    const isWide = aspect > 1.5;
    const src = img.url || (img.sizes && (img.sizes.full || img.sizes.large)) || img.url;

    // Importante: SIN opacity inline y SIN onload aquí.
    // Dejamos que el efecto 'fade' de Swiper maneje la opacidad del slide.
    return `
      <div class="swiper-slide">
        <div class="image-slide-container" ${contained ? 'data-contained="true"' : ''}>
          <img
            src="${src}"
            alt="${altText}"
            class="full-screen-image"
            ${isWide ? 'data-wide="true"' : ''}
            loading="lazy"
            fetchpriority="auto"
          >
        </div>
      </div>`;
  }).join('');
}

/* ===== PLANOS: slides con contenedor scrollable (solo mobile/tablet) ===== */
function generatePlanoSlides(images) {
  return images.map(img => {
    const src = img.url || (img.sizes && (img.sizes.full || img.sizes.large)) || img.url;
    return `
      <div class="swiper-slide">
        <div class="panogram" data-pan="1">
          <img src="${src}" alt="Plano del apartamento" class="panogram-img" loading="lazy">
        </div>
      </div>`;
  }).join('');
}

/* Versión dinámica: en mobile usa pan horizontal; en desktop mantiene tu layout previo */
function generatePlanosMarkup() {
  const images = <?= json_encode($planos_images) ?>;
  const slides = isMobile()
    ? generatePlanoSlides(images)                               // pan horizontal
    : generateSlides(images, 'Plano del apartamento', true);    // desktop: como estaba (contain)
  return `
    <div class="swiper h-full w-full">
      <div class="swiper-wrapper">
        ${slides}
      </div>
      <div class="swiper-button-prev btn-slide-arrow"><i class="fa-solid fa-chevron-left"></i></div>
      <div class="swiper-button-next btn-slide-arrow"><i class="fa-solid fa-chevron-right"></i></div>
    </div>`;
}



  const templates = {
    'tab-galeria': `
      <div class="swiper h-full w-full">
        <div class="swiper-wrapper">
          ${generateSlides(<?= json_encode($gallery_images) ?>, 'Imagen galería', true)}
        </div>
        <div class="swiper-button-prev btn-slide-arrow"><i class="fa-solid fa-chevron-left"></i></div>
        <div class="swiper-button-next btn-slide-arrow"><i class="fa-solid fa-chevron-right"></i></div>
      </div>
    `,
    'tab-vistas': `
      <div class="swiper h-full w-full">
        <div class="swiper-wrapper">
          ${generateSlides(<?= json_encode($vistas_images) ?>, 'Vista del apartamento', false)}
        </div>
        <div class="swiper-button-prev btn-slide-arrow"><i class="fa-solid fa-chevron-left"></i></div>
        <div class="swiper-button-next btn-slide-arrow"><i class="fa-solid fa-chevron-right"></i></div>
      </div>
    `,
'tab-planos': `${generatePlanosMarkup()}

    `,
    'tab-tour': `
      <iframe src="<?= esc_url($tour_url) ?>"
              class="w-full h-full object-cover bg-black"
              frameborder="0"
              allowfullscreen></iframe>
    `
  };

  // Topbar superior (overlay)
const topbar = document.getElementById('apto-topbar');

/** Oculta el nav superior sólo en Tour360; lo muestra en el resto */
function toggleTopbarForTab(id){
  if (!topbar) return;
  if (id === 'tab-tour') topbar.classList.add('nav-hidden');
  else topbar.classList.remove('nav-hidden');
}


  function showCompareIfNeeded(activeId) {
    if (activeId === 'tab-vistas' && HAS_COMPARE && PISOS.length > 1) {
      compareBtn?.classList.remove('hidden');
      compareBtn?.setAttribute('aria-expanded', 'false');
    } else {
      compareBtn?.classList.add('hidden');
      if (state.open && (now() - state.openingAt) >= OPEN_GUARD_MS) hideCompare();
    }
  }

  function centerPlanoActive(){
  if (!isMobile()) return;
  const scroller = document.querySelector('#tab-planos .swiper-slide-active .panogram');
  if (!scroller) return;
  const mid = Math.max(0, (scroller.scrollWidth - scroller.clientWidth) / 2);
  scroller.scrollLeft = mid;
}

  function activarTab(id) {
    LOG('activarTab →', id);
    state.tab = id;
    toggleTopbarForTab(id);

    const allTabs = Array.from(document.querySelectorAll('div[id^="tab-"]'));
    const next = document.getElementById(id);
    const current = allTabs.find(d => !d.classList.contains('hidden') && d.id !== id);

    // 1) Preparar el destino
    next.innerHTML = templates[id];

    // 2) Instanciar swiper/iframe si corresponde
    if (id !== 'tab-tour') {
      const loopFlag =
        id === 'tab-galeria' ? LOOP_GALERIA :
        id === 'tab-vistas'  ? LOOP_VISTAS  :
        id === 'tab-planos'  ? LOOP_PLANOS  : false;

new Swiper(`#${id} .swiper`, {
  slidesPerView: 1,
  loop: loopFlag,
  allowTouchMove: false,
  effect: 'fade',
  fadeEffect: { crossFade: true },
  speed: 450,
  observer: true,
  observeParents: true,
  preloadImages: true,
  updateOnImagesReady: true,
  watchSlidesProgress: true,
  navigation: {
    nextEl: `#${id} .swiper-button-next`,
    prevEl: `#${id} .swiper-button-prev`,
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
        if (id === 'tab-planos') {
          setTimeout(() => centerPlanoActive(), 100);
        }
      });
    },
    
    imagesReady(s) {
      s.update();
      // Centrar cuando las imágenes estén listas
      if (id === 'tab-planos') {
        setTimeout(() => centerPlanoActive(), 100);
      }
    },
    
    slideChange(s) {
      // Centrar al cambiar de slide
      if (id === 'tab-planos') {
        setTimeout(() => centerPlanoActive(), 50);
      }
    },
    
    slideChangeTransitionEnd(s) {
      // Centrar cuando termine la transición
      if (id === 'tab-planos') {
        centerPlanoActive();
      }
    }
  },
});


    }

    // 3) Fade-out del tab actual (si hay)
    if (current) {
      current.classList.add(ANIM_OUT_DESK);
      current.addEventListener('animationend', () => {
        current.classList.add('hidden');
        current.classList.remove(ANIM_OUT_DESK);
        current.innerHTML = '';
      }, { once: true });
    }

    // 4) Mostrar el nuevo y hacer fade-in
    next.classList.remove('hidden');
    next.classList.add(ANIM_IN_DESK);
    next.addEventListener('animationend', () => {
      next.classList.remove(ANIM_IN_DESK);
      if (id === 'tab-planos') centerPlanoActive();       // ← nuevo
    }, { once: true });


    // 5) Marcar botón activo + hash + compare
    tabsBtns.forEach(b => b.classList.remove('active'));
    document.querySelector(`[data-tab="${id}"]`)?.classList.add('active');
    history.replaceState(null, null, `#${id.replace('tab-','')}`);

    showCompareIfNeeded(id);

    // 6) Ocultar y vaciar todos los demás tabs no relevantes
    allTabs.forEach(div => {
      if (div.id !== id && div !== current) {
        div.classList.add('hidden');
        div.innerHTML = '';
      }
    });
  }

  tabsBtns.forEach(btn => btn.addEventListener('click', () => activarTab(btn.dataset.tab)));

  /**********************
   * SELECTOR DE PISOS (mobile bottom sheet + desktop popover)
   **********************/
  function renderCompareList() {
    if (!compareList) return;
    compareList.innerHTML = PISOS.map(item => {
      const isCurrent = !!item.is_current;
      const safeHref  = (item.permalink || '') + '#vistas';
      return `
        <button type="button" class="compare-pill ${isCurrent ? 'active' : ''}"
                data-href="${safeHref}" data-current="${isCurrent ? '1':'0'}">
          Piso ${item.nivel_name}
        </button>
      `;
    }).join('');
  }

  function showCompare() {
    // si no estamos en Vistas, cambiamos primero (evita interferencias)
    if (state.tab !== 'tab-vistas') activarTab('tab-vistas');

    renderCompareList();

    requestAnimationFrame(() => {
      if (isMobile()) {
        // MOBILE: mantener animación existente (slide up)
        compareOverlay?.classList.remove('hidden');
        comparePopover.classList.remove('hidden', ANIM_OUT_MOBILE);
        void comparePopover.offsetWidth;
        comparePopover.classList.add(ANIM_IN_MOBILE);
      } else {
        // DESKTOP/TABLET: nuevo fade-in
        comparePopover.classList.remove('hidden');
        comparePopover.classList.remove(ANIM_OUT_DESK);
        void comparePopover.offsetWidth;
        comparePopover.classList.add(ANIM_IN_DESK);
        comparePopover.addEventListener('animationend', () => {
          comparePopover.classList.remove(ANIM_IN_DESK);
        }, { once: true });
      }
      compareBtn?.setAttribute('aria-expanded', 'true');
      state.open = true;
      state.openingAt = now();
      setSquelch();
    });
  }

  function hideCompare() {
    if (!state.open && comparePopover.classList.contains('hidden')) {
      compareOverlay?.classList.add('hidden');
      return;
    }
    if (isMobile()) {
      // MOBILE: mantener animación existente (slide down)
      comparePopover.classList.remove(ANIM_IN_MOBILE);
      comparePopover.classList.add(ANIM_OUT_MOBILE);
      comparePopover.addEventListener('animationend', () => {
        comparePopover.classList.add('hidden');
        comparePopover.classList.remove(ANIM_OUT_MOBILE);
      }, { once: true });
      compareOverlay?.classList.add('hidden');
    } else {
      // DESKTOP/TABLET: nuevo fade-out
      comparePopover.classList.remove(ANIM_IN_DESK);
      comparePopover.classList.add(ANIM_OUT_DESK);
      comparePopover.addEventListener('animationend', () => {
        comparePopover.classList.add('hidden');
        comparePopover.classList.remove(ANIM_OUT_DESK);
      }, { once: true });
    }
    compareBtn?.setAttribute('aria-expanded', 'false');
    state.open = false;
  }

  function toggleCompare(e) {
    if (e) { e.preventDefault(); e.stopPropagation(); }
    if (inSquelch()) return;
    state.open ? hideCompare() : showCompare();
  }

  // Abrir con pointerdown (evita click fantasma que cae en el overlay)
  compareBtn?.addEventListener('pointerdown', toggleCompare);
  // idempotente en desktop: anulamos click
  compareBtn?.addEventListener('click', (e) => e.preventDefault());

  compareClose?.addEventListener('click', (e) => { e.stopPropagation(); hideCompare(); });
  compareOverlay?.addEventListener('click', () => {
    if ((now() - state.openingAt) < OPEN_GUARD_MS) return; // guardia de apertura
    hideCompare();
  });

  // Click-fuera SOLO desktop/tablet
  document.addEventListener('click', (e) => {
    if (isMobile()) return;
    if (!compareWrap) return;
    if (!compareWrap.contains(e.target)) hideCompare();
  });

  // ESC
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') hideCompare(); });

  // Limpieza al cambiar breakpoint
  window.addEventListener('resize', () => {
    if (!isMobile()) {
      compareOverlay?.classList.add('hidden');
      comparePopover?.classList.remove(ANIM_IN_MOBILE, ANIM_OUT_MOBILE);
      comparePopover?.classList.remove(ANIM_IN_DESK, ANIM_OUT_DESK);
    }
  });

  // Navegación entre pisos (delegación)
  compareList?.addEventListener('click', (e) => {
    const pill = e.target.closest('.compare-pill');
    if (!pill) return;
    e.preventDefault();
    e.stopPropagation();
    const isCurrent = pill.dataset.current === '1';
    const href      = pill.dataset.href;
    hideCompare();
    if (!isCurrent && href) window.location.replace(href);
  });

  /**********************
   * INIT
   **********************/
  const h = location.hash.replace('#','');
  if (['galeria','vistas','planos','tour'].includes(h)) activarTab(`tab-${h}`);
  else if (<?= json_encode(!empty($gallery_images)); ?>) activarTab('tab-galeria');
  else if (<?= json_encode(!empty($vistas_images)); ?>) activarTab('tab-vistas');
  else if (<?= json_encode(!empty($planos_images)); ?>) activarTab('tab-planos');
  else if ('<?= $tour_url ?>') activarTab('tab-tour');

  LOG('ready');

  window.addEventListener('load', () => {
  if (state.tab === 'tab-planos') centerPlanoActive();
});
window.addEventListener('resize', () => {
  if (state.tab === 'tab-planos') setTimeout(centerPlanoActive, 50);
});
window.addEventListener('orientationchange', () => {
  if (state.tab === 'tab-planos') setTimeout(centerPlanoActive, 100);
});

});
</script>


<script>
document.addEventListener('DOMContentLoaded', () => {
  const wrapper  = document.getElementById('info-wrapper');
  const btn      = document.getElementById('btn-info');
  const popover  = document.getElementById('info-popover');
  const closeBtn = document.getElementById('close-info');
  const overlay  = document.getElementById('info-overlay');

  // Mobile (NO tocar)
  const ANIM_IN_MOBILE  = 'animate-fade-in-up';
  const ANIM_OUT_MOBILE = 'animate-fade-out-down';

  // Desktop/Tablet (fade como selector de pisos)
  const ANIM_IN_DESK  = 'animate-fade-in';
  const ANIM_OUT_DESK = 'animate-fade-out';

  // Anti-cierre inmediato
  const OPEN_GUARD_MS = 400;
  const SQUELCH_MS    = 260;

  const isMobile   = () => window.matchMedia('(max-width: 1023px)').matches;
  const now        = () => performance.now();

  const state = {
    open: false,
    openingAt: 0,
    squelchUntil: 0,
  };
  const inSquelch = () => now() < state.squelchUntil;
  const setSquelch = (ms=SQUELCH_MS) => state.squelchUntil = now() + ms;

  if (!wrapper || !btn || !popover) return;

  /* ---------- MOBILE: mantener slide up/down ---------- */
  function showMobileSheet() {
    overlay?.classList.remove('hidden');
    popover.classList.remove('hidden');
    popover.classList.remove(ANIM_OUT_MOBILE, ANIM_IN_DESK, ANIM_OUT_DESK);
    void popover.offsetWidth; // reflow
    popover.classList.add(ANIM_IN_MOBILE);
    btn.setAttribute('aria-expanded', 'true');
    state.open = true;
    state.openingAt = now();
    setSquelch();
  }

  function hideMobileSheet() {
    if (popover.classList.contains('hidden')) return;
    popover.classList.remove(ANIM_IN_MOBILE, ANIM_IN_DESK);
    popover.classList.add(ANIM_OUT_MOBILE);
    const onEnd = () => {
      popover.classList.add('hidden');
      popover.classList.remove(ANIM_OUT_MOBILE);
      popover.removeEventListener('animationend', onEnd);
    };
    popover.addEventListener('animationend', onEnd, { once: true });
    overlay?.classList.add('hidden');
    btn.setAttribute('aria-expanded', 'false');
    state.open = false;
  }

  /* ---------- DESKTOP/TABLET: nuevo fade in/out + guard ---------- */
  function openInfoDesk() {
    overlay?.classList.add('hidden'); // sin overlay en desktop
    popover.classList.remove('hidden');
    popover.classList.remove(ANIM_OUT_DESK, ANIM_IN_MOBILE, ANIM_OUT_MOBILE);
    void popover.offsetWidth; // reflow
    popover.classList.add(ANIM_IN_DESK);
    popover.addEventListener('animationend', () => {
      popover.classList.remove(ANIM_IN_DESK);
    }, { once: true });
    btn.setAttribute('aria-expanded', 'true');
    state.open = true;
    state.openingAt = now();
    setSquelch();
  }

  function closeInfoDesk() {
    popover.classList.remove(ANIM_IN_DESK, ANIM_IN_MOBILE);
    popover.classList.add(ANIM_OUT_DESK);
    popover.addEventListener('animationend', () => {
      popover.classList.add('hidden');
      popover.classList.remove(ANIM_OUT_DESK);
    }, { once: true });
    btn.setAttribute('aria-expanded', 'false');
    state.open = false;
  }




  function openInfo()  { isMobile() ? showMobileSheet() : openInfoDesk(); }
  function closeInfo() { isMobile() ? hideMobileSheet() : closeInfoDesk(); }

  function toggleInfo(e) {
    // Evita doble firing (click después de pointerdown) y burbujeo
    e?.preventDefault?.();
    e?.stopPropagation?.();
    if (inSquelch()) return;
    state.open ? closeInfo() : openInfo();
  }

  // Usa pointerdown para abrir/cerrar (evita el "click fantasma")
  btn.addEventListener('pointerdown', toggleInfo);
  // y anulamos el click para que no dispare nada más
  btn.addEventListener('click', (e) => e.preventDefault());

  closeBtn?.addEventListener('click', (e) => { e.stopPropagation(); closeInfo(); });
 function onOverlayTap(e){
  // evita que el primer tap que abre también lo cierre
  if ((now() - state.openingAt) < OPEN_GUARD_MS) return;
  e?.preventDefault?.();
  e?.stopPropagation?.();
  closeInfo();
}
overlay?.addEventListener('pointerdown', onOverlayTap);
overlay?.addEventListener('click', onOverlayTap);;

  // Cerrar con ESC
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && state.open) closeInfo();
  });

  // Click-fuera SOLO desktop/tablet (con guard de apertura)
  document.addEventListener('click', (e) => {
    if (isMobile()) return;
    if (!state.open) return;
    if ((now() - state.openingAt) < OPEN_GUARD_MS) return; // ⬅️ evita cierre inmediato al abrir
    if (!wrapper.contains(e.target)) closeInfoDesk();
  });

  // Limpieza al cambiar breakpoint
  window.addEventListener('resize', () => {
    if (!isMobile()) {
      overlay?.classList.add('hidden');
      popover?.classList.remove(ANIM_IN_MOBILE, ANIM_OUT_MOBILE, ANIM_IN_DESK, ANIM_OUT_DESK);
    }
  });
});
</script>



<script>
document.addEventListener('DOMContentLoaded', () => {
  const back = document.getElementById('btn-back');
  if (!back) return;

  // ¿Venimos del archive?
  const REF = document.referrer || '';
  const cameFromArchive = /\/unidades(\/|$|\?)/.test(REF);

  // Click en "Regresar"
  back.addEventListener('click', (e) => {
    // Si realmente venís del archive y hay historial, usá history.back (bfcache = vuelve con todo el estado intacto)
    if (cameFromArchive && history.length > 1) {
      e.preventDefault();
      history.back();
      return;
    }
    // Si no, dejamos que use el href que abajo resolvemos con filtros de sessionStorage
  });

  // Fallback: construir href al archive con los filtros/página aplicados
  try {
    // cluster actual desde PHP
    const CLUSTER_ID  = <?php echo (int) ($current_cluster_id ?? 0); ?>;
    const STORAGE_KEY = `unidadesFilters:${CLUSTER_ID}`;

    // Empezamos desde el data-back-href (ya apunta al archive con cluster_id base)
    const baseHref = back.getAttribute('data-back-href') || back.href;
    const url = new URL(baseHref, window.location.origin);

    // Asegurar que quede el cluster_id
    if (CLUSTER_ID) url.searchParams.set('cluster_id', String(CLUSTER_ID));

    // Si hay filtros guardados, agregarlos a la URL
    const raw = CLUSTER_ID ? sessionStorage.getItem(STORAGE_KEY) : null;
    if (raw) {
      const parsed = JSON.parse(raw);
      const filters = parsed && parsed.filters ? parsed.filters : null;
      const page    = parsed && parsed.page ? Number(parsed.page) : 1;

      if (filters && typeof filters === 'object') {
        ['nivel','habitaciones','superficie','vista'].forEach(k => {
          const v = filters[k];
          if (v) url.searchParams.set(k, v);
          else   url.searchParams.delete(k);
        });
      }
      if (page && page > 1) url.searchParams.set('pagina', String(page));
      else url.searchParams.delete('pagina');
    }

    // Actualizar el href final del botón
    back.setAttribute('href', url.toString());
    back.setAttribute('data-back-resolved', '1');
  } catch (err) {
    // Si algo falla, dejamos el href original (cluster_back_url) y listo.
    // console.debug('fallback back-url', err);
  }
});
</script>




<?php get_footer(); ?>
