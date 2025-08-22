<?php

// =============================================================================
// server.php
// Script de conexión a la base de datos MySQL usando la extensión MySQLi.
// Configurado para XAMPP por defecto.
// Este archivo debe ser incluido por otros scripts PHP que necesiten interactuar
// con la base de datos (especialmente los de la carpeta 'api/').
// =============================================================================

// -----------------------------------------------------------------------------
// Configuración de la base de datos
// ¡IMPORTANTE! Reemplaza 'nombre_de_tu_base_de_datos' con el nombre real de tu DB.
// Las otras credenciales son las por defecto de XAMPP.
// -----------------------------------------------------------------------------
define('DB_SERVER', 'localhost');             // Para XAMPP, el servidor es casi siempre 'localhost'
define('DB_USERNAME', 'root');                // Para XAMPP, el usuario por defecto es 'root'
define('DB_PASSWORD', '');                    // Para XAMPP, la contraseña por defecto es una cadena vacía ''
define('DB_NAME', 'sharedbible_db');              // <<--- ¡CAMBIA ESTO! Nombre de tu base de datos (ej. 'cerimex_db')

// -----------------------------------------------------------------------------
// Intentar establecer la conexión a la base de datos
// El objeto $mysqli estará disponible globalmente después de incluir este archivo.
// -----------------------------------------------------------------------------
$mysqli = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// -----------------------------------------------------------------------------
// Verificar si la conexión fue exitosa
// Si hay un error, el script termina y muestra un mensaje.
// -----------------------------------------------------------------------------
if ($mysqli->connect_error) {
    // Registra el error internamente (útil para depuración y producción)
    // Es buena práctica usar error_log() en lugar de echo/die para errores críticos.
    error_log("Error de conexión a la base de datos: " . $mysqli->connect_error);
    // En un entorno de producción, nunca expongas detalles del error al usuario final.
    die("Lo sentimos, no podemos conectar con la base de datos en este momento. Por favor, inténtalo de nuevo más tarde.");
}

// -----------------------------------------------------------------------------
// Opcional: Establecer el conjunto de caracteres a utf8mb4.
// Esto es CRUCIAL para manejar correctamente caracteres especiales, acentos, y emojis.
// Si fallara, se registra el error pero no se detiene el script (a menos que quieras).
// -----------------------------------------------------------------------------
if (!$mysqli->set_charset("utf8mb4")) {
    error_log("Error al establecer el conjunto de caracteres utf8mb4: " . $mysqli->error);
    // Opcional: Podrías considerar detener el script aquí también si la codificación es crítica.
}

/**
 * Función de utilidad para cerrar la conexión a la base de datos.
 * Es una buena práctica llamar a esta función al final de cualquier script
 * que utilice la conexión para liberar recursos.
 *
 * @param mysqli $conn La instancia de la conexión MySQLi.
 */
function close_db_connection(mysqli $conn) {
    $conn->close();
}

// =============================================================================
// FIN de server.php
// Cómo usar en otros scripts PHP (ej. en 'api/add_to_cart.php'):
// require_once '../server.php'; // Ajusta la ruta si 'server.php' no está en la raíz
//
// Luego, puedes usar el objeto $mysqli para realizar consultas.
// No olvides cerrar la conexión al final: close_db_connection($mysqli);
// =============================================================================
?>