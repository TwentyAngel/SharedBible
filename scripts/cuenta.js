document.addEventListener('DOMContentLoaded', async () => {
    // --- ELEMENTOS HTML ---
    const userNameDisplay = document.getElementById('userNameDisplay');
    const userBranchDisplay = document.getElementById('userBranchDisplay');
    const userInitialsDisplay = document.getElementById('userInitialsDisplay');
    const logoutBtn = document.querySelector('.logout-btn');
    const personalInfoCard = document.getElementById('personalInfoCard'); // Tarjeta para abrir el diálogo de info personal
    const personalInfoDialog = document.getElementById('personalInfoDialog');
    const dialogCloseBtns = personalInfoDialog.querySelectorAll('.dialog-close-btn'); // Botones de cerrar del diálogo de info personal
    const updateProfileForm = document.getElementById('updateProfileForm');
    const updateNameInput = document.getElementById('updateName');
    const updateEmailInput = document.getElementById('updateEmail');
    const updatePasswordInput = document.getElementById('updatePassword');
    const updateConfirmPasswordInput = document.getElementById('updateConfirmPassword');
    const updateBranchSelect = document.getElementById('updateBranch');
    const profileMessage = document.getElementById('profileMessage');

    // Elementos para la gestión de testimonios
    const openTestimoniesDialogBtn = document.getElementById('openTestimoniesDialog'); // El enlace que abre el diálogo de testimonios
    const manageTestimoniesDialog = document.getElementById('manageTestimoniesDialog');
    const myTestimoniesList = document.getElementById('myTestimoniesList'); // El contenedor dentro del diálogo
    const manageDialogCloseBtn = manageTestimoniesDialog.querySelector('.dialog-close-btn'); // Botón de cerrar del diálogo de testimonios

    // Elementos para la gestión de publicaciones guardadas
    const openSavedPublicationsDialogBtn = document.getElementById('openSavedPublicationsDialog');
    const savedPublicationsDialog = document.getElementById('savedPublicationsDialog');
    const mySavedPublicationsList = document.getElementById('mySavedPublicationsList');
    const savedDialogCloseBtn = savedPublicationsDialog.querySelector('.dialog-close-btn');

    // NUEVOS Elementos para la gestión de MIS Publicaciones
    const openMyPublicationsDialogBtn = document.getElementById('openMyPublicationsDialog');
    const myPublicationsDialog = document.getElementById('myPublicationsDialog');
    const userPostsList = document.getElementById('userPostsList');
    const myPublicationsDialogCloseBtn = myPublicationsDialog.querySelector('.dialog-close-btn');


    let currentUserData = null; // Para almacenar los datos del usuario después de la carga inicial

    // --- FUNCIONES ASÍNCRONAS ---

    // Función para obtener y mostrar los datos del perfil
    async function fetchUserProfile() {
        try {
            const response = await fetch('api/get_profile.php');
            if (!response.ok) {
                if (response.status === 401) { // No autenticado
                    window.location.href = 'login.html'; // Redirigir al login
                    return null;
                }
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const result = await response.json();

            if (result.success) {
                currentUserData = result.user; // Almacenar datos del usuario
                userNameDisplay.textContent = currentUserData.nombre_usuario;
                userBranchDisplay.textContent = currentUserData.nombre_rama;
                userInitialsDisplay.textContent = currentUserData.nombre_usuario.charAt(0).toUpperCase();

                // Llenar el formulario de edición de perfil
                updateNameInput.value = currentUserData.nombre_usuario;
                updateEmailInput.value = currentUserData.correo_usuario;

                return result.user; // Devuelve los datos del usuario para el Promise.all
            } else {
                console.error('Error al obtener perfil:', result.message);
                // Si el perfil falla, redirigir al login
                window.location.href = 'login.html';
                return null;
            }
        } catch (error) {
            console.error('Error al conectar con la API de perfil:', error);
            window.location.href = 'login.html'; // Redirigir al login en caso de error de conexión
            return null;
        }
    }

    // Función para obtener las ramas doctrinales
    async function fetchBranches() {
        try {
            const response = await fetch('api/get_branches.php');
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const result = await response.json();
            if (result.success) {
                updateBranchSelect.innerHTML = '<option value="" disabled selected hidden>Selecciona tu rama...</option>'; // Limpiar opciones existentes
                result.branches.forEach(branch => {
                    const option = document.createElement('option');
                    option.value = branch.id_rama;
                    option.textContent = branch.rama;
                    updateBranchSelect.appendChild(option);
                });
                return result.branches; // Devuelve las ramas para el Promise.all
            } else {
                console.error('Error al obtener ramas:', result.message);
                return null;
            }
        } catch (error) {
            console.error('Error al conectar con la API de ramas:', error);
            return null;
        }
    }

    // Función para obtener los testimonios del usuario logueado
    async function fetchUserTestimonies() {
        if (!myTestimoniesList) { // Asegurarse de que el elemento existe
            console.error("Elemento 'myTestimoniesList' no encontrado.");
            return;
        }
        myTestimoniesList.innerHTML = '<p>Cargando tus testimonios...</p>'; // Mensaje de carga
        try {
            const response = await fetch('api/get_user_testimonios.php');
            const result = await response.json();

            if (result.success && result.testimonies) {
                renderUserTestimonies(result.testimonies);
            } else {
                myTestimoniesList.innerHTML = `<p>${result.message || 'Error al cargar tus testimonios.'}</p>`;
                console.error('Error al cargar testimonios del usuario:', result.message);
            }
        } catch (error) {
            myTestimoniesList.innerHTML = '<p>Error de conexión al cargar tus testimonios.</p>';
            console.error('Error al conectar con la API de testimonios del usuario:', error);
        }
    }

    // Función para renderizar los testimonios del usuario
    function renderUserTestimonies(testimonies) {
        if (!myTestimoniesList) return; // Asegurarse de que el elemento existe
        myTestimoniesList.innerHTML = ''; // Limpiar la lista antes de añadir

        if (testimonies.length === 0) {
            myTestimoniesList.innerHTML = '<p>Aún no has compartido ningún testimonio.</p>';
            return;
        }

        testimonies.forEach(testimony => {
            const testimonyCard = document.createElement('div');
            testimonyCard.className = 'testimony-card'; // Reutilizar la clase si te gusta el estilo
            testimonyCard.setAttribute('data-id', testimony.id_testimonio); // Para el botón de eliminar

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
                    <span class="testimony-date">${testimony.fecha_formateada || 'Fecha desconocida'}</span>
                </div>
                <div class="testimony-body">
                    <p>"${testimony.contenido_testimonio}"</p>
                    ${videoHtml}
                    <button class="delete-testimony-btn" data-id="${testimony.id_testimonio}">Eliminar</button>
                </div>
            `;
            myTestimoniesList.appendChild(testimonyCard); // Añadir al final
        });
    }

    // Función para eliminar un testimonio
    async function deleteTestimony(testimonyId) {
        if (!confirm('¿Estás seguro de que quieres eliminar este testimonio? Esta acción no se puede deshacer.')) {
            return; // Si el usuario cancela, no hacer nada
        }

        try {
            const formData = new FormData();
            formData.append('testimony_id', testimonyId);

            const response = await fetch('api/delete_testimony.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                alert(result.message); // O usa tu propio sistema de mensajes
                fetchUserTestimonies(); // Recargar la lista de testimonios después de la eliminación
            } else {
                alert(result.message);
                console.error('Error al eliminar testimonio:', result.message);
            }
        } catch (error) {
            alert('Error de conexión al intentar eliminar el testimonio.');
            console.error('Error de conexión:', error);
        }
    }

    // Función para obtener las publicaciones guardadas del usuario logueado
    async function fetchSavedPublications() {
        if (!mySavedPublicationsList) {
            console.error("Elemento 'mySavedPublicationsList' no encontrado.");
            return;
        }
        mySavedPublicationsList.innerHTML = '<p>Cargando tus publicaciones guardadas...</p>';

        try {
            const response = await fetch('api/get_saved_publications.php');
            if (!response.ok) {
                 if (response.status === 401) {
                    window.location.href = 'login.html'; // Redirigir si no está autenticado
                    return;
                }
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const result = await response.json();

            if (result.success && result.publications) {
                renderSavedPublications(result.publications);
            } else {
                mySavedPublicationsList.innerHTML = `<p>${result.message || 'Error al cargar tus publicaciones guardadas.'}</p>`;
                console.error('Error al cargar publicaciones guardadas:', result.message);
            }
        } catch (error) {
            mySavedPublicationsList.innerHTML = '<p>Error de conexión al cargar tus publicaciones guardadas.</p>';
            console.error('Error al conectar con la API de publicaciones guardadas:', error);
        }
    }

    // Función para renderizar las publicaciones guardadas
    function renderSavedPublications(publications) {
        if (!mySavedPublicationsList) return;
        mySavedPublicationsList.innerHTML = ''; // Limpiar la lista antes de añadir

        if (publications.length === 0) {
            mySavedPublicationsList.innerHTML = '<p>Aún no has guardado ninguna publicación.</p>';
            return;
        }

        publications.forEach(post => {
            const postCard = document.createElement('div');
            postCard.className = 'post-card'; // O la clase que uses para mostrar publicaciones en tu feed
            postCard.innerHTML = `
                <div class="post-header">
                    <span class="post-username"><strong>${post.nombre_usuario}</strong></span>
                    <span class="post-date">${post.fecha_formateada || 'Fecha desconocida'}</span>
                </div>
                <div class="post-body">
                    <p class="post-content">${post.contenido_publicacion.substring(0, 150)}...</p>
                    <a href="publicacion.html?id=${post.id_publicacion}" class="read-more-btn">Leer más</a>
                </div>
                <div class="post-footer">
                    <button class="unsave-post-btn" data-id="${post.id_publicacion}">Desguardar</button>
                </div>
            `;
            mySavedPublicationsList.appendChild(postCard);
        });

        // Event Listener para los botones de "Desguardar"
        mySavedPublicationsList.querySelectorAll('.unsave-post-btn').forEach(button => {
            button.addEventListener('click', () => {
                const postIdToUnsave = button.dataset.id;
                if (postIdToUnsave) {
                    unsavePublication(postIdToUnsave);
                }
            });
        });
    }

    // Función para "desguardar" una publicación
    async function unsavePublication(postId) {
        if (!confirm('¿Estás seguro de que quieres eliminar esta publicación de tus guardados?')) {
            return;
        }

        try {
            // Usamos el endpoint toggle_save_post.php que ya tienes, pero con la acción 'unsave'
            const response = await fetch('api/toggle_save_post.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ publicacionId: postId, action: 'unsave' })
            });

            const result = await response.json();

            if (result.success) {
                alert(result.message);
                fetchSavedPublications(); // Recargar la lista
            } else {
                alert(result.message);
                console.error('Error al desguardar publicación:', result.message);
            }
        } catch (error) {
            alert('Error de conexión al intentar desguardar la publicación.');
            console.error('Error de conexión:', error);
        }
    }

    // NUEVO: Función para obtener las publicaciones del usuario logueado
    async function fetchUserPublications() {
        if (!userPostsList) {
            console.error("Elemento 'userPostsList' no encontrado.");
            return;
        }
        userPostsList.innerHTML = '<p>Cargando tus publicaciones...</p>';

        try {
            // Asumiendo que crearás un endpoint para esto, por ejemplo: api/get_user_posts.php
            const response = await fetch('api/get_user_posts.php');
            if (!response.ok) {
                 if (response.status === 401) {
                    window.location.href = 'login.html'; // Redirigir si no está autenticado
                    return;
                }
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const result = await response.json();

            if (result.success && result.publications) {
                renderUserPublications(result.publications);
            } else {
                userPostsList.innerHTML = `<p>${result.message || 'Error al cargar tus publicaciones.'}</p>`;
                console.error('Error al cargar publicaciones del usuario:', result.message);
            }
        } catch (error) {
            userPostsList.innerHTML = '<p>Error de conexión al cargar tus publicaciones.</p>';
            console.error('Error al conectar con la API de publicaciones del usuario:', error);
        }
    }

    // NUEVO: Función para renderizar las publicaciones del usuario
    function renderUserPublications(publications) {
        if (!userPostsList) return;
        userPostsList.innerHTML = ''; // Limpiar la lista antes de añadir

        if (publications.length === 0) {
            userPostsList.innerHTML = '<p>Aún no has creado ninguna publicación.</p>';
            return;
        }

        publications.forEach(post => {
            const postCard = document.createElement('div');
            postCard.className = 'post-card'; // Reutiliza la clase de estilo de post
            postCard.setAttribute('data-id', post.id_publicacion); // Para el botón de eliminar

            const postDate = new Date(post.fecha_publicacion);
            const timeElapsed = getTimeElapsed(postDate); // Reutiliza esta función

            postCard.innerHTML = `
                <div class="post-header">
                    <span class="post-username"><strong>${post.nombre_usuario}</strong></span>
                    <span class="post-date">${timeElapsed}</span>
                </div>
                <div class="post-body">
                    <p class="post-content">${post.contenido_publicacion}</p>
                </div>
                <div class="post-footer">
                    <button class="delete-my-post-btn" data-id="${post.id_publicacion}">Eliminar</button>
                </div>
            `;
            userPostsList.appendChild(postCard);
        });

        // Event Listener para los botones de "Eliminar" en las publicaciones del usuario
        userPostsList.querySelectorAll('.delete-my-post-btn').forEach(button => {
            button.addEventListener('click', () => {
                const postIdToDelete = button.dataset.id;
                if (postIdToDelete) {
                    deleteUserPublication(postIdToDelete); // Debes implementar esta función
                }
            });
        });
    }

    // NUEVO: Función para eliminar una publicación del usuario
    async function deleteUserPublication(postId) {
        if (!confirm('¿Estás seguro de que quieres eliminar esta publicación? Esta acción no se puede deshacer.')) {
            return;
        }

        try {
            const response = await fetch('api/delete_post.php', { // Reutiliza tu script delete_post.php
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ postId: postId })
            });

            const result = await response.json();

            if (result.success) {
                alert(result.message);
                fetchUserPublications(); // Recargar la lista de publicaciones del usuario
            } else {
                alert(result.message);
                console.error('Error al eliminar publicación:', result.message);
            }
        } catch (error) {
            alert('Error de conexión al intentar eliminar la publicación.');
            console.error('Error de conexión:', error);
        }
    }


    // Función para extraer el ID del video de YouTube
    function getYouTubeVideoId(url) {
        let videoId = '';
        const regExp = /(?:https?:\/\/)?(?:www\.)?(?:m\.)?(?:youtube\.com|youtu\.be)\/(?:watch\?v=|embed\/|v\/|)([\w-]{11})(?:\S+)?/;
        const match = url.match(regExp);
        if (match && match[1] && match[1].length === 11) {
            videoId = match[1];
        }
        return videoId;
    }

    // Función para calcular el tiempo transcurrido (reutilizada de comunidad.js)
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


    // --- EVENT LISTENERS ---

    // Event Listener para los botones de eliminar (delegación de eventos) dentro de myTestimoniesList
    if (myTestimoniesList) {
        myTestimoniesList.addEventListener('click', (event) => {
            if (event.target.classList.contains('delete-testimony-btn')) {
                const testimonyId = event.target.dataset.id;
                if (testimonyId) {
                    deleteTestimony(testimonyId);
                }
            }
        });
    }

    if (logoutBtn) {
        logoutBtn.addEventListener('click', async () => {
            try {
                const response = await fetch('api/logout.php'); // Asume que tienes un logout.php
                const result = await response.json();
                if (result.success) {
                    window.location.href = 'login.html';
                } else {
                    console.error('Error al cerrar sesión:', result.message);
                    alert('No se pudo cerrar sesión. Inténtalo de nuevo.');
                }
            } catch (error) {
                console.error('Error de red al cerrar sesión:', error);
                alert('Error de conexión al intentar cerrar sesión.');
            }
        });
    }

    // Abre el diálogo de información personal cuando se hace clic en la tarjeta
    if (personalInfoCard) {
        personalInfoCard.addEventListener('click', (event) => {
            event.preventDefault(); // Evita que el enlace # se active
            personalInfoDialog.showModal();
        });
    }

    // Cierra el diálogo de información personal
    dialogCloseBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            personalInfoDialog.close();
            profileMessage.textContent = ''; // Limpiar mensaje de perfil
        });
    });

    if (updateProfileForm) {
        updateProfileForm.addEventListener('submit', async (event) => {
            event.preventDefault();

            const name = updateNameInput.value;
            const email = updateEmailInput.value;
            const password = updatePasswordInput.value;
            const confirmPassword = updateConfirmPasswordInput.value;
            const doctrinal_branch = updateBranchSelect.value;

            if (password !== confirmPassword) {
                profileMessage.textContent = 'Las contraseñas no coinciden.';
                profileMessage.style.color = 'red';
                return;
            }

            const formData = new FormData();
            formData.append('name', name);
            formData.append('email', email);
            if (password) { // Solo añadir si la contraseña no está vacía
                formData.append('password', password);
            }
            formData.append('doctrinal_branch', doctrinal_branch);

            try {
                const response = await fetch('api/update_profile.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    profileMessage.textContent = result.message;
                    profileMessage.style.color = 'green';
                    await fetchUserProfile(); // Recargar los datos para que se vean reflejados
                    // Si la contraseña fue cambiada, no la mantenemos en los campos
                    updatePasswordInput.value = '';
                    updateConfirmPasswordInput.value = '';

                } else {
                    profileMessage.textContent = result.message;
                    profileMessage.style.color = 'red';
                }
            } catch (error) {
                console.error('Error al actualizar el perfil:', error);
                profileMessage.textContent = 'Error de conexión al servidor al actualizar.';
                profileMessage.style.color = 'red';
            }
        });
    }

    // Event Listeners para el diálogo de testimonios
    if (openTestimoniesDialogBtn) {
        openTestimoniesDialogBtn.addEventListener('click', (event) => {
            event.preventDefault(); // Evita que el enlace # se active
            manageTestimoniesDialog.showModal();
            fetchUserTestimonies(); // Cargar los testimonios cada vez que se abre el diálogo
        });
    }

    if (manageDialogCloseBtn) {
        manageDialogCloseBtn.addEventListener('click', () => {
            manageTestimoniesDialog.close();
        });
    }

    // Event Listeners para el diálogo de publicaciones guardadas
    if (openSavedPublicationsDialogBtn) {
        openSavedPublicationsDialogBtn.addEventListener('click', (event) => {
            event.preventDefault();
            savedPublicationsDialog.showModal();
            fetchSavedPublications(); // Cargar las publicaciones cada vez que se abre el diálogo
        });
    }

    if (savedDialogCloseBtn) {
        savedDialogCloseBtn.addEventListener('click', () => {
            savedPublicationsDialog.close();
        });
    }

    // NUEVOS EVENT LISTENERS para el diálogo de MIS Publicaciones
    if (openMyPublicationsDialogBtn) {
        openMyPublicationsDialogBtn.addEventListener('click', (event) => {
            event.preventDefault();
            myPublicationsDialog.showModal();
            fetchUserPublications(); // Cargar las publicaciones del usuario cada vez que se abre el diálogo
        });
    }

    if (myPublicationsDialogCloseBtn) {
        myPublicationsDialogCloseBtn.addEventListener('click', () => {
            myPublicationsDialog.close();
        });
    }


    // Cargar los datos del perfil y las ramas al cargar la página
    await Promise.all([
        fetchUserProfile(),
        fetchBranches()
    ]);

    // Seleccionar la rama doctrinal correcta después de que ambos se hayan cargado
    if (updateBranchSelect && currentUserData && currentUserData.id_rama) {
        updateBranchSelect.value = currentUserData.id_rama;
    }
});