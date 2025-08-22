<?php
session_start();
require_once '../server.php'; // Asegúrate de que la ruta sea correcta

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

// Verifica que el usuario esté autenticado
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Usuario no autenticado. Por favor, inicia sesión.';
    echo json_encode($response);
    exit();
}

$loggedInUserId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $publicacionId = $data['publicacionId'] ?? null; // ¡CAMBIO AQUI! Ahora espera 'publicacionId'
    $action = $data['action'] ?? null; // 'like' o 'unlike'

    if (is_null($publicacionId) || !is_numeric($publicacionId) || !in_array($action, ['like', 'unlike'])) {
        $response['message'] = 'Datos inválidos. Se requiere publicacionId (numérico) y action (like/unlike).'; // Actualiza el mensaje
        error_log("DEBUG: toggle_like.php - Datos inválidos. publicacionId: " . ($publicacionId ?? 'NULL') . ", action: " . ($action ?? 'NULL')); // Actualiza el log
        echo json_encode($response);
        close_db_connection($mysqli);
        exit();
    }

    $publicacionId = (int)$publicacionId; // Asegurar que es un entero

    $mysqli->begin_transaction(); // Iniciar una transacción para asegurar la atomicidad

    try {
        // Primero, verificar el estado actual del "me gusta" del usuario para esta publicación
        $stmt_check = $mysqli->prepare("SELECT id_usuario FROM likes WHERE id_publicacion = ? AND id_usuario = ?");
        if (!$stmt_check) {
            throw new Exception("Error al preparar la verificación de like: " . $mysqli->error);
        }
        $stmt_check->bind_param("ii", $publicacionId, $loggedInUserId); // Usa publicacionId
        $stmt_check->execute();
        $stmt_check->store_result();
        $hasLiked = $stmt_check->num_rows > 0;
        $stmt_check->close();

        $newLikesCount = 0; // Inicializar

        if ($action === 'like') {
            if (!$hasLiked) {
                // Si no ha dado 'me gusta' antes, insertarlo
                $stmt_insert = $mysqli->prepare("INSERT INTO likes (id_publicacion, id_usuario) VALUES (?, ?)");
                if (!$stmt_insert) {
                    throw new Exception("Error al preparar 'dar me gusta': " . $mysqli->error);
                }
                $stmt_insert->bind_param("ii", $publicacionId, $loggedInUserId); // Usa publicacionId
                if (!$stmt_insert->execute()) {
                    throw new Exception("Error al ejecutar 'dar me gusta': " . $stmt_insert->error);
                }
                $stmt_insert->close();

                // Incrementar el contador de likes en la tabla 'publicaciones'
                $stmt_update = $mysqli->prepare("UPDATE publicaciones SET likes_publicacion = likes_publicacion + 1 WHERE id_publicacion = ?");
                if (!$stmt_update) {
                    throw new Exception("Error al preparar la actualización de 'sumar me gusta': " . $mysqli->error);
                }
                $stmt_update->bind_param("i", $publicacionId); // Usa publicacionId
                if (!$stmt_update->execute()) {
                    throw new Exception("Error al ejecutar la actualización de 'sumar me gusta': " . $stmt_update->error);
                }
                $stmt_update->close();
                $response['message'] = 'Me gusta añadido.';
            } else {
                $response['message'] = 'Ya has dado me gusta a esta publicación.';
                $response['success'] = true;
            }
        } elseif ($action === 'unlike') {
            if ($hasLiked) {
                // Si ya ha dado 'me gusta' antes, eliminarlo
                $stmt_delete = $mysqli->prepare("DELETE FROM likes WHERE id_publicacion = ? AND id_usuario = ?");
                if (!$stmt_delete) {
                    throw new Exception("Error al preparar 'quitar me gusta': " . $mysqli->error);
                }
                $stmt_delete->bind_param("ii", $publicacionId, $loggedInUserId); // Usa publicacionId
                if (!$stmt_delete->execute()) {
                    throw new Exception("Error al ejecutar 'quitar me gusta': " . $stmt_delete->error);
                }
                $stmt_delete->close();

                // Decrementar el contador de likes en la tabla 'publicaciones'
                $stmt_update = $mysqli->prepare("UPDATE publicaciones SET likes_publicacion = likes_publicacion - 1 WHERE id_publicacion = ?");
                if (!$stmt_update) {
                    throw new Exception("Error al preparar la actualización de 'quitar me gusta': " . $mysqli->error);
                }
                $stmt_update->bind_param("i", $publicacionId); // Usa publicacionId
                if (!$stmt_update->execute()) {
                    throw new Exception("Error al ejecutar la actualización de 'quitar me gusta': " . $stmt_update->error);
                }
                $stmt_update->close();
                $response['message'] = 'Me gusta quitado.';
            } else {
                $response['message'] = 'No habías dado me gusta a esta publicación.';
                $response['success'] = true;
            }
        }

        // Obtener el nuevo conteo de likes de la base de datos
        $stmt_get_likes = $mysqli->prepare("SELECT likes_publicacion FROM publicaciones WHERE id_publicacion = ?");
        if (!$stmt_get_likes) {
            throw new Exception("Error al preparar la consulta de conteo de likes: " . $mysqli->error);
        }
        $stmt_get_likes->bind_param("i", $publicacionId); // Usa publicacionId
        $stmt_get_likes->execute();
        $stmt_get_likes->bind_result($newLikesCount);
        $stmt_get_likes->fetch();
        $stmt_get_likes->close();

        $mysqli->commit(); // Confirmar la transacción
        $response['success'] = true;
        $response['newLikesCount'] = $newLikesCount;

    } catch (Exception $e) {
        $mysqli->rollback(); // Revertir la transacción en caso de error
        $response['message'] = 'Error en la operación: ' . $e->getMessage();
        error_log("Error en toggle_like.php: " . $e->getMessage());
    } finally {
        close_db_connection($mysqli);
    }
} else {
    $response['message'] = 'Método de solicitud no permitido.';
}

echo json_encode($response);
?>