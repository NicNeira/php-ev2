<?php
require_once 'config/database.php';
require_once 'utils/auth.php';

function validateServiceBody($data)
{
  // Definir la estructura esperada
  $expectedKeys = [
    'titulo' => ['esp', 'eng'],
    'descripcion' => ['esp', 'eng'],
    'activo'
  ];

  // Verificar claves principales
  foreach ($expectedKeys as $key => $subKeys) {
    if (is_array($subKeys)) {
      // Verificar si la clave compuesta existe
      if (!isset($data[$key]) || !is_array($data[$key])) {
        return ['valid' => false, 'message' => "Missing or invalid key: $key"];
      }
      // Verificar subclaves
      foreach ($subKeys as $subKey) {
        if (!isset($data[$key][$subKey])) {
          return ['valid' => false, 'message' => "Missing key: $key.$subKey"];
        }
      }
    } else {
      // Verificar claves simples
      if (!array_key_exists($subKeys, $data)) {
        return ['valid' => false, 'message' => "Missing key: $subKeys"];
      }
    }
  }

  return ['valid' => true];
}

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
    // Validar el cuerpo de la solicitud
    $validation = validateServiceBody($data);

    if (!$validation['valid']) {
      http_response_code(400); // Bad Request
      echo json_encode(['message' => $validation['message']]);
      return;
    }

    // Proceder con la creación del servicio si pasa la validación
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
      http_response_code(500); // Internal Server Error
      echo json_encode(['message' => 'Failed to create service']);
    }
  }
}
