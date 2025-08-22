document.addEventListener('DOMContentLoaded', () => {
    const phraseOfTheDayElement = document.querySelector('.frase-del-dia p');

    if (phraseOfTheDayElement) {
        // La ruta de fetch ahora debe apuntar a la carpeta 'api'
        fetch('api/getPhrase.php') // Ruta correcta para acceder a tu API
            .then(response => response.json())
            .then(data => {
                if (data.phrase) {
                    phraseOfTheDayElement.textContent = data.phrase;
                } else if (data.error) {
                    console.error('Error al obtener la frase:', data.error);
                    phraseOfTheDayElement.textContent = "Error al cargar la frase. Por favor, inténtalo de nuevo más tarde.";
                }
            })
            .catch(error => {
                console.error('Error de red o problema del servidor:', error);
                phraseOfTheDayElement.textContent = "No se pudo conectar al servidor para obtener la frase.";
            });
    }
});