


<?php
/**
 * Template Name: Archive Unidades
 */

get_header();




// 1) Recoger el cluster_id
$cluster_id = isset($_GET['cluster_id']) ? intval($_GET['cluster_id']) : null;

// 2) Traer el nombre del clúster
$cluster_nombre = '';
if ($cluster_id) {
  $cluster = get_post($cluster_id);
  if ($cluster && !is_wp_error($cluster)) {
    $cluster_nombre = $cluster->post_title;
  }
}

// 3) Obtener número de página actual (con fallback)
$paged = max(
  1,
  (int)($_GET['pagina'] ?? 0) ?: ( get_query_var('paged') ?: get_query_var('page') ?: 1 )
);


// 4) Montar argumentos de consulta
$args = [
  'post_type'      => 'apartamento',
  'posts_per_page' => 8,
  'paged'          => $paged,
];

// 5) Aplicar filtro por clúster si existe
if ($cluster_id) {
  $args['meta_query'] = [[
    'key'     => 'cluster_asociado',
    'value'   => $cluster_id,
    'compare' => '=',
  ]];
}


// Obtener todos los clusters publicados
$clusters = get_posts([
  'post_type'      => 'cluster',
  'posts_per_page' => -1,
  'orderby'        => 'title',
  'order'          => 'ASC',
]);


$query = new WP_Query($args);

// Función para generar URLs de paginado manteniendo parámetros
function get_pagination_url($page_num, $cluster_id = null) {
  // Usar la URL base sin parámetros
  $base_url = home_url('/unidades/');
  
  $params = [];
  
  // Mantener cluster_id si existe
  if ($cluster_id) {
    $params['cluster_id'] = $cluster_id;
  }
  
  // Usar 'pagina' en lugar de 'paged' para evitar conflictos con WordPress
  if ($page_num > 1) {
    $params['pagina'] = $page_num;
  }
  
  return $base_url . (!empty($params) ? '?' . http_build_query($params) : '');
}

function custom_pagination($query, $cluster_id = null) {
  if (!$query) return;

  $total_pages  = max(1, (int) $query->max_num_pages);
  $current_page = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;

  if ($total_pages <= 1) return;

  // Estilos de botones (look redondo tipo pills)
  $btn_link_cls    = 'inline-flex items-center justify-center w-8 h-8 text-primary hover:bg-primary-100 rounded-full transition-colors focus:outline-none focus:ring-primary';
  $btn_current_cls = 'inline-flex items-center justify-center w-8 h-8 bg-primary text-white rounded-full transition';
  $btn_disabled    = 'inline-flex items-center justify-center w-8 h-8 text-gray-300 rounded-full border border-gray-200';
  $dots_cls        = 'inline-flex items-center justify-center w-8 h-8 text-gray-500';

  // Cálculo de “Mostrando”
  $total_posts    = (int) $query->found_posts;
  $posts_per_page = (int) $query->get('posts_per_page');
  $start_item     = (($current_page - 1) * $posts_per_page) + 1;
  $end_item       = min($current_page * $posts_per_page, $total_posts);

  // GRID responsive:
  // - < lg  : 1 columna -> [BOTONES] arriba, [INFO] abajo
  // - >= lg : 3 columnas -> [INFO | BOTONES | SPACER]
  echo '<nav id="pagination" class="grid w-full items-center gap-3 lg:grid-cols-[1fr_auto_1fr] mt-4 mb-4" aria-label="Navegación de páginas">';

  // INFO (izquierda en desktop, debajo en mobile/tablet)
  echo '<div class="order-2 lg:order-1 justify-self-center lg:justify-self-start text-sm text-gray-600 text-center lg:text-left lg:whitespace-nowrap">';
  echo 'Mostrando ' . $start_item . '-' . $end_item . ' de ' . $total_posts . ' unidades';
  echo '</div>';

  // BOTONES (siempre centrados; con scroll horizontal si se excede el ancho)
  echo '<div class="order-1 lg:order-2 flex items-center space-x-2 justify-self-center max-w-full overflow-x-auto px-2">';

  // Prev
  if ($current_page > 1) {
    $prev_url = esc_url(get_pagination_url($current_page - 1, $cluster_id));
      echo '<a href="'.$prev_url.'" class="'.$btn_link_cls.' js-ajax-page" data-page="'.($current_page - 1).'" aria-label="Página anterior">'
        .'<i class="fa-solid fa-chevron-left text-xs" aria-hidden="true"></i>'
        .'</a>';
  } else {
    echo '<span class="'.$btn_disabled.'" aria-hidden="true">'
        .'<i class="fa-solid fa-chevron-left text-xs"></i>'
        .'</span>';
  }

  // Rango de páginas
  $range = 2;
  $start = max(1, $current_page - $range);
  $end   = min($total_pages, $current_page + $range);

  // Primera + puntos
  if ($start > 1) {
    $first_url = esc_url(get_pagination_url(1, $cluster_id));
echo '<a href="'.$first_url.'" class="'.$btn_link_cls.' js-ajax-page" data-page="1" aria-label="Ir a la página 1">1</a>';
    if ($start > 2) {
      echo '<span class="'.$dots_cls.'" aria-hidden="true">…</span>';
    }
  }

  // Páginas del rango
  for ($i = $start; $i <= $end; $i++) {
    if ($i == $current_page) {
      echo '<span class="'.$btn_current_cls.'" aria-current="page">'.$i.'</span>';
    } else {
      $url = esc_url(get_pagination_url($i, $cluster_id));
    echo '<a href="'.$url.'" class="'.$btn_link_cls.' js-ajax-page" data-page="'.$i.'" aria-label="Ir a la página '.$i.'">'.$i.'</a>';
    }
  }

  // Última + puntos
  if ($end < $total_pages) {
    if ($end < $total_pages - 1) {
      echo '<span class="'.$dots_cls.'" aria-hidden="true">…</span>';
    }
    $last_url = esc_url(get_pagination_url($total_pages, $cluster_id));
  echo '<a href="'.$last_url.'" class="'.$btn_link_cls.' js-ajax-page" data-page="'.$total_pages.'" aria-label="Ir a la página '.$total_pages.'">'.$total_pages.'</a>';
  }

  // Next
  if ($current_page < $total_pages) {
    $next_url = esc_url(get_pagination_url($current_page + 1, $cluster_id));
    echo '<a href="'.$next_url.'" class="'.$btn_link_cls.'" aria-label="Página siguiente">'
        .'<i class="fa-solid fa-chevron-right text-xs" aria-hidden="true"></i>'
        .'</a>';
  } else {
    echo '<span class="'.$btn_disabled.'" aria-hidden="true">'
        .'<i class="fa-solid fa-chevron-right text-xs"></i>'
        .'</span>';
  }

  echo '</div>'; // fin botones

  // Spacer (solo desktop; mantiene el centrado perfecto)
  echo '<div class="hidden lg:block lg:order-3"></div>';

  echo '</nav>';
}


?>





<!-- Contenido principal -->
<main class="min-h-screen flex flex-col font-fuente_primaria">
 

  <!-- HEADER UNIDADES -->
  <div class="w-full  ">


  <!-- Header -->
<div class="flex items-center  justify-between px-6 py-4 border-b shadow-[0px_8px_12px_0px_rgba(0,0,0,0.08)]">

  <!-- Botón Tour SOLO ICONO (mobile izq) -->
  <a href="https://graff3d.factorycreativestudio.com/nivel-1/"
     class="order-1 md:hidden">
  <button class="flex items-center justify-center w-9 h-9  border boder-gray-400 hover:bg-gray-100 rounded">
            <i class="fa-solid fa-arrow-turn-up fa-rotate-270"></i>
    </button>
  </a>

  <!-- Selector de Clusters -->
  <div class="order-2 flex-1 flex justify-center md:justify-start">
<select
  id="cluster-select"
  name="cluster"
  class="cluster-dd"
  data-redirect="true"
>
  <?php foreach($clusters as $c): ?>
    <option
      value="<?php echo esc_url( home_url('/unidades?cluster_id=' . $c->ID) ); ?>"
      <?php selected( $c->ID, $cluster_id ); ?>
    >
      <?php echo esc_html( $c->post_title ); ?>
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


      <!-- Subheader: sección Unidades + botones Filtros y Amenidades -->
      <div class=" flex items-center justify-between px-6 py-6">
        <!-- Label -->
                <span 
                    class="text-lg md:text-lg font-medium text-primary bg-[#EDECF1] border border-[#EDECF1] px-3 py-1 lg:py-0.5 mr-2 rounded-full w-full">
            Unidades
          </span>
          <div  class="flex flex-nowrap items-center lg:gap-2 lg:text-sm ">
        </div>
        

  

        <!-- Botones de acción -->
        <!-- Amenidades + Filtrar -->
        <div class="flex items-center space-x-2">
<!-- Botón Filtrar (desktop/tablet) -->
<button id="btn-filtrar" class="btn-secondary relative hidden md:inline-flex items-center">
  <i class="fa-solid fa-sliders"></i>
  <span class="hidden sm:inline ml-1">Filtrar</span>
  <span id="filtros-count"
        class="ml-2 inline-flex items-center justify-center text-[11px] font-semibold rounded-full bg-primary text-white w-5 h-5 hidden"
        aria-live="polite"></span>
</button>
          <a href="<?= esc_url(home_url('/amenidades/?cluster_id=' . ($cluster_id ?? ''))) ?>"
            class="btn-secondary flex items-center">
            <i class="fa-solid fa-water-ladder"></i>
            <span class="hidden sm:inline ml-1">Amenidades</span>
          </a>
        </div>
      </div>
    
  </div>


  <div
    class="
    w-full                   
    px-6                     
  ">
    <!-- 🧱 GRID de apartamentos -->
    <div
      id="grid-unidades"
      class="
      grid
      grid-cols-1
      sm:grid-cols-2
      lg:grid-cols-4
      gap-6 lg:gap-5
      w-auto           /* que el grid se encoja a su contenido */
    ">

      <?php if ($query->have_posts()): while ($query->have_posts()): $query->the_post(); ?>
          <?php get_template_part('apartamento/card', 'apartamento'); ?>
        <?php endwhile;
      else: ?>
        <p class="col-span-full text-center text-gray-500">No hay unidades para este clúster.</p>
      <?php endif; ?>
    </div>

      <!-- ✨ PAGINADO -->
  <?php custom_pagination($query, $cluster_id); ?>
</div>

 
  <footer class="mt-auto flex flex-col items-center justify-center gap-4 py-6 w-full bg-[#EDECF1] text-gray-700 text-sm">
  
  <!-- Marca -->
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


  <!-- Derechos -->
  <div class="text-xs text-gray-600">
    © 2025 Todos los derechos reservados
  </div>

  <!-- Links -->
  <div class="flex flex-wrap justify-center gap-6 text-xs font-medium">
    <a href="/terminos-condiciones" class="hover:underline">Términos y Condiciones</a>
    <a href="/politica-privacidad" class="hover:underline">Política de Privacidad</a>
    <a href="/contacto" class="hover:underline">Contacto</a>
  </div>
</footer>

</main>

<script>
function buildCustomSelect(sel) {
    if (!sel || sel.dataset.csInit === '1') return;
    sel.dataset.csInit = '1';

    const wrap = document.createElement('div');
    wrap.className = 'relative';
    sel.parentNode.insertBefore(wrap, sel);
    wrap.appendChild(sel);
    sel.classList.add('hidden');

    const selectedText = sel.selectedOptions?.[0]?.textContent || 'Seleccionar';

    const btn = document.createElement('button');
    btn.type = 'button'; 
    btn.className = 'w-full bg-white gap-2 rounded-md px-3 py-2 text-xl font-semibold text-primary cursor-pointer flex items-center justify-between';
    btn.setAttribute('aria-haspopup', 'listbox'); 
    btn.setAttribute('aria-expanded', 'false');
    btn.innerHTML = `<span class="flex-1 text-center">${selectedText}</span><span class="opacity-70">▾</span>`;
    wrap.appendChild(btn);

    const menu = document.createElement('div');
    menu.className = 'absolute left-0 right-0 top-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg p-1 z-50 max-h-48 overflow-auto hidden'; 
    menu.setAttribute('role', 'listbox');
    wrap.appendChild(menu);

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
                sel.dispatchEvent(new Event('change', {bubbles: true}));
                btn.querySelector('span').textContent = o.textContent;
                menu.querySelectorAll('button').forEach(b => {
                    b.classList.toggle('bg-gray-100', b.dataset.value === sel.value);
                    b.setAttribute('aria-selected', b.dataset.value === sel.value ? 'true' : 'false');
                });
                closeMenu();
                
                if (sel.dataset.redirect === 'true' && o.value) {
                    window.location.href = o.value;
                }
            });
            menu.appendChild(item);
        });
    }

    function openMenu() { 
        menu.classList.remove('hidden');
        btn.setAttribute('aria-expanded', 'true'); 
    }
    
    function closeMenu() { 
        menu.classList.add('hidden');
        btn.setAttribute('aria-expanded', 'false'); 
    }
    
    function toggleMenu() { 
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

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('select.cluster-dd').forEach(buildCustomSelect);
});
</script>





<!-- Botón Filtrar flotante (solo MOBILE) -->

<div class="md:hidden fixed inset-x-0 bottom-0 z-50 p-5 pointer-events-none bg-white border-t border-gray-200 shadow-lg">
  <button id="btn-filtrar-mobile"
          class="pointer-events-auto w-full bg-white border border-gray-300 rounded-xl shadow-lg px-4 py-3 flex items-center justify-center">
    <i class="fa-solid fa-sliders mr-2"></i>
    <span class="font-medium">Filtrar búsqueda</span>
    <span id="filtros-count-mobile"
          class="ml-2 inline-flex items-center justify-center text-[11px] font-semibold rounded-full bg-primary text-white w-5 h-5 hidden"
          aria-live="polite"></span>
  </button>
</div>

<div class="h-20 md:hidden"></div>


<?php get_footer(); ?>

<?php get_template_part('filtros/filtro'); ?>
<?php get_template_part('filtros/ajax-filtros.php'); ?>