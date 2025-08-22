function navegarConAnimacion(destino) {
  const body = document.getElementById('body');
  body.classList.add('fade-out');
  setTimeout(() => {
    window.location.href = destino;
  }, 100); // tiempo igual al de la animación
}