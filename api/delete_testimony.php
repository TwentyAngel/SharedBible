<?php
// error_reporting(0); // Para producción
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../server.php'; // Ajusta la ruta si es necesario
session_start();
header('Content-Type: application/json');

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado.']);
    close_db_connection($mysqli);
    exit();
}

// Verificar que la solicitud sea POST y que el ID del testimonio se haya enviado
if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST['testimony_id'])) {
    echo json_encode(['success' => false, 'message' => 'Método de solicitud no válido o ID de testimonio no proporcionado.']);
    close_db_connection($mysqli);
    exit();
}

$user_id = $_SESSION['user_id'];
$testimony_id = (int)$_POST['testimony_id']; // Asegurar que es un entero

try {
    // IMPORTANTE: Asegurarse de que el testimonio pertenece al usuario logueado antes de eliminar
    $stmt = $mysqli->prepare("DELETE FROM testimonios WHERE id_testimonio = ? AND id_usuario = ?");

    if ($stmt === false) {
        throw new Exception("Error al preparar la consulta de eliminación: " . $mysqli->error);
    }

    $stmt->bind_param("ii", $testimony_id, $user_id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Testimonio eliminado exitosamente.']);
        } else {
            // Esto puede ocurrir si el testimonio no existe o no pertenece al usuario
            echo json_encode(['success' => false, 'message' => 'No se pudo eliminar el testimonio. (Puede que no exista o no sea tuyo)']);
        }
    } else {
        throw new Exception("Error al ejecutar la eliminación del testimonio: " . $stmt->error);
    }

    $stmt->close();

} catch (Exception $e) {
    error_log("Error en delete_testimony.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor al eliminar el testimonio.']);
} finally {
    close_db_connection($mysqli);
}
?>