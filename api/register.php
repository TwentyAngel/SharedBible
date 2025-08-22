<?php
// Incluir el archivo de conexión a la base de datos
require_once '../server.php';

// Iniciar sesión (necesario para manejar mensajes de éxito/error si se usan sesiones)
session_start();

// Establecer la cabecera para devolver JSON
header('Content-Type: application/json');

// Verificar si la solicitud es POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Recibir y sanear los datos del formulario
    // trim() elimina espacios en blanco al inicio y final
    // htmlspecialchars() convierte caracteres especiales en entidades HTML para evitar XSS
    // stripslashes() elimina barras invertidas si magic_quotes_gpc está activado (obsoleto, pero buena práctica defensiva)
    $nombre_usuario = trim(htmlspecialchars(stripslashes($_POST['name'] ?? '')));
    $email_usuario = trim(htmlspecialchars(stripslashes($_POST['email'] ?? '')));
    $pass_usuario = $_POST['password'] ?? ''; // No sanear la contraseña antes de hashearla
    $confirm_pass = $_POST['confirm_password'] ?? '';
    $rama_nombre = trim(htmlspecialchars(stripslashes($_POST['doctrinal_branch'] ?? '')));

    // --- Validaciones de entrada ---

    // 1. Campos vacíos
    if (empty($nombre_usuario) || empty($email_usuario) || empty($pass_usuario) || empty($confirm_pass) || empty($rama_nombre)) {
        echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios.']);
        // Asegúrate de cerrar la conexión si no se va a usar más
        close_db_connection($mysqli);
        exit();
    }

    // 2. Formato de email
    if (!filter_var($email_usuario, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Formato de correo electrónico inválido.']);
        close_db_connection($mysqli);
        exit();
    }

    // 3. Coincidencia de contraseñas
    if ($pass_usuario !== $confirm_pass) {
        echo json_encode(['success' => false, 'message' => 'Las contraseñas no coinciden.']);
        close_db_connection($mysqli);
        exit();
    }

    // 4. Longitud de la contraseña (mínimo 8 caracteres, puedes ajustar)
    if (strlen($pass_usuario) < 8) {
        echo json_encode(['success' => false, 'message' => 'La contraseña debe tener al menos 8 caracteres.']);
        close_db_connection($mysqli);
        exit();
    }

    // 5. Verificar si el email ya existe
    // Preparamos la consulta para evitar inyección SQL
    $stmt_check_email = $mysqli->prepare("SELECT id_usuario FROM usuarios WHERE correo_usuario = ?");
    if ($stmt_check_email === false) {
        error_log("Error al preparar la consulta de email existente: " . $mysqli->error);
        echo json_encode(['success' => false, 'message' => 'Error interno del servidor al verificar el correo.']);
        close_db_connection($mysqli);
        exit();
    }
    $stmt_check_email->bind_param("s", $email_usuario);
    $stmt_check_email->execute();
    $stmt_check_email->store_result(); // Almacenar el resultado para poder usar num_rows

    if ($stmt_check_email->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Este correo electrónico ya está registrado.']);
        $stmt_check_email->close();
        close_db_connection($mysqli);
        exit();
    }
    $stmt_check_email->close();

    // 6. Obtener el ID de la rama doctrinal
    $stmt_get_rama = $mysqli->prepare("SELECT id_rama FROM ramas WHERE rama = ?");
    if ($stmt_get_rama === false) {
        error_log("Error al preparar la consulta de rama doctrinal: " . $mysqli->error);
        echo json_encode(['success' => false, 'message' => 'Error interno del servidor al obtener la rama doctrinal.']);
        close_db_connection($mysqli);
        exit();
    }
    $stmt_get_rama->bind_param("s", $rama_nombre);
    $stmt_get_rama->execute();
    $stmt_get_rama->bind_result($id_rama);
    $stmt_get_rama->fetch();
    $stmt_get_rama->close();

    if (empty($id_rama)) {
        echo json_encode(['success' => false, 'message' => 'La rama doctrinal seleccionada no es válida.']);
        close_db_connection($mysqli);
        exit();
    }

    // --- Fin de Validaciones ---

    // Hashear la contraseña de forma segura (usando PASSWORD_BCRYPT como se recomendó)
    $password_hashed = password_hash($pass_usuario, PASSWORD_BCRYPT);
    // password_hash() retorna false en caso de error. Se recomienda verificar.
    if ($password_hashed === false) {
        error_log("Error al hashear la contraseña.");
        echo json_encode(['success' => false, 'message' => 'Error interno del servidor al procesar la contraseña.']);
        close_db_connection($mysqli);
        exit();
    }

    // Insertar el nuevo usuario en la base de datos
    // 'predi_usuario' se establece como 'N' por defecto para nuevos registros
    $predi_usuario_default = 'N'; // O el valor por defecto que desees

    $stmt_insert_user = $mysqli->prepare("INSERT INTO usuarios (nombre_usuario, correo_usuario, pass_usuario, predi_usuario, id_rama) VALUES (?, ?, ?, ?, ?)");
    if ($stmt_insert_user === false) {
        error_log("Error al preparar la consulta de inserción de usuario: " . $mysqli->error);
        echo json_encode(['success' => false, 'message' => 'Error interno del servidor al registrar el usuario.']);
        close_db_connection($mysqli);
        exit();
    }

    $stmt_insert_user->bind_param("ssssi", $nombre_usuario, $email_usuario, $password_hashed, $predi_usuario_default, $id_rama);

    if ($stmt_insert_user->execute()) {
        echo json_encode(['success' => true, 'message' => '¡Registro exitoso! Ahora puedes iniciar sesión.']);
    } else {
        error_log("Error al ejecutar la inserción de usuario: " . $stmt_insert_user->error);
        echo json_encode(['success' => false, 'message' => 'Error al registrar el usuario. Inténtalo de nuevo más tarde.']);
    }

    $stmt_insert_user->close();

} else {
    // Si la solicitud no es POST
    echo json_encode(['success' => false, 'message' => 'Método de solicitud no permitido.']);
}

// Cerrar la conexión a la base de datos
close_db_connection($mysqli);
?>