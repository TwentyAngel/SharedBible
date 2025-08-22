<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../server.php'; // Subir un nivel para encontrar server.php

// Asegúrate de que $mysqli esté disponible después de incluir server.php
// Cambiado de $conn a $mysqli para que coincida con server.php
if (!isset($mysqli) || $mysqli->connect_error) {
    die(json_encode(["error" => "Error de conexión a la base de datos: " . $mysqli->connect_error]));
}

// Calcula el día del año (1 a 365/366)
$dayOfYear = date('z') + 1;

$phraseId = ($dayOfYear % 365 == 0) ? 365 : ($dayOfYear % 365);

// Consulta SQL para obtener la frase basada en el ID calculado
$sql = "SELECT frase FROM frase WHERE id_frase = ?";
// Usar $mysqli para la preparación de la consulta
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $phraseId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo json_encode(["phrase" => '"' . addslashes($row['frase']) . '"']);
} else {
    echo json_encode(["phrase" => "No se encontró una frase para hoy. Por favor, añade más frases a la base de datos."]);
}

$stmt->close();
// Es una buena práctica cerrar la conexión cuando ya no se necesita,
// pero si server.php ya define una función close_db_connection, puedes usarla.
// close_db_connection($mysqli); // Si tu server.php tiene esta función y quieres usarla explícitamente
$mysqli->close(); // Si prefieres cerrar directamente la conexión $mysqli
?>