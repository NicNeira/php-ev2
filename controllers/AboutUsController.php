<?php
require_once 'config/database.php';
require_once 'utils/auth.php';

/**
 * Validates the structure of the About Us body data.
 *
 * This function checks if the provided data contains the expected keys and sub-keys.
 * The expected structure is:
 * - 'titulo' with sub-keys 'esp' and 'eng'
 * - 'descripcion' with sub-keys 'esp' and 'eng'
 *
 * @param array $data The data to validate.
 * 
 * @return array An associative array with:
 * - 'valid' (bool): Indicates whether the data is valid.
 * - 'message' (string, optional): Contains an error message if the data is invalid.
 */
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

  /**
   * Retrieves the "About Us" information from the database and returns it as a JSON response.
   *
   * This function executes a SQL query to fetch all records from the specified table,
   * orders them by the 'id' field, and reconstructs the data into a structured JSON format.
   * The JSON structure includes titles and descriptions in both Spanish ('esp') and English ('eng').
   *
   * @return void Outputs the JSON-encoded "About Us" data.
   */
  public function getAboutUs()
  {
    $query = "SELECT * FROM " . $this->table . " ORDER BY id";
    $stmt = $this->db->prepare($query);
    $stmt->execute();
    $aboutUs = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

  /**
   * Creates a new About Us item in the database.
   *
   * This function validates the provided data and inserts a new record into the About Us table.
   * If the data is invalid, it returns a 400 Bad Request response with an error message.
   * If the insertion is successful, it returns a 201 Created response with a success message.
   * If the insertion fails, it returns a 500 Internal Server Error response with an error message.
   *
   * @param array $data The data to create the About Us item, including:
   *                    - 'titulo' (array): Titles in different languages.
   *                      - 'esp' (string): Title in Spanish.
   *                      - 'eng' (string): Title in English.
   *                    - 'descripcion' (array): Descriptions in different languages.
   *                      - 'esp' (string): Description in Spanish.
   *                      - 'eng' (string): Description in English.
   *
   * @return void
   */
  public function createAboutUs($data)
  {

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
