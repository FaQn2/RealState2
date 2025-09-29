<?php

function cargar_fuentes_custom() {
  wp_enqueue_style(
    'google-fonts',
    'https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&family=Playfair+Display:wght@400;700&display=swap',
    false
  );
}
add_action('wp_enqueue_scripts', 'cargar_fuentes_custom');

function theme_enqueue_assets() {
  $css_file = get_template_directory() . '/dist/assets/style.css';
  if ( file_exists( $css_file ) ) {
    $ver = filemtime( $css_file );
    wp_enqueue_style(
      'theme-tailwind',
      get_template_directory_uri() . '/dist/assets/style.css',
      [],
      $ver
    );
  }
}
add_action('wp_enqueue_scripts', 'theme_enqueue_assets');



// Soporte básico del theme
add_action('after_setup_theme', function() {
  add_theme_support('title-tag');
  add_theme_support('post-thumbnails');
});

// Registrar Custom Post Types
function register_custom_post_types() {
  register_post_type('cluster', [
    'label' => 'Clusters',
    'public' => true,
    'menu_icon' => 'dashicons-location-alt',
    'has_archive' => true,
    'rewrite' => ['slug' => 'clusters'],
    'supports' => ['title'],
    'show_in_rest' => true,
  ]);

  register_post_type('apartamento', [
    'label' => 'Unidades',
    'public' => true,
    'menu_icon' => 'dashicons-building',
    'has_archive' => true,
    'rewrite'      => [
    'slug'       => 'unidades',
    'with_front' => false,
  ],
    'supports' => ['title', 'thumbnail'],
    'show_in_rest' => true,
  ]);
  

  register_post_type('amenity', [
    'label' => 'Amenities',
    'public' => true,
    'menu_icon' => 'dashicons-palmtree',
    'has_archive' => true,
    'rewrite' => ['slug' => 'amenidades'],
    'supports' => ['title', 'thumbnail'],
    'show_in_rest' => true,
  ]);
}
add_action('init', 'register_custom_post_types');

// CPT adicional: Galería
function register_galeria_cpt() {
  register_post_type('galeria', [
    'label' => 'Galerías',
    'public' => true,
    'menu_icon' => 'dashicons-format-gallery',
    'has_archive' => false,
    'rewrite' => ['slug' => 'galerias'],
    'supports' => ['title'],
    'show_in_rest' => true,
  ]);
}
add_action('init', 'register_galeria_cpt');


/**
 * Valida que el campo 'orden' de amenities sea único
 */
add_filter('acf/validate_value/name=orden', function ($valid, $value, $field, $input) {

    if (!$valid) {
        return $valid; // ya hay otro error
    }

    // ID actual (para permitir editar sin conflicto)
    $current_post_id = isset($_POST['post_ID']) ? intval($_POST['post_ID']) : 0;

    // Buscamos si hay otro amenity con esa posición
    $args = [
        'post_type'      => 'amenity',
        'post_status'    => 'any',
        'meta_key'       => 'orden',
        'meta_value'     => $value,
        'fields'         => 'ids',
        'posts_per_page' => 1,
    ];

    $query = new WP_Query($args);
    if ($query->have_posts()) {
        foreach ($query->posts as $post_id) {
            if ($post_id != $current_post_id) {
                return '⚠️ Esta posición ya está asignada a otra amenidad.';
            }
        }
    }

    return $valid;
}, 10, 4);



// Opciones de colores globales (ACF)
if (function_exists('acf_add_options_page')) {
  acf_add_options_page([
    'page_title'  => 'Ajustes del Proyecto',
    'menu_title'  => 'Ajustes del Proyecto',
    'menu_slug'   => 'ajustes-proyecto',
    'capability'  => 'edit_posts',
    'redirect'    => false
  ]);
}

//Color del boton de whatsapp
add_action('wp_enqueue_scripts', function () {
  $css = '
  /* ===== Click to Chat: usar el color del theme ===== */

  /* 1) Estado normal: solo el círculo */
  #ht-ctc-chat .ctc_s_7_icon_padding {
    background-color: rgb(var(--color-primary)) !important;
    color: rgb(var(--color-white)) !important;
    border-radius: 50% !important;        /* asegura la forma circular */
  }

  /* 2) Forzar el fill del ícono a currentColor (blanco) */
  #ht-ctc-chat .ctc_s_7_icon_padding svg path,
  #ht-ctc-chat .ctc_s_7_icon_padding svg [fill] {
    fill: currentColor !important;
  }

  /* 3) Hover: ganarle al estilo inline del plugin (usa !important y más especificidad) */
  #ht-ctc-chat .ctc_s_7:hover .ctc_s_7_icon_padding,
  #ht-ctc-chat .ctc_s_7:hover .ctc_cta_stick {
    background-color: rgb(var(--color-primary)) !important;
    color: rgb(var(--color-white)) !important;
  }
  #ht-ctc-chat .ctc_s_7:hover svg g path {
    fill: currentColor !important;
  }

  /* 4) (Opcional) CTA pill cuando esté visible */
  #ht-ctc-chat .ctc_cta_stick {
    background-color: rgb(var(--color-primary)) !important;
    color: rgb(var(--color-white)) !important;
    border-radius: 10px !important;
  }

  /* 5) Micro-interacciones */
  #ht-ctc-chat .ctc_s_7_icon_padding:hover { filter: brightness(0.95); }
  #ht-ctc-chat .ctc_s_7_icon_padding:active { transform: scale(0.98); }
  ';
  wp_add_inline_style('theme-tailwind', $css);
}, 20);


// Estilos para ítems del dashboard admin
add_action('admin_head', function() {
  echo '<style>
    #adminmenu .menu-icon-cluster > a,
    #adminmenu .menu-icon-apartamento > a,
    #adminmenu .menu-icon-amenity > a,
    #adminmenu .menu-icon-galeria > a {
        background-color: #0057b8 !important;
        color: #ffffff !important;
        border-radius: 4px;
    }
    #adminmenu .menu-icon-cluster:hover > a,
    #adminmenu .menu-icon-apartamento:hover > a,
    #adminmenu .menu-icon-amenity:hover > a,
    #adminmenu .menu-icon-galeria:hover > a {
        background-color: #004aa1 !important;
    }
    #adminmenu .menu-icon-cluster.wp-has-current-submenu > a,
    #adminmenu .menu-icon-apartamento.wp-has-current-submenu > a,
    #adminmenu .menu-icon-amenity.wp-has-current-submenu > a,
    #adminmenu .menu-icon-galeria.wp-has-current-submenu > a {
        background-color: #004aa1 !important;
    }
  </style>';
});



function enqueue_swup_scripts() {
  wp_enqueue_script(
    'swup',
    'https://unpkg.com/swup@4/dist/Swup.modern.js',
    [],
    '4.6.1',
    true
  );
  
  wp_enqueue_script(
    'swup-init',
    get_template_directory_uri() . '/assets/js/swup-init.js',
    ['swup'],
    filemtime(get_template_directory() . '/assets/js/swup-init.js'),
    true
  );
  
  wp_enqueue_style(
    'swup-transitions',
    get_template_directory_uri() . '/assets/css/swup-transitions.css',
    ['theme-tailwind'],
    filemtime(get_template_directory() . '/assets/css/swup-transitions.css')
  );
}
add_action('wp_enqueue_scripts', 'enqueue_swup_scripts');


// 🧠 Columnas personalizadas para el dashboard (mover archivo a /functions/)
require_once get_template_directory() . '/functions/columns-dashboard.php';

//llamaos a las funciones de filtrado
require_once get_template_directory() . '/filtros/ajax-filtros.php';

//metatags para el opengraph para compartir
require_once get_template_directory() . '/functions/open-graph.php';


//full screen buttom
/* require_once get_template_directory() . '/functions/full-screen-buttom.php';
 */



