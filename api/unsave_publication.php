<?php
session_start();
header('Content-Type: application/json');

require_once 'db_connect.php';

$response = ['success' => false, 'message' => ''];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Usuario no autenticado.';
    echo json_encode($response);
    exit();
}

if (!isset($_POST['publicacion_id']) || !filter_var($_POST['publicacion_id'], FILTER_VALIDATE_INT)) {
    $response['message'] = 'ID de publicación no válido.';
    echo json_encode($response);
    exit();
}

$userId = $_SESSION['user_id'];
$publicacionId = (int)$_POST['publicacion_id'];

try {
    $conn = db_connect();

    $stmt = $conn->prepare("DELETE FROM publicaciones_guardadas WHERE id_usuario = ? AND id_publicacion = ?");
    $stmt->bind_param("ii", $userId, $publicacionId);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $response['success'] = true;
        $response['message'] = 'Publicación eliminada de tus guardados.';
    } else {
        $response['message'] = 'La publicación no se encontró en tus guardados o ya ha sido eliminada.';
    }
    $stmt->close();

} catch (Exception $e) {
    $response['message'] = 'Error en el servidor: ' . $e->getMessage();
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}

echo json_encode($response);
?>