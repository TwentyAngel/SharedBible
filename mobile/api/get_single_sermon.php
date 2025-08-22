<?php
// api/get_single_sermon.php
require_once '../server.php';

session_start();
header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'No se pudo obtener la predicación.', 'sermon' => null];

if (isset($_GET['id'])) {
    $id_predi = $_GET['id'];

    $stmt = $mysqli->prepare("SELECT id_predi, id_usuario, titulo_predi, contenido_predi, autor_predi, img_predi, fecha_predi, vURL_predi, rama_predi FROM predicaciones WHERE id_predi = ?");

    if ($stmt) {
        $stmt->bind_param("i", $id_predi);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $sermon = $result->fetch_assoc();

            // Imagen (igual que antes)
            $image_path_from_db = $sermon['img_predi'];
            $final_image_url_for_frontend = '';
            $base_upload_dir_server_path = realpath(__DIR__ . '/../mobile/uploads');
            $base_mobile_img_dir_server_path = realpath(__DIR__ . '/../mobile/img/uploaded');

            if (!empty($image_path_from_db)) {
                $image_filename_from_db = basename($image_path_from_db);
                $full_path_default_location = $base_upload_dir_server_path . DIRECTORY_SEPARATOR . $image_filename_from_db;
                if (file_exists($full_path_default_location)) {
                    $final_image_url_for_frontend = 'mobile/uploads/' . $image_filename_from_db;
                } else {
                    $full_path_alternative_location = $base_mobile_img_dir_server_path . DIRECTORY_SEPARATOR . $image_filename_from_db;
                    if (file_exists($full_path_alternative_location)) {
                        $final_image_url_for_frontend = '/mobile/img/uploaded/' . $image_filename_from_db;
                    }
                }
            }
            $sermon['img_predi_final_url'] = $final_image_url_for_frontend;

            // Video (usar URL embed directa)
            $video_embed_url_from_db = $sermon['vURL_predi'];
            $sermon['video_file_url'] = !empty($video_embed_url_from_db) ? $video_embed_url_from_db : '';

            // Añadir ID de usuario actual para frontend si quieres
            $sermon['current_user_id'] = $_SESSION['user_id'] ?? null;

            $response = ['status' => 'success', 'message' => 'Predicación obtenida exitosamente.', 'sermon' => $sermon];
        } else {
            $response['message'] = 'Predicación no encontrada.';
        }
        $stmt->close();
    } else {
        $response['message'] = 'Error al preparar la consulta: ' . $mysqli->error;
    }
} else {
    $response['message'] = 'ID de predicación no proporcionado.';
}

echo json_encode($response);
close_db_connection($mysqli);
?>
