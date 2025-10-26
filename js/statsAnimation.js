// Función para animar contadores
function animateValue(element, start, end, duration) {
  if (start === end) return;
  
  const range = end - start;
  const increment = end > start ? 1 : -1;
  const stepTime = Math.abs(Math.floor(duration / range));
  
  let current = start;
  const timer = setInterval(() => {
    current += increment;
    element.textContent = current;
    
    if (current === end) {
      clearInterval(timer);
    }
  }, stepTime);
}

// Exponer la función globalmente para que otras páginas puedan llamarla
window.animateValue = animateValue;

// Función para aplicar animación a las tarjetas de estadísticas
function initStatsAnimations() {
  const cards = document.querySelectorAll('.stat-card, .info-card');
  
  // Añadir delay incremental para cada tarjeta
  cards.forEach((card, index) => {
    card.style.animationDelay = `${0.1 * (index + 1)}s`;
  });

  // Observar cuando las tarjetas entran en el viewport
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.style.opacity = '1';
        entry.target.style.transform = 'translateY(0)';
      }
    });
  }, { threshold: 0.1 });

  cards.forEach(card => observer.observe(card));
}

// Función para actualizar fecha y hora en tiempo real
function updateDateTime() {
  const dateElement = document.getElementById('dateNow');
  if (dateElement) {
    const updateTime = () => {
      const now = new Date();
      const options = { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
      };
      dateElement.textContent = now.toLocaleDateString('es-MX', options);
    };
    
    updateTime();
    setInterval(updateTime, 1000);
  }
}

// Inicializar todas las animaciones cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
  initStatsAnimations();
  updateDateTime();
  
  // Animar valores de estadísticas
  setTimeout(() => {
    const statsElements = document.querySelectorAll('.animate-count');
    statsElements.forEach(element => {
      const finalValue = parseInt(element.textContent);
      if (!isNaN(finalValue)) {
        animateValue(element, 0, finalValue, 2000);
      }
    });
  }, 500);
});