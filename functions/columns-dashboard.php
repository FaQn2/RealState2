<?php
// Columns personalizadas para la taxonomía 'nivel'

add_filter('manage_edit-nivel_columns', function ($columns) {
    $columns['orden'] = 'Orden';
    $columns['cluster'] = 'Cluster Asociado';
    return $columns;
});

add_filter('manage_nivel_custom_column', function ($out, $column, $term_id) {
    switch ($column) {
        case 'orden':
            return get_field('orden', 'nivel_' . $term_id);
        case 'cluster':
            $cluster = get_field('cluster_asociado', 'nivel_' . $term_id);
            return $cluster ? $cluster->post_title : '-';
    }
    return $out;
}, 10, 3);


add_filter('manage_edit-tipologia_columns', function ($columns) {
    $columns['cluster_asociado'] = 'Cluster Asociado';
    $columns['cantidad_habitaciones'] = 'Habitaciones';
    $columns['superficie'] = 'Superficie';
    return $columns;
});


add_filter('manage_tipologia_custom_column', function ($out, $column, $term_id) {
    switch ($column) {
        case 'cluster_asociado':
            $cluster_id = get_field('cluster_asociado', 'tipologia_' . $term_id);
            return $cluster_id ? get_the_title($cluster_id) : '-';

        case 'cantidad_habitaciones':
            return get_field('cantidad_de_habitaciones', 'tipologia_' . $term_id) ?: '-';

        case 'superficie':
            return get_field('superficie', 'tipologia_' . $term_id) ?: '-';
    }

    return $out;
}, 10, 3);



// 1. Agregar columnas a la tabla de unidades
add_filter('manage_apartamento_posts_columns', function ($columns) {
    $new_columns = [];

    foreach ($columns as $key => $label) {
        $new_columns[$key] = $label;

        if ($key === 'title') {
            $new_columns['codigo'] = 'Código';
            $new_columns['cluster_asociado'] = 'Cluster';
            $new_columns['nivel'] = 'Nivel';
            $new_columns['tipologia'] = 'Tipología';
            $new_columns['vista'] = 'Vista';

            // 👇 Tus nuevas columnas
            $new_columns['disponibilidad'] = 'Disponibilidad';
            $new_columns['tipo_disponibilidad'] = 'Tipo de disponibilidad';
            $new_columns['precio'] = 'Precio';
        }
    }

    return $new_columns;
});

// 2. Mostrar contenido en las columnas
add_action('manage_apartamento_posts_custom_column', function ($column, $post_id) {
    switch ($column) {
        case 'codigo':
            echo get_field('codigo', $post_id);
            break;

        case 'cluster_asociado':
            $cluster_id = get_field('cluster_asociado', $post_id);
            echo $cluster_id ? get_the_title($cluster_id) : '-';
            break;

        case 'nivel':
            $nivel_id = get_field('nivel', $post_id);
            echo $nivel_id ? get_term($nivel_id)->name : '-';
            break;

        case 'tipologia':
            $tipologia_id = get_field('tipologia', $post_id);
            echo $tipologia_id ? get_term($tipologia_id)->name : '-';
            break;

        case 'vista':
            $vistas = get_field('vista', $post_id);
            if (!empty($vistas) && is_array($vistas)) {
                $nombres = array_map(fn($id) => get_term($id)->name, $vistas);
                echo implode(', ', $nombres);
            } else {
                echo '-';
            }
            break;

        case 'disponibilidad':
            $disp = get_field('disponibilidad', $post_id);
            echo $disp ? ucfirst($disp) : '-';
            break;

        case 'tipo_disponibilidad':
            $term_id = get_field('tipo_de_disponibilidad', $post_id);
            if ($term_id) {
                $term = get_term($term_id, 'tipo-de-disponibilidad');
                echo $term ? $term->name : '-';
            } else {
                echo '-';
            }
            break;

        case 'precio':
            $precio = get_field('precio', $post_id);
            echo $precio ? '$' . number_format($precio, 0, ',', '.') : 'Consultar';
            break;
    }
}, 10, 2);

// 3. (Opcional) Hacer que la columna "precio" sea ordenable
add_filter('manage_edit-apartamento_sortable_columns', function ($columns) {
    $columns['precio'] = 'precio';
    return $columns;
});

// 4. Ordenar por precio en el admin
add_action('pre_get_posts', function ($query) {
    if (!is_admin()) return;
    $orderby = $query->get('orderby');

    if ($orderby === 'precio') {
        $query->set('meta_key', 'precio');
        $query->set('orderby', 'meta_value_num');
    }
});



// 1. Agregar columnas a la tabla de amenidades
add_filter('manage_amenity_posts_columns', function ($columns) {
    $new_columns = [];

    // Inserta después del checkbox
    foreach ($columns as $key => $label) {
        $new_columns[$key] = $label;
        if ($key === 'title') {
            $new_columns['orden'] = 'Orden'; // 👈 nueva columna
            $new_columns['cluster_perteneciente'] = 'Clusters';
            $new_columns['nivel'] = 'Nivel';
        }
    }

    return $new_columns;
});

// 2. Mostrar contenido en las columnas
add_action('manage_amenity_posts_custom_column', function ($column, $post_id) {
    switch ($column) {
       
        case 'orden':
            echo esc_html(get_field('orden', $post_id)); // 👈 ACF campo orden
            break;

        case 'cluster_perteneciente':
            $cluster_ids = get_field('cluster_perteneciente', $post_id);
            
            if ($cluster_ids) {
                // Si es un array (múltiples valores)
                if (is_array($cluster_ids)) {
                    $cluster_names = [];
                    foreach ($cluster_ids as $cluster_id) {
                        $cluster_title = get_the_title($cluster_id);
                        if ($cluster_title) {
                            $cluster_names[] = $cluster_title;
                        }
                    }
                    echo !empty($cluster_names) ? implode(', ', $cluster_names) : '-';
                } 
                // Si es un solo valor
                else {
                    $cluster_title = get_the_title($cluster_ids);
                    echo $cluster_title ? $cluster_title : '-';
                }
            } else {
                echo '-';
            }
            break;

        case 'nivel':
            $nivel_id = get_field('nivel', $post_id);
            echo $nivel_id ? get_term($nivel_id)->name : '-';
            break;
    }
}, 10, 2);


// 1. Agregar columnas a la tabla de imagenes
add_filter('manage_galeria_posts_columns', function ($columns) {
    $new_columns = [];

    foreach ($columns as $key => $label) {
        $new_columns[$key] = $label;
        if ($key === 'title') {
            $new_columns['tipo_de_galeria'] = 'Tipo de Galería';
        }
    }

    return $new_columns;
});


// 2. Mostrar contenido en las columnas imagenes
add_action('manage_galeria_posts_custom_column', function ($column, $post_id) {
    if ($column === 'tipo_de_galeria') {
        $terms = get_the_terms($post_id, 'tipo-de-galeria');
        if (!empty($terms) && !is_wp_error($terms)) {
            echo esc_html(implode(', ', wp_list_pluck($terms, 'name')));
        } else {
            echo '-';
        }
    }
}, 10, 2);


/*  Ordenar Amenities por defecto por el campo 'orden' (ACF)  */
add_action('pre_get_posts', function ($query) {
    // Solo en admin, pantalla principal de amenity y consulta principal
    if (is_admin() && $query->is_main_query() && $query->get('post_type') === 'amenity') {
        // Si no hay un orderby definido, forzar 'orden'
        if (!$query->get('orderby')) {
            $query->set('meta_key', 'orden');
            $query->set('orderby', 'meta_value_num');
            $query->set('order', 'ASC');
        }
    }
});
