<?php
session_start();
header('Content-Type: application/json');

// Incluye tu archivo de conexión a la base de datos
// MODIFICADO: Apunta a server.php que está en el directorio padre.
// server.php ya inicializa $mysqli globalmente.
require_once '../server.php';

$response = ['success' => false, 'message' => ''];

// Si la conexión a la base de datos global ($mysqli) falló en server.php, salir.
// server.php ya tiene un 'die()' para esto, pero es bueno tener una verificación aquí también.
// Asumimos que $mysqli es el objeto de conexión de la base de datos.
global $mysqli; // Asegurarse de que $mysqli es accesible en este ámbito

if (!isset($mysqli) || $mysqli->connect_errno) {
    // Si la conexión no se estableció o falló en server.php, manejar aquí
    $response['message'] = 'Error al conectar con la base de datos.';
    error_log("Error: \$mysqli no está disponible o la conexión falló en get_saved_publications.php.");
    echo json_encode($response);
    exit();
}

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Usuario no autenticado.';
    echo json_encode($response);
    // MODIFICADO: Asegurarse de cerrar la conexión que ya está globalmente disponible si existe.
    if (isset($mysqli) && $mysqli instanceof mysqli) {
        close_db_connection($mysqli);
    }
    exit();
}

$userId = $_SESSION['user_id'];

try {
    // MODIFICADO: Usar directamente el objeto de conexión global $mysqli,
    // que es provisto por server.php al ser incluido.
    // Ya no necesitas llamar a db_connect() porque $mysqli ya está disponible.
    $conn = $mysqli;

    // Paso 1: Obtener los IDs de las publicaciones guardadas por el usuario
    $stmt = $conn->prepare("SELECT id_publicacion FROM publicaciones_guardadas WHERE id_usuario = ?");
    if (!$stmt) {
        throw new Exception("Error al preparar la consulta de IDs guardados: " . $conn->error);
    }
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $savedPostIds = [];
    while ($row = $result->fetch_assoc()) {
        $savedPostIds[] = $row['id_publicacion'];
    }
    $stmt->close();

    if (empty($savedPostIds)) {
        $response['success'] = true;
        $response['publications'] = [];
        $response['message'] = 'No tienes publicaciones guardadas.';
        echo json_encode($response);
        // MODIFICADO: Cerrar conexión y salir si no hay publicaciones.
        // Usar $mysqli directamente.
        close_db_connection($mysqli);
        exit();
    }

    // Paso 2: Obtener los detalles completos de esas publicaciones
    $placeholders = implode(',', array_fill(0, count($savedPostIds), '?'));
    $types = str_repeat('i', count($savedPostIds));

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
        WHERE p.id_publicacion IN ($placeholders)
        ORDER BY p.fecha_publicacion DESC
    ");
    if (!$stmt) {
        throw new Exception("Error al preparar la consulta de detalles de publicaciones: " . $conn->error);
    }
    $stmt->bind_param($types, ...$savedPostIds);
    $stmt->execute();
    $result = $stmt->get_result();

    $publications = [];
    while ($row = $result->fetch_assoc()) {
        $row['fecha_formateada'] = date('d/m/Y H:i', strtotime($row['fecha_publicacion']));
        $publications[] = $row;
    }
    $stmt->close();

    $response['success'] = true;
    $response['publications'] = $publications;

} catch (Exception $e) {
    $response['message'] = 'Error en la operación: ' . $e->getMessage();
    // Registra el error para depuración en el servidor
    error_log("Error en get_saved_publications.php: " . $e->getMessage());
} finally {
    // Asegurarse de que la conexión se cierre al final
    // MODIFICADO: Siempre usar $mysqli para cerrar la conexión global.
    if (isset($mysqli) && $mysqli instanceof mysqli) {
        close_db_connection($mysqli);
    }
}

echo json_encode($response);