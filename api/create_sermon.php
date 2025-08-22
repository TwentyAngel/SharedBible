<?php
// api/create_sermon.php
require_once '../server.php';

session_start();
header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'Ocurrió un error inesperado.'];

/**
 * Sube un video a Archive.org usando la API estilo S3 (con autenticación LOW).
 *
 * @param string $videoTmpPath Ruta temporal del archivo de video.
 * @param string $videoOriginalName Nombre original del archivo.
 * @return string|false URL de embed si fue exitoso, false si falló.
 */
function subirVideoAArchiveOrg($videoTmpPath, $videoOriginalName) {
    $access_key = 'nClzKub3xaTNDA4y';    // Tu clave de acceso Archive.org
    $secret_key = 'VkKcSTkiPwobyu8N';    // Tu clave secreta

    $identifier = 'sharedbible_' . date('YmdHis') . '_' . uniqid();
    $uploadUrl = "https://s3.us.archive.org/$identifier/$videoOriginalName";

    $headers = [
        'authorization: LOW ' . $access_key . ':' . $secret_key,
        'x-archive-meta01-title: ' . $videoOriginalName,
        'x-archive-meta01-description: Video predicación SharedBible',
        'x-archive-meta01-subject: sermon;video;sharedbible',
        'x-archive-meta01-creator: SharedBible',
        'x-archive-meta01-language: Spanish',
        'x-archive-meta01-mediatype: movies',
        'x-archive-auto-make-bucket: 1',
        'Expect:'
    ];

    $fileHandle = fopen($videoTmpPath, 'rb');
    if (!$fileHandle) {
        error_log("❌ No se pudo abrir el archivo temporal del video.");
        return false;
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $uploadUrl);
    curl_setopt($ch, CURLOPT_PUT, true);
    curl_setopt($ch, CURLOPT_INFILE, $fileHandle);
    curl_setopt($ch, CURLOPT_INFILESIZE, filesize($videoTmpPath));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_VERBOSE, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);

    curl_close($ch);
    fclose($fileHandle);

    if ($httpCode >= 200 && $httpCode < 300) {
        error_log("✅ Video subido correctamente como $identifier");
        return "https://archive.org/embed/$identifier";
    } else {
        error_log("❌ Error al subir a Archive.org: HTTP $httpCode - $response - $error");
        return false;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_name'])) {
        $response['message'] = 'Usuario no autenticado.';
        echo json_encode($response);
        close_db_connection($mysqli);
        exit();
    }

    $id_usuario = $_SESSION['user_id'];
    $nombre_usuario = $_SESSION['user_name'];

    $stmt_check_predi = $mysqli->prepare(
        "SELECT u.predi_usuario, r.rama
         FROM usuarios u
         JOIN ramas r ON u.id_rama = r.id_rama
         WHERE u.id_usuario = ?"
    );
    if ($stmt_check_predi) {
        $stmt_check_predi->bind_param("i", $id_usuario);
        $stmt_check_predi->execute();
        $result_check_predi = $stmt_check_predi->get_result();
        $user_data = $result_check_predi->fetch_assoc();
        $stmt_check_predi->close();

        if (!isset($user_data['predi_usuario']) || $user_data['predi_usuario'] !== 'Y') {
            $response['message'] = 'Permiso denegado: Solo los predicadores pueden publicar sermones.';
            echo json_encode($response);
            close_db_connection($mysqli);
            exit();
        }
        $rama_predi = $user_data['rama'] ?? null;
    } else {
        $response['message'] = 'Error interno al verificar permisos: ' . $mysqli->error;
        echo json_encode($response);
        close_db_connection($mysqli);
        exit();
    }

    $titulo_predi = trim($_POST['titulo_predi'] ?? '');
    $contenido_predi = trim($_POST['contenido_predi'] ?? '');
    $fecha_predi = date('Y-m-d');

    $img_predi = '';
    $vURL_predi = '';

    $upload_dir_images = realpath(__DIR__ . '/../mobile/uploads') . DIRECTORY_SEPARATOR;
    if (!is_dir($upload_dir_images)) mkdir($upload_dir_images, 0777, true);

    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] == UPLOAD_ERR_OK) {
        $img_name = uniqid() . '_' . basename($_FILES['image_file']['name']);
        $target_img_file = $upload_dir_images . $img_name;
        if (move_uploaded_file($_FILES['image_file']['tmp_name'], $target_img_file)) {
            $img_predi = $img_name;
        } else {
            $response['message'] = 'Error al subir la imagen.';
            echo json_encode($response);
            close_db_connection($mysqli);
            exit();
        }
    }

    if (isset($_FILES['video_file']) && $_FILES['video_file']['error'] == UPLOAD_ERR_OK) {
        $videoTmp = $_FILES['video_file']['tmp_name'];
        $videoName = basename($_FILES['video_file']['name']);

        $video_file_type = strtolower(pathinfo($videoName, PATHINFO_EXTENSION));
        $allowed_video_types = ['mp4', 'webm', 'ogg'];

        if (!in_array($video_file_type, $allowed_video_types)) {
            $response['message'] = 'Tipo de archivo de video no permitido. Solo se aceptan MP4, WebM, OGG.';
            echo json_encode($response);
            close_db_connection($mysqli);
            exit();
        }

        $vURL_predi = subirVideoAArchiveOrg($videoTmp, $videoName);

        if (!$vURL_predi) {
            $response['message'] = 'Error al subir el video a Archive.org.';
            echo json_encode($response);
            close_db_connection($mysqli);
            exit();
        }
    }

    if (empty($titulo_predi)) {
        $response['message'] = 'El título de la predicación no puede estar vacío.';
        echo json_encode($response);
        close_db_connection($mysqli);
        exit();
    }
    if (empty($contenido_predi)) {
        $response['message'] = 'El contenido de la predicación no puede estar vacío.';
        echo json_encode($response);
        close_db_connection($mysqli);
        exit();
    }

    if (!empty($img_predi) && !empty($vURL_predi)) {
        if (file_exists($upload_dir_images . $img_predi)) unlink($upload_dir_images . $img_predi);
        $response['message'] = 'No se puede subir una imagen Y un video al mismo tiempo. Elija solo uno.';
        echo json_encode($response);
        close_db_connection($mysqli);
        exit();
    }

    $stmt = $mysqli->prepare(
        "INSERT INTO predicaciones (id_usuario, titulo_predi, contenido_predi, autor_predi, img_predi, fecha_predi, vURL_predi, rama_predi)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );

    if ($stmt) {
        $stmt->bind_param("isssssss", $id_usuario, $titulo_predi, $contenido_predi, $nombre_usuario, $img_predi, $fecha_predi, $vURL_predi, $rama_predi);

        if ($stmt->execute()) {
            $response = ['status' => 'success', 'message' => 'Predicación publicada exitosamente.'];
        } else {
            $response['message'] = 'Error al publicar la predicación: ' . $mysqli->error;
        }
        $stmt->close();
    } else {
        $response['message'] = 'Error al preparar la consulta de inserción: ' . $mysqli->error;
    }
} else {
    $response['message'] = 'Método de solicitud no permitido.';
}

echo json_encode($response);
close_db_connection($mysqli);
exit();
?>
