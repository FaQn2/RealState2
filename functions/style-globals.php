<?php
  // Colores
  $color_primario   = get_field('color_primario', 'option') ?: '#2481f5'; 
  $color_secundario = get_field('color_secundario', 'option') ?: '#28a745';
  $color_text       = get_field('color_text', 'option') ?: '#454545';
  $color_white      = get_field('color_white', 'option') ?: '#ffffff';
  $color_black      = get_field('color_black', 'option') ?: '#000000';

  // Fuentes
  $fuente_primaria   = get_field('fuente_primaria', 'option') ?: "'Poppins', sans-serif";
  $fuente_secundaria = get_field('fuente_secundaria', 'option') ?: "'Playfair Display', serif";

  function hexToRGB($hex) {
    $hex = str_replace('#', '', $hex);
    if (strlen($hex) === 3) {
      $hex = $hex[0].$hex[0] . $hex[1].$hex[1] . $hex[2].$hex[2];
    }
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    return "$r $g $b";
  }
?>

<style>
  :root {
    --color-primary: <?= hexToRGB($color_primario) ?>;
    --color-secondary: <?= hexToRGB($color_secundario) ?>;
    --color-text: <?= hexToRGB($color_text) ?>;
    --color-white: <?= hexToRGB($color_white) ?>;
    --color-black: <?= hexToRGB($color_black) ?>;
    --fuente_primaria: <?= esc_html($fuente_primaria) ?>;
    --fuente_secundaria: <?= esc_html($fuente_secundaria) ?>;

  }
</style>
