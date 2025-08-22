<?php
session_start();
require_once '../server.php'; // Asegúrate de que la ruta a server.php sea correcta

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

// Verifica que el usuario esté autenticado
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Usuario no autenticado. Por favor, inicia sesión.';
    echo json_encode($response);
    close_db_connection($mysqli);
    exit();
}

$loggedInUserId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $publicacionId = $data['publicacionId'] ?? null;
    $action = $data['action'] ?? null; // 'save' o 'unsave'

    // Validar datos de entrada
    if (is_null($publicacionId) || !is_numeric($publicacionId) || !in_array($action, ['save', 'unsave'])) {
        $response['message'] = 'Datos inválidos. Se requiere publicacionId (numérico) y action (save/unsave).';
        echo json_encode($response);
        close_db_connection($mysqli);
        exit();
    }

    $publicacionId = (int)$publicacionId; // Asegurar que es un entero

    // Iniciar una transacción para asegurar la integridad de los datos
    $mysqli->begin_transaction();

    try {
        if ($action === 'save') {
            // Verificar si la publicación ya está guardada para evitar duplicados
            $stmt_check = $mysqli->prepare("SELECT COUNT(*) FROM publicaciones_guardadas WHERE id_usuario = ? AND id_publicacion = ?");
            if (!$stmt_check) {
                throw new Exception("Error al preparar la verificación de guardado: " . $mysqli->error);
            }
            $stmt_check->bind_param("ii", $loggedInUserId, $publicacionId);
            $stmt_check->execute();
            $stmt_check->bind_result($count);
            $stmt_check->fetch();
            $stmt_check->close();

            if ($count > 0) {
                // Ya guardado, no hay que hacer nada (o devolver éxito)
                $response['success'] = true;
                $response['message'] = 'Esta publicación ya estaba guardada.';
            } else {
                // Insertar la publicación como guardada
                $stmt_insert = $mysqli->prepare("INSERT INTO publicaciones_guardadas (id_usuario, id_publicacion) VALUES (?, ?)");
                if (!$stmt_insert) {
                    throw new Exception("Error al preparar 'guardar publicación': " . $mysqli->error);
                }
                $stmt_insert->bind_param("ii", $loggedInUserId, $publicacionId);
                if (!$stmt_insert->execute()) {
                    throw new Exception("Error al ejecutar 'guardar publicación': " . $stmt_insert->error);
                }
                $stmt_insert->close();
                $response['success'] = true;
                $response['message'] = 'Publicación guardada exitosamente.';
            }

        } elseif ($action === 'unsave') {
            // Eliminar la publicación de los guardados
            $stmt_delete = $mysqli->prepare("DELETE FROM publicaciones_guardadas WHERE id_usuario = ? AND id_publicacion = ?");
            if (!$stmt_delete) {
                throw new Exception("Error al preparar 'desguardar publicación': " . $mysqli->error);
            }
            $stmt_delete->bind_param("ii", $loggedInUserId, $publicacionId);
            if (!$stmt_delete->execute()) {
                throw new Exception("Error al ejecutar 'desguardar publicación': " . $stmt_delete->error);
            }
            $stmt_delete->close();
            $response['success'] = true;
            $response['message'] = 'Publicación eliminada de guardados.';
        }

        $mysqli->commit(); // Confirmar la transacción

    } catch (Exception $e) {
        $mysqli->rollback(); // Revertir la transacción en caso de error
        $response['message'] = 'Error en la operación: ' . $e->getMessage();
        error_log("Error en toggle_save_post.php: " . $e->getMessage());
    } finally {
        close_db_connection($mysqli);
    }

} else {
    $response['message'] = 'Método de solicitud no permitido.';
}

echo json_encode($response);
exit();
?>