<?php
session_start();
require_once '../server.php'; // Asegúrate de que la ruta sea correcta

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

// 1. Verificar autenticación del usuario
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Usuario no autenticado. Por favor, inicia sesión.';
    error_log("DEBUG: delete_comment.php - Usuario no autenticado.");
    echo json_encode($response);
    exit();
}

$loggedInUserId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $commentId = $data['commentId'] ?? null;

    error_log("DEBUG: delete_comment.php - Recibido commentId: " . ($commentId ?? 'NULL') . ", loggedInUserId: " . $loggedInUserId);

    // 2. Validar que se haya proporcionado y sea un ID de comentario válido
    if (is_null($commentId) || !is_numeric($commentId) || $commentId <= 0) {
        $response['message'] = 'ID del comentario no proporcionado o inválido.';
        error_log("ERROR: delete_comment.php - ID de comentario inválido o nulo: " . ($commentId ?? 'NULL'));
        echo json_encode($response);
        close_db_connection($mysqli);
        exit();
    }

    // Convertir a entero para asegurar tipo correcto
    $commentId = (int)$commentId;

    // Iniciar una transacción para asegurar la integridad
    $mysqli->begin_transaction();

    try {
        // 3. Verificar que el usuario autenticado sea el propietario del comentario
        // Y que el comentario con ese ID exista
        $stmt_check_owner = $mysqli->prepare("SELECT id_usuario FROM comentarios WHERE id_com = ?");
        if (!$stmt_check_owner) {
            throw new Exception("Error al preparar la verificación del propietario: " . $mysqli->error);
        }
        $stmt_check_owner->bind_param("i", $commentId);
        $stmt_check_owner->execute();
        $result_owner = $stmt_check_owner->get_result();

        if ($result_owner->num_rows === 0) {
            // El comentario no existe o no se encontró con ese ID
            $response['message'] = 'El comentario no existe o ya fue eliminado.';
            error_log("DEBUG: delete_comment.php - Comentario con ID " . $commentId . " no encontrado.");
            echo json_encode($response);
            $stmt_check_owner->close();
            close_db_connection($mysqli);
            exit();
        }

        $comment_owner_row = $result_owner->fetch_assoc();
        $commentOwnerId = $comment_owner_row['id_usuario'];
        $stmt_check_owner->close();

        if ($commentOwnerId != $loggedInUserId) {
            // El usuario no es el propietario del comentario
            $response['message'] = 'No tienes permiso para eliminar este comentario.';
            error_log("ALERT: delete_comment.php - Intento de eliminación no autorizado. User ID: " . $loggedInUserId . ", Comment ID: " . $commentId . ", Owner ID: " . $commentOwnerId);
            echo json_encode($response);
            close_db_connection($mysqli);
            exit();
        }

        // 4. Si el usuario es el propietario, proceder con la eliminación
        $stmt_delete = $mysqli->prepare("DELETE FROM comentarios WHERE id_com = ? AND id_usuario = ?");
        if (!$stmt_delete) {
            throw new Exception("Error al preparar la eliminación del comentario: " . $mysqli->error);
        }
        $stmt_delete->bind_param("ii", $commentId, $loggedInUserId); // Ambos deben ser enteros
        if (!$stmt_delete->execute()) {
            throw new Exception("Error al ejecutar la eliminación del comentario: " . $stmt_delete->error);
        }

        // Verificar cuántas filas fueron afectadas
        $affectedRows = $stmt_delete->affected_rows;
        $stmt_delete->close();

        if ($affectedRows > 0) {
            $mysqli->commit(); // Confirmar la transacción
            $response['success'] = true;
            $response['message'] = 'Comentario eliminado exitosamente.';
            error_log("INFO: delete_comment.php - Comentario ID " . $commentId . " de usuario " . $loggedInUserId . " eliminado. Filas afectadas: " . $affectedRows);
        } else {
            // Esto podría ocurrir si el comentario fue eliminado entre la verificación y la eliminación
            // o si el ID del comentario no coincidió por alguna razón inesperada.
            $mysqli->rollback(); // Revertir la transacción si no se eliminó nada
            $response['message'] = 'No se pudo eliminar el comentario. Es posible que ya no exista.';
            error_log("WARN: delete_comment.php - Comentario ID " . $commentId . " de usuario " . $loggedInUserId . " no pudo ser eliminado. Filas afectadas: " . $affectedRows . ". Posiblemente ya no existe.");
        }

    } catch (Exception $e) {
        $mysqli->rollback(); // Revertir la transacción en caso de error
        $response['message'] = 'Error en la operación: ' . $e->getMessage();
        error_log("FATAL ERROR: delete_comment.php - Excepción durante la eliminación: " . $e->getMessage() . " (Comment ID: " . $commentId . ", User ID: " . $loggedInUserId . ")");
    }

} else {
    $response['message'] = 'Método de solicitud no permitido.';
    error_log("WARN: delete_comment.php - Método de solicitud no permitido: " . $_SERVER['REQUEST_METHOD']);
}

close_db_connection($mysqli);
echo json_encode($response);
?>