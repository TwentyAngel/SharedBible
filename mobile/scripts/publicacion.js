document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const publicacionId = urlParams.get('id'); // Obtener el ID de la publicación de la URL

    // Referencias a los elementos individuales del HTML
    const postUsername = document.getElementById('postUsername');
    const postDate = document.getElementById('postDate');
    const postContent = document.getElementById('postContent');
    const likeButton = document.getElementById('likeButton');
    const likeCountSpan = document.getElementById('likeCount');
    const shareButton = document.getElementById('shareButton'); // Ya existe

    const commentsList = document.getElementById('commentsList');
    const commentTextarea = document.getElementById('commentTextarea');
    const addCommentButton = document.getElementById('addCommentButton');

    // Referencias a los elementos del diálogo de confirmación de eliminación de comentarios
    const deleteCommentConfirmationDialog = document.getElementById('deleteCommentConfirmationDialog');
    const confirmDeleteCommentButton = document.getElementById('confirmDeleteComment');
    const cancelDeleteCommentButton = document.getElementById('cancelDeleteComment');
    const closeCommentDialogButton = deleteCommentConfirmationDialog ? deleteCommentConfirmationDialog.querySelector('.close-button') : null;


    let loggedInUserId = null; // Variable para almacenar el ID del usuario logueado

    // --- NUEVO: Referencias a los elementos del modal de compartir ---
    const shareModal = document.getElementById('shareModal');
    const savePostButton = document.getElementById('savePostButton');
    const copyLinkButton = document.getElementById('copyLinkButton');
    const closeShareModalButton = shareModal ? shareModal.querySelector('.close-button') : null; // Añadir chequeo de nulidad
    let currentPostId = null; // Para almacenar el ID de la publicación a compartir

    // Función para cerrar el modal de compartir
    function closeShareModal() {
        if (shareModal) {
            shareModal.style.display = 'none';
        }
    }


    // Función para cerrar el diálogo de eliminación de comentario
    function closeDeleteCommentDialog() {
        if (deleteCommentConfirmationDialog) {
            deleteCommentConfirmationDialog.style.display = 'none';
            deleteCommentConfirmationDialog.dataset.commentIdToDelete = ''; // Limpiar el ID
        }
    }

    // Event listeners para los botones del diálogo de eliminación de comentarios
    if (confirmDeleteCommentButton) {
        confirmDeleteCommentButton.addEventListener('click', async () => {
            const commentId = deleteCommentConfirmationDialog.dataset.commentIdToDelete;
            if (commentId) {
                await deleteComment(commentId);
                closeDeleteCommentDialog();
            }
        });
    }

    if (cancelDeleteCommentButton) {
        cancelDeleteCommentButton.addEventListener('click', closeDeleteCommentDialog);
    }

    if (closeCommentDialogButton) {
        closeCommentDialogButton.addEventListener('click', closeDeleteCommentDialog);
    }

    // Cargar el ID del usuario logueado al iniciar
    async function loadLoggedInUserId() {
        try {
            const response = await fetch('api/get_profile.php');
            const data = await response.json();
            if (data.success) {
                loggedInUserId = data.user_id;
            } else {
                console.warn('No se pudo obtener el ID de usuario de la sesión:', data.message);
                // Si no hay usuario logueado, algunas funcionalidades (comentar, likear, guardar) no estarán disponibles
            }
        } catch (error) {
            console.error('Error al cargar el ID de usuario de la sesión:', error);
        }
    }


    // Función para cargar los detalles de la publicación
    async function fetchPostDetails() {
        if (!publicacionId) {
            console.error('No se proporcionó un ID de publicación en la URL.');
            if (postContent) postContent.textContent = 'Error: Publicación no encontrada.';
            return;
        }

        try {
            const response = await fetch(`api/get_post_details.php?id=${publicacionId}`); //
            const result = await response.json();

            if (result.success && result.post) {
                const post = result.post;
                currentPostId = post.id_publicacion; // Actualiza el ID para el modal de compartir

                if (postUsername) postUsername.textContent = post.nombre_usuario; //
                if (postDate) postDate.textContent = post.fecha_publicacion_formateada; //
                if (postContent) postContent.textContent = post.contenido_publicacion; //
                if (likeCountSpan) likeCountSpan.textContent = post.likes_publicacion;

                // Actualizar el estado del botón de "Me gusta"
                if (likeButton) {
                    if (post.liked_by_user) {
                        likeButton.classList.add('liked');
                    } else {
                        likeButton.classList.remove('liked');
                    }
                }
            } else {
                console.error('Error al cargar la publicación:', result.message);
                if (postContent) postContent.textContent = `Error: ${result.message}`;
            }
        } catch (error) {
            console.error('Error de conexión al cargar la publicación:', error);
            if (postContent) postContent.textContent = 'Error de conexión al servidor.';
        }
    }

    // Función para cargar los comentarios
    async function fetchComments() {
        if (!publicacionId) return;

        try {
            // CORREGIDO: Usar publicacionId para la variable y para el parámetro de la URL,
            // que coincide con get_comments.php
            const response = await fetch(`api/get_comments.php?publicacionId=${publicacionId}`);
            const result = await response.json();

            if (result.success) {
                commentsList.innerHTML = ''; // Limpiar comentarios existentes
                if (result.comments.length > 0) {
                    result.comments.forEach(comment => {
                        const commentElement = document.createElement('div');
                        commentElement.classList.add('comment-item');
                        let deleteBtnHtml = '';
                        // Solo muestra el botón de eliminar si el comentario pertenece al usuario logueado
                        if (loggedInUserId && comment.id_usuario == loggedInUserId) { 
                            deleteBtnHtml = ``; 
                        }
                        commentElement.innerHTML = `
                            <p><strong>${comment.nombre_usuario}</strong> <span class="comment-date">${comment.tiempo_transcurrido}</span></p> 
                            <p>${comment.contenido_com}</p> 
                            ${deleteBtnHtml}
                        `;
                        commentsList.appendChild(commentElement);
                    });

                    // Añadir event listeners a los nuevos botones de eliminar
                    commentsList.querySelectorAll('.delete-comment-btn').forEach(button => {
                        button.addEventListener('click', (event) => {
                            const commentIdToDelete = event.target.dataset.commentId;
                            if (deleteCommentConfirmationDialog) {
                                deleteCommentConfirmationDialog.dataset.commentIdToDelete = commentIdToDelete;
                                deleteCommentConfirmationDialog.style.display = 'block';
                            }
                        });
                    });

                } else {
                    commentsList.innerHTML = '<p>No hay comentarios aún. Sé el primero en comentar.</p>';
                }
            } else {
                console.error('Error al cargar comentarios:', result.message);
                commentsList.innerHTML = `<p>Error al cargar comentarios: ${result.message}</p>`;
            }
        } catch (error) {
            console.error('Error de conexión al cargar comentarios:', error);
            commentsList.innerHTML = '<p>Error de conexión al servidor al cargar comentarios.</p>';
        }
    }

    // Función para añadir un comentario
    if (addCommentButton) {
        addCommentButton.addEventListener('click', async () => {
            if (!loggedInUserId) {
                alert('Debes iniciar sesión para añadir un comentario.');
                return;
            }
            const commentContent = commentTextarea.value.trim();
            if (commentContent === '') {
                alert('El comentario no puede estar vacío.');
                return;
            }

            try {
                const response = await fetch('api/add_comment.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        publicacionId: publicacionId, //
                        content: commentContent
                    })
                });
                const result = await response.json();

                if (result.success) {
                    commentTextarea.value = ''; // Limpiar textarea
                    fetchComments(); // Recargar comentarios
                } else {
                    alert('Error al añadir comentario: ' + result.message);
                }
            } catch (error) {
                console.error('Error de conexión al añadir comentario:', error);
                alert('Error de conexión al servidor al añadir comentario.');
            }
        });
    }

    // Función para eliminar un comentario
    async function deleteComment(commentId) {
        if (!loggedInUserId) {
            alert('Debes iniciar sesión para eliminar un comentario.');
            return;
        }
        try {
            const response = await fetch('api/delete_comment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    commentId: commentId
                })
            });
            const result = await response.json();

            if (result.success) {
                alert(result.message);
                fetchComments(); // Recargar comentarios
            } else {
                alert('Error al eliminar comentario: ' + result.message);
            }
        } catch (error) {
            console.error('Error de conexión al eliminar comentario:', error);
            alert('Error de conexión al servidor al eliminar comentario.');
        }
    }


    // Lógica para el botón de "Me gusta"
    if (likeButton) {
        likeButton.addEventListener('click', async () => {
            if (!loggedInUserId) {
                alert('Debes iniciar sesión para dar "Me gusta".');
                return;
            }

            const isLiked = likeButton.classList.contains('liked');
            const action = isLiked ? 'unlike' : 'like';

            try {
                const response = await fetch('api/toggle_like.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        publicacionId: publicacionId, //
                        action: action
                    })
                });
                const result = await response.json();

                if (result.success) {
                    if (likeCountSpan) likeCountSpan.textContent = result.newLikesCount;
                    if (action === 'like') {
                        likeButton.classList.add('liked');
                    } else {
                        likeButton.classList.remove('liked');
                    }
                } else {
                    alert('Error al procesar el "Me gusta": ' + result.message);
                }
            } catch (error) {
                console.error('Error de conexión al alternar "Me gusta":', error);
                alert('Error de conexión al servidor al alternar "Me gusta".');
            }
        });
    }

    // Lógica para el botón de compartir
    if (shareButton) {
        shareButton.addEventListener('click', () => {
            if (currentPostId) {
                if (shareModal) {
                    shareModal.style.display = 'flex';
                }
            } else {
                alert('No se pudo obtener el ID de la publicación para compartir.');
            }
        });
    }

    if (savePostButton) {
        savePostButton.addEventListener('click', async () => {
            if (!loggedInUserId) {
                alert('Debes iniciar sesión para guardar publicaciones.');
                return;
            }
            if (currentPostId) {
                try {
                    const response = await fetch('api/toggle_save_post.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            publicacionId: currentPostId, //
                            action: 'save' // Forzar a "guardar" aquí, ya que el botón es para guardar
                        })
                    });
                    const result = await response.json();
                    if (result.success) {
                        alert(result.message);
                    } else {
                        alert(result.message);
                    }
                } catch (error) {
                    console.error('Error de conexión al guardar/desguardar publicación:', error);
                    alert('Error de conexión al servidor al intentar guardar/desguardar.');
                } finally {
                    closeShareModal(); // Siempre cierra el modal después de intentar la operación
                }
            }
        });
    }

    if (copyLinkButton) {
        copyLinkButton.addEventListener('click', () => {
            if (currentPostId) {
                const postLink = `${window.location.origin}/mobile/publicacion.html?id=${currentPostId}`;
                navigator.clipboard.writeText(postLink).then(() => {
                    alert('Enlace copiado al portapapeles.');
                    closeShareModal();
                }).catch((err) => {
                    console.error('Error al copiar el enlace: ', err);
                    alert('No se pudo copiar el enlace.');
                });
            }
        });
    }

    if (closeShareModalButton) {
        closeShareModalButton.addEventListener('click', closeShareModal);
    }

    if (shareModal) {
        window.addEventListener('click', (event) => {
            if (event.target == shareModal) {
                closeShareModal();
            }
        });
    }
    // --- FIN: Lógica para el modal de compartir ---


    // --- FUNCIONES AUXILIARES ---
    // Esta función no se usa directamente en el frontend para mostrar el tiempo,
    // pero puede ser útil si decides cambiar cómo se muestra la fecha.
    function getTimeElapsed(date) {
        const seconds = Math.floor((new Date() - date) / 1000);

        let interval = seconds / 31536000;
        if (interval > 1) { return Math.floor(interval) + " años"; }
        interval = seconds / 2592000;
        if (interval > 1) { return Math.floor(interval) + " meses"; }
        interval = seconds / 86400;
        if (interval > 1) { return Math.floor(interval) + " días"; }
        interval = seconds / 3600;
        if (interval > 1) { return Math.floor(interval) + " horas"; }
        interval = seconds / 60;
        if (interval > 1) { return Math.floor(interval) + " minutos"; }
        return Math.floor(seconds) + " segundos";
    }

    // No se utiliza directamente en publicacion.js, pero se mantiene por si acaso.
    function getYouTubeVideoId(url) {
        let videoId = '';
        const regExp = /(?:https?:\/\/)?(?:www\.)?(?:m\.)?(?:m\.)?(?:youtube\.com|youtu\.be)\/(?:watch\?v=|embed\/|v\/|)([\\w-]{11})(?:\\S+)?/; //
        const match = url.match(regExp);
        if (match && match[1] && match[1].length === 11) {
            videoId = match[1];
        }
        return videoId;
    }


    // Iniciar la carga de datos al cargar la página
    loadLoggedInUserId().then(() => {
        fetchPostDetails();
        fetchComments();
    });
});