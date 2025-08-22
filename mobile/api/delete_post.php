<?php
session_start();
require_once '../server.php'; // Asegúrate de que la ruta a server.php sea correcta

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

// 1. Verificar que el usuario esté autenticado
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Usuario no autenticado. Por favor, inicia sesión.';
    echo json_encode($response);
    close_db_connection($mysqli);
    exit();
}

$loggedInUserId = $_SESSION['user_id'];

// 2. Verificar que la solicitud sea POST y obtener el ID de la publicación
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $publicacionId = $data['postId'] ?? null;

    // 3. Validar el ID de la publicación
    if (is_null($publicacionId) || !is_numeric($publicacionId)) {
        $response['message'] = 'ID de publicación inválido.';
        echo json_encode($response);
        close_db_connection($mysqli);
        exit();
    }

    $publicacionId = (int)$publicacionId; // Asegurar que es un entero

    // Iniciar una transacción para asegurar la atomicidad de las operaciones
    $mysqli->begin_transaction();

    try {
        // 4. Verificar que el usuario sea el propietario de la publicación
        // También necesitamos el id_usuario en publicaciones_guardadas para eliminar entradas
        $stmt_check = $mysqli->prepare("SELECT id_usuario FROM publicaciones WHERE id_publicacion = ?");
        if (!$stmt_check) {
            throw new Exception("Error al preparar la verificación de propietario: " . $mysqli->error);
        }
        $stmt_check->bind_param("i", $publicacionId);
        $stmt_check->execute();
        $stmt_check->bind_result($ownerUserId);
        $stmt_check->fetch();
        $stmt_check->close();

        if ($ownerUserId !== $loggedInUserId) {
            throw new Exception('No tienes permiso para eliminar esta publicación.');
        }

        // 5. Eliminar primero las entradas relacionadas en `publicaciones_guardadas`
        // Esto es crucial para evitar errores de clave foránea
        $stmt_delete_saved = $mysqli->prepare("DELETE FROM publicaciones_guardadas WHERE id_publicacion = ?");
        if (!$stmt_delete_saved) {
            throw new Exception("Error al preparar la eliminación de publicaciones guardadas: " . $mysqli->error);
        }
        $stmt_delete_saved->bind_param("i", $publicacionId);
        if (!$stmt_delete_saved->execute()) {
            throw new Exception("Error al eliminar publicaciones guardadas: " . $stmt_delete_saved->error);
        }
        $stmt_delete_saved->close();

        // 6. Eliminar la publicación de la tabla `publicaciones`
        $stmt_delete = $mysqli->prepare("DELETE FROM publicaciones WHERE id_publicacion = ? AND id_usuario = ?");
        if (!$stmt_delete) {
            throw new Exception("Error al preparar la eliminación de publicación: " . $mysqli->error);
        }
        $stmt_delete->bind_param("ii", $publicacionId, $loggedInUserId); // Verificar nuevamente el propietario
        if (!$stmt_delete->execute()) {
            throw new Exception("Error al ejecutar la eliminación de publicación: " . $stmt_delete->error);
        }

        if ($stmt_delete->affected_rows === 0) {
            // Esto debería capturar casos donde la publicación no existe o el usuario no es el propietario
            throw new Exception('No se encontró la publicación o no tienes permiso para eliminarla.');
        }
        $stmt_delete->close();

        $mysqli->commit(); // Confirmar la transacción
        $response['success'] = true;
        $response['message'] = 'Publicación eliminada exitosamente.';

    } catch (Exception $e) {
        $mysqli->rollback(); // Revertir la transacción en caso de error
        $response['message'] = 'Error en la operación: ' . $e->getMessage();
        error_log("Error en delete_post.php: " . $e->getMessage()); // Para depuración
    } finally {
        close_db_connection($mysqli);
    }

} else {
    $response['message'] = 'Método de solicitud no permitido.';
}

echo json_encode($response);
?>