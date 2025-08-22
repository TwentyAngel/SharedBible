<?php
session_start();
require_once '../server.php'; // Ajusta la ruta si es necesario

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

// 1. Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Usuario no autenticado. Por favor, inicia sesión.';
    echo json_encode($response);
    close_db_connection($mysqli);
    exit();
}

$loggedInUserId = $_SESSION['user_id'];

// 2. Verificar que la solicitud sea POST y decodificar los datos
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $publicacionId = $data['publicacionId'] ?? null;
    $contenidoPublicacion = $data['contenidoPublicacion'] ?? null;

    // 3. Validar los datos recibidos
    if (is_null($publicacionId) || !is_numeric($publicacionId) || is_null($contenidoPublicacion) || empty($contenidoPublicacion)) {
        $response['message'] = 'Datos inválidos. Se requiere publicacionId (numérico) y contenidoPublicacion.';
        echo json_encode($response);
        close_db_connection($mysqli);
        exit();
    }

    // Castear a entero para seguridad
    $publicacionId = (int)$publicacionId;

    // 4. Iniciar una transacción para asegurar la integridad
    $mysqli->begin_transaction();

    try {
        // 5. Verificar si la publicación ya está guardada por este usuario
        $stmt_check = $mysqli->prepare("SELECT id_publiguardado FROM publi_guardado WHERE id_publicacion = ? AND id_usuario = ?");
        if (!$stmt_check) {
            throw new Exception("Error al preparar la verificación: " . $mysqli->error);
        }
        $stmt_check->bind_param("ii", $publicacionId, $loggedInUserId);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            // La publicación ya está guardada por este usuario
            $response['message'] = 'Esta publicación ya está en tus guardados.';
            $mysqli->rollback(); // No es un error crítico, pero no necesitamos la transacción
            close_db_connection($mysqli);
            echo json_encode($response);
            exit();
        }
        $stmt_check->close();

        // 6. Insertar la publicación en la tabla publi_guardado
        $stmt_insert = $mysqli->prepare("INSERT INTO publi_guardado (id_publicacion, id_usuario, contenido_publicacion) VALUES (?, ?, ?)");
        if (!$stmt_insert) {
            throw new Exception("Error al preparar la inserción: " . $mysqli->error);
        }
        $stmt_insert->bind_param("iis", $publicacionId, $loggedInUserId, $contenidoPublicacion);

        if (!$stmt_insert->execute()) {
            // Si falla la ejecución, verificar si es por una clave única duplicada (aunque ya lo verificamos arriba)
            if ($mysqli->errno === 1062) { // Error code for Duplicate entry for key 'uq_publi_usuario'
                throw new Exception("Esta publicación ya ha sido guardada por ti.");
            } else {
                throw new Exception("Error al ejecutar la inserción: " . $stmt_insert->error);
            }
        }
        $stmt_insert->close();

        $mysqli->commit(); // Confirmar la transacción
        $response['success'] = true;
        $response['message'] = 'Publicación guardada exitosamente.';

    } catch (Exception $e) {
        $mysqli->rollback(); // Revertir la transacción en caso de error
        $response['message'] = 'Error en la operación: ' . $e->getMessage();
        error_log("Error en save_publication.php: " . $e->getMessage());
    } finally {
        close_db_connection($mysqli);
    }

} else {
    $response['message'] = 'Método de solicitud no permitido.';
}

echo json_encode($response);
?>