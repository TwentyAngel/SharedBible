<?php
// Iniciar sesión para acceder a las variables de sesión
session_start();

require_once '../server.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Usuario no autenticado. Por favor, inicia sesión para publicar.';
    echo json_encode($response);
    exit();
}

$loggedInUserId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $contenido_publicacion = $data['postContent'] ?? '';

    if (empty($contenido_publicacion)) {
        $response['message'] = 'El contenido de la publicación no puede estar vacío.';
        echo json_encode($response);
        exit();
    }

    // Opcional: Obtener el nombre del usuario para la respuesta inmediata del frontend
    $nombre_usuario_actual = 'Usuario Desconocido'; // Valor por defecto
    $stmt_user = $mysqli->prepare("SELECT nombre_usuario FROM usuarios WHERE id_usuario = ?");
    if ($stmt_user) {
        $stmt_user->bind_param("i", $loggedInUserId);
        $stmt_user->execute();
        $stmt_user->bind_result($fetched_username);
        $stmt_user->fetch();
        if ($fetched_username) {
            $nombre_usuario_actual = $fetched_username;
        }
        $stmt_user->close();
    }


    $fecha_publicacion = date('Y-m-d H:i:s');
    $likes_publicacion = 0; // Nueva publicación empieza con 0 likes

    $stmt = $mysqli->prepare("INSERT INTO publicaciones (id_usuario, contenido_publicacion, likes_publicacion, fecha_publicacion) VALUES (?, ?, ?, ?)");

    if ($stmt) {
        $stmt->bind_param("isss", $loggedInUserId, $contenido_publicacion, $likes_publicacion, $fecha_publicacion);
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Publicación creada exitosamente.';
            $response['newPost'] = [
                'id_publicacion' => $mysqli->insert_id,
                'nombre_usuario' => $nombre_usuario_actual, // Usar el nombre del usuario obtenido de la DB
                'contenido_publicacion' => $contenido_publicacion,
                'likes_publicacion' => $likes_publicacion,
                'fecha_publicacion' => $fecha_publicacion,
                'tiempo_transcurrido' => 'Hace unos segundos' // O un cálculo más preciso
            ];

        } else {
            $response['message'] = 'Error al insertar la publicación: ' . $stmt->error;
        }
        $stmt->close();
    } else {
        $response['message'] = 'Error al preparar la declaración: ' . $mysqli->error;
    }
} else {
    $response['message'] = 'Método de solicitud no permitido.';
}

close_db_connection($mysqli);
echo json_encode($response);
?>