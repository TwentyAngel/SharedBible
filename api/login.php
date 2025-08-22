<?php
// Incluir el archivo de conexión a la base de datos
require_once '../server.php';

// Iniciar sesión para almacenar datos del usuario si el login es exitoso
session_start();

// Establecer la cabecera para devolver JSON
header('Content-Type: application/json');

// --- Depuración: Verificar el método de solicitud al inicio del script ---
error_log("DEBUG: login.php accedido. REQUEST_METHOD: " . $_SERVER["REQUEST_METHOD"]);

// Verificar si la solicitud es POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    error_log("DEBUG: Método POST detectado.");

    // Recibir y sanear los datos del formulario
    $username_email = trim(htmlspecialchars(stripslashes($_POST['username_email'] ?? '')));
    $password = $_POST['password'] ?? '';

    // --- Depuración: Verificar si $_POST está vacío o si los campos llegaron ---
    if (empty($_POST)) {
        error_log("DEBUG: \$ _POST está vacío. Esto podría ser un problema con body-parser en JS.");
        echo json_encode(['success' => false, 'message' => 'Datos de formulario vacíos.']);
        close_db_connection($mysqli);
        exit();
    }

    error_log("DEBUG: Campos recibidos: username_email='" . $username_email . "', password (length): " . strlen($password));

    // Validar campos vacíos
    if (empty($username_email) || empty($password)) {
        error_log("DEBUG: Validación fallida: Campos vacíos detectados.");
        echo json_encode(['success' => false, 'message' => 'Por favor, introduce tu correo electrónico y contraseña.']);
        close_db_connection($mysqli);
        exit();
    }
    error_log("DEBUG: Validación de campos vacíos pasada.");

    // Preparar la consulta para buscar al usuario por correo electrónico
    $stmt = $mysqli->prepare("SELECT id_usuario, nombre_usuario, correo_usuario, pass_usuario, id_rama FROM usuarios WHERE correo_usuario = ?");

    if ($stmt === false) {
        // --- Depuración: Error al preparar la consulta SQL ---
        error_log("ERROR: Error al preparar la consulta de login: " . $mysqli->error);
        echo json_encode(['success' => false, 'message' => 'Error interno del servidor al preparar la consulta.']);
        close_db_connection($mysqli);
        exit();
    }
    error_log("DEBUG: Consulta SQL preparada exitosamente.");


    $stmt->bind_param("s", $username_email);
    $stmt->execute();
    $result = $stmt->get_result();

    // --- Depuración: Número de filas encontradas ---
    error_log("DEBUG: Consulta ejecutada. Número de filas encontradas: " . $result->num_rows);

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // --- Depuración: Usuario encontrado ---
        error_log("DEBUG: Usuario encontrado: " . json_encode($user['correo_usuario'])); // No loguear la contraseña.

        // Verificar la contraseña hasheada
        if (password_verify($password, $user['pass_usuario'])) {
            // Contraseña correcta, iniciar sesión
            $_SESSION['user_id'] = $user['id_usuario'];
            $_SESSION['user_name'] = $user['nombre_usuario'];
            $_SESSION['user_email'] = $user['correo_usuario'];
            $_SESSION['id_rama'] = $user['id_rama'];
            $_SESSION['logged_in'] = true; // Flag para saber si el usuario está logueado

            error_log("DEBUG: Login exitoso para el usuario: " . $user['correo_usuario']);
            echo json_encode(['success' => true, 'message' => 'Inicio de sesión exitoso.', 'redirect' => 'cuenta.html']);
        } else {
            // Contraseña incorrecta
            error_log("DEBUG: Fallo de verificación de contraseña para el usuario: " . $username_email);
            echo json_encode(['success' => false, 'message' => 'Correo electrónico o contraseña incorrectos.']);
        }
    } else {
        // Usuario no encontrado
        error_log("DEBUG: Usuario no encontrado con email: " . $username_email);
        echo json_encode(['success' => false, 'message' => 'Correo electrónico o contraseña incorrectos.']);
    }

    $stmt->close();

} else {
    // Si la solicitud no es POST (lo cual dices que no es el caso, pero por seguridad)
    error_log("DEBUG: Error inesperado: La solicitud NO es POST. Método recibido: " . $_SERVER["REQUEST_METHOD"]);
    echo json_encode(['success' => false, 'message' => 'Método de solicitud no permitido.']);
}
close_db_connection($mysqli);
exit(); // Asegurarse de que el script termine aquí
?>