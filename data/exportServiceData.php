<?php
// import_services.php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

$jsonData = file_get_contents('serviceData.json');
$data = json_decode($jsonData, true)['data'];

foreach ($data as $service) {
    $query = "INSERT INTO services (id, titulo_esp, titulo_eng, descripcion_esp, descripcion_eng, activo)
              VALUES (:id, :titulo_esp, :titulo_eng, :descripcion_esp, :descripcion_eng, :activo)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $service['id'], PDO::PARAM_INT);
    $stmt->bindParam(':titulo_esp', $service['titulo']['esp']);
    $stmt->bindParam(':titulo_eng', $service['titulo']['eng']);
    $stmt->bindParam(':descripcion_esp', $service['descripcion']['esp']);
    $stmt->bindParam(':descripcion_eng', $service['descripcion']['eng']);
    $stmt->bindParam(':activo', $service['activo'], PDO::PARAM_BOOL);

    if ($stmt->execute()) {
        echo "Servicio con ID {$service['id']} importado exitosamente.\n";
    } else {
        $errorInfo = $stmt->errorInfo();
        echo "Error al importar el servicio con ID {$service['id']}: {$errorInfo[2]}\n";
    }
}
