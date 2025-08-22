let deferredPrompt = null;

window.addEventListener('beforeinstallprompt', (e) => {
  deferredPrompt = e;
  console.log('Evento beforeinstallprompt disparado. ¡El navegador ahora PUEDE mostrar su banner de instalación!');
});