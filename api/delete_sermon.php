<?php
// api/delete_sermon.php
require_once '../server.php';

session_start();
header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'Ocurrió un error inesperado.'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_SESSION['user_id'])) {
        $response['message'] = 'Usuario no autenticado.';
        echo json_encode($response);
        close_db_connection($mysqli);
        exit();
    }

    $id_usuario_actual = $_SESSION['user_id'];
    $id_predi_a_eliminar = $_POST['id_predi'] ?? null;

    if (empty($id_predi_a_eliminar)) {
        $response['message'] = 'ID de predicación no proporcionado.';
        echo json_encode($response);
        close_db_connection($mysqli);
        exit();
    }

    // Primero, verificar que el usuario actual es el autor de la predicación
    $stmt_check_owner = $mysqli->prepare("SELECT id_usuario FROM predicaciones WHERE id_predi = ?");
    if ($stmt_check_owner) {
        $stmt_check_owner->bind_param("i", $id_predi_a_eliminar);
        $stmt_check_owner->execute();
        $result_check_owner = $stmt_check_owner->get_result();
        
        if ($result_check_owner->num_rows > 0) {
            $sermon_data = $result_check_owner->fetch_assoc();
            $owner_id = $sermon_data['id_usuario'];

            if ($owner_id != $id_usuario_actual) {
                $response['message'] = 'Permiso denegado: No puedes eliminar predicaciones de otros usuarios.';
                echo json_encode($response);
                close_db_connection($mysqli);
                exit();
            }
        } else {
            $response['message'] = 'La predicación no existe.';
            echo json_encode($response);
            close_db_connection($mysqli);
            exit();
        }
        $stmt_check_owner->close();
    } else {
        $response['message'] = 'Error al preparar la verificación de propietario: ' . $mysqli->error;
        echo json_encode($response);
        close_db_connection($mysqli);
        exit();
    }

    // Si todo está bien, proceder con la eliminación
    $stmt_delete = $mysqli->prepare("DELETE FROM predicaciones WHERE id_predi = ?");
    if ($stmt_delete) {
        $stmt_delete->bind_param("i", $id_predi_a_eliminar);

        if ($stmt_delete->execute()) {
            $response = ['status' => 'success', 'message' => 'Predicación eliminada exitosamente.'];
        } else {
            $response['message'] = 'Error al eliminar la predicación: ' . $mysqli->error;
        }
        $stmt_delete->close();
    } else {
        $response['message'] = 'Error al preparar la consulta de eliminación: ' . $mysqli->error;
    }

} else {
    $response['message'] = 'Método de solicitud no permitido.';
}

echo json_encode($response);
close_db_connection($mysqli);
?>