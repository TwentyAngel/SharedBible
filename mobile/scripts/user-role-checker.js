// scripts/user-role-checker.js

document.addEventListener('DOMContentLoaded', function() {
    const predicadorActionsDiv = document.getElementById('predicador-actions');
    const createSermonBtn = document.getElementById('create-sermon-btn');
    const sermonModal = document.getElementById('create-sermon-modal');
    const closeSermonModalBtn = document.getElementById('close-sermon-modal');
    const sermonForm = document.getElementById('sermon-form');
    // const sermonImageInput = document.getElementById('sermon-image-file'); // Este input se maneja en load-sermons.js

    // Función para verificar el rol del usuario
    function checkUserRole() {
        fetch('api/get_user_role.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error de red o del servidor al obtener el rol del usuario.');
                }
                return response.json();
            })
            .then(data => {
                if (data.status === 'success' && data.is_predicador) {
                    predicadorActionsDiv.style.display = 'block'; // Muestra el botón
                } else {
                    predicadorActionsDiv.style.display = 'none'; // Oculta el botón
                    console.log(data.message || 'El usuario no es predicador o no está autenticado.');
                }
            })
            .catch(error => {
                console.error('Error al verificar el rol del usuario:', error);
                // Opcional: Mostrar un mensaje al usuario en caso de error
            });
    }

    // Event listeners para el modal de creación de predicación
    if (createSermonBtn) {
        createSermonBtn.addEventListener('click', function() {
            sermonModal.showModal(); // Abre el modal
        });
    }

    if (closeSermonModalBtn) {
        closeSermonModalBtn.addEventListener('click', function() {
            sermonModal.close(); // Cierra el modal
            sermonForm.reset(); // Limpia el formulario al cerrar
        });
    }

    // La lógica de envío del formulario se ha MOVIDO COMPLETAMENTE a load-sermons.js
    // ¡Asegúrate de que no haya otro listener de 'submit' aquí!

    // Llamar a la función al cargar la página
    checkUserRole();
});