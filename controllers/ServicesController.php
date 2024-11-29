<?php
require_once 'config/database.php';
require_once 'utils/auth.php';

class ServicesController
{
  private $db;
  private $table = 'services';

  public function __construct()
  {
    $database = new Database();
    $this->db = $database->getConnection();
  }

  public function getServices()
  {
    $query = "SELECT * FROM " . $this->table . " ORDER BY id";
    $stmt = $this->db->prepare($query);
    $stmt->execute();
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Reconstruir la estructura del JSON
    $data = [];
    foreach ($services as $service) {
      $data[] = [
        'id' => (int)$service['id'],
        'titulo' => [
          'esp' => $service['titulo_esp'],
          'eng' => $service['titulo_eng']
        ],
        'descripcion' => [
          'esp' => $service['descripcion_esp'],
          'eng' => $service['descripcion_eng']
        ],
        'activo' => $service['activo'] === 't' ? true : false
      ];
    }

    echo json_encode(['data' => $data]);
  }

  public function createService($data)
  {
    $query = "INSERT INTO " . $this->table . " (titulo_esp, titulo_eng, descripcion_esp, descripcion_eng, activo)
                  VALUES (:titulo_esp, :titulo_eng, :descripcion_esp, :descripcion_eng, :activo)";
    $stmt = $this->db->prepare($query);
    $stmt->bindParam(':titulo_esp', $data['titulo']['esp']);
    $stmt->bindParam(':titulo_eng', $data['titulo']['eng']);
    $stmt->bindParam(':descripcion_esp', $data['descripcion']['esp']);
    $stmt->bindParam(':descripcion_eng', $data['descripcion']['eng']);
    $stmt->bindParam(':activo', $data['activo'], PDO::PARAM_BOOL);

    if ($stmt->execute()) {
      http_response_code(201);
      echo json_encode(['message' => 'Service created successfully']);
    } else {
      http_response_code(500);
      echo json_encode(['message' => 'Failed to create service']);
    }
  }
}
