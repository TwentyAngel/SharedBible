<?php
// api/upload_image.php
require_once '../server.php'; // Asegúrate de que la ruta a server.php sea correcta

session_start();
header('Content-Type: application/json');

// Inicializa la respuesta con un estado de error y file_path como null.
$response = ['status' => 'error', 'message' => 'No se pudo subir la imagen.', 'file_path' => null];

// --- INICIO DEPURACIÓN GENERAL ---
error_log("DEBUG: upload_image.php accedido. REQUEST_METHOD: " . $_SERVER["REQUEST_METHOD"]);
error_log("DEBUG: Contenido de \$_FILES: " . print_r($_FILES, true));
error_log("DEBUG: Contenido de \$_POST: " . print_r($_POST, true));
// --- FIN DEPURACIÓN GENERAL ---

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

        // --- CAMBIOS CLAVE AQUÍ: Definición de rutas ---
        // 1. Ruta ABSOLUTA en el servidor donde se guardará el archivo.
        // Asumiendo que 'api' y 'mobile' están dentro de la carpeta raíz del proyecto.
        // Ejemplo: C:\Users\ANG\Pictures\sb\mobile\img\uploaded
        // Se va un nivel arriba de 'api' (a la raíz), y luego a 'mobile/img/uploaded'.
        $uploadBaseDirServerPath = realpath(__DIR__ . '/../mobile/img/uploaded');

        // --- DEPURACIÓN DE RUTA ---
        error_log("DEBUG: Valor de __DIR__: " . __DIR__);
        error_log("DEBUG: Ruta base intentada para realpath: " . (__DIR__ . '/../mobile/img/uploaded')); // ESTA ES LA RUTA RELATIVA CORRECTA PARA TU ESTRUCTURA
        error_log("DEBUG: realpath(\$uploadBaseDirServerPath) resuelto a: " . ($uploadBaseDirServerPath === false ? 'FALSE (Ruta inválida o inaccesible)' : $uploadBaseDirServerPath));
        // --- FIN DEPURACIÓN DE RUTA ---

        // 2. La ruta ACCESIBLE por URL para el frontend y la DB.
        // Según tu solicitud, la URL base es '/sb/mobile/img/uploaded/'.
        $uploadUrlPath = '/sb/mobile/img/uploaded/';

        // Verificar si el directorio de destino existe y tiene permisos de escritura
        // Esto se ejecutará solo si realpath() devuelve una ruta válida.
        if ($uploadBaseDirServerPath === false || !is_dir($uploadBaseDirServerPath)) {
            // Si realpath devolvió FALSE o el directorio no existe, intentamos crearlo.
            // Para poder crear el directorio, $uploadBaseDirServerPath debe ser una ruta string válida,
            // no FALSE. Necesitamos asegurarnos de que al menos la base de la ruta sea accesible
            // para mkdir. Si realpath falla, la ruta es problemática.

            // Si realpath falló, intentamos una aproximación directa para mkdir,
            // pero el problema de fondo de realpath debe resolverse.
            // La ruta que le pasamos a mkdir debe ser una cadena, no FALSE.
            $fallbackMkdirPath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'mobile' . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'uploaded';
            
            error_log("DEBUG: El directorio o ruta realpath es inválida/no existe. Intentando crear con fallback: " . $fallbackMkdirPath);

            if (!mkdir($fallbackMkdirPath, 0777, true)) {
                $response['message'] = 'Error: No se pudo crear el directorio de destino de la imagen.';
                error_log("Error: No se pudo crear el directorio de subida: " . $fallbackMkdirPath);
                echo json_encode($response);
                exit();
            } else {
                error_log("DEBUG: Directorio creado exitosamente con fallback: " . $fallbackMkdirPath);
                // Si el fallback mkdir fue exitoso, actualizamos la ruta base para continuar.
                $uploadBaseDirServerPath = $fallbackMkdirPath;
            }
        } else {
            error_log("DEBUG: El directorio " . $uploadBaseDirServerPath . " ya existe y es accesible.");
        }

        $file = $_FILES["image_file"];

        // Validar tipo de archivo (solo imágenes)
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedMimeTypes)) {
            $response['message'] = 'Tipo de archivo no permitido. Solo se permiten imágenes JPEG, PNG, GIF, WEBP.';
            $response['file_path'] = ''; // Vaciar file_path en error de tipo
            error_log("DEBUG: Tipo de archivo no permitido: " . $mimeType);
            echo json_encode($response);
            exit();
        }

        // Generar un nombre único para el archivo para evitar colisiones
        $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $uniqueFileName = uniqid() . '.' . $fileExtension;
        
        // Construye la ruta ABSOLUTA COMPLETA en el servidor donde se moverá el archivo
        $targetFileServerPath = $uploadBaseDirServerPath . DIRECTORY_SEPARATOR . $uniqueFileName;

        // --- DEPURACIÓN DE RUTA DE DESTINO DEL ARCHIVO ---
        error_log("DEBUG: Archivo temporal a mover: " . $file['tmp_name']);
        error_log("DEBUG: Ruta de destino final en el servidor: " . $targetFileServerPath);
        // --- FIN DEPURACIÓN DE RUTA DE DESTINO DEL ARCHIVO ---

        // Mover el archivo subido al directorio de destino
        if (move_uploaded_file($file['tmp_name'], $targetFileServerPath)) {
            $response['status'] = 'success';
            $response['message'] = 'Imagen subida exitosamente.';
            
            // Construye la URL completa que se guardará en la DB y se usará en el frontend
            $response['file_path'] = $uploadUrlPath . $uniqueFileName;

            error_log("DEBUG: Imagen subida exitosamente a (Server Path): " . $targetFileServerPath);
            error_log("DEBUG: Path devuelto al frontend y DB (URL Path): " . $response['file_path']);

        } else {
            $response['message'] = 'Error al mover el archivo subido. Posible problema de permisos o directorio.';
            $response['file_path'] = ''; // Vaciar file_path en error de movimiento
            error_log("Error: No se pudo mover el archivo: " . $file['tmp_name'] . " a " . $targetFileServerPath . " (Error code: " . $file['error'] . ")");
        }
    } else {
        // Este bloque maneja casos donde $_FILES["image_file"] no está configurado
        // (es decir, no se seleccionó ningún archivo) o si hay un error de subida PHP.
        if (isset($_FILES["image_file"])) {
            $php_upload_errors = [
                UPLOAD_ERR_OK         => "No hay errores.",
                UPLOAD_ERR_INI_SIZE   => "El archivo subido excede la directiva upload_max_filesize en php.ini.",
                UPLOAD_ERR_FORM_SIZE  => "El archivo subido excede la directiva MAX_FILE_SIZE que fue especificada en el formulario HTML.",
                UPLOAD_ERR_PARTIAL    => "El archivo subido fue solo parcialmente subido.",
                UPLOAD_ERR_NO_FILE    => "No se subió ningún archivo.",
                UPLOAD_ERR_NO_TMP_DIR => "Falta una carpeta temporal.",
                UPLOAD_ERR_CANT_WRITE => "Fallo al escribir el archivo en el disco.",
                UPLOAD_ERR_EXTENSION  => "Una extensión de PHP detuvo la subida del archivo.",
            ];
            $error_code = $_FILES["image_file"]["error"];
            $response['message'] .= " Código de error: " . $error_code . " (" . ($php_upload_errors[$error_code] ?? "Desconocido") . ")";
            error_log("DEBUG: Error de subida PHP: " . $response['message']);
        } else {
            error_log("DEBUG: \$_FILES['image_file'] no está configurado. El input de archivo puede estar vacío o el 'name' no coincide.");
            // LÍNEA CORREGIDA ABAJO
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