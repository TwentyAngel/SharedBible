<?php
// api/get_sermons.php
require_once '../server.php';

session_start();
header('Content-Type: application/json');

error_log("DEBUG: get_sermons.php - SESSION user_id: " . ($_SESSION['user_id'] ?? 'NOT SET'));

$response = ['status' => 'error', 'message' => 'No se pudieron obtener las predicaciones.', 'sermons' => []];

$current_user_id = $_SESSION['user_id'] ?? null;

// Variables de filtro
$search_query = $_GET['search'] ?? '';
$filter_rama = $_GET['rama'] ?? '';

$sql = "SELECT id_predi, id_usuario, titulo_predi, contenido_predi, autor_predi, img_predi, fecha_predi, vURL_predi, rama_predi 
        FROM predicaciones 
        WHERE 1=1";

$params = [];
$types = '';

if (!empty($search_query)) {
    $sql .= " AND (titulo_predi LIKE ? OR contenido_predi LIKE ? OR autor_predi LIKE ?)";
    $params[] = '%' . $search_query . '%';
    $params[] = '%' . $search_query . '%';
    $params[] = '%' . $search_query . '%';
    $types .= 'sss';
}

if (!empty($filter_rama)) {
    $sql .= " AND rama_predi = ?";
    $params[] = $filter_rama;
    $types .= 's';
}

$sql .= " ORDER BY id_predi DESC";

$stmt = $mysqli->prepare($sql);

if ($stmt) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $sermons = [];

    // Directorios para imágenes (igual que antes)
    $base_upload_dir_server_path = realpath(__DIR__ . '/../mobile/uploads'); 
    $base_mobile_img_dir_server_path = realpath(__DIR__ . '/../mobile/img/uploaded'); 

    while ($row = $result->fetch_assoc()) {
        // --- Lógica para IMG ---
        $image_path_from_db = $row['img_predi'];
        $final_image_url_for_frontend = '';

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
        $row['img_predi_final_url'] = $final_image_url_for_frontend;

        // --- Lógica para VIDEO (usar directamente la URL embed de Archive.org) ---
        $video_embed_url_from_db = $row['vURL_predi'];
        $row['video_file_url'] = !empty($video_embed_url_from_db) ? $video_embed_url_from_db : '';

        $sermons[] = $row;
    }
    
    $response = [
        'status' => 'success',
        'message' => 'Predicaciones obtenidas exitosamente.',
        'sermons' => $sermons,
        'current_user_id' => $current_user_id 
    ];
    $result->free();
} else {
    $response['message'] = 'Error al preparar la consulta: ' . $mysqli->error;
}

error_log("DEBUG from get_sermons.php: Final Response Data: " . json_encode($response));

echo json_encode($response);
close_db_connection($mysqli);
?>
