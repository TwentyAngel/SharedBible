<?php
// error_reporting(0); // Para producción
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../server.php'; // Ajusta la ruta si es necesario
session_start();
header('Content-Type: application/json');

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado.']);
    close_db_connection($mysqli);
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // Seleccionar los testimonios del usuario actual
    // Asumo que tu tabla 'testimonios' tiene 'id_testimonio' y 'fecha_testimonio'
    // Y que 'vURL_testimonio' es el nombre real de la columna para la URL de YouTube
    $stmt = $mysqli->prepare("SELECT id_testimonio, contenido_testimonio, fecha_testimonio, vURL_testimonio AS youtube_url
                              FROM testimonios
                              WHERE id_usuario = ?
                              ORDER BY id_testimonio DESC"); // Ordenar por ID para el más reciente primero

    if ($stmt === false) {
        throw new Exception("Error al preparar la consulta de testimonios: " . $mysqli->error);
    }

    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $testimonies = [];
    while ($row = $result->fetch_assoc()) {
        $formatted_date = 'Fecha desconocida';
        if (isset($row['fecha_testimonio']) && !empty($row['fecha_testimonio']) && $row['fecha_testimonio'] !== '0000-00-00 00:00:00') {
            try {
                $date = new DateTime($row['fecha_testimonio']);
                $meses = [
                    'January' => 'Enero', 'February' => 'Febrero', 'March' => 'Marzo', 'April' => 'Abril',
                    'May' => 'Mayo', 'June' => 'Junio', 'July' => 'Julio', 'August' => 'Agosto',
                    'September' => 'Septiembre', 'October' => 'Octubre', 'November' => 'Noviembre', 'December' => 'Diciembre'
                ];
                $formatted_date = str_replace(array_keys($meses), array_values($meses), $date->format('d \d\e F, Y'));
            } catch (Exception $e) {
                error_log("Error al formatear fecha de testimonio (get_user_testimonies): " . $e->getMessage() . " para la fecha: " . $row['fecha_testimonio']);
                $formatted_date = 'Error al formatear fecha';
            }
        }
        $row['fecha_formateada'] = $formatted_date;
        $testimonies[] = $row;
    }

    echo json_encode(['success' => true, 'testimonies' => $testimonies]);

    $stmt->close();

} catch (Exception $e) {
    error_log("Error en get_user_testimonies.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al cargar tus testimonios.']);
} finally {
    close_db_connection($mysqli);
}
?>