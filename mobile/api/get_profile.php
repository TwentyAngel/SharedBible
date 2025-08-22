<?php
require_once '../server.php'; // Ajusta la ruta si es necesario
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado.']);
    close_db_connection($mysqli);
    exit();
}

$user_id = $_SESSION['user_id'];

// Obtener datos del usuario y el nombre de su rama doctrinal
$stmt = $mysqli->prepare("SELECT u.nombre_usuario, u.correo_usuario, r.rama AS nombre_rama FROM usuarios u JOIN ramas r ON u.id_rama = r.id_rama WHERE u.id_usuario = ?");

if ($stmt === false) {
    error_log("Error al preparar la consulta de perfil: " . $mysqli->error);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor.']);
    close_db_connection($mysqli);
    exit();
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    // CAMBIO AQUI: Incluye user_id directamente
    echo json_encode(['success' => true, 'user_id' => $user_id, 'user' => $user]);
} else {
    session_unset(); // Limpiar sesión por seguridad
    session_destroy();
    echo json_encode(['success' => false, 'message' => 'No se encontró el perfil del usuario.']);
}

$stmt->close();
close_db_connection($mysqli);
?>