<?php
add_action('wp_ajax_filtrar_apartamentos_ajax', 'filtrar_apartamentos_ajax');
add_action('wp_ajax_nopriv_filtrar_apartamentos_ajax', 'filtrar_apartamentos_ajax');

function filtrar_apartamentos_ajax() {

  // ================== 1) Entradas ==================
  $cluster_id   = intval($_POST['cluster_id'] ?? 0);
  $nivel        = sanitize_text_field($_POST['nivel'] ?? '');
  $habitaciones = sanitize_text_field($_POST['habitaciones'] ?? '');

  // Vista puede venir CSV (a,b,c) o array
  $vista_raw = $_POST['vista'] ?? '';
  $vistas_slugs = [];
  if (is_array($vista_raw)) {
    $vistas_slugs = array_map('sanitize_text_field', $vista_raw);
  } elseif (is_string($vista_raw) && $vista_raw !== '') {
    $vistas_slugs = array_filter(array_map('sanitize_text_field', array_map('trim', explode(',', $vista_raw))));
  }

  // Superficie: soportar min/max y compat con 'superficie' CSV
  $smin = isset($_POST['superficie_min']) ? floatval($_POST['superficie_min']) : null;
  $smax = isset($_POST['superficie_max']) ? floatval($_POST['superficie_max']) : null;

  $superficie_raw = trim((string)($_POST['superficie'] ?? '')); // puede ser "min,max" o un número suelto (exacto)
  $superficie_single = null;

  if ($smin === null && $smax === null && $superficie_raw !== '') {
    if (strpos($superficie_raw, ',') !== false) {
      $parts = array_map('trim', explode(',', $superficie_raw));
      if (count($parts) >= 2) {
        $a = floatval($parts[0]); $b = floatval($parts[1]);
        $smin = min($a, $b); $smax = max($a, $b);
      }
    } else {
      // modo exacto (no lo usa el slider, pero lo dejamos por compat)
      $superficie_single = floatval($superficie_raw);
    }
  }

  // Paginación
  $per_page = 8;
  $page     = max(1, intval($_POST['pagina'] ?? 1));
  $offset   = ($page - 1) * $per_page;

  // ================== 2) Meta/Tax (sin numéricos) ==================
  $meta_query = ['relation' => 'AND'];
  if ($cluster_id) {
    $meta_query[] = [
      'key'     => 'cluster_asociado',
      'value'   => $cluster_id,
      'compare' => '=',
    ];
  }

  $tax_query = [];
  if ($nivel !== '') {
    $tax_query[] = ['taxonomy' => 'nivel', 'field' => 'slug', 'terms' => $nivel];
  }
  if (!empty($vistas_slugs)) {
    $tax_query[] = ['taxonomy' => 'vista', 'field' => 'slug', 'terms' => $vistas_slugs, 'operator' => 'IN'];
  }

  // ================== 3) Traer TODOS los IDs que matchean meta/tax ==================
  $args_ids = [
    'post_type'      => 'apartamento',
    'post_status'    => 'publish',
    'fields'         => 'ids',
    'posts_per_page' => -1,
    'meta_query'     => $meta_query,
    'no_found_rows'  => true,
    'ignore_sticky_posts' => true,
  ];
  if (!empty($tax_query)) {
    $args_ids['tax_query'] = $tax_query;
  }

  $q_ids    = new WP_Query($args_ids);
  $ids_base = $q_ids->posts ?: [];
  wp_reset_postdata();

  if (empty($ids_base)) {
    wp_send_json([
      'total'     => 0,
      'page'      => $page,
      'per_page'  => $per_page,
      'max_pages' => 0,
      'html'      => '<p class="col-span-full text-center text-gray-500">No se encontraron unidades.</p>',
    ]);
  }

  // ================== 4) Preparar ACF y extremos disponibles ==================
  $rows = []; // [ [post=>id, habit=>int|null, sup=>float|null], ... ]
  $sup_min_avail = INF;
  $sup_max_avail = -INF;

  foreach ($ids_base as $post_id) {
    $tip_id = get_field('tipologia', $post_id);
    if (!$tip_id) continue;

    $term_key = 'tipologia_' . intval($tip_id);
    $habit = get_field('cantidad_de_habitaciones', $term_key);
    $sup   = get_field('superficie',                $term_key);

    $habit_n = is_numeric($habit) ? intval($habit) : null;
    $sup_n   = is_numeric($sup)   ? floatval($sup) : null;

    if ($sup_n !== null) {
      if ($sup_n < $sup_min_avail) $sup_min_avail = $sup_n;
      if ($sup_n > $sup_max_avail) $sup_max_avail = $sup_n;
    }

    $rows[] = ['post' => $post_id, 'habit' => $habit_n, 'sup' => $sup_n];
  }

  if ($sup_min_avail === INF)  $sup_min_avail = null;
  if ($sup_max_avail === -INF) $sup_max_avail = null;

  // ¿Aplicamos filtro de superficie?
  $apply_superficie = false;
  if ($superficie_single !== null) {
    // modo exacto
    $apply_superficie = true;
  } elseif ($smin !== null && $smax !== null && $sup_min_avail !== null && $sup_max_avail !== null) {
    // Si el rango recibido cubre TODO el rango disponible → NO filtra
    $covers_all = ($smin <= $sup_min_avail && $smax >= $sup_max_avail);
    $apply_superficie = !$covers_all;
  }

  // ================== 5) Filtrado final en PHP ==================
  $filtered_ids = [];
  foreach ($rows as $r) {
    $pasa = true;

    // habitaciones (igualdad exacta)
    if ($habitaciones !== '' && $r['habit'] !== null) {
      if (intval($habitaciones) !== intval($r['habit'])) $pasa = false;
    }

    // superficie
    if ($pasa && $apply_superficie && $r['sup'] !== null) {
      if ($superficie_single !== null) {
        if (floatval($r['sup']) !== floatval($superficie_single)) $pasa = false;
      } else {
        // rango inclusivo
        $lo = min($smin, $smax);
        $hi = max($smin, $smax);
        if ($r['sup'] < $lo || $r['sup'] > $hi) $pasa = false;
      }
    }

    if ($pasa) $filtered_ids[] = $r['post'];
  }

  $total_all = count($filtered_ids);

  // ================== 6) Paginar esos IDs con un WP_Query REAL ==================
  $resultados = [];
  if ($total_all > 0) {
    $args_page = [
      'post_type'           => 'apartamento',
      'post_status'         => 'publish',
      'posts_per_page'      => $per_page,
      'paged'               => $page,
      'post__in'            => $filtered_ids,
      'orderby'             => 'post__in',
      'ignore_sticky_posts' => true,
    ];

    $query_page = new WP_Query($args_page);

    if ($query_page->have_posts()) {
      while ($query_page->have_posts()) {
        $query_page->the_post();
        ob_start();
        get_template_part('apartamento/card', 'apartamento');
        $resultados[] = ob_get_clean();
      }
      wp_reset_postdata();
    }
  }

  $html = !empty($resultados)
    ? implode('', $resultados)
    : '<p class="col-span-full text-center text-gray-500">No se encontraron unidades.</p>';

  // ================== 7) Respuesta ==================
  wp_send_json([
    'total'     => $total_all,
    'page'      => $page,
    'per_page'  => $per_page,
    'max_pages' => (int) ceil($total_all / $per_page),
    'html'      => $html,
  ]);
}
