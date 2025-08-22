<?php
// api/get_user_role.php
// Incluye el archivo de conexión a la base de datos
require_once '../server.php';

header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'No se pudo obtener el rol del usuario.', 'is_predicador' => false];

// Inicia la sesión si no está ya iniciada.
// login.php ya inicia la sesión y guarda 'user_id' y 'user_name'.
session_start();

if (isset($_SESSION['user_id'])) { // Usar 'user_id' según login.php
    $id_usuario = $_SESSION['user_id'];

    // Prepara la consulta SQL para evitar inyecciones SQL
    $stmt = $mysqli->prepare("SELECT predi_usuario FROM usuarios WHERE id_usuario = ?");

    if ($stmt) {
        $stmt->bind_param("i", $id_usuario); // "i" indica que es un entero
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user_data = $result->fetch_assoc();
            $is_predicador = ($user_data['predi_usuario'] === 'Y');
            $response = ['status' => 'success', 'message' => 'Rol de usuario obtenido.', 'is_predicador' => $is_predicador];
        } else {
            $response['message'] = 'Usuario no encontrado.';
        }
        $stmt->close();
    } else {
        $response['message'] = 'Error al preparar la consulta: ' . $mysqli->error;
    }
} else {
    $response['message'] = 'Usuario no autenticado. ID de usuario no encontrado en la sesión.';
}

echo json_encode($response);

// Cierra la conexión a la base de datos
close_db_connection($mysqli);
?>