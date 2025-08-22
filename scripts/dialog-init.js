document.addEventListener("DOMContentLoaded", () => {
    const dialog = document.getElementById("Bienvenido");
    const closeButton = document.getElementById("closeDialog");

    // Check if the dialog has been shown before using a cookie
    if (!getCookie("welcomeDialogShown")) {
        if (dialog && typeof dialog.showModal === "function") {
            dialog.showModal();

            // Set cookie when dialog is closed by the button
            closeButton.addEventListener("click", () => {
                setCookie("welcomeDialogShown", "true", 365); // Set cookie for 365 days
            });

            // Allow closing by clicking outside and set cookie
            dialog.addEventListener("click", (event) => {
                const rect = dialog.getBoundingClientRect();
                const isInDialog =
                    event.clientX >= rect.left &&
                    event.clientX <= rect.right &&
                    event.clientY >= rect.top &&
                    event.clientY <= rect.bottom;

                if (!isInDialog) {
                    dialog.close();
                    setCookie("welcomeDialogShown", "true", 365); // Set cookie for 365 days
                }
            });

            // Set cookie if dialog is closed using the escape key
            dialog.addEventListener("close", () => {
                setCookie("welcomeDialogShown", "true", 365); // Set cookie for 365 days
            });

        } else {
            console.error("El elemento <dialog> no es compatible o no se encontró.");
        }
    }
});

// These cookie functions will be in cookie-handler.js
// function setCookie(name, value, days) { ... }
// function getCookie(name) { ... }