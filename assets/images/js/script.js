document.addEventListener('DOMContentLoaded', () => {
  // 1. Mobile Menu Logic
  const burgerBtn = document.getElementById('burger-btn');
  const mobileMenu = document.getElementById('mobile-menu');
  const closeBtn = document.getElementById('mobile-menu-close');

  if (burgerBtn && mobileMenu && closeBtn) {
    burgerBtn.addEventListener('click', () => {
      mobileMenu.classList.add('open');
      document.body.style.overflow = 'hidden'; // Evitar scroll
    });

    closeBtn.addEventListener('click', () => {
      mobileMenu.classList.remove('open');
      document.body.style.overflow = '';
    });
  }

  // 2. Bento Scroll Logic (Intersection Observer Fade-In)
  const revealElements = document.querySelectorAll('.reveal');

  if (revealElements.length > 0) {
    const revealObserver = new IntersectionObserver((entries, observer) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('active');
          // Opcional: Desobservar si solo queremos que anime una vez
          // observer.unobserve(entry.target); 
        }
      });
    }, {
      root: null,
      rootMargin: '0px 0px -50px 0px',
      threshold: 0.1
    });

    revealElements.forEach(el => revealObserver.observe(el));
  }

  // 3. Accordion Logic for FAQs
  const accordions = document.querySelectorAll('.accordion');

  accordions.forEach(acc => {
    const header = acc.querySelector('.accordion-header');

    header.addEventListener('click', () => {
      // 3.1 Cierra los demás si se desea que solo esté uno activo (Exclusive Accordion)
      accordions.forEach(item => {
        if (item !== acc && item.classList.contains('active')) {
          item.classList.remove('active');
        }
      });

      // 3.2 Alterna el estado del presionado
      acc.classList.toggle('active');
    });
  });

});
