<?php
// Iniciar sesión para acceder a las variables de sesión
session_start();

// Incluye el archivo de conexión a la base de datos
require_once '../server.php';

header('Content-Type: application/json');

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado. Por favor, inicia sesión.']);
    exit();
}

$loggedInUserId = $_SESSION['user_id'];

// 1. Obtener la rama del usuario logueado
$user_rama_id = null;
$stmt = $mysqli->prepare("SELECT id_rama FROM usuarios WHERE id_usuario = ?");
if ($stmt) {
    $stmt->bind_param("i", $loggedInUserId);
    $stmt->execute();
    $stmt->bind_result($user_rama_id);
    $stmt->fetch();
    $stmt->close();
}

if (is_null($user_rama_id)) {
    echo json_encode(['success' => false, 'message' => 'No se pudo encontrar la rama del usuario.']);
    close_db_connection($mysqli);
    exit();
}

// 2. Obtener publicaciones de usuarios con la misma rama doctrinal
$posts = [];
$query = "
    SELECT
        p.id_publicacion,
        u.nombre_usuario,
        p.contenido_publicacion,
        p.likes_publicacion,
        p.fecha_publicacion,
        -- Eliminada la línea p.url_video, ya que no existe en tu tabla publicaciones
        -- Añade esta línea para verificar si el usuario actual ha dado 'me gusta'
        CASE WHEN l.id_usuario IS NOT NULL THEN TRUE ELSE FALSE END AS liked_by_user
    FROM publicaciones p
    JOIN usuarios u ON p.id_usuario = u.id_usuario
    -- LEFT JOIN con la tabla 'likes' para ver si el usuario logueado ha dado 'me gusta'
    LEFT JOIN likes l ON p.id_publicacion = l.id_publicacion AND l.id_usuario = ?
    WHERE u.id_rama = ?
    ORDER BY p.id_publicacion ASC;
";

$stmt = $mysqli->prepare($query);
if ($stmt) {
    // Vincula los dos parámetros: primero el ID del usuario logueado para el LEFT JOIN, luego el ID de la rama
    $stmt->bind_param("ii", $loggedInUserId, $user_rama_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        // Formatear la fecha para que sea más legible en el frontend
        $date = new DateTime($row['fecha_publicacion']);
        $row['fecha_publicacion_formato'] = $date->format('d/m/Y H:i'); // Ejemplo: 04/07/2025 14:30

        // Calcular "Hace X tiempo"
        $now = new DateTime();
        $interval = $now->diff($date);
        if ($interval->y > 0) {
            $row['tiempo_transcurrido'] = 'Hace ' . $interval->y . ' año(s)';
        } elseif ($interval->m > 0) {
            $row['tiempo_transcurrido'] = 'Hace ' . $interval->m . ' mes(es)';
        } elseif ($interval->d > 0) {
            $row['tiempo_transcurrido'] = 'Hace ' . $interval->d . ' día(s)';
        } elseif ($interval->h > 0) {
            $row['tiempo_transcurrido'] = 'Hace ' . $interval->h . ' hora(s)';
        } elseif ($interval->i > 0) {
            $row['tiempo_transcurrido'] = 'Hace ' . $interval->i . ' minuto(s)';
        } else {
            $row['tiempo_transcurrido'] = 'Hace unos segundos';
        }
        $posts[] = $row;
    }
    $stmt->close();

    echo json_encode(['success' => true, 'posts' => $posts]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al preparar la consulta de publicaciones: ' . $mysqli->error]);
}

close_db_connection($mysqli);
?>