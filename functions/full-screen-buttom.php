<button id="btn-fullscreen" title="Pantalla completa"
  class="fixed right-4 top-[calc(1rem+var(--wp-admin--admin-bar--height,0px))] z-50 
         bg-white text-gray-700 hover:bg-blue-600 hover:text-white 
         shadow-md hover:shadow-lg rounded-full p-2 transition duration-300 group">

  <!-- Icono expandir -->
  <svg id="icon-expand" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 transition duration-300"
       fill="none" viewBox="0 0 24 24" stroke="currentColor">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M4 8V4h4M4 4l6 6m10 6v4h-4m4 4l-6-6" />
  </svg>

  <!-- Icono salir -->
  <svg id="icon-compress" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 hidden transition duration-300"
       fill="none" viewBox="0 0 24 24" stroke="currentColor">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M4 20h4v-4m0 4l-6-6m16-8h-4v4m0-4l6 6" />
  </svg>
</button>

<script>
document.addEventListener("DOMContentLoaded", () => {
  const btn = document.getElementById("btn-fullscreen");
  const iconExpand = document.getElementById("icon-expand");
  const iconCompress = document.getElementById("icon-compress");

  function updateIcons(isFullscreen) {
    if (isFullscreen) {
      iconExpand.classList.add('hidden');
      iconCompress.classList.remove('hidden');
      btn.title = "Salir de pantalla completa";
    } else {
      iconExpand.classList.remove('hidden');
      iconCompress.classList.add('hidden');
      btn.title = "Pantalla completa";
    }
  }

  btn.addEventListener("click", () => {
    if (!document.fullscreenElement) {
      document.documentElement.requestFullscreen()
        .then(() => updateIcons(true))
        .catch(err => console.error("Error al entrar en fullscreen:", err));
    } else {
      document.exitFullscreen()
        .then(() => updateIcons(false))
        .catch(err => console.error("Error al salir de fullscreen:", err));
    }
  });

  // Captura cuando el usuario sale con ESC o cambia de modo
  document.addEventListener("fullscreenchange", () => {
    updateIcons(!!document.fullscreenElement);
  });
});
</script>

<style>
@media (max-width: 768px) {
  #btn-fullscreen {
    bottom: 1rem;
    right: 1rem;
    padding: 0.5rem;
    top: auto; /* en mobile lo pasamos abajo */
  }
}
#btn-fullscreen {
  top: calc(1rem + var(--wp-admin--admin-bar--height, 0px));
}
body.admin-bar #btn-fullscreen {
  top: calc(3.5rem + 1rem);
}
</style>
