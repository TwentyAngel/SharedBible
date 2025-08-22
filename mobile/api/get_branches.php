<?php
require_once '../server.php'; // Ajusta la ruta si es necesario
// session_start(); // No es estrictamente necesario iniciar sesión para esto, ya que solo devuelve datos públicos

header('Content-Type: application/json');

try {
    $stmt = $mysqli->prepare("SELECT id_rama, rama FROM ramas ORDER BY rama ASC");
    if ($stmt === false) {
        throw new Exception("Error al preparar la consulta de ramas: " . $mysqli->error);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $branches = [];
    while ($row = $result->fetch_assoc()) {
        $branches[] = $row;
    }

    echo json_encode(['success' => true, 'branches' => $branches]);

    $stmt->close();

} catch (Exception $e) {
    error_log("Error en get_branches.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al cargar las ramas doctrinales.']);
} finally {
    close_db_connection($mysqli);
}
?>