<?php
session_start();
require_once '../server.php'; // Asegúrate de que la ruta sea correcta

header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'post' => null];

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $response['message'] = 'ID de publicación inválido o no proporcionado.';
    echo json_encode($response);
    close_db_connection($mysqli);
    exit();
}

$postId = $_GET['id'];

try {
    $stmt = $mysqli->prepare("SELECT
                                p.id_publicacion,
                                p.contenido_publicacion,
                                p.likes_publicacion,
                                p.fecha_publicacion,
                                u.nombre_usuario
                              FROM publicaciones p
                              JOIN usuarios u ON p.id_usuario = u.id_usuario
                              WHERE p.id_publicacion = ?");

    if (!$stmt) {
        throw new Exception("Error al preparar la consulta de publicación: " . $mysqli->error);
    }

    $stmt->bind_param("i", $postId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $post = $result->fetch_assoc();

        // Formatear la fecha
        $post['fecha_publicacion_formateada'] = (new DateTime($post['fecha_publicacion']))->format('d/m/Y H:i');

        // IMPORTANTE: Con la estructura actual de tu DB (likes_publicacion como columna en publicaciones),
        // no es posible saber si un *usuario específico* le dio like.
        // Solo podemos mostrar el contador total de likes.
        // Por lo tanto, 'liked_by_user' se establecerá siempre en false o se eliminará.
        // Lo mantendremos en false para que el frontend no intente mostrar el corazón lleno.
        $post['liked_by_user'] = false;

        $response['success'] = true;
        $response['post'] = $post;
    } else {
        $response['message'] = 'Publicación no encontrada.';
    }

    $stmt->close();

} catch (Exception $e) {
    $response['message'] = 'Error en la operación: ' . $e->getMessage();
    error_log("Error en get_post_details.php: " . $e->getMessage());
} finally {
    close_db_connection($mysqli);
}

echo json_encode($response);
?>