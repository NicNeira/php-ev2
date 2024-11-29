<?php
// import_about_us.php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

$jsonData = file_get_contents('aboutData.json');
$data = json_decode($jsonData, true)['data'];

foreach ($data as $item) {
    $query = "INSERT INTO about_us (titulo_esp, titulo_eng, descripcion_esp, descripcion_eng)
              VALUES (:titulo_esp, :titulo_eng, :descripcion_esp, :descripcion_eng)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':titulo_esp', $item['titulo']['esp']);
    $stmt->bindParam(':titulo_eng', $item['titulo']['eng']);
    $stmt->bindParam(':descripcion_esp', $item['descripcion']['esp']);
    $stmt->bindParam(':descripcion_eng', $item['descripcion']['eng']);

    if ($stmt->execute()) {
        echo "Elemento de About Us importado exitosamente.\n";
    } else {
        $errorInfo = $stmt->errorInfo();
        echo "Error al importar un elemento de About Us: {$errorInfo[2]}\n";
    }
}
