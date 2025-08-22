<?php
require_once '../server.php'; // Ajusta la ruta si es necesario
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado.']);
    close_db_connection($mysqli);
    exit();
}

$user_id = $_SESSION['user_id'];

// Recibir y sanear los datos del formulario
$name = trim(htmlspecialchars(stripslashes($_POST['name'] ?? '')));
$email = trim(htmlspecialchars(stripslashes($_POST['email'] ?? '')));
$password = $_POST['password'] ?? ''; // Nueva contraseña, si se proporciona
$doctrinal_branch_id = (int)($_POST['doctrinal_branch'] ?? 0); // ID de la rama doctrinal

// Validaciones básicas
if (empty($name) || empty($email) || $doctrinal_branch_id === 0) {
    echo json_encode(['success' => false, 'message' => 'Por favor, completa todos los campos obligatorios.']);
    close_db_connection($mysqli);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Formato de correo electrónico inválido.']);
    close_db_connection($mysqli);
    exit();
}

try {
    // 1. Verificar si el nuevo correo electrónico ya existe para otro usuario
    // Solo verificar si el correo es diferente al actual del usuario
    $stmt_check_email = $mysqli->prepare("SELECT id_usuario FROM usuarios WHERE correo_usuario = ? AND id_usuario != ?");
    if ($stmt_check_email === false) {
        throw new Exception("Error al preparar la verificación de email: " . $mysqli->error);
    }
    $stmt_check_email->bind_param("si", $email, $user_id);
    $stmt_check_email->execute();
    $result_check_email = $stmt_check_email->get_result();
    if ($result_check_email->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'El correo electrónico ya está registrado por otro usuario.']);
        $stmt_check_email->close();
        close_db_connection($mysqli);
        exit();
    }
    $stmt_check_email->close();

    // 2. Construir la consulta de actualización dinámicamente
    $query_parts = [];
    $params = [];
    $types = "";

    $query_parts[] = "nombre_usuario = ?";
    $params[] = $name;
    $types .= "s";

    $query_parts[] = "correo_usuario = ?";
    $params[] = $email;
    $types .= "s";

    $query_parts[] = "id_rama = ?";
    $params[] = $doctrinal_branch_id;
    $types .= "i";

    // Si se proporcionó una nueva contraseña
    if (!empty($password)) {
        if (strlen($password) < 6) { // Validación de longitud mínima de contraseña
            echo json_encode(['success' => false, 'message' => 'La nueva contraseña debe tener al menos 6 caracteres.']);
            close_db_connection($mysqli);
            exit();
        }
        $password_hashed = password_hash($password, PASSWORD_BCRYPT);
        if ($password_hashed === false) {
            throw new Exception("Error al hashear la nueva contraseña.");
        }
        $query_parts[] = "pass_usuario = ?";
        $params[] = $password_hashed;
        $types .= "s";
    }

    $sql = "UPDATE usuarios SET " . implode(", ", $query_parts) . " WHERE id_usuario = ?";
    $params[] = $user_id; // Añadir el ID del usuario al final de los parámetros
    $types .= "i"; // Añadir el tipo para el ID del usuario

    $stmt_update = $mysqli->prepare($sql);
    if ($stmt_update === false) {
        throw new Exception("Error al preparar la consulta de actualización: " . $mysqli->error);
    }

    // Vincular parámetros
    // Utilizar el operador de expansión (...) para pasar los parámetros como argumentos individuales
    $stmt_update->bind_param($types, ...$params);

    if ($stmt_update->execute()) {
        // Actualizar la sesión si el nombre o correo electrónico cambian
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;
        $_SESSION['id_rama'] = $doctrinal_branch_id; // Aunque no se usa directamente en el display, es buena práctica

        echo json_encode(['success' => true, 'message' => 'Perfil actualizado exitosamente.']);
    } else {
        throw new Exception("Error al ejecutar la actualización del perfil: " . $stmt_update->error);
    }

    $stmt_update->close();

} catch (Exception $e) {
    error_log("Error en update_profile.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al actualizar el perfil: ' . $e->getMessage()]);
} finally {
    close_db_connection($mysqli);
}
?>