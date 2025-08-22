document.addEventListener('DOMContentLoaded', () => {
    const testimonyForm = document.getElementById('testimonyForm');
    const testimonyList = document.getElementById('testimoniesList');
    const messageDiv = document.getElementById('formMessage'); // Para mostrar mensajes de éxito/error

    // Asegurarse de que el contenedor de testimonios exista
    if (!testimonyList) {
        console.error("Error: Elemento con ID 'testimoniesList' no encontrado en el DOM.");
        return; // Detener la ejecución si el elemento crítico no se encuentra
    }

    // Función para limpiar el formulario
    function clearForm() {
        document.getElementById('testimonyContent').value = '';
        document.getElementById('youtubeUrl').value = ''; // Limpiar también el campo de URL
    }

    // Función para mostrar mensajes
    function showMessage(msg, isSuccess) {
        if (messageDiv) { // Asegurarse de que messageDiv exista
            messageDiv.textContent = msg;
            messageDiv.style.color = isSuccess ? 'green' : 'red';
            messageDiv.style.display = 'block';
            setTimeout(() => {
                messageDiv.style.display = 'none';
                messageDiv.textContent = '';
            }, 5000); // Ocultar después de 5 segundos
        }
    }

    // Función para extraer el ID del video de YouTube
    function getYouTubeVideoId(url) {
        let videoId = '';
        // Regex más robusta para varias URLs de YouTube
        const regExp = /(?:https?:\/\/)?(?:www\.)?(?:m\.)?(?:youtube\.com|youtu\.be)\/(?:watch\?v=|embed\/|v\/|)([\w-]{11})(?:\S+)?/;
        const match = url.match(regExp);
        if (match && match[1] && match[1].length === 11) {
            videoId = match[1];
        }
        return videoId;
    }

    // Función para cargar y mostrar los testimonios
    async function loadTestimonies() {
        try {
            // Se asume que 'api/get_testimonies.php' es la ruta correcta del backend
            const response = await fetch('api/get_testimonies.php', { method: 'GET' });
            
            const contentType = response.headers.get("content-type");
            if (!response.ok || !contentType || !contentType.includes("application/json")) {
                const errorText = await response.text();
                console.error('Error de respuesta del servidor o no es JSON:', response.status, errorText);
                showMessage('Error al cargar testimonios: La respuesta del servidor no es válida o está vacía. (Revisa la consola para más detalles)', false);
                testimonyList.innerHTML = '<p>No se pudieron cargar los testimonios en este momento.</p>';
                return;
            }

            const result = await response.json();

            if (result.success && result.testimonies && result.testimonies.length > 0) {
                testimonyList.innerHTML = ''; // Limpiar lista antes de cargar
                result.testimonies.forEach(testimony => {
                    const testimonyCard = document.createElement('div');
                    testimonyCard.className = 'testimony-card';

                    let videoHtml = '';
                    if (testimony.youtube_url) {
                        const videoId = getYouTubeVideoId(testimony.youtube_url);
                        if (videoId) {
                            videoHtml = `
                                <div class="testimony-video-container">
                                    <iframe src="https://www.youtube.com/embed/${videoId}" 
                                            frameborder="0" 
                                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" 
                                            referrerpolicy="strict-origin-when-cross-origin" 
                                            allowfullscreen>
                                    </iframe>
                                </div>
                            `;
                        }
                    }

                    testimonyCard.innerHTML = `
                        <div class="testimony-header">
                            <span class="testifier-name">${testimony.nombre_usuario || 'Anónimo'}</span>
                            <span class="testimony-date">${testimony.fecha_formateada || 'Fecha desconocida'}</span>
                        </div>
                        <div class="testimony-body">
                            <p>"${testimony.contenido_testimonio}"</p>
                            ${videoHtml}
                        </div>
                    `;
                    // *** CORRECCIÓN CRUCIAL AQUÍ: Usar appendChild para que el orden sea de más reciente a más antiguo ***
                    testimonyList.appendChild(testimonyCard); 
                });
            } else if (result.success && result.testimonies.length === 0) {
                testimonyList.innerHTML = '<p>Aún no hay testimonios. ¡Sé el primero en compartir el tuyo!</p>';
            }
            else {
                console.error('Error al cargar testimonios:', result.message || 'Mensaje de error no disponible.');
                showMessage(result.message || 'Error desconocido al cargar testimonios.', false);
                testimonyList.innerHTML = '<p>No se pudieron cargar los testimonios en este momento.</p>';
            }
        } catch (error) {
            console.error('Error de conexión o parseo al cargar testimonios:', error);
            showMessage('Error de conexión o datos incompletos del servidor al cargar testimonios. Inténtalo de nuevo. (Revisa la consola)', false);
            testimonyList.innerHTML = '<p>Error de conexión al cargar testimonios.</p>';
        }
    }

    // Event Listener para el envío del formulario
    if (testimonyForm) {
        testimonyForm.addEventListener('submit', async (event) => {
            event.preventDefault();

            const formData = new FormData(testimonyForm);

            try {
                // Se asume que 'api/get_testimonies.php' es la ruta correcta del backend
                const response = await fetch('api/get_testimonies.php', {
                    method: 'POST',
                    body: formData
                });

                const contentType = response.headers.get("content-type");
                if (!response.ok || !contentType || !contentType.includes("application/json")) {
                    const errorText = await response.text();
                    console.error('Error de respuesta del servidor al enviar testimonio o no es JSON:', response.status, errorText);
                    showMessage('Error al enviar testimonio: La respuesta del servidor no es válida. (Revisa la consola para más detalles)', false);
                    return;
                }

                const result = await response.json();

                if (result.success) {
                    showMessage(result.message, true);
                    clearForm();
                    await loadTestimonies(); // Recargar testimonios para mostrar el nuevo
                } else {
                    showMessage(result.message, false);
                }
            } catch (error) {
                console.error('Error al enviar testimonio:', error);
                showMessage('Error al conectar con el servidor. Inténtalo de nuevo.', false);
            }
        });
    }

    // Cargar testimonios al cargar la página
    loadTestimonies();
});