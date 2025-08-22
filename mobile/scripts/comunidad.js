document.addEventListener('DOMContentLoaded', () => {
    const postContentTextarea = document.getElementById('postContent');
    const publishPostButton = document.getElementById('publishPost');
    const postsFeed = document.querySelector('.posts-feed');

    let loggedInUserId = null; // Variable para almacenar el ID del usuario logueado

    // --- Referencias a los elementos del diálogo de confirmación de eliminación ---
    const deleteConfirmationDialog = document.getElementById('deleteConfirmationDialog');
    const confirmDeleteButton = document.getElementById('confirmDelete');
    const cancelDeleteButton = document.getElementById('cancelDelete');

    // --- Referencias a los elementos del modal de compartir ---
    const shareModal = document.getElementById('shareModal');
    const savePostButton = document.getElementById('savePostButton');
    const copyLinkButton = document.getElementById('copyLinkButton');

    let currentPostId = null; // Para almacenar el ID de la publicación a compartir

    // Función para cerrar el diálogo de eliminación
    function closeDeleteDialog() {
        deleteConfirmationDialog.style.display = 'none';
        deleteConfirmationDialog.dataset.commentIdToDelete = '';
    }

    // Función para cerrar el modal de compartir
    function closeShareModal() {
        shareModal.style.display = 'none';
        currentPostId = null;
    }

    // Función para formatear la fecha a "Hace X tiempo"
    function getTimeElapsed(dateString) {
        if (!dateString || typeof dateString !== 'string') {
            return 'Fecha desconocida';
        }

        // Se elimina el 'Z' o se convierte el espacio a T para evitar problemas con zonas horarias
        // También se añade control para formatos inesperados
        let safeDateStr = dateString.trim();
        if (safeDateStr.includes(' ')) {
            safeDateStr = safeDateStr.replace(' ', 'T');
        }

        const postDate = new Date(safeDateStr);
        if (isNaN(postDate)) {
            return 'Fecha inválida';
        }

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

    // Nueva función auxiliar para el texto de likes
    function getLikesText(count) {
        if (count === 0) {
            return '';
        } else if (count === 1) {
            return 'A otra persona le gustó esta publicación.';
        } else {
            return `A otras ${count} personas les gustó esta publicación.`;
        }
    }

    // Función para crear una tarjeta de publicación (post card)
    function createPostCard(post) {
        const postCard = document.createElement('div');
        postCard.classList.add('post-card');
        postCard.dataset.postId = post.id_publicacion;

        const timeElapsed = post.fecha_publicacion_formateada || post.tiempo_transcurrido || getTimeElapsed(new Date(post.fecha_publicacion));
        const isLiked = post.liked_by_user ? 'liked' : '';
        const likesText = getLikesText(post.likes_publicacion);

        postCard.innerHTML = `
            <div class="post-header">
                <span class="username"><strong>${post.nombre_usuario}</strong></span>
                <span class="post-date">${timeElapsed}</span>
            </div>
            <div class="post-body">
                <p>${post.contenido_publicacion}</p>
            </div>
            ${post.likes_publicacion > 0 ? `
            <div class="post-like-summary">
                <p class="like-text">${likesText}</p>
            </div>
            ` : ''}
            <div class="post-actions">
                <button class="action-btn like-btn ${isLiked}" data-post-id="${post.id_publicacion}">❤️ Like</button>
                <button class="action-btn comment-btn" data-post-id="${post.id_publicacion}">💬 Comentar</button>
                <button class="action-btn share-btn" data-post-id="${post.id_publicacion}">🔗 Compartir</button>
            </div>
            <div class="comments-section" style="display: none;">
                <div class="comments-list" data-post-id="${post.id_publicacion}">
                </div>
                <div class="comment-input-area">
                    <div class="comment-input-container">
                        <textarea class="comment-textarea" placeholder="Escribe un comentario..." rows="4"></textarea>
                        <button class="add-comment-btn" data-post-id="${post.id_publicacion}"><img src="img/send.png" width="22px" height="22px"></button>
                    </div>
                </div>
            </div>
        `;

        // Lógica para los botones de acción
        const likeButton = postCard.querySelector('.like-btn');
        let likeSummaryElement = postCard.querySelector('.post-like-summary');
        
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
                        if (result.newLikesCount > 0) {
                            if (!likeSummaryElement) {
                                // Si no existe el elemento, créalo
                                const postActions = postCard.querySelector('.post-actions');
                                likeSummaryElement = document.createElement('div');
                                likeSummaryElement.classList.add('post-like-summary');
                                postActions.parentNode.insertBefore(likeSummaryElement, postActions);
                            }
                            likeSummaryElement.innerHTML = `<p class="like-text">${getLikesText(result.newLikesCount)}</p>`;
                        } else if (likeSummaryElement) {
                            // Si el número de likes es 0, elimina el elemento
                            likeSummaryElement.remove();
                        }

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
        
        const commentBtn = postCard.querySelector('.comment-btn');
        commentBtn.addEventListener('click', () => {
            const commentsSection = postCard.querySelector('.comments-section');
            const commentsList = postCard.querySelector('.comments-list');
            const postId = commentBtn.dataset.postId;

            if (commentsSection.style.display === 'none') {
                commentsSection.style.display = 'block';
                loadComments(postId, commentsList);
            } else {
                commentsSection.style.display = 'none';
                commentsList.innerHTML = '';
            }
        });

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
                        commentTextarea.value = '';
                        const commentsList = postCard.querySelector('.comments-list');
                        loadComments(postId, commentsList);
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

        const shareBtn = postCard.querySelector('.share-btn');
        if (shareBtn) {
            shareBtn.addEventListener('click', () => {
                currentPostId = shareBtn.dataset.postId;
                shareModal.style.display = 'flex';
            });
        }

        return postCard;
    }

    function createCommentItem(comment) {
        const commentItem = document.createElement('div');
        commentItem.classList.add('comment-item');
        commentItem.dataset.commentId = comment.id_com;

        // Se usa la lógica de publicacion.js para mostrar el tiempo transcurrido
        const timeElapsed = comment.tiempo_transcurrido;

        let deleteButtonHtml = '';
        if (loggedInUserId && comment.id_usuario == loggedInUserId) {
            deleteButtonHtml = `<button class="delete-comment-btn" data-comment-id="${comment.id_com}"><img src="img/equis.png" width="22px" height="22px"></button>`;
        }

        commentItem.innerHTML = `
            <div class="comment-header">
                <span class="comment-username"><strong>${comment.nombre_usuario}</strong></span>
                <span class="post-date">${timeElapsed}</span>
                ${deleteButtonHtml}
            </div>
            <div class="comment-body">
                <p>${comment.contenido_com}</p>
            </div>
        `;

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
                postsFeed.innerHTML = '<p class="ini">Inicia sesión para poder ver y hacer publicaciones.</p>';
            }
        } catch (error) {
            console.error('Error de conexión al cargar publicaciones:', error);
            postsFeed.innerHTML = '<p class="ini">Inicia sesión para poder ver y hacer publicaciones.</p>';
            
            // Si el usuario no ha iniciado sesión, también se vacía el postContentTextarea
            postContentTextarea.value = '';
            publishPostButton.disabled = true; // Deshabilita el botón de publicar si no hay sesión
        }
    }

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

    loadPosts();

    // --- Lógica del diálogo de confirmación de eliminación ---
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
    
    // Cierra el diálogo de eliminación al hacer clic en el fondo
    window.addEventListener('click', (event) => {
        if (event.target == deleteConfirmationDialog) {
            closeDeleteDialog();
        }
    });

    // --- Lógica del modal de compartir (actualizada) ---

    // Lógica para el botón Guardar Publicación
    savePostButton.addEventListener('click', async () => {
        if (currentPostId) {
            try {
                const response = await fetch('api/toggle_save_post.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        publicacionId: currentPostId,
                        action: 'save' // Siempre envía 'save'. El PHP maneja la lógica de si ya existe.
                    })
                });

                const result = await response.json();

                if (result.success) {
                    // Vacía el innerHTML en caso de éxito, como lo solicitaste
                    savePostButton.parentElement.innerHTML = '';
                    alert(result.message);
                } else {
                    alert('Error al guardar publicación: ' + result.message);
                }
            } catch (error) {
                console.error('Error de conexión al guardar publicación:', error);
                alert('Error de conexión al servidor.');
            } finally {
                closeShareModal();
            }
        }
    });

    // Lógica para el botón Copiar Enlace
    copyLinkButton.addEventListener('click', () => {
        if (currentPostId) {
            const postLink = `${window.location.origin}/mobile/publicacion.html?id=${currentPostId}`;
            // Reemplazar navigator.clipboard.writeText por document.execCommand('copy') por compatibilidad
            const tempInput = document.createElement('input');
            tempInput.value = postLink;
            document.body.appendChild(tempInput);
            tempInput.select();
            document.execCommand('copy');
            document.body.removeChild(tempInput);
            
            const originalText = copyLinkButton.textContent;
            copyLinkButton.textContent = '¡Copiado!';
            
            setTimeout(() => {
                copyLinkButton.textContent = originalText;
                closeShareModal();
            }, 2000); 
        }
    });
    
    // Cierra el modal de compartir solo al hacer clic en el fondo
    window.addEventListener('click', (event) => {
        if (event.target == shareModal) {
            closeShareModal();
        }
    });
    // --- FIN: Lógica para el modal de compartir ---

    // Función para manejar el modal personalizado (simulando alert)
    // Se mantiene aquí como referencia en caso de que decidas volver a usarlo.
    function showCustomAlert(message) {
        const customAlert = document.createElement('div');
        customAlert.classList.add('custom-alert');
        customAlert.innerHTML = `
            <div class="custom-alert-content">
                <p>${message}</p>
                <button class="custom-alert-close">Aceptar</button>
            </div>
        `;
        document.body.appendChild(customAlert);
        customAlert.querySelector('.custom-alert-close').addEventListener('click', () => {
            customAlert.remove();
        });
        window.addEventListener('click', (event) => {
            if (event.target == customAlert) {
                customAlert.remove();
            }
        });
    }
});
