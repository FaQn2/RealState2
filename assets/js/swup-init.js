/**
 * swup-init.js - Versión completa con Fullscreen persistente
 * Ubicación: /assets/js/swup-init.js
 */

// ============================
// FULLSCREEN MANAGER
// ============================
class FullscreenManager {
  constructor() {
    this.isFullscreen = false;
    this.targetElement = null;
    this.init();
  }

  init() {
    // Escuchar cambios de fullscreen
    document.addEventListener('fullscreenchange', () => {
      this.isFullscreen = !!document.fullscreenElement;
      this.updateUI();
    });
    
    document.addEventListener('webkitfullscreenchange', () => {
      this.isFullscreen = !!document.webkitFullscreenElement;
      this.updateUI();
    });
  }

  async enterFullscreen(element = document.documentElement) {
    this.targetElement = element;
    
    try {
      if (element.requestFullscreen) {
        await element.requestFullscreen();
      } else if (element.webkitRequestFullscreen) {
        await element.webkitRequestFullscreen();
      } else if (element.mozRequestFullScreen) {
        await element.mozRequestFullScreen();
      } else if (element.msRequestFullscreen) {
        await element.msRequestFullscreen();
      }
      
      this.isFullscreen = true;
      // Guardar estado
      sessionStorage.setItem('fullscreen_active', 'true');
      
      return true;
    } catch (error) {
      console.error('Error entering fullscreen:', error);
      return false;
    }
  }

  async exitFullscreen() {
    try {
      if (document.exitFullscreen) {
        await document.exitFullscreen();
      } else if (document.webkitExitFullscreen) {
        await document.webkitExitFullscreen();
      } else if (document.mozCancelFullScreen) {
        await document.mozCancelFullScreen();
      } else if (document.msExitFullscreen) {
        await document.msExitFullscreen();
      }
      
      this.isFullscreen = false;
      sessionStorage.removeItem('fullscreen_active');
      
      return true;
    } catch (error) {
      console.error('Error exiting fullscreen:', error);
      return false;
    }
  }

  toggle() {
    if (this.isFullscreen) {
      return this.exitFullscreen();
    } else {
      return this.enterFullscreen();
    }
  }

  updateUI() {
    // Actualizar todos los botones de fullscreen
    const buttons = document.querySelectorAll('[data-fullscreen-btn]');
    buttons.forEach(btn => {
      if (this.isFullscreen) {
        btn.innerHTML = '<i class="fa-solid fa-compress"></i> <span class="hidden sm:inline">Salir</span>';
        btn.classList.add('fullscreen-active');
      } else {
        btn.innerHTML = '<i class="fa-solid fa-expand"></i> <span class="hidden sm:inline">Pantalla completa</span>';
        btn.classList.remove('fullscreen-active');
      }
    });
    
    // Agregar clase al body
    document.body.classList.toggle('is-fullscreen', this.isFullscreen);
  }

  // Mantener fullscreen después de navegación
  async restore() {
    if (sessionStorage.getItem('fullscreen_active') === 'true' && !this.isFullscreen) {
      await this.enterFullscreen();
    }
  }
}

// ============================
// SWUP CONFIGURATION
// ============================
document.addEventListener('DOMContentLoaded', function() {
  
  // Inicializar Fullscreen Manager
  const fullscreenManager = new FullscreenManager();
  window.fullscreenManager = fullscreenManager;
  
  // Configurar SWUP
  const swup = new Swup({
    containers: ['#swup'],
    cache: true,
    animationSelector: '[class*="transition-"]',
    linkSelector: 'a[href^="' + window.location.origin + '"]:not([data-no-swup]):not([href^="#"]):not([href*="wp-admin"]):not([target="_blank"])',
    skipPopStateHandling: function(event) {
      return event.state && event.state.source === 'swup';
    }
  });
  
  // ============================
  // SWUP EVENTS
  // ============================
  
  // ANTES de cambiar página
  swup.on('willReplaceContent', function() {
    // Limpiar recursos actuales
    if (window.swiperInstances) {
      Object.values(window.swiperInstances).forEach(swiper => {
        if (swiper && swiper.destroy) {
          swiper.destroy(true, true);
        }
      });
      window.swiperInstances = {};
    }
    
    // Guardar estado de filtros si existen
    const filterState = document.querySelector('#form-filtro');
    if (filterState) {
      const formData = new FormData(filterState);
      const state = {};
      for (let [key, value] of formData.entries()) {
        state[key] = value;
      }
      sessionStorage.setItem('filter_state', JSON.stringify(state));
    }
  });
  
  // DESPUÉS de reemplazar contenido
  swup.on('contentReplaced', function() {
    // 1. Restaurar Fullscreen
    fullscreenManager.restore();
    
    // 2. Re-inicializar botones de fullscreen
    initFullscreenButtons();
    
    // 3. Re-init custom selects
    if (typeof buildCustomSelect === 'function') {
      document.querySelectorAll('select.cluster-dd').forEach(buildCustomSelect);
    }
    
    // 4. Re-init Swipers con configuración correcta
    initSwipers();
    
    // 5. Re-ejecutar scripts inline marcados
    document.querySelectorAll('script[data-swup-reload-script]').forEach(function(script) {
      try {
        eval(script.innerHTML);
      } catch(e) {
        console.error('Error executing script:', e);
      }
    });
    
    // 6. Re-inicializar según la página
    reinitByPage();
    
    // 7. Restaurar estado de filtros si volvemos a unidades
    if (document.querySelector('#form-filtro')) {
      const savedState = sessionStorage.getItem('filter_state');
      if (savedState) {
        try {
          const state = JSON.parse(savedState);
          const form = document.querySelector('#form-filtro');
          Object.keys(state).forEach(key => {
            const input = form.querySelector(`[name="${key}"]`);
            if (input) input.value = state[key];
          });
        } catch(e) {}
      }
    }
  });
  
  // Animación iniciada
  swup.on('animationInStart', function() {
    // Scroll suave al top solo si no es paginación
    if (!window.location.search.includes('pagina=')) {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  });
  
  // Transición completada
  swup.on('transitionEnd', function() {
    // Disparar resize para componentes que lo necesiten
    window.dispatchEvent(new Event('resize'));
  });
  
  // Page view (analytics)
  swup.on('pageView', function() {
    // Google Analytics
    if (typeof gtag !== 'undefined') {
      gtag('config', 'GA_MEASUREMENT_ID', {
        page_path: window.location.pathname
      });
    }
  });
  
  // ============================
  // HELPER FUNCTIONS
  // ============================
  
  function initFullscreenButtons() {
    const buttons = document.querySelectorAll('[data-fullscreen-btn], #btn-fullscreen');
    
    buttons.forEach(btn => {
      // Remover listeners antiguos clonando
      const newBtn = btn.cloneNode(true);
      btn.parentNode.replaceChild(newBtn, btn);
      
      // Agregar nuevo listener
      newBtn.addEventListener('click', (e) => {
        e.preventDefault();
        fullscreenManager.toggle();
      });
    });
    
    // Actualizar UI
    fullscreenManager.updateUI();
  }
  
  function initSwipers() {
    if (!window.Swiper) return;
    
    window.swiperInstances = window.swiperInstances || {};
    
    // Configuraciones específicas por tipo
    const configs = {
      '.swiper-gallery': {
        loop: true,
        navigation: {
          nextEl: '.swiper-button-next',
          prevEl: '.swiper-button-prev'
        },
        keyboard: true,
        lazy: {
          loadPrevNext: true
        }
      },
      '.swiper-planos': {
        loop: false,
        navigation: {
          nextEl: '.swiper-button-next',
          prevEl: '.swiper-button-prev'
        },
        keyboard: true
      }
    };
    
    // Inicializar cada tipo
    Object.entries(configs).forEach(([selector, config]) => {
      document.querySelectorAll(selector).forEach((el, index) => {
        const key = `${selector}-${index}`;
        if (window.swiperInstances[key]) {
          window.swiperInstances[key].destroy(true, true);
        }
        window.swiperInstances[key] = new Swiper(el, config);
      });
    });
    
    // Swipers genéricos
    document.querySelectorAll('.swiper:not(.swiper-gallery):not(.swiper-planos)').forEach((el, index) => {
      const key = `generic-${index}`;
      if (!window.swiperInstances[key]) {
        window.swiperInstances[key] = new Swiper(el, {
          loop: true,
          navigation: {
            nextEl: '.swiper-button-next',
            prevEl: '.swiper-button-prev'
          }
        });
      }
    });
  }
  
  function reinitByPage() {
    const path = window.location.pathname;
    
    // Frontpage - revisar si hay video
    if (path === '/' || document.querySelector('.video-container')) {
      const video = document.querySelector('video[autoplay]');
      if (video) {
        video.play().catch(() => {});
      }
    }
    
    // Nivel 1 - iframe 360
    if (path.includes('/nivel-1/') || document.querySelector('.iframe-container')) {
      const iframe = document.querySelector('iframe[data-src]');
      if (iframe && !iframe.src) {
        iframe.src = iframe.dataset.src;
      }
    }
    
    // Unidades - re-bind filtros
    if (document.querySelector('#btn-filtrar')) {
      const btnFiltrar = document.getElementById('btn-filtrar');
      const btnFiltrarMobile = document.getElementById('btn-filtrar-mobile');
      
      [btnFiltrar, btnFiltrarMobile].forEach(btn => {
        if (btn) {
          btn.addEventListener('click', (e) => {
            e.preventDefault();
            const modal = document.getElementById('filtro-popup');
            if (modal) {
              modal.classList.remove('hidden');
              // Trigger open modal animation si existe
              if (typeof openModal === 'function') {
                openModal();
              }
            }
          });
        }
      });
    }
    
    // WhatsApp button
    if (window.ht_ctc && window.ht_ctc.init) {
      window.ht_ctc.init();
    }
  }
  
  // ============================
  // INITIAL SETUP
  // ============================
  
  // Inicializar botones de fullscreen
  initFullscreenButtons();
  
  // Inicializar componentes de la página actual
  reinitByPage();
  
  // Si hay un estado de fullscreen guardado, restaurarlo
  fullscreenManager.restore();
  
  // Export para uso global
  window.swup = swup;
  
  // Prevenir salida accidental del fullscreen con ESC
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && fullscreenManager.isFullscreen) {
      e.preventDefault();
      e.stopPropagation();
      // Mostrar confirmación antes de salir
      if (confirm('¿Deseas salir de pantalla completa?')) {
        fullscreenManager.exitFullscreen();
      }
    }
  }, true);
});

// ============================
// BOTÓN FULLSCREEN EN FRONTPAGE
// ============================
document.addEventListener('DOMContentLoaded', function() {
  // Solo si estamos en frontpage
  if (document.querySelector('.frontpage-container')) {
    // Crear botón si no existe
    if (!document.querySelector('#btn-fullscreen')) {
      const btnHTML = `
        <button id="btn-fullscreen" 
                data-fullscreen-btn
                class="fixed bottom-6 right-6 z-50 px-4 py-2 bg-primary text-white rounded-full shadow-lg hover:shadow-xl transition-all">
          <i class="fa-solid fa-expand"></i> 
          <span class="hidden sm:inline">Pantalla completa</span>
        </button>
      `;
      document.body.insertAdjacentHTML('beforeend', btnHTML);
    }
  }
});