<?php
/**
 * The main template file
 */
get_header();
?>

<main class="min-h-screen p-6">
  <?php
  if ( have_posts() ) {
    while ( have_posts() ) {
      the_post();
      the_content();
    }
  } else {
    echo '<p>No hay contenido que mostrar.</p>';
  }
  ?>
</main>

<?php get_footer(); ?>
