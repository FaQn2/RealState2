<?php
// Helper: normaliza relationship fields que devuelven arrays de IDs, objetos o arrays.
function acf_get_ids( $field_name, $post_id = null ) {
    $values = get_field( $field_name, $post_id );
    if ( ! $values || ! is_array( $values ) ) {
        return [];
    }
    $ids = [];
    foreach ( $values as $item ) {
        if ( is_numeric( $item ) ) {
            $ids[] = (int) $item;
        } elseif ( is_object( $item ) && isset( $item->ID ) ) {
            $ids[] = $item->ID;
        } elseif ( is_array( $item ) && isset( $item['ID'] ) ) {
            $ids[] = $item['ID'];
        }
    }
    return $ids;
}

function agregar_open_graph_tags() {
    if ( ! is_singular( ['amenity','apartamento'] ) ) {
        return;
    }
    global $post;
    $post_id = $post->ID;
    $title   = get_the_title( $post_id );
    $url     = get_permalink( $post_id );
    $desc    = get_bloginfo('description');
    $img_id  = get_post_thumbnail_id( $post_id );

    if ( get_post_type( $post_id ) === 'amenity' ) {
        // Descripción corta
        $desc = get_field( 'descripcion_corta', $post_id ) ?: $desc;

        // Si no hay featured image, toma la primera de la galería de galerías
        if ( ! $img_id ) {
            $gallery_post_ids = acf_get_ids( 'imagenes', $post_id );
            if ( ! empty( $gallery_post_ids ) ) {
                $first_gallery_id = $gallery_post_ids[0];
                $images_in_gallery = get_field( 'galeria', $first_gallery_id );
                if ( is_array( $images_in_gallery ) && isset( $images_in_gallery[0]['ID'] ) ) {
                    $img_id = $images_in_gallery[0]['ID'];
                }
            }
        }
    }

    if ( get_post_type( $post_id ) === 'apartamento' ) {
        // Construye la desc a partir de la tipología
        $tip_id    = get_field( 'tipologia', $post_id );
        $habit     = get_field( 'cantidad_de_habitaciones', 'term_' . $tip_id ) ?: '';
        $sup       = get_field( 'superficie',             'term_' . $tip_id ) ?: '';
        $desc      = trim( "{$habit} habitaciones • {$sup} m²" ) ?: $desc;

        // Imagen destacada de la tipología
        $img_id = get_field( 'imagen_destacada', 'term_' . $tip_id );
    }

    $img_url = $img_id ? wp_get_attachment_image_url( $img_id, 'large' ) : '';

    echo "
      <meta property='og:type'        content='article' />
      <meta property='og:title'       content='" . esc_attr( $title ) . "' />
      <meta property='og:description' content='" . esc_attr( wp_strip_all_tags( $desc ) ) . "' />
      <meta property='og:url'         content='" . esc_url( $url ) . "' />
      <meta property='og:image'       content='" . esc_url( $img_url ) . "' />
      <meta property='og:image:alt'   content='" . esc_attr( $title ) . "' />
      <meta property='og:site_name'   content='" . get_bloginfo('name') . "' />

      <meta name='twitter:card'        content='summary_large_image' />
      <meta name='twitter:title'       content='" . esc_attr( $title ) . "' />
      <meta name='twitter:description' content='" . esc_attr( wp_strip_all_tags( $desc ) ) . "' />
      <meta name='twitter:image'       content='" . esc_url( $img_url ) . "' />
    ";
}
add_action( 'wp_head', 'agregar_open_graph_tags' );
