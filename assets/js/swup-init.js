document.addEventListener('DOMContentLoaded', function() {
  const swup = new Swup({
    containers: ['#swup'],
    cache: true,
    linkSelector: 'a[href^="' + window.location.origin + '"]:not([data-no-swup]):not([href*="wp-admin"])'
  });
  
  swup.on('contentReplaced', function() {
    // Re-init custom selects
    if (typeof buildCustomSelect === 'function') {
      document.querySelectorAll('select.cluster-dd').forEach(buildCustomSelect);
    }
    
    // Re-init Swipers
    if (window.Swiper) {
      document.querySelectorAll('.swiper').forEach(function(el) {
        new Swiper(el, {
          navigation: {
            nextEl: '.swiper-button-next',
            prevEl: '.swiper-button-prev'
          },
          loop: true
        });
      });
    }
    
    // Re-ejecutar scripts inline marcados
    document.querySelectorAll('script[data-swup-reload-script]').forEach(function(script) {
      eval(script.innerHTML);
    });
  });
});