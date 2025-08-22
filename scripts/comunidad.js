document.addEventListener('DOMContentLoaded', () => {
    const postContentTextarea = document.getElementById('postContent');
    const publishPostButton = document.getElementById('publishPost');
    const postsFeed = document.querySelector('.posts-feed');

    let loggedInUserId = null; // Variable para almacenar el ID del usuario logueado

    // --- Referencias a los elementos del diálogo de confirmación de eliminación ---
    const deleteConfirmationDialog = document.getElementById('deleteConfirmationDialog');
    const confirmDeleteButton = document.getElementById('confirmDelete');
    const cancelDeleteButton = document.getElementById('cancelDelete');
    const closeDialogButton = deleteConfirmationDialog.querySelector('.close-button');

    // --- NUEVO: Referencias a los elementos del modal de compartir ---
    const shareModal = document.getElementById('shareModal');
    const savePostButton = document.getElementById('savePostButton');
    const copyLinkButton = document.getElementById('copyLinkButton');
    const closeShareModalButton = shareModal.querySelector('.close-button');
    let currentPostId = null; // Para almacenar el ID de la publicación a compartir

    // Función para cerrar el diálogo de eliminación
    function closeDeleteDialog() {
        deleteConfirmationDialog.style.display = 'none';
        deleteConfirmationDialog.dataset.commentIdToDelete = '';
    }

    // --- NUEVO: Función para cerrar el modal de compartir ---
    function closeShareModal() {
        shareModal.style.display = 'none';
        currentPostId = null;
    }

    // Función para formatear la fecha a "Hace X tiempo"
    function getTimeElapsed(dateString) {
        const postDate = new Date(dateString);
        const now = new Date();
        const seconds = Math.floor((now - postDate) / 1000);

        let interval = seconds / 31536000;
        if (interval > 1) {
            return `Hace ${Math.floor(interval)} año(s)`;
        }
        interval = seconds / 2592000;
        if (interval > 1) {
            return `Hace ${Math.floor(interval)} mes(es)`;
        }
        interval = seconds / 86400;
        if (interval > 1) {
            return `Hace ${Math.floor(interval)} día(s)`;
        }
        interval = seconds / 3600;
        if (interval > 1) {
            return `Hace ${Math.floor(interval)} hora(s)`;
        }
        interval = seconds / 60;
        if (interval > 1) {
            return `Hace ${Math.floor(interval)} minuto(s)`;
        }
        return `Hace unos segundos`;
    }

    // Función para crear una tarjeta de publicación
    function createPostCard(post) {
        const postCard = document.createElement('div');
        postCard.classList.add('post-card');
        postCard.dataset.postId = post.id_publicacion;

        const timeElapsed = getTimeElapsed(post.fecha_publicacion);

        // Asumiendo que `post.liked_by_user` viene del backend (si lo implementaste)
        const isLiked = post.liked_by_user ? 'liked' : '';

        postCard.innerHTML = `
            <div class="post-header">
                <span class="username">${post.nombre_usuario}</span>
                <span class="post-date">${timeElapsed}</span>
            </div>
            <div class="post-body">
                <p>${post.contenido_publicacion}</p>
            </div>
            <div class="post-actions">
                <button class="action-btn like-btn ${isLiked}" data-post-id="${post.id_publicacion}">❤️ Me gusta (<span class="like-count">${post.likes_publicacion}</span>)</button>
                <button class="action-btn comment-btn" data-post-id="${post.id_publicacion}">💬 Comentar</button>
                <button class="action-btn share-btn" data-post-id="${post.id_publicacion}">🔗 Compartir</button>
            </div>
            <div class="comments-section" style="display: none;">
                <div class="comments-list" data-post-id="${post.id_publicacion}">
                    </div>
                <div class="comment-input-area">
                    <textarea class="comment-textarea" placeholder="Escribe un comentario..." rows="1"></textarea>
                    <button class="add-comment-btn" data-post-id="${post.id_publicacion}">Añadir Comentario</button>
                </div>
            </div>
        `;

        // Añadir evento para mostrar/ocultar comentarios
        const commentBtn = postCard.querySelector('.comment-btn');
        commentBtn.addEventListener('click', () => {
            const commentsSection = postCard.querySelector('.comments-section');
            const commentsList = postCard.querySelector('.comments-list');
            const postId = commentBtn.dataset.postId;

            if (commentsSection.style.display === 'none') {
                commentsSection.style.display = 'block';
                loadComments(postId, commentsList); // Cargar comentarios al abrir
            } else {
                commentsSection.style.display = 'none';
                commentsList.innerHTML = ''; // Limpiar comentarios al cerrar
            }
        });

        // Añadir evento para añadir comentario
        const addCommentBtn = postCard.querySelector('.add-comment-btn');
        addCommentBtn.addEventListener('click', async () => {
            const commentTextarea = postCard.querySelector('.comment-textarea');
            const commentContent = commentTextarea.value.trim();
            const postId = addCommentBtn.dataset.postId;

            if (commentContent) {
                try {
                    const response = await fetch('api/add_comment.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            publicacionId: postId,
                            commentContent: commentContent
                        })
                    });
                    const result = await response.json();

                    if (result.success) {
                        commentTextarea.value = ''; // Limpiar textarea
                        const commentsList = postCard.querySelector('.comments-list');
                        loadComments(postId, commentsList); // Recargar comentarios
                    } else {
                        alert('Error al añadir comentario: ' + result.message);
                    }
                } catch (error) {
                    console.error('Error al añadir comentario:', error);
                    alert('Error de conexión al añadir comentario.');
                }
            } else {
                alert('El comentario no puede estar vacío.');
            }
        });

        // Evento para el botón de Like
        const likeButton = postCard.querySelector('.like-btn');
        const likeCountSpan = likeButton.querySelector('.like-count');

        if (likeButton) {
            likeButton.addEventListener('click', async () => {
                const postId = likeButton.dataset.postId;
                const action = likeButton.classList.contains('liked') ? 'unlike' : 'like';

                try {
                    const response = await fetch('api/toggle_like.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ publicacionId: postId, action: action })
                    });
                    const result = await response.json();

                    if (result.success) {
                        likeCountSpan.textContent = result.newLikesCount;
                        if (action === 'like') {
                            likeButton.classList.add('liked');
                        } else {
                            likeButton.classList.remove('liked');
                        }
                    } else {
                        alert('Error al dar/quitar me gusta: ' + result.message);
                    }
                } catch (error) {
                    console.error('Error de conexión al dar/quitar me gusta:', error);
                    alert('Error de conexión al servidor.');
                }
            });
        }

        // --- Evento para el botón de Compartir ---
        const shareBtn = postCard.querySelector('.share-btn');
        if (shareBtn) {
            shareBtn.addEventListener('click', () => {
                currentPostId = shareBtn.dataset.postId;
                shareModal.style.display = 'flex'; // Mostrar el modal de compartir
            });
        }
        // --- FIN: Evento para el botón de Compartir ---

        return postCard;
    } // Cierre de createPostCard

    // Función para crear un elemento de comentario
    function createCommentItem(comment) {
        const commentItem = document.createElement('div');
        commentItem.classList.add('comment-item');
        commentItem.dataset.commentId = comment.id_com;

        const timeElapsed = comment.tiempo_transcurrido;

        let deleteButtonHtml = '';
        if (loggedInUserId && comment.id_usuario == loggedInUserId) {
            deleteButtonHtml = `<button class="delete-comment-btn" data-comment-id="${comment.id_com}">❌</button>`;
        }

        commentItem.innerHTML = `
            <div class="comment-header">
                <span class="comment-username">${comment.nombre_usuario}</span>
                <span class="comment-date">${timeElapsed}</span>
                ${deleteButtonHtml}
            </div>
            <div class="comment-body">
                <p>${comment.contenido_com}</p>
            </div>
        `;

        // Evento para el botón de eliminar comentario
        const deleteButton = commentItem.querySelector('.delete-comment-btn');
        if (deleteButton) {
            deleteButton.addEventListener('click', () => {
                const commentIdToDelete = deleteButton.dataset.commentId;
                deleteConfirmationDialog.dataset.commentIdToDelete = commentIdToDelete;
                deleteConfirmationDialog.style.display = 'flex';
            });
        }

        return commentItem;
    }

    // Función para cargar comentarios de una publicación
    async function loadComments(postId, commentsListElement) {
        try {
            const response = await fetch(`api/get_comments.php?publicacionId=${postId}`);
            const data = await response.json();

            if (data.success) {
                if (loggedInUserId === null && data.loggedInUserId !== undefined) {
                    loggedInUserId = data.loggedInUserId;
                }

                commentsListElement.innerHTML = '';
                if (data.comments.length > 0) {
                    data.comments.forEach(comment => {
                        commentsListElement.appendChild(createCommentItem(comment));
                    });
                } else {
                    commentsListElement.innerHTML = '<p>No hay comentarios aún.</p>';
                }
            } else {
                console.error('Error al cargar comentarios:', data.message);
                commentsListElement.innerHTML = '<p>Error al cargar comentarios.</p>';
            }
        } catch (error) {
            console.error('Error de conexión al cargar comentarios:', error);
            commentsListElement.innerHTML = '<p>Error de conexión al cargar comentarios.</p>';
        }
    }

    // --- NUEVO: Función para manejar el clic en el botón de Guardar ---
    async function handleSaveButtonClick(event) {
        const saveButton = event.currentTarget;
        const postId = parseInt(saveButton.dataset.postId, 10);
        const postContent = saveButton.dataset.postContent; // Obtener el contenido de la publicación

        if (isNaN(postId) || !postContent) {
            alert('Error: Datos de publicación incompletos para guardar.');
            return;
        }

        try {
            const response = await fetch('api/save_publication.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ publicacionId: postId, contenidoPublicacion: postContent })
            });
            const result = await response.json();

            if (result.success) {
                alert('Publicación guardada exitosamente.');
                // Opcional: Deshabilitar el botón o cambiar su texto/ícono
                // saveButton.textContent = '✅ Guardado';
                // saveButton.disabled = true;
            } else {
                alert('Error al guardar publicación: ' + result.message);
            }
        } catch (error) {
            console.error('Error al guardar publicación:', error);
            alert('Error de conexión al servidor al intentar guardar publicación.');
        }
    }

    // Función para cargar todas las publicaciones
    async function loadPosts() {
        try {
            const response = await fetch('api/get_community_posts.php');
            const data = await response.json();

            if (data.success) {
                if (loggedInUserId === null && data.loggedInUserId !== undefined) {
                    loggedInUserId = data.loggedInUserId;
                }

                postsFeed.innerHTML = '';
                if (data.posts.length > 0) {
                    data.posts.forEach(post => {
                        postsFeed.prepend(createPostCard(post));
                    });
                } else {
                    postsFeed.innerHTML = '<p>No hay publicaciones aún.</p>';
                }
            } else {
                console.error('Error al cargar publicaciones:', data.message);
                postsFeed.innerHTML = '<p>Inicia sesión para poder ver y hacer publicaciones.</p>';
            }
        } catch (error) {
            console.error('Error de conexión al cargar publicaciones:', error);
            postsFeed.innerHTML = '<p>Inicia sesión para poder ver y hacer publicaciones.</p>';
        }
    }

    // Evento para publicar un nuevo post
    publishPostButton.addEventListener('click', async () => {
        const postContent = postContentTextarea.value.trim();
        if (postContent) {
            try {
                const response = await fetch('api/create_post.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ postContent: postContent })
                });
                const result = await response.json();

                if (result.success) {
                    postContentTextarea.value = '';
                    loadPosts();
                } else {
                    alert('Error al publicar: ' + result.message);
                }
            } catch (error) {
                console.error('Error al publicar:', error);
                alert('Error de conexión al publicar.');
            }
        } else {
            alert('La publicación no puede estar vacía.');
        }
    });

    // Cargar publicaciones al iniciar la página
    loadPosts();

    // --- Lógica para el diálogo de confirmación de eliminación ---
    confirmDeleteButton.addEventListener('click', async () => {
        const commentId = deleteConfirmationDialog.dataset.commentIdToDelete;
        if (commentId) {
            try {
                const response = await fetch('api/delete_comment.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ commentId: commentId })
                });
                const result = await response.json();

                if (result.success) {
                    const commentToRemove = document.querySelector(`.comment-item[data-comment-id="${commentId}"]`);
                    if (commentToRemove) {
                        commentToRemove.remove();
                    }
                    closeDeleteDialog();
                    console.log('Comentario eliminado exitosamente.');
                } else {
                    alert('Error al eliminar comentario: ' + result.message);
                }
            } catch (error) {
                console.error('Error al eliminar comentario:', error);
                alert('Error de conexión al eliminar comentario.');
            }
        }
    });

    cancelDeleteButton.addEventListener('click', closeDeleteDialog);
    closeDialogButton.addEventListener('click', closeDeleteDialog);
    window.addEventListener('click', (event) => {
        if (event.target == deleteConfirmationDialog) {
            closeDeleteDialog();
        }
    });

    // --- Lógica para el modal de compartir ---

    // Lógica para el botón Guardar Publicación
    savePostButton.addEventListener('click', async () => {
        if (currentPostId) {
            const postCard = document.querySelector(`.post-card[data-post-id="${currentPostId}"]`);
            const saveButtonOnCard = postCard ? postCard.querySelector('.save-btn') : null;

            // Determinar la acción: 'save' si el botón en la tarjeta dice 'Guardar', 'unsave' si dice 'Guardado'
            // NOTA: Esto asume que el botón 'Guardar' existe en la tarjeta y lo estamos actualizando dinámicamente.
            // Si tu botón de guardar solo está en el modal, necesitarás una forma de saber el estado.
            // Por ahora, si no hay un botón específico en la tarjeta, asumiremos que siempre intentamos guardar
            // y el PHP manejará si ya está guardado.
            let action = 'save'; // Por defecto intentaremos guardar
            let initialText = '💾 Guardar';
            let savedText = '✅ Guardado';

            if (saveButtonOnCard && saveButtonOnCard.textContent.includes('Guardado')) {
                action = 'unsave';
            }


            try {
                const response = await fetch('api/toggle_save_post.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        publicacionId: currentPostId,
                        action: action
                    })
                });

                const result = await response.json();

                if (result.success) {
                    alert(result.message); // Muestra el mensaje de éxito del servidor
                    // Si la operación fue exitosa, actualiza el texto del botón en la tarjeta
                    if (saveButtonOnCard) {
                        if (action === 'save') {
                            saveButtonOnCard.textContent = savedText;
                            saveButtonOnCard.classList.add('saved'); // Opcional: añade una clase para estilos
                        } else {
                            saveButtonOnCard.textContent = initialText;
                            saveButtonOnCard.classList.remove('saved'); // Opcional: elimina la clase
                        }
                    }
                } else {
                    alert('Error al guardar/desguardar publicación: ' + result.message);
                }
            } catch (error) {
                console.error('Error de conexión al guardar/desguardar publicación:', error);
                alert('Error de conexión al servidor al intentar guardar/desguardar.');
            } finally {
                closeShareModal(); // Siempre cierra el modal después de intentar la operación
            }
        }
    });

    // Lógica para el botón Copiar Enlace
    copyLinkButton.addEventListener('click', () => {
        if (currentPostId) {
            // Genera el enlace usando el ID de la publicación
            // Asume que tu página para ver una publicación individual es publicacion.html?id=
            const postLink = `${window.location.origin}/publicacion.html?id=${currentPostId}`;
            navigator.clipboard.writeText(postLink).then(() => {
                alert('Enlace copiado al portapapeles.');
                closeShareModal();
            }).catch((err) => {
                console.error('Error al copiar el enlace: ', err);
                alert('No se pudo copiar el enlace.');
            });
        }
    });

    // Cerrar modal de compartir al hacer clic en la "X"
    closeShareModalButton.addEventListener('click', closeShareModal);

    // Cerrar modal de compartir al hacer clic fuera del contenido
    window.addEventListener('click', (event) => {
        if (event.target == shareModal) {
            closeShareModal();
        }
    });
    // --- FIN: Lógica para el modal de compartir ---

});