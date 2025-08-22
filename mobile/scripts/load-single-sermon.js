document.addEventListener('DOMContentLoaded', function () {
    const sermonContainer = document.getElementById('sermon-container');
    const urlParams = new URLSearchParams(window.location.search);
    const sermonId = urlParams.get('id');

    // Referencias para modal compartir (si existen en la página)
    const shareModalSermons = document.getElementById('shareModal');
    const copyLinkButtonSermons = document.getElementById('copyLinkButtonSermons');
    const closeShareModalSermonsButton = document.getElementById('closeShareModalSermons');
    let currentSermonId = null;

    function closeShareModalSermons() {
        if (shareModalSermons) {
            shareModalSermons.style.display = 'none';
            currentSermonId = null;
        }
    }

    if (closeShareModalSermonsButton) {
        closeShareModalSermonsButton.addEventListener('click', closeShareModalSermons);
    }

    window.addEventListener('click', (event) => {
        if (event.target == shareModalSermons) {
            closeShareModalSermons();
        }
    });

    if (copyLinkButtonSermons) {
        copyLinkButtonSermons.addEventListener('click', () => {
            if (currentSermonId) {
                const sermonLink = `${window.location.origin}/mobile/predica.html?id=${currentSermonId}`;
                const originalButtonText = copyLinkButtonSermons.querySelector('span').textContent;

                navigator.clipboard.writeText(sermonLink).then(() => {
                    copyLinkButtonSermons.querySelector('span').textContent = 'Enlace copiado';
                    setTimeout(() => {
                        copyLinkButtonSermons.querySelector('span').textContent = originalButtonText;
                        closeShareModalSermons();
                    }, 2000);
                }).catch((err) => {
                    console.error('Error al copiar el enlace de la predicación: ', err);
                    alert('No se pudo copiar el enlace de la predicación.');
                });
            }
        });
    }

    if (sermonId) {
        fetch(`api/get_single_sermon.php?id=${sermonId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error de red o del servidor al obtener la predicación.');
                }
                return response.json();
            })
            .then(data => {
                if (data.status === 'success' && data.sermon) {
                    const sermon = data.sermon;
                    let sermonHtml = '';

                    // Mostrar el video o imagen primero
                    if (sermon.video_file_url) {
                        sermonHtml += `
                            <div class="video-container">
                                <iframe
                                    src="${sermon.video_file_url}"
                                    width="580px"
                                    height="400px"
                                    frameborder="0"
                                    allowfullscreen
                                    webkitallowfullscreen
                                    mozallowfullscreen
                                    allow="autoplay; fullscreen;"
                                    loading="lazy"
                                    title="Video predicación"
                                ></iframe>
                            </div>
                        `;
                    } else if (sermon.img_predi_final_url) {
                        sermonHtml += `
                            <img src="${sermon.img_predi_final_url}" alt="Imagen de la predicación" />
                        `;
                    }

                    // Luego el título
                    sermonHtml += `
                        <h2>${sermon.titulo_predi}</h2>
                        ${sermon.rama_predi ? '' : ''}
                    `;

                    const formattedContent = sermon.contenido_predi.replace(/\n\n/g, '</p><p>').replace(/\n/g, '<br>');
                    sermonHtml += `<div class="contenido-sermon"><p>${formattedContent}</p></div>`;
                    sermonHtml += `<p class="autor"><strong>Autor:</strong> ${sermon.autor_predi}</p>`;
                    sermonHtml += `<p class="fecha"><strong>Fecha:</strong> ${sermon.fecha_predi}</p>`;

                    if (sermon.id_usuario == data.current_user_id) {
                        sermonHtml += `<button class="action-btn delete-sermon-btn" data-id-predi="${sermon.id_predi}">Eliminar</button>`;
                    }

                    sermonContainer.innerHTML = sermonHtml;

                    const deleteBtn = sermonContainer.querySelector('.delete-sermon-btn');
                    if (deleteBtn) {
                        deleteBtn.addEventListener('click', function () {
                            if (confirm('¿Estás seguro de que quieres eliminar esta predicación?')) {
                                deleteSermon(sermon.id_predi);
                            }
                        });
                    }
                } else {
                    sermonContainer.innerHTML = `<p>Error: ${data.message || 'No se encontró la predicación.'}</p>`;
                }
            })
            .catch(error => {
                console.error('Error al cargar la predicación:', error);
                sermonContainer.innerHTML = '<p>Error de conexión al cargar la predicación.</p>';
            });
    } else {
        sermonContainer.innerHTML = '<p>ID de predicación no proporcionado en la URL.</p>';
    }

    function deleteSermon(idPredi) {
        const formData = new FormData();
        formData.append('id_predi', idPredi);

        fetch('api/delete_sermon.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Error de red o del servidor al eliminar la predicación.');
            }
            return response.json();
        })
        .then(data => {
            if (data.status === 'success') {
                alert(data.message);
                window.location.href = 'index.html';
            } else {
                alert('Error al eliminar: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error al eliminar la predicación:', error);
            alert('Hubo un problema al eliminar la predicación. Inténtalo de nuevo.');
        });
    }
});
