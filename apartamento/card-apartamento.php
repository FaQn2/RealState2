<?php
$tip_id     = get_field('tipologia');
$tip_nombre = $tip_id ? get_term($tip_id, 'tipologia')->name : 'Sin dato';
$cod        = get_field('codigo') ?: '';
$nivel      = get_term(get_field('nivel'), 'nivel');
$nivel_name = $nivel && !is_wp_error($nivel) ? $nivel->name : '';
$img        = get_field('imagen_destacada', 'term_' . $tip_id);
$habit      = get_field('cantidad_de_habitaciones', 'term_' . $tip_id) ?: '';
$sup        = get_field('superficie', 'term_' . $tip_id) ?: '';
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
?>




<!-- Card NEW con datos dinámicos -->
<article class="font-fuente_primaria"> 
  <a href="<?php the_permalink(); ?>" class="block group">

    <!-- Imagen con radio grande y clip -->
    <div class="relative w-full aspect-[15/10] rounded-2xl overflow-hidden shadow-md transition 
                group-hover:shadow-lg">
      <?php if (!empty($vista_nombres)) : ?>
                <div class="absolute top-3 right-3 bg-[rgba(96,98,110,1)] flex items-center gap-1 px-2 py-1 rounded-full shadow-sm z-10">
          <span class="material-symbols-outlined text-white text-sm">landscape_2</span>
          <span class="text-[12px] font-medium text-white">
            <?= esc_html(implode(', ', $vista_nombres)) ?>
          </span>
        </div>
      <?php endif; ?>

      <?php
      if ($img) {
        echo wp_get_attachment_image(
          $img,
          'unidad-card',
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

    <!-- Data SIN borde ni radio -->
    <div class="pt-2 font-fuente_primaria" >
      <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
          <span class="block text-[12px] font-medium uppercase tracking-wide text-gray-500 ">
            <?= esc_html('Piso ' . $nivel_name) ?>
          </span>
        <h3 class=" text-[17px]   leading-[22px] font-semibold text-black truncate">
          <?= esc_html('Modelo ' . $tip_nombre) ?>
        </h3>
        </div>

        <div class="flex flex-col items-end gap-0.5 flex-none shrink-0 grow-0 whitespace-nowrap">
                    <div class="flex items-center gap-1">
            <span class="material-symbols-outlined  text-gray-500 text-[18px]">bed</span>            
         <span class="text-[12px] font-medium text-gray-500"><?= esc_html($habit) ?></span>
          </div>
                    <div class="flex items-center gap-1">
            <span class="material-symbols-outlined  text-gray-500 text-[18px]">crop_free</span>       
            <span class="text-[12px] font-medium text-gray-500"><?= esc_html($sup) ?> m²</span>
          </div>


        </div>
      </div>

      <div class="min-h-[4px] "></div>
    </div>

  </a>
</article>












<!-- <script>
document.addEventListener('click', async function (e) {
  const btn = e.target.closest('.btn-share');
  if (!btn) return;

  e.preventDefault();
  const url   = btn.dataset.url;
  const title = btn.dataset.title || document.title;
  const img   = btn.dataset.img || '';

  // Para navegadores que soportan la Web Share API:
  if (navigator.share) {
    navigator.share({ title, url })
      .catch(() => {}); // Silent fail si el usuario cancela
    return;
  }

  // Fallback Clipboard + Prompt: incluimos también la URL de la imagen
  const payload = `${title}\n${url}` + (img ? `\n${img}` : '');
  if (navigator.clipboard) {
    try {
      await navigator.clipboard.writeText(payload);
      alert('Título, enlace y URL de imagen copiados al portapapeles');
    } catch {
      prompt('Copia manualmente este texto:', payload);
    }
  } else {
    prompt('Copia manualmente este texto:', payload);
  }
});
</script> -->