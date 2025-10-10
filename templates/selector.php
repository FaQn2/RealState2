<?php
/**
 * Template Name: Selector Clúster
 */
get_header();

$cluster_id = $_GET['cluster_id'] ?? null;
if ( ! $cluster_id ) {
    echo "<p class='text-text font-fuente_primaria p-6'>No se seleccionó ningún clúster</p>";
    get_footer();
    exit;
}
$cluster = get_post( $cluster_id );
?>

<style>
  /* ——— RESET scroll global ——— */
  html, body { height: 100%; margin: 0; overflow: hidden; }

  /* ——— Overlay + blur para el fondo ——— */
  .background-blur::before {
    content: "";
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,.08);
    backdrop-filter: blur(8px);
  }
</style>

<div class="relative min-h-screen font-fuente_primaria">
  <!-- Fondo full-screen con imagen y blur -->
  <div
    class="background-blur fixed inset-0 bg-center bg-cover z-0"
    style="background-image: url('<?php echo esc_url( get_field('background_image', $cluster_id )
        ?: 'https://graff3d.factorycreativestudio.com/wp-content/uploads/2025/07/Costanera-Nivel-1.jpg' ); ?>');"
  ></div>

  <div class="relative z-10 flex items-center justify-center h-screen p-4 " >
    <!-- Card centrada -->
    <div
      class="w-full max-w-2xl bg-white rounded-lg shadow-[0_20px_60px_rgba(0,0,0,0.3)]
             overflow-hidden animate-fade-in-up flex flex-col">

      <!-- Header fijo -->
      <div
        class="bg-primary text-white xs:text-xl xs2:text-2xl sm:text-3xl font-fuente_primaria font-bold
               py-6 px-6 flex items-center justify-center gap-3 flex-shrink-0"
      >
        <i class="fa-solid fa-building fa-xl"></i>
        <?php echo esc_html( $cluster->post_title ); ?>
      </div>

      <!-- Opciones: ocupa todo el espacio restante -->
      <div class="flex flex-col md:flex-row justify-center gap-4 p-4 flex-1   ">
        <!-- Apartamentos -->
        <a
          href="<?php echo esc_url( get_post_type_archive_link('apartamento')
              . '?cluster_id=' . $cluster_id ); ?>"
          class="relative block w-full md:w-96 aspect-video rounded-sm overflow-hidden 
                 shadow-xl hover:shadow-lg transition-shadow duration-300 group xs:h-[120px] xs1:h-[160px] xs2:h-[190px] xs3:h-[200px]   sm:h-[210px]  "
        >
          <img
            src="<?php echo esc_url( get_field('image_apartments', $cluster_id )
                ?: 'https://arqa.com/wp-content/uploads/2016/05/mast-3900alton-06-bedroom-03.jpg' ); ?>"
            alt="Ver Apartamentos"
            class="absolute inset-0 w-full h-full object-cover border  border-gray-300
                   transition-transform duration-300 group-hover:scale-105 "
          />
          <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent"></div>
          <span
            class="absolute bottom-4 left-1/2 transform -translate-x-1/2
                   text-white text-lg font-medium whitespace-nowrap"
          >
            Ver Apartamentos
          </span>
        </a>

        <!-- Amenidades -->
        <a
          href="<?php echo esc_url( get_post_type_archive_link('amenity')
              . '?cluster_id=' . $cluster_id ); ?>"
          class="relative block w-full md:w-96 aspect-video rounded-sm overflow-hidden
                 shadow-xl hover:shadow-lg transition-shadow duration-300 group  xs:h-[120px] xs1:h-[160px] xs2:h-[190px] xs3:h-[200px]   sm:h-[210px] "
        >
          <img
            src="<?php echo esc_url( get_field('image_amenities', $cluster_id )
                ?: 'https://arqa.com/wp-content/uploads/2016/05/mast-3900alton-06-pooldeckday_8k-04.jpg' ); ?>"
            alt="Ver Amenities"
            class="absolute inset-0 w-full h-full object-cover
                   transition-transform duration-300 group-hover:scale-105"
          />
          <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent"></div>
          <span
            class="absolute bottom-4 left-1/2 transform -translate-x-1/2
                   text-white text-lg font-medium whitespace-nowrap"
          >
            Ver Amenities
          </span>
        </a>
      </div>

      <!-- Botón volver fijo -->
      <div class="flex justify-center pb-4   flex-shrink-0">
        <a
          href="<?php echo esc_url( home_url('/nivel-1') ); ?>"
          class="btn-secondary inline-flex items-center gap-2"
        >
          <i class="fa-solid fa-arrow-left"></i>
          <span class="hidden sm:inline">Regresar</span>
        </a>
      </div>

    </div>
  </div>
</div>


<?php get_footer(); ?>
