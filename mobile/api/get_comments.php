<?php
session_start();
require_once '../server.php'; // Asegúrate de que la ruta sea correcta

header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'comments' => []];

// Obtener el ID del usuario logueado (si existe)
$loggedInUserId = $_SESSION['user_id'] ?? null;
$response['loggedInUserId'] = $loggedInUserId; // Añadir al array de respuesta

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $publicacionId = $_GET['publicacionId'] ?? null;

    if (is_null($publicacionId)) {
        $response['message'] = 'ID de publicación no proporcionado.';
        echo json_encode($response);
        close_db_connection($mysqli);
        exit();
    }

    $comments = [];
    $query = "
        SELECT
            c.id_com,
            c.id_usuario,        -- ¡Asegúrate de seleccionar id_usuario aquí!
            c.contenido_com,
            c.likes_com,
            c.fecha_com,
            u.nombre_usuario
        FROM
            comentarios c
        JOIN
            usuarios u ON c.id_usuario = u.id_usuario
        WHERE
            c.id_publicacion = ?
        ORDER BY
            c.fecha_com ASC
    ";

    $stmt = $mysqli->prepare($query);
    if ($stmt) {
        $stmt->bind_param("i", $publicacionId);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            // Calcular "Hace X tiempo" para cada comentario
            $date = new DateTime($row['fecha_com']);
            $now = new DateTime();
            $interval = $now->diff($date);
            $time_elapsed = 'Hace unos segundos';

            if ($interval->y > 0) {
                $time_elapsed = 'Hace ' . $interval->y . ' año(s)';
            } elseif ($interval->m > 0) {
                $time_elapsed = 'Hace ' . $interval->m . ' mes(es)';
            } elseif ($interval->d > 0) {
                $time_elapsed = 'Hace ' . $interval->d . ' día(s)';
            } elseif ($interval->h > 0) {
                $time_elapsed = 'Hace ' . $interval->h . ' hora(s)';
            } elseif ($interval->i > 0) {
                $time_elapsed = 'Hace ' . $interval->i . ' minuto(s)';
            }
            $row['tiempo_transcurrido'] = $time_elapsed;
            $comments[] = $row;
        }
        $stmt->close();
        $response['success'] = true;
        $response['comments'] = $comments;
    } else {
        $response['message'] = 'Error al preparar la consulta de comentarios: ' . $mysqli->error;
        error_log("Error al preparar get_comments statement: " . $mysqli->error);
    }
} else {
    $response['message'] = 'Método de solicitud no permitido.';
}

close_db_connection($mysqli);
echo json_encode($response);
?>