<?php
session_start();
header('Content-Type: application/json');

require_once '../server.php'; // Ajusta la ruta si tu server.php está en otro lugar

$response = ['success' => false, 'message' => ''];

// Asegurarse de que $mysqli es accesible y la conexión es válida
global $mysqli;
if (!isset($mysqli) || $mysqli->connect_errno) {
    $response['message'] = 'Error al conectar con la base de datos.';
    error_log("Error: \$mysqli no está disponible o la conexión falló en get_user_posts.php.");
    echo json_encode($response);
    exit();
}

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Usuario no autenticado.';
    echo json_encode($response);
    close_db_connection($mysqli);
    exit();
}

$userId = $_SESSION['user_id'];

try {
    $conn = $mysqli; // Usar la conexión global

    $stmt = $conn->prepare("
        SELECT
            p.id_publicacion,
            p.contenido_publicacion,
            p.fecha_publicacion,
            p.likes_publicacion,
            u.nombre_usuario,
            r.rama AS nombre_rama
        FROM publicaciones p
        JOIN usuarios u ON p.id_usuario = u.id_usuario
        JOIN ramas r ON u.id_rama = r.id_rama
        WHERE p.id_usuario = ?
        ORDER BY p.fecha_publicacion DESC
    ");

    if (!$stmt) {
        throw new Exception("Error al preparar la consulta: " . $conn->error);
    }

    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $publications = [];
    while ($row = $result->fetch_assoc()) {
        // Formatear la fecha para una mejor visualización en el frontend
        $row['fecha_formateada'] = date('d/m/Y H:i', strtotime($row['fecha_publicacion']));
        $publications[] = $row;
    }

    $stmt->close();

    $response['success'] = true;
    $response['publications'] = $publications;

} catch (Exception $e) {
    $response['message'] = 'Error en la operación: ' . $e->getMessage();
    error_log("Error en get_user_posts.php: " . $e->getMessage());
} finally {
    if (isset($mysqli) && $mysqli instanceof mysqli) {
        close_db_connection($mysqli);
    }
}

echo json_encode($response);
?>