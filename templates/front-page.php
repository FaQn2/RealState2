<?php
/**
 * Template Name: Video Portada (full-screen sin scroll)
 */

$page = get_page_by_title('Portada');
if (!$page) { echo "<p class='text-center py-20 text-gray-600'>🚫 La página 'Portada' no existe.</p>"; get_footer(); return; }

$page_id         = $page->ID;
$mostrar         = get_field('mostrar_portada',  $page_id);
$video_url       = get_field('video_url',        $page_id);
$imagen_url      = get_field('imagen_url',       $page_id);
$text_principal  = get_field('texto_principal',  $page_id);
$text_secundario = get_field('texto_secundario', $page_id);
$texto_boton     = get_field('texto_boton',      $page_id);
$url_boton       = get_field('url_boton',        $page_id);

if (!$mostrar) { echo "<p class='text-center py-20 text-gray-600'>🚫 Portada desactivada.</p>"; get_footer(); return; }

get_header();

/** Helpers **/
function tt_get_youtube_id($url) {
  // youtu.be/ID  |  youtube.com/watch?v=ID  |  youtube.com/embed/ID
  if (preg_match('~youtu\.be/([a-zA-Z0-9_-]{11})~', $url, $m)) return $m[1];
  if (preg_match('~youtube\.com/(?:embed/|shorts/|watch\?v=)([a-zA-Z0-9_-]{11})~', $url, $m)) return $m[1];
  // fallback: busca un token de 11 chars
  if (preg_match('~([a-zA-Z0-9_-]{11})~', $url, $m)) return $m[1];
  return null;
}

function tt_get_vimeo_id($url) {
  // vimeo.com/ID  |  player.vimeo.com/video/ID
  if (preg_match('~vimeo\.com/(?:video/)?([0-9]+)~', $url, $m)) return $m[1];
  return null;
}

// Construir src del iframe según proveedor
$embed_src = '';
if ($video_url && !$imagen_url) {
  if (preg_match('~(youtube\.com|youtu\.be)~i', $video_url)) {
    $yt_id = tt_get_youtube_id($video_url);
    if ($yt_id) {
      $params = http_build_query([
        'autoplay'        => 1,
        'mute'            => 1,          // clave para autoplay
        'playsinline'     => 1,          // iOS inline
        'controls'        => 0,
        'rel'             => 0,
        'modestbranding'  => 1,
        'loop'            => 1,
        'playlist'        => $yt_id,     // requerido para loop en un solo video
      ]);
      $embed_src = "https://www.youtube.com/embed/{$yt_id}?{$params}";
    }
  } elseif (preg_match('~vimeo\.com~i', $video_url)) {
    $vm_id = tt_get_vimeo_id($video_url);
    if ($vm_id) {
      $params = http_build_query([
        'background' => 1,
        'autoplay'   => 1,
        'muted'      => 1,
        'loop'       => 1,
        'autopause'  => 0,
      ]);
      $embed_src = "https://player.vimeo.com/video/{$vm_id}?{$params}";
    } else {
      // Si ya viene con player.vimeo.com + params, úsalo tal cual
      $embed_src = $video_url;
    }
  } else {
    // URL no reconocida: usar tal cual (último recurso)
    $embed_src = $video_url;
  }
}
?>
<style>
  /* 1️⃣ Sin scroll global */
  html, body { height: 100%; overflow: hidden; }
  body { margin: 0; overscroll-behavior: none; }
  body::-webkit-scrollbar { display: none; }
</style>

<main class="relative w-[100vw] h-[100dvh] overflow-hidden font-fuente_primaria">
  <!-- Media de fondo -->
  <div class="absolute inset-0 -z-10 overflow-hidden">
    <?php if ($imagen_url): ?>
      <img src=Para q"<?= esc_url($imagen_url) ?>" alt=""
           class="w-full h-full object-cover select-none pointer-events-none">
    <?php elseif ($embed_src): ?>
      <!-- 16:9 cover centrado -->
     <iframe
  src="<?= esc_url($embed_src) ?>"
  title="Background video"
  loading="eager"
  frameborder="0"
  referrerpolicy="strict-origin-when-cross-origin"
  allow="autoplay; fullscreen; picture-in-picture; encrypted-media"
  allowfullscreen
  class="absolute top-1/2 left-1/2
         w-[max(100vw,177.78dvh)] h-[max(56.25vw,100dvh)]
         -translate-x-1/2 -translate-y-1/2
         pointer-events-none select-none"></iframe>

    <?php else: ?>
      <div class="w-full h-full grid place-items-center bg-black/40 text-white">
        <p>No se encontró un video válido ni imagen de portada.</p>
      </div>
    <?php endif; ?>
  </div>

  <!-- Overlay centrado -->
  <div class="absolute inset-0 flex flex-col items-center justify-center text-center text-white px-[clamp(1rem,3vw,3rem)]">
    <?php if ($text_secundario): ?>
      <p class="mb-[clamp(.5rem,1vh,1rem)] text-[clamp(1rem,2.5vw,1.5rem)]">
        <?= esc_html($text_secundario) ?>
      </p>
    <?php endif; ?>
    <?php if ($text_principal): ?>
      <h1 class="font-bold leading-tight mb-[clamp(1rem,2vh,2rem)] text-[clamp(2rem,6vw,4.5rem)]">
        <?= esc_html($text_principal) ?>
      </h1>
    <?php endif; ?>
    <?php if ($texto_boton && $url_boton): ?>
      <a href="<?= esc_url($url_boton) ?>"
         class="inline-block border-2 border-white rounded-full font-semibold
                px-[clamp(1.5rem,4vw,3rem)]
                py-[clamp(.75rem,1.5vw,1rem)]
                hover:bg-white/20 transition-colors">
        <?= esc_html($texto_boton) ?>
      </a>
    <?php endif; ?>
  </div>
</main>

<?php get_footer(); ?>
