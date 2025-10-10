<?php
/*
Template Name: Nivel 1 (Visual Interactivo)
*/
get_header();
$iframe_url = get_field('iframe_nivel_1');

if ($iframe_url): ?>
  <style>
    /* Safe areas para notch (iPhone) */
    :root{
      --safe-top: env(safe-area-inset-top, 0px);
      --safe-bottom: env(safe-area-inset-bottom, 0px);
    }

    html, body{
      margin:0; padding:0;
      height:100%;
      overflow:hidden;
      background:#000;
    }

    /* Contenedor a pantalla completa con fallbacks ordenados */
    .iframe-container{
      position: fixed;
      inset: 0;
      width: 100vw;

      /* Fallback clásico */
      height: 100vh;

      /* iOS/Android modernos: evita el bug de la barra */
      /* (cuando hay soporte de svh/dvh, pisa al 100vh) */
    }
    @supports (height: 100svh){
      .iframe-container{ height: 100svh; }
    }
    @supports (height: 100dvh){
      .iframe-container{ height: 100dvh; }
    }
    /* iOS Safari viejito (13/14) */
    @supports (-webkit-touch-callout: none){
      html, body{ height: -webkit-fill-available; }
      .iframe-container{ height: -webkit-fill-available; }
    }

    /* Respeta safe-areas (evita que el iframe quede bajo la barra/home-indicator) */
    .iframe-container{
      padding-top: var(--safe-top);
      padding-bottom: var(--safe-bottom);
      box-sizing: border-box;
      overscroll-behavior: none;
    }

    .iframe-container iframe{
      display:block;
      width:100%;
      height:100%;
      border:0;
    }
  </style>

  <div class="iframe-container">
    <iframe loading="lazy" src="<?= esc_url($iframe_url) ?>" allowfullscreen></iframe>
  </div>

<?php else: ?>
  <div style="padding: 2rem; text-align: center;">
    <h2>⚠️ Visual no disponible</h2>
    <p>No se encontró la URL del visual interactivo. Configúralo desde el admin.</p>
  </div>
<?php endif; ?>
<?php get_footer(); ?>