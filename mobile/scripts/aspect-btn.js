document.addEventListener('DOMContentLoaded', () => {
    const btn = document.getElementById('btn');
    const header = document.querySelector('header');
    const footer = document.querySelector('footer');

    // Función para aplicar el tema
    const applyTheme = (theme) => {
        document.body.classList.remove('light', 'dark');
        document.body.classList.add(theme);

        if (theme === 'dark') {
            btn.innerText = '☀️';
            btn.style.color = '#000';
            if (footer) {
                footer.classList.add('dark-mode');
            }
        } else { // theme === 'light'
            btn.innerText = '🌙';
            btn.style.color = '#fff';
            if (footer) {
                footer.classList.remove('dark-mode');
            }
        }
        // La barra de navegación (header) siempre tiene el mismo color oscuro
        if (header) {
            header.style.backgroundColor = '#2C3E50';
        }
        // Los enlaces de la navbar siempre tendrán el mismo color claro para contrastar con el header oscuro
        document.querySelectorAll('.navbar-nav .nav-link').forEach(link => {
            link.style.color = '#ECF0F1';
        });
        document.querySelector('.navbar-brand').style.color = '#FFFFFF';
    };

    // Al cargar la página, verificar la cookie de preferencia de tema
    const savedTheme = getCookie('themePreference');
    if (savedTheme) {
        applyTheme(savedTheme);
    } else {
        // Si no hay cookie, aplicar el tema 'light' por defecto y guardar la preferencia
        applyTheme('light');
        setCookie('themePreference', 'light', 365); // Guardar por un año
    }

    // Event listener para el botón de cambio de tema
    btn.addEventListener('click', () => {
        let currentTheme = document.body.classList.contains('dark') ? 'dark' : 'light';
        let newTheme = (currentTheme === 'dark') ? 'light' : 'dark';

        applyTheme(newTheme);
        setCookie('themePreference', newTheme, 365); // Guardar la nueva preferencia por un año
    });
});