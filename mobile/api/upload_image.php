<?php
// api/upload_image.php
require_once '../server.php'; // Asegúrate de que la ruta a server.php sea correcta

session_start();
header('Content-Type: application/json');

// Inicializa la respuesta con un estado de error y file_path como null.
$response = ['status' => 'error', 'message' => 'No se pudo subir la imagen.', 'file_path' => null];

// --- INICIO DEPURACIÓN ---
error_log("DEBUG: upload_image.php accedido. REQUEST_METHOD: " . $_SERVER["REQUEST_METHOD"]);
error_log("DEBUG: Contenido de \$_FILES: " . print_r($_FILES, true));
error_log("DEBUG: Contenido de \$_POST: " . print_r($_POST, true));
// --- FIN DEPURACIÓN ---

// --- INICIO DEPURACIÓN ---
error_log("DEBUG: upload_image.php accedido. REQUEST_METHOD: " . $_SERVER["REQUEST_METHOD"]);
error_log("DEBUG: Contenido de \$_FILES: " . print_r($_FILES, true));
error_log("DEBUG(" . __LINE__ . "): Contenido de \$_POST: " . print_r($_POST, true)); // Modificado para incluir __LINE__
// --- FIN DEPURACIÓN ---

// Verificar si el usuario está autenticado (opcional, pero buena práctica para uploads)
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Usuario no autenticado.';
    $response['file_path'] = ''; // Asegurar que sea cadena vacía si el usuario no está autenticado
    echo json_encode($response);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Si se subió un archivo y no hay errores
    if (isset($_FILES["image_file"]) && $_FILES["image_file"]["error"] === UPLOAD_ERR_OK) {
        $uploadDir = '../img/uploaded/'; // Ruta donde se guardarán las imágenes
        // Asegúrate de que esta carpeta exista y tenga permisos de escritura (chmod 775 o 777)

        $file = $_FILES["image_file"];

        // Validar tipo de archivo (solo imágenes)
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedTypes)) {
            $response['message'] = 'Tipo de archivo no permitido. Solo se permiten imágenes JPEG, PNG, GIF, WEBP.';
            $response['file_path'] = ''; // Vaciar file_path en error de tipo
            echo json_encode($response);
            exit();
        }

        // Generar un nombre único para el archivo
        $fileName = uniqid() . '_' . basename($file['name']);
        $targetFilePath = $uploadDir . $fileName;

        // Mover el archivo subido al directorio de destino
        if (move_uploaded_file($file['tmp_name'], $targetFilePath)) {
            // Éxito: devolver la ruta del archivo relativa para guardar en la DB
            $response['status'] = 'success';
            $response['message'] = 'Imagen subida exitosamente.';
            
            // Generar la ruta relativa.
            $relativePath = str_replace('../', '', $targetFilePath);
            
            // <<< MODIFICACIÓN CLAVE ADICIONAL >>>
            // Asegurarse de que $relativePath sea una cadena no vacía; de lo contrario, usar una cadena vacía.
            $response['file_path'] = !empty($relativePath) ? $relativePath : ''; 
            // <<< FIN MODIFICACIÓN CLAVE ADICIONAL >>>

        } else {
            $response['message'] = 'Error al mover el archivo subido.';
            $response['file_path'] = ''; // Vaciar file_path en error de movimiento
        }
    } else {
        // Este bloque maneja casos donde $_FILES["image_file"] no está configurado
        // (es decir, no se seleccionó ningún archivo) o si hay un error de subida PHP.
        // UPLOAD_ERR_NO_FILE cae aquí.
        if (isset($_FILES["image_file"])) {
            $php_upload_errors = [
                UPLOAD_ERR_OK         => "No hay errores.",
                UPLOAD_ERR_INI_SIZE   => "El archivo subido excede la directiva upload_max_filesize en php.ini.",
                UPLOAD_ERR_FORM_SIZE  => "El archivo subido excede la directiva MAX_FILE_SIZE que fue especificada en el formulario HTML.",
                UPLOAD_ERR_PARTIAL    => "El archivo subido fue solo parcialmente subido.",
                UPLOAD_ERR_NO_FILE    => "No se subió ningún archivo.", // Este es el caso clave cuando no se selecciona imagen
                UPLOAD_ERR_NO_TMP_DIR => "Falta una carpeta temporal.",
                UPLOAD_ERR_CANT_WRITE => "Fallo al escribir el archivo en el disco.",
                UPLOAD_ERR_EXTENSION  => "Una extensión de PHP detuvo la subida del archivo.",
            ];
            $error_code = $_FILES["image_file"]["error"];
            $response['message'] .= " Código de error: " . $error_code . " (" . ($php_upload_errors[$error_code] ?? "Desconocido") . ")";
        } else {
            error_log("DEBUG: \$_FILES['image_file'] no está configurado. El input de archivo puede estar vacío o el 'name' no coincide.");
            $response['message'] .= " (No se detectó el archivo 'image_file' en \$_FILES).";
        }
        $response['file_path'] = ''; // Asegurar que file_path sea una cadena vacía en casos de error o no-archivo
    }
} else {
    $response['message'] = 'Método de solicitud no permitido. Se esperaba POST, se recibió: ' . $_SERVER["REQUEST_METHOD"];
    $response['file_path'] = ''; // Asegurar que sea cadena vacía si el método no es POST
}

echo json_encode($response);
close_db_connection($mysqli);
?>