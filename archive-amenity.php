<?php
/**
 * Archive template para Amenities (GRID 2x4)
 * Archivo: archive-amenity.php
 */

get_header();

// 1) Recoger IDs de clúster desde query string
$cluster_ids = [];
if (isset($_GET['cluster_ids'])) {
  $cluster_ids = array_map('intval', (array) $_GET['cluster_ids']);
} elseif (isset($_GET['cluster_id'])) {
  $cluster_ids = [intval($_GET['cluster_id'])];
}
$cluster_id = $cluster_ids[0] ?? '';
$cluster    = $cluster_id ? get_post($cluster_id) : null;

// 2) Construir WP_Query
$args = [
  'post_type'      => 'amenity',
  'posts_per_page' => -1,
];

if ($cluster_ids) {
  $mq = ['relation' => 'OR'];
  foreach ($cluster_ids as $cid) {
    $mq[] = [
      'key'     => 'cluster_perteneciente',
      'value'   => '"' . $cid . '"',
      'compare' => 'LIKE',
    ];
  }
  $args['meta_query'] = $mq;
}

// 3) Obtener todos los clusters publicados (para el selector)
$clusters = get_posts([
  'post_type'      => 'cluster',
  'posts_per_page' => -1,
  'orderby'        => 'title',
  'order'          => 'ASC',
]);

// 4) URL base del archivo de amenities
$amenities_archive = get_post_type_archive_link('amenity');
if (!$amenities_archive) {
  // fallback si no tuviera archivo público
  $amenities_archive = home_url('/amenities/');
}

//Ordenamiento personalizado por campo 'orden'
$args['meta_key'] = 'orden';
$args['orderby']  = 'meta_value_num';
$args['order']    = 'ASC';


$query = new WP_Query($args);


?>

<main class="min-h-screen flex flex-col bg-white font-fuente_primaria text-text overflow-x-hidden">

  <div class="w-full">
    <div class="flex items-center justify-between px-6 py-4 border-b shadow-[0px_8px_12px_0px_rgba(0,0,0,0.08)]">

       <!-- Botón Tour SOLO ICONO (mobile izq) -->
  <a href="https://graff3d.factorycreativestudio.com/nivel-1/"
     class="order-1 md:hidden">
    <button class="flex items-center justify-center w-9 h-9  border boder-gray-400 hover:bg-gray-100 rounded">
            <i class="fa-solid fa-arrow-turn-up fa-rotate-270"></i>
    </button>
  </a>


      <!-- Selector de Cluster -->
  <div class="order-2 flex-1 flex justify-center md:justify-start">
    <select
        id="cluster-select"
        name="cluster"
        class="cluster-dd"           
        data-redirect="true"          
      >
        <?php foreach($clusters as $c): ?>
          <?php $opt_url = add_query_arg('cluster_id', $c->ID, $amenities_archive); ?>
          <option value="<?php echo esc_url($opt_url); ?>" <?php selected($c->ID, $cluster_id); ?>>
            <?php echo esc_html($c->post_title); ?>
          </option>
        <?php endforeach; ?>
      </select>

      </div>

        <!-- Acciones (desktop y tablet) -->
  <div class="order-3 hidden md:flex items-center space-x-4">
    <!-- Tour 360 -->
    <a href="https://graff3d.factorycreativestudio.com/nivel-1/">
      <button class="flex items-center space-x-2 px-3 py-1 border border-gray-400 hover:bg-gray-100   rounded">
        <span class="text-sm font-medium text-gray-700">Volver al exterior</span>
      </button>
    </a>
    <!-- Idioma -->
    <div class="flex items-center space-x-1 px-3 py-1 border border-black/90  bg-black/90 rounded-full text-white">
      <span class="material-symbols-outlined text-[18px]">
      language
      </span>
      <span class="text-sm font-medium ">ES</span>
    </div>
  </div>

  <!-- Idioma (mobile a la derecha) -->
  <div class="order-3 md:hidden flex items-center space-x-1 px-3 py-1 border border-black/90  bg-black/90 rounded-full text-white">
    <span class="material-symbols-outlined text-[18px]">
      language
      </span>
    <span class="text-sm font-medium ">ES</span>
  </div>

</div>
    </div>

    <div class="flex items-center justify-between px-6 py-6">
      <span                     class="text-lg md:text-lg font-medium text-primary bg-[#EDECF1] border border-[#EDECF1] px-3 py-1 lg:py-0.5  mr-2 rounded-full w-full">Amenities</span>
      <div class="flex items-center space-x-2">
        <a href="<?php echo esc_url( add_query_arg('cluster_id', ($cluster_id ?? ''), home_url('/unidades/')) ); ?>"
           class="btn-secondary flex items-center">
          <i class="fa-solid fa-building-user"></i>
          <span class="hidden sm:inline ml-1">Unidades</span>
        </a>
      </div>
    </div>
  </div>

  <!-- CONTENIDO que crece para empujar el footer -->
  <div class="flex-1 w-full">
    <?php if ($query->have_posts()): ?>
      <div class="w-full px-6  pb-10">
        <!-- GRID 2x4 en desktop (mostramos solo 8) -->
        <div class="grid gap-6 lg:gap-5 sm:grid-cols-2 lg:grid-cols-4 auto-rows-fr">
          <?php
            $max_items = 8;
            $count = 0;
            while ($query->have_posts() && $count < $max_items):
              $query->the_post(); $count++;

              $nivel_id   = get_field('nivel');
              $nivel_term = $nivel_id ? get_term($nivel_id, 'nivel') : null;
              $nivel_name = ($nivel_term && !is_wp_error($nivel_term)) ? $nivel_term->name : 'Sin nivel';
              $img_id     = get_post_thumbnail_id();

              get_template_part(
                'amenity/card-amenity', // tu ruta actual
                'amenity',
                [
                  'img_id'     => $img_id,
                  'nivel_name' => $nivel_name,
                ]
              );
            endwhile;
          ?>
        </div>
      </div>
    <?php else: ?>
      <p class="text-center text-text py-10">No se encontraron amenities para este clúster.</p>
    <?php endif; ?>
  </div>

  <!-- Footer STICKY: mt-auto lo empuja a la base -->
  <footer class="mt-auto flex flex-col items-center justify-center gap-4 py-6 w-full bg-[#EDECF1] text-gray-700 text-sm">
    <!-- Marca -->
  <div class="font-semibold text-base">
    <?php
      if ($cluster && !is_wp_error($cluster)) {
        echo esc_html($cluster->post_title) . ' · GRAFF3D';
      } else {
        echo 'Selecciona un clúster';
      }
    ?>
  </div>

    <div class="text-xs text-gray-600">© 2025 Todos los derechos reservados</div>
    <div class="flex flex-wrap justify-center gap-6 text-xs font-medium">
      <a href="/terminos-condiciones" class="hover:underline">Términos y Condiciones</a>
      <a href="/politica-privacidad" class="hover:underline">Política de Privacidad</a>
      <a href="/contacto" class="hover:underline">Contacto</a>
    </div>
  </footer>
</main>


<script>
  // Listener global para botón de compartir (reutilizable)
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.btn-share');
    if (!btn) return;

    const url   = btn.dataset.url;
    const title = btn.dataset.title || document.title;

    if (navigator.share) {
      navigator.share({ title, url }).catch(()=>{});
    } else {
      navigator.clipboard.writeText(url)
        .then(() => alert('¡Enlace copiado! 📋'))
        .catch(() => alert('No se pudo copiar el enlace. 🚫'));
    }
  });
</script>

<script>
(function(){
  function buildCustomSelect(sel) {
    if (!sel || sel.dataset.csInit === '1') return;
    sel.dataset.csInit = '1';

    // wrapper
    const wrap = document.createElement('div');
    wrap.className = 'relative';
    sel.parentNode.insertBefore(wrap, sel);
    wrap.appendChild(sel);

    // ocultar select nativo
    sel.classList.add('hidden');

    // label inicial
    const selectedText = sel.selectedOptions?.[0]?.textContent || 'Seleccionar';

    // botón trigger
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'w-full bg-white gap-2 rounded-md px-3 py-2 text-xl font-semibold text-primary cursor-pointer flex items-center justify-between';
    btn.setAttribute('aria-haspopup', 'listbox');
    btn.setAttribute('aria-expanded', 'false');
    btn.innerHTML = `<span class="flex-1 text-center">${selectedText}</span><span class="opacity-70">▾</span>`;
    wrap.appendChild(btn);

    // menú
    const menu = document.createElement('div');
    menu.className = 'absolute left-0 right-0 top-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg p-1 z-50 max-h-48 overflow-auto hidden';
    menu.setAttribute('role', 'listbox');
    wrap.appendChild(menu);

    // render de opciones
    function renderOptions() {
      menu.innerHTML = '';
      [...sel.options].forEach((o) => {
        const item = document.createElement('button');
        item.type = 'button';
        item.className = `w-full text-center text-text bg-transparent border-0 p-2 rounded-md text-xl font-semibold cursor-pointer flex items-center justify-center hover:bg-blue-50 ${o.selected ? 'bg-gray-100' : ''}`;
        item.setAttribute('role', 'option');
        item.dataset.value = o.value;
        item.setAttribute('aria-selected', o.selected ? 'true' : 'false');
        item.innerHTML = `<span>${o.textContent}</span>`;

        item.addEventListener('click', () => {
          sel.value = o.value;
          sel.dispatchEvent(new Event('change', { bubbles: true }));

          // actualizar label del botón
          const label = btn.querySelector('span'); // primer span = label
          if (label) label.textContent = o.textContent;

          // resaltar seleccionado
          menu.querySelectorAll('button').forEach(b => {
            const active = (b.dataset.value === sel.value);
            b.classList.toggle('bg-gray-100', active);
            b.setAttribute('aria-selected', active ? 'true' : 'false');
          });

          closeMenu();

          // redirect
          if (sel.dataset.redirect === 'true' && o.value) {
            window.location.href = o.value;
          }
        });

        menu.appendChild(item);
      });
    }

    function openMenu(){
      menu.classList.remove('hidden');
      btn.setAttribute('aria-expanded', 'true');
    }
    function closeMenu(){
      menu.classList.add('hidden');
      btn.setAttribute('aria-expanded', 'false');
    }
    function toggleMenu(){
      menu.classList.contains('hidden') ? openMenu() : closeMenu();
    }

    btn.addEventListener('click', (e) => {
      e.preventDefault();
      toggleMenu();
    });

    document.addEventListener('click', (e) => {
      if (!wrap.contains(e.target)) closeMenu();
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') closeMenu();
    });

    renderOptions();
  }

  document.addEventListener('DOMContentLoaded', function(){
    // si ya tenés un buildCustomSelect global, lo usamos; si no, usamos este
    const enhancer = window.buildCustomSelect || buildCustomSelect;

    // Inicializar solo los del amenity
  document.querySelectorAll('select.cluster-dd, select.amenity-cluster-dd').forEach(enhancer);
  });
})();
</script>



<?php
wp_reset_postdata();
get_footer();
