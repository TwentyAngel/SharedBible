let lastScroll = 0;
const header = document.getElementById('header');

window.addEventListener('scroll', () => {
  const currentScroll = window.pageYOffset;
  if (currentScroll > lastScroll) {
    header.style.top = '-70px'; // Oculta el header
  } else {
    header.style.top = '0'; // Muestra el header
  }
  lastScroll = currentScroll;
});