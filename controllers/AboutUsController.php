<?php
require_once 'config/database.php';
require_once 'utils/auth.php';

function validateAboutUsBody($data)
{
  $expectedKeys = [
    'titulo' => ['esp', 'eng'],
    'descripcion' => ['esp', 'eng']
  ];

  foreach ($expectedKeys as $key => $subKeys) {
    if (!isset($data[$key]) || !is_array($data[$key])) {
      return ['valid' => false, 'message' => "Missing or invalid key: $key"];
    }

    foreach ($subKeys as $subKey) {
      if (!isset($data[$key][$subKey])) {
        return ['valid' => false, 'message' => "Missing key: $key.$subKey"];
      }
    }
  }

  return ['valid' => true];
}

class AboutUsController
{
  private $db;
  private $table = 'about_us';

  public function __construct()
  {
    $database = new Database();
    $this->db = $database->getConnection();
  }

  public function getAboutUs()
  {
    $query = "SELECT * FROM " . $this->table . " ORDER BY id";
    $stmt = $this->db->prepare($query);
    $stmt->execute();
    $aboutUs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Reconstruir la estructura del JSON
    $data = [];
    foreach ($aboutUs as $item) {
      $data[] = [
        'titulo' => [
          'esp' => $item['titulo_esp'],
          'eng' => $item['titulo_eng']
        ],
        'descripcion' => [
          'esp' => $item['descripcion_esp'],
          'eng' => $item['descripcion_eng']
        ]
      ];
    }

    echo json_encode(['data' => $data]);
  }

  public function createAboutUs($data)
  {

    // Validar el cuerpo de la solicitud
    $validation = validateAboutUsBody($data);
    if (!$validation['valid']) {
      http_response_code(400); // Bad Request
      echo json_encode(['message' => $validation['message']]);
      return;
    }

    $query = "INSERT INTO " . $this->table . " (titulo_esp, titulo_eng, descripcion_esp, descripcion_eng)
                  VALUES (:titulo_esp, :titulo_eng, :descripcion_esp, :descripcion_eng)";
    $stmt = $this->db->prepare($query);
    $stmt->bindParam(':titulo_esp', $data['titulo']['esp']);
    $stmt->bindParam(':titulo_eng', $data['titulo']['eng']);
    $stmt->bindParam(':descripcion_esp', $data['descripcion']['esp']);
    $stmt->bindParam(':descripcion_eng', $data['descripcion']['eng']);

    if ($stmt->execute()) {
      http_response_code(201);
      echo json_encode(['message' => 'About Us item created successfully']);
    } else {
      http_response_code(500);
      echo json_encode(['message' => 'Failed to create About Us item']);
    }
  }
}
