<?php
session_start();
require_once '../server.php'; // Asegúrate de que la ruta sea correcta

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

// Verifica que el usuario esté autenticado
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Usuario no autenticado. Por favor, inicia sesión para comentar.';
    echo json_encode($response);
    exit();
}

$loggedInUserId = $_SESSION['user_id'];
// Obtener el nombre del usuario de la sesión, asumimos que se guarda al iniciar sesión en login.php
$loggedInUserName = $_SESSION['user_name'] ?? 'Usuario Desconocido'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $publicacionId = $data['publicacionId'] ?? null;
    $commentContent = trim($data['commentContent'] ?? '');

    if (is_null($publicacionId) || empty($commentContent)) {
        $response['message'] = 'Datos inválidos. Se requiere ID de publicación y contenido del comentario.';
        echo json_encode($response);
        close_db_connection($mysqli);
        exit();
    }

    $likes_com = 0; // Los comentarios nuevos empiezan con 0 likes

    $stmt = $mysqli->prepare("INSERT INTO comentarios (id_publicacion, id_usuario, contenido_com, likes_com, fecha_com) VALUES (?, ?, ?, ?, NOW())"); // Añadido fecha_com y NOW()

    if ($stmt) {
        $stmt->bind_param("iisi", $publicacionId, $loggedInUserId, $commentContent, $likes_com);
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Comentario añadido exitosamente.';
            $response['newComment'] = [
                'id_com' => $mysqli->insert_id,
                'id_publicacion' => $publicacionId,
                'id_usuario' => $loggedInUserId,
                'nombre_usuario' => $loggedInUserName, // Devolvemos el nombre del usuario para mostrarlo en el frontend
                'contenido_com' => $commentContent,
                'likes_com' => $likes_com,
                'fecha_com' => date('Y-m-d H:i:s'), // Fecha actual para mostrarla inmediatamente en el frontend
                'tiempo_transcurrido' => 'Hace unos segundos' // Para una visualización instantánea
            ];
        } else {
            $response['message'] = 'Error al insertar el comentario: ' . $stmt->error;
            error_log("Error al ejecutar inserción de comentario: " . $stmt->error);
        }
        $stmt->close();
    } else {
        $response['message'] = 'Error al preparar la declaración para añadir comentario: ' . $mysqli->error;
        error_log("Error al preparar add_comment statement: " . $mysqli->error);
    }
} else {
    $response['message'] = 'Método de solicitud no permitido.';
}

close_db_connection($mysqli);
echo json_encode($response);
?>