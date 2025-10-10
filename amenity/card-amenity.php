<?php
/**
 * Template Part: Card Amenity (mismo diseño que card unidad)
 * Ruta sugerida: template-parts/cards/card-amenity.php
 *
 * Args esperados:
 * - img_id (int)        -> ID de la imagen destacada del amenity
 * - nivel_name (string) -> Nombre del nivel (ej: "2", "Planta Baja", etc.)
 */
$img_id     = $args['img_id']     ?? 0;
$nivel_name = $args['nivel_name'] ?? '';
?>

<article class="font-fuente_primaria">
  <a href="<?php the_permalink(); ?>" class="block group">

    <!-- Imagen con radio grande y clip (idéntico a card unidad) -->
    <div class="relative w-full aspect-[15/10] rounded-2xl overflow-hidden shadow-md transition group-hover:shadow-lg">
      <?php
      if ($img_id) {
        echo wp_get_attachment_image(
          $img_id,
          'unidad-card', // mismo size que usás en la card de unidad
          false,
          [
            'class'    => 'absolute inset-0 w-full h-full object-cover object-center transition-transform duration-300 group-hover:scale-[1.02]',
            'loading'  => 'lazy',
            'decoding' => 'async'
          ]
        );
      } else {
        echo '<div class="absolute inset-0 w-full h-full flex items-center justify-center text-gray-400">Sin imagen</div>';
      }
      ?>
    </div>

    <!-- Data SIN borde ni radio (idéntico, pero solo nivel + título) -->
    <div class="pt-2">
      <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
          <span class="block text-[12px] font-medium uppercase tracking-wide text-gray-500 mb-0.5">
            <?= esc_html($nivel_name ? 'Piso ' . $nivel_name : 'Piso —') ?>
          </span>
          <h3 class="text-[17px] leading-[22px] font-semibold text-black truncate">
            <?php the_title(); ?>
          </h3>
        </div>
      </div>

      <div class="min-h-[4px]"></div>
    </div>

  </a>
</article>
