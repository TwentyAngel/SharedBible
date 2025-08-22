// scripts/load-sermons.js
document.addEventListener('DOMContentLoaded', function() {
    const sermonsContainer = document.getElementById('sermons-container');

    // --- NUEVO: Elementos de búsqueda y filtro ---
    const searchBar = document.getElementById('sermon-search-bar');
    const filterRama = document.getElementById('sermon-filter-rama');
    const applyFiltersBtn = document.getElementById('apply-filters-btn');

    // --- NUEVO: Elementos del modal de creación de predicación ---
    const sermonForm = document.getElementById('sermon-form');
    const sermonImageInput = document.getElementById('sermon-image-file');
    const sermonVideoInput = document.getElementById('sermon-video-file'); // NUEVO: input de video

    // --- NUEVO: Referencias a los elementos del modal de compartir para Sermones ---
    const shareModalSermons = document.getElementById('shareModal');
    const copyLinkButtonSermons = document.getElementById('copyLinkButtonSermons');
    const closeShareModalSermonsButton = document.getElementById('closeShareModalSermons');
    let currentSermonId = null; // Para almacenar el ID de la predicación a compartir

    // --- NUEVO: Función para abrir el modal de compartir predicación ---
    function openShareModalSermons(sermonId) {
        currentSermonId = sermonId;
        shareModalSermons.style.display = 'block';
        // Restablecer el texto del botón al abrir el modal por si se cambió antes
        if (copyLinkButtonSermons) {
            copyLinkButtonSermons.querySelector('span').textContent = '🔗 Copiar Enlace';
        }
    }

    // --- NUEVO: Función para cerrar el modal de compartir predicación ---
    function closeShareModalSermons() {
        shareModalSermons.style.display = 'none';
        currentSermonId = null;
    }

    // --- NUEVO: Event listener para el botón de cerrar del modal ---
    if (closeShareModalSermonsButton) {
        closeShareModalSermonsButton.addEventListener('click', closeShareModalSermons);
    }

    // --- NUEVO: Cerrar modal al hacer clic fuera del contenido ---
    window.addEventListener('click', (event) => {
        if (event.target == shareModalSermons) {
            closeShareModalSermons();
        }
    });

    // --- NUEVO: Lógica para el botón Copiar Enlace ---
    if (copyLinkButtonSermons) {
        copyLinkButtonSermons.addEventListener('click', () => {
            if (currentSermonId) {
                const sermonLink = `${window.location.origin}/predica.html?id=${currentSermonId}`;
                const originalButtonText = copyLinkButtonSermons.querySelector('span').textContent; // Guardar texto original
                
                navigator.clipboard.writeText(sermonLink).then(() => {
                    // Cambiar el texto del botón
                    copyLinkButtonSermons.querySelector('span').textContent = 'Enlace copiado';
                    
                    // Revertir el texto después de 2 segundos
                    setTimeout(() => {
                        copyLinkButtonSermons.querySelector('span').textContent = originalButtonText;
                        closeShareModalSermons(); // Cierra el modal después de que el texto vuelva a la normalidad
                    }, 2000); // 2000 milisegundos = 2 segundos
                    
                }).catch((err) => {
                    console.error('Error al copiar el enlace de la predicación: ', err);
                    alert('No se pudo copiar el enlace de la predicación.'); // Mantener alerta para errores
                });
            }
        });
    }

    // Función para truncar el texto
    function truncateText(text, maxLength) {
        if (text.length <= maxLength) {
            return text;
        }
        let truncated = text.substring(0, maxLength);
        let lastSpace = truncated.lastIndexOf(' ');
        if (lastSpace !== -1) {
            truncated = truncated.substring(0, lastSpace);
        }
        return truncated + '...';
    }

    // Manejar el envío del formulario de la predicación
    if (sermonForm) {
        sermonForm.addEventListener('submit', async function(event) {
            event.preventDefault(); // Prevenir el envío por defecto del formulario

            // Obtener una referencia al botón de envío dentro del formulario
            const submitButton = sermonForm.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.textContent; // Guardar texto original del botón

            // Deshabilitar el botón y cambiar su texto
            submitButton.disabled = true;
            submitButton.textContent = 'Publicando...';

            const formData = new FormData(sermonForm); // Crea un FormData con todos los campos del formulario

            // Eliminar los campos de imagen o video si el otro está presente
            const imageFile = sermonImageInput.files[0];
            const videoFile = sermonVideoInput.files[0];

            if (imageFile && videoFile) {
                alert('No se puede subir una imagen Y un video al mismo tiempo. Elija solo uno.');
                submitButton.disabled = false; // Re-habilitar el botón
                submitButton.textContent = originalButtonText; // Restaurar texto original
                return; // Detener la ejecución si la validación falla
            }
            if (!imageFile) {
                formData.delete('image_file');
            }
            if (!videoFile) {
                formData.delete('video_file');
            }

            try {
                const response = await fetch('api/create_sermon.php', {
                    method: 'POST',
                    body: formData // FormData se envía directamente
                });

                if (!response.ok) {
                    throw new Error('Error de red o del servidor al publicar la predicación.');
                }

                const data = await response.json();

                if (data.status === 'success') {
                    document.getElementById('create-sermon-modal').close(); // Cierra el modal
                    sermonForm.reset(); // Limpia el formulario
                    if (typeof window.loadSermons === 'function') {
                        window.loadSermons(); // Recarga las predicaciones
                    }
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                console.error('Error al publicar la predicación:', error);
                alert('Hubo un problema al publicar la predicación. Inténtalo de nuevo.');
            } finally {
                // Este bloque se ejecuta siempre, ya sea éxito o error
                submitButton.disabled = false; // Habilitar el botón de nuevo
                submitButton.textContent = originalButtonText; // Restaurar texto original del botón
            }
        });
    }

    // Function to load and display sermons (MODIFIED for video embed iframe)
    window.loadSermons = function() {
        const searchTerm = searchBar.value.trim();
        const selectedRama = filterRama.value;

        let url = 'api/get_sermons.php';
        const params = new URLSearchParams();

        if (searchTerm) {
            params.append('search', searchTerm);
        }
        if (selectedRama) {
            params.append('rama', selectedRama);
        }

        if (params.toString()) {
            url += '?' + params.toString();
        }

        fetch(url)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error de red o del servidor al obtener las predicaciones.');
                }
                return response.json();
            })
            .then(data => {
                if (data.status === 'success') {
                    sermonsContainer.innerHTML = '';
                    if (data.sermons.length === 0) {
                        sermonsContainer.innerHTML = '<p>No se encontraron predicaciones con los criterios de búsqueda o filtro.</p>';
                        return;
                    }

                    data.sermons.forEach(sermon => {
                        const card = document.createElement('div');
                        card.className = 'card';
                        card.setAttribute('data-id-predi', sermon.id_predi);

                        const imageUrlToUse = sermon.img_predi_final_url;
                        const videoFileUrlToUse = sermon.video_file_url; // NUEVO: URL embed del video

                        let mediaHtml = '';
                        if (videoFileUrlToUse) {
                            mediaHtml = `
                                <div class="video-container">
                                    <img src="img/mini.png" alt="Miniatura">
                                </div>
                            `;
                        } else if (imageUrlToUse) {
                            mediaHtml = `<img src="${imageUrlToUse}" alt="Imagen de la predicación">`;
                        }

                        card.innerHTML = `
                            <a href="predica.html?id=${sermon.id_predi}" class="linked">
                                ${mediaHtml}
                                <h3>${sermon.titulo_predi || 'Sin título'}</h3>
                                <div class="sermon-content-text">${truncateText(sermon.contenido_predi, 150)}</div>
                                <p><strong>Autor:</strong> ${sermon.autor_predi}</p>
                                ${sermon.rama_predi ? `<p><strong>Rama Doctrinal:</strong> ${sermon.rama_predi}</p>` : ''}
                                <p class="sermon-date"><strong>Fecha:</strong> ${sermon.fecha_predi}</p>
                            </a>
                            <div class="card-actions">
                                ${sermon.id_usuario == data.current_user_id ?
                                `<button class="action-btn delete-sermon-btn" data-id-predi="${sermon.id_predi}">Eliminar</button>` : ''}
                                <button class="action-btn share-btn2" data-id-predi="${sermon.id_predi}">🔗 Compartir</button>
                            </div>
                        `;

                        sermonsContainer.appendChild(card);
                    });

                    document.querySelectorAll('.view-sermon-btn').forEach(button => {
                        button.addEventListener('click', function() {
                            const idPredi = this.dataset.idPredi;
                            window.location.href = `predica.html?id=${idPredi}`;
                        });
                    });

                    document.querySelectorAll('.delete-sermon-btn').forEach(button => {
                        button.addEventListener('click', function() {
                            const idPredi = this.dataset.idPredi;
                            if (confirm('¿Estás seguro de que quieres eliminar esta predicación?')) {
                                deleteSermon(idPredi);
                            }
                        });
                    });

                    document.querySelectorAll('.share-btn2').forEach(button => {
                        button.addEventListener('click', function() {
                            const idPredi = this.dataset.idPredi;
                            openShareModalSermons(idPredi);
                        });
                    });

                } else {
                    console.error('Error al cargar predicaciones:', data.message);
                    sermonsContainer.innerHTML = '<p>No se pudieron cargar las predicaciones.</p>';
                }
            })
            .catch(error => {
                console.error('Error al cargar predicaciones:', error);
                sermonsContainer.innerHTML = '<p>Error de conexión al cargar las predicaciones.</p>';
            });
    }

    // Function to delete a sermon
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
                loadSermons();
            } else {
                alert('Error al eliminar: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error al eliminar la predicación:', error);
            alert('Hubo un problema al eliminar la predicación. Inténtalo de nuevo.');
        });
    }

    // Event listeners para búsqueda y filtro
    applyFiltersBtn.addEventListener('click', loadSermons);
    searchBar.addEventListener('keypress', function(event) {
        if (event.key === 'Enter') {
            loadSermons();
        }
    });

    loadSermons();
});
