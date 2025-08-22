<?php
session_start();
require_once '../server.php'; // Asegúrate de que la ruta sea correcta

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

// Verifica que el usuario esté autenticado
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Usuario no autenticado. Por favor, inicia sesión.';
    echo json_encode($response);
    close_db_connection($mysqli); // Cerrar conexión antes de salir
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    // Validar publicacionId y action de forma más estricta
    // Se asegura que publicacionId exista, sea numérico y que action sea 'like' o 'unlike'
    if (!isset($data['publicacionId']) || !is_numeric($data['publicacionId']) || !in_array($data['action'] ?? null, ['like', 'unlike'])) {
        $response['message'] = 'Datos inválidos. Se requiere publicacionId (numérico) y action (like/unlike).';
        echo json_encode($response);
        close_db_connection($mysqli);
        exit();
    }

    $publicacionId = (int)$data['publicacionId']; // Castear a entero después de la validación
    $action = $data['action']; // Ya sabemos que es 'like' o 'unlike' por la validación

    // Iniciar una transacción para asegurar la integridad de los datos
    $mysqli->begin_transaction();

    try {
        if ($action === 'like') {
            // Incrementa el contador de likes_publicacion
            $stmt_update = $mysqli->prepare("UPDATE publicaciones SET likes_publicacion = likes_publicacion + 1 WHERE id_publicacion = ?");
            if (!$stmt_update) {
                throw new Exception("Error al preparar la actualización de 'me gusta': " . $mysqli->error);
            }
            $stmt_update->bind_param("i", $publicacionId);
            if (!$stmt_update->execute()) {
                throw new Exception("Error al ejecutar la actualización de 'me gusta': " . $stmt_update->error);
            }
            $stmt_update->close();
            $response['message'] = 'Me gusta añadido.';
        } else { // action === 'unlike'
            // Decrementa el contador de likes_publicacion, asegurándose de que no sea negativo
            $stmt_update = $mysqli->prepare("UPDATE publicaciones SET likes_publicacion = GREATEST(0, likes_publicacion - 1) WHERE id_publicacion = ?");
            if (!$stmt_update) {
                throw new Exception("Error al preparar la actualización de 'quitar me gusta': " . $mysqli->error);
            }
            $stmt_update->bind_param("i", $publicacionId);
            if (!$stmt_update->execute()) {
                throw new Exception("Error al ejecutar la actualización de 'quitar me gusta': " . $stmt_update->error);
            }
            $stmt_update->close();
            $response['message'] = 'Me gusta quitado.';
        }

        // Obtener el nuevo conteo de likes de la base de datos
        $stmt_get_likes = $mysqli->prepare("SELECT likes_publicacion FROM publicaciones WHERE id_publicacion = ?");
        if (!$stmt_get_likes) {
            throw new Exception("Error al preparar la consulta de conteo de likes: " . $mysqli->error);
        }
        $stmt_get_likes->bind_param("i", $publicacionId);
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
        echo json_encode($response);
    }
} else {
    $response['message'] = 'Método de solicitud no permitido.';
    echo json_encode($response);
}
?>