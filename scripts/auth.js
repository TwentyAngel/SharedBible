document.addEventListener('DOMContentLoaded', () => {
    // --- Lógica para el formulario de Registro ---
    const regForm = document.querySelector('.registration-form');
    if (regForm) {
        regForm.addEventListener('submit', async (event) => {
            event.preventDefault(); // Evitar el envío predeterminado del formulario

            const formData = new FormData(regForm);
            const responseDiv = document.createElement('div');
            responseDiv.className = 'form-message'; // Clase para estilo

            try {
                const response = await fetch('api/register.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                // Eliminar cualquier mensaje anterior
                const oldResponseDiv = regForm.querySelector('.form-message');
                if (oldResponseDiv) {
                    oldResponseDiv.remove();
                }

                responseDiv.textContent = result.message;
                if (result.success) {
                    responseDiv.style.color = 'green';
                    regForm.reset(); // Limpiar el formulario si el registro fue exitoso
                    // Opcional: Redirigir al usuario al login después de un breve retraso
                    setTimeout(() => {
                        window.location.href = 'login.html';
                    }, 2000); // Redirigir después de 2 segundos
                } else {
                    responseDiv.style.color = 'red';
                }
                regForm.prepend(responseDiv); // Mostrar mensaje al inicio del formulario

            } catch (error) {
                console.error('Error en el registro:', error);
                const oldResponseDiv = regForm.querySelector('.form-message');
                if (oldResponseDiv) {
                    oldResponseDiv.remove();
                }
                responseDiv.textContent = 'Error al conectar con el servidor. Inténtalo de nuevo.';
                responseDiv.style.color = 'red';
                regForm.prepend(responseDiv);
            }
        });
    }

    // --- Lógica para el formulario de Inicio de Sesión ---
    const loginForm = document.querySelector('.login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', async (event) => {
            event.preventDefault(); // Evitar el envío predeterminado del formulario

            const formData = new FormData(loginForm);
            const responseDiv = document.createElement('div');
            responseDiv.className = 'form-message'; // Clase para estilo

            try {
                const response = await fetch('api/login.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                // Eliminar cualquier mensaje anterior
                const oldResponseDiv = loginForm.querySelector('.form-message');
                if (oldResponseDiv) {
                    oldResponseDiv.remove();
                }

                responseDiv.textContent = result.message;
                if (result.success) {
                    responseDiv.style.color = 'green';
                    // Redirigir al usuario a la página de destino si el login fue exitoso
                    if (result.redirect) {
                        window.location.href = result.redirect;
                    }
                } else {
                    responseDiv.style.color = 'red';
                }
                loginForm.prepend(responseDiv); // Mostrar mensaje al inicio del formulario

            } catch (error) {
                console.error('Error en el inicio de sesión:', error);
                const oldResponseDiv = loginForm.querySelector('.form-message');
                if (oldResponseDiv) {
                    oldResponseDiv.remove();
                }
                responseDiv.textContent = 'Error al conectar con el servidor. Inténtalo de nuevo.';
                responseDiv.style.color = 'red';
                loginForm.prepend(responseDiv);
            }
        });
    }
});