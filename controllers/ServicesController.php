<?php
require_once 'config/database.php';
require_once 'utils/auth.php';

/**
 * Validates the structure of the service body data.
 *
 * This function checks if the provided data array contains the expected keys and subkeys.
 * The expected structure is:
 * - 'titulo' (array with keys 'esp' and 'eng')
 * - 'descripcion' (array with keys 'esp' and 'eng')
 * - 'activo' (simple key)
 *
 * @param array $data The data array to validate.
 * @return array An associative array with 'valid' (boolean) indicating if the data is valid,
 *               and 'message' (string) providing details in case of invalid data.
 */
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

  /**
   * Retrieves all services from the database, reconstructs the JSON structure, and outputs it.
   *
   * This function executes a SQL query to fetch all records from the services table,
   * orders them by the 'id' field, and then reconstructs the JSON structure to include
   * titles and descriptions in both Spanish and English. It also converts the 'activo'
   * field to a boolean value.
   *
   * @return void Outputs the JSON-encoded data of services.
   */
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

  /**
   * Create a new service.
   *
   * This method validates the request body and creates a new service in the database
   * if the validation passes. It returns appropriate HTTP response codes and messages
   * based on the outcome of the operation.
   *
   * @param array $data The data for the new service, including:
   *                    - 'titulo' (array): The title of the service in different languages.
   *                      - 'esp' (string): The title in Spanish.
   *                      - 'eng' (string): The title in English.
   *                    - 'descripcion' (array): The description of the service in different languages.
   *                      - 'esp' (string): The description in Spanish.
   *                      - 'eng' (string): The description in English.
   *                    - 'activo' (bool): The active status of the service.
   *
   * @return void
   */
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
