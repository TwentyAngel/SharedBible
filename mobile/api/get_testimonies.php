<?php
// error_reporting(0); // Suprimir la visualización de errores PHP en la salida para producción
// Para depuración, habilita los errores:
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../server.php'; // Asegúrate de que esta ruta sea correcta
session_start();
header('Content-Type: application/json');

// Lógica para solicitudes GET (cargar testimonios)
if ($_SERVER["REQUEST_METHOD"] == "GET") {
    $query = "SELECT t.contenido_testimonio, t.fecha_testimonio, t.vURL_testimonio AS youtube_url, u.nombre_usuario
              FROM testimonios t
              JOIN usuarios u ON t.id_usuario = u.id_usuario
              ORDER BY t.id_testimonio DESC";

    $result = $mysqli->query($query);

    $testimonios = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $formatted_date = 'Fecha desconocida'; // Valor por defecto si hay un problema
            
            // Asegúrate de que 'fecha_testimonio' exista y no esté vacío antes de intentar formatear
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
                    // Si hay un error al parsear la fecha, lo registramos
                    error_log("Error al formatear fecha de testimonio: " . $e->getMessage() . " para la fecha: " . $row['fecha_testimonio']);
                    $formatted_date = 'Error al formatear fecha'; // Puedes cambiar esto por lo que prefieras mostrar
                }
            }
            
            $row['fecha_formateada'] = $formatted_date;
            $testimonios[] = $row;
        }
        echo json_encode(['success' => true, 'testimonies' => $testimonios]);
        $result->free();
    } else {
        error_log("Error al obtener testimonios (GET): " . $mysqli->error);
        echo json_encode(['success' => false, 'message' => 'Error al cargar los testimonios.']);
    }
    close_db_connection($mysqli);
    exit();

}
// Lógica para solicitudes POST (añadir un testimonio)
else if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión para compartir un testimonio.']);
        close_db_connection($mysqli);
        exit();
    }

    $id_usuario = $_SESSION['user_id'];
    $contenido = trim(htmlspecialchars(stripslashes($_POST['testimony_content'] ?? '')));
    $youtube_url = trim(htmlspecialchars(stripslashes($_POST['youtube_url'] ?? '')));

    if (empty($contenido)) {
        echo json_encode(['success' => false, 'message' => 'Por favor, escribe tu testimonio.']);
        close_db_connection($mysqli);
        exit();
    }

    if (!empty($youtube_url) && !filter_var($youtube_url, FILTER_VALIDATE_URL)) {
        echo json_encode(['success' => false, 'message' => 'Por favor, introduce una URL de YouTube válida o déjala vacía.']);
        close_db_connection($mysqli);
        exit();
    }

    // *** CORRECCIÓN CLAVE AQUÍ: Incluir 'fecha_testimonio' y usar NOW() ***
    $stmt = $mysqli->prepare("INSERT INTO testimonios (id_usuario, contenido_testimonio, vURL_testimonio, fecha_testimonio) VALUES (?, ?, ?, NOW())");
    if ($stmt === false) {
        error_log("Error al preparar la consulta de inserción de testimonio (POST): " . $mysqli->error);
        echo json_encode(['success' => false, 'message' => 'Error interno del servidor al guardar el testimonio.']);
        close_db_connection($mysqli);
        exit();
    }

    // Asegúrate de que el número de 's' en bind_param coincida con el número de placeholders (?)
    // 'i' para id_usuario (integer), 's' para contenido (string), 's' para youtube_url (string)
    // NOW() no necesita un placeholder, ya que es una función de MySQL.
    $stmt->bind_param("iss", $id_usuario, $contenido, $youtube_url);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => '¡Testimonio enviado con éxito!']);
    } else {
        error_log("Error al ejecutar la inserción de testimonio (POST): " . $stmt->error);
        echo json_encode(['success' => false, 'message' => 'Error al guardar el testimonio. Inténtalo de nuevo.']);
    }

    $stmt->close();
    close_db_connection($mysqli);
    exit();

}
// Si el método no es GET ni POST
else {
    echo json_encode(['success' => false, 'message' => 'Método de solicitud no permitido para esta operación (solo GET o POST).']);
    close_db_connection($mysqli);
    exit();
}
?>