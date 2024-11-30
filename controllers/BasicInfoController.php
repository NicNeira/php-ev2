<?php
require_once 'config/database.php';
require_once 'utils/auth.php';

/**
 * Validate the basic information body.
 *
 * @param array $data The data to validate.
 * @return array The validation result.
 */
function validateBasicInfoBody($data)
{
  if (!isset($data['tipo'])) {
    return ['valid' => false, 'message' => 'Missing key: tipo'];
  }

  $tipo = $data['tipo'];
  $validTypes = ['menu-principal', 'hero', 'contacto', 'rrss'];

  if (!in_array($tipo, $validTypes)) {
    return ['valid' => false, 'message' => "Invalid tipo: $tipo. Expected values are: " . implode(', ', $validTypes)];
  }

  if (!isset($data['activo'])) {
    return ['valid' => false, 'message' => 'Missing key: activo'];
  }

  if (!is_bool($data['activo'])) {
    return ['valid' => false, 'message' => 'Invalid value for activo. Must be a boolean'];
  }

  switch ($tipo) {
    case 'menu-principal':
      if (!isset($data['items']) || !is_array($data['items'])) {
        return ['valid' => false, 'message' => 'Missing or invalid key: items for menu-principal'];
      }
      break;

    case 'hero':
      if (!isset($data['titulo']) || !isset($data['parrafo'])) {
        return ['valid' => false, 'message' => 'Missing keys: titulo or parrafo for hero'];
      }
      break;

    case 'contacto':
    case 'rrss':
      if (!isset($data['items']) || !is_array($data['items'])) {
        return ['valid' => false, 'message' => 'Missing or invalid key: items for ' . $tipo];
      }
      break;
  }

  return ['valid' => true];
}
class BasicInfoController
{
  private $db;

  public function __construct()
  {
    $database = new Database();
    $this->db = $database->getConnection();
  }

  /**
   * Retrieves basic information from the database and processes it based on its type.
   *
   * This function fetches all records from the `basic_info` table, processes each record
   * according to its type, and returns the processed data in JSON format. The types of
   * `basic_info` include 'menu-principal', 'hero', 'contacto', and 'rrss', each of which
   * is handled differently.
   *
   * @return void Outputs JSON encoded data or an error message.
   */
  public function getBasicInfo()
  {
    try {
      // Obtener toda la información básica
      $query = "SELECT * FROM basic_info ORDER BY id";
      $stmt = $this->db->prepare($query);
      $stmt->execute();
      $basicInfos = $stmt->fetchAll(PDO::FETCH_ASSOC);

      $data = [];

      foreach ($basicInfos as $basicInfo) {
        $tipo = $basicInfo['tipo'];
        $item = [
          'tipo' => $tipo,
          'activo' => $basicInfo['activo'] === 't' || $basicInfo['activo'] === true || $basicInfo['activo'] === '1' ? true : false

        ];

        // Procesar cada tipo de `basic_info`
        switch ($tipo) {
          case 'menu-principal':
            $item['items'] = $this->getMenuItems($basicInfo['id']);
            break;

          case 'hero':
            $item['heroes'] = $this->getAllHeroRecords($basicInfo['id']);
            break;

          case 'contacto':
            $item['items'] = $this->getContactItems($basicInfo['id']);
            break;

          case 'rrss':
            $item['items'] = $this->getSocialMediaItems($basicInfo['id']);
            break;
        }

        $data[] = $item;
      }

      // Enviar la respuesta en formato JSON
      echo json_encode(['data' => $data]);
    } catch (PDOException $e) {
      http_response_code(500);
      echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
    }
  }

  /**
   * Retrieves menu items for a given basic info ID and organizes them by language.
   *
   * @param int $basic_info_id The ID of the basic info to retrieve menu items for.
   * @return array An associative array with keys 'esp' and 'eng', each containing an array of menu items.
   *               Each menu item is an associative array with the following keys:
   *               - 'link': The link of the menu item.
   *               - 'texto': The text of the menu item.
   *               - 'activo': A boolean indicating whether the menu item is active.
   */
  private function getMenuItems($basic_info_id)
  {
    $query = "SELECT * FROM menu_items WHERE basic_info_id = :id ORDER BY id";
    $stmt = $this->db->prepare($query);
    $stmt->bindParam(':id', $basic_info_id);
    $stmt->execute();
    $menuItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $items = ['esp' => [], 'eng' => []];
    foreach ($menuItems as $menuItem) {
      $language = $menuItem['language'];
      $items[$language][] = [
        'link' => $menuItem['link'],
        'texto' => $menuItem['texto'],
        'activo' => $menuItem['activo'] === 't' || $menuItem['activo'] === true || $menuItem['activo'] === '1' ? true : false

      ];
    }
    return $items;
  }



  /**
   * Retrieves all hero records associated with a given basic info ID.
   *
   * This function executes a SQL query to fetch hero information from the database
   * based on the provided basic info ID. It then processes the results to ensure
   * that the 'activo' field is correctly converted to a boolean value.
   *
   * @param int $basicInfoId The ID of the basic info record to retrieve hero records for.
   * @return array An array of associative arrays, each containing hero information.
   *               The 'activo' field is converted to a boolean value.
   */
  private function getAllHeroRecords($basicInfoId)
  {
    $query = "SELECT titulo_esp, titulo_eng, parrafo_esp, parrafo_eng, activo 
              FROM hero_info WHERE basic_info_id = :id";
    $stmt = $this->db->prepare($query);
    $stmt->bindParam(':id', $basicInfoId);
    $stmt->execute();
    $heroes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Convertir valores booleanos correctamente
    foreach ($heroes as &$hero) {
      $hero['activo'] = $hero['activo'] === 't' || $hero['activo'] === true || $hero['activo'] === '1';
    }

    return $heroes;
  }

  /**
   * Retrieves hero paragraphs in both Spanish and English for a given basic info ID.
   *
   * @param int $basic_info_id The ID of the basic info to retrieve hero paragraphs for.
   * @return array An associative array containing the hero paragraphs with keys 'esp' for Spanish and 'eng' for English.
   */
  private function getHeroParagraphs($basic_info_id)
  {
    $query = "SELECT parrafo_esp, parrafo_eng FROM hero_info WHERE basic_info_id = :id";
    $stmt = $this->db->prepare($query);
    $stmt->bindParam(':id', $basic_info_id);
    $stmt->execute();
    $heroParagraphs = $stmt->fetch(PDO::FETCH_ASSOC);

    return [
      'esp' => $heroParagraphs['parrafo_esp'],
      'eng' => $heroParagraphs['parrafo_eng']
    ];
  }


  /**
   * Retrieves contact items associated with a given basic information ID.
   *
   * This function queries the database for contact items that are linked to the specified
   * basic information ID. It then processes the results and returns an array of contact items
   * with their type, value, and active status.
   *
   * @param int $basic_info_id The ID of the basic information record to retrieve contact items for.
   * @return array An array of contact items, where each item is an associative array containing:
   *               - 'tipo' (string): The type of the contact item.
   *               - 'valor' (string): The value of the contact item.
   *               - 'activo' (bool): The active status of the contact item.
   */
  private function getContactItems($basic_info_id)
  {
    $query = "SELECT * FROM contact_items WHERE basic_info_id = :id ORDER BY id";
    $stmt = $this->db->prepare($query);
    $stmt->bindParam(':id', $basic_info_id);
    $stmt->execute();
    $contactItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $items = [];
    foreach ($contactItems as $contactItem) {
      $items[] = [
        'tipo' => $contactItem['tipo'],
        'valor' => $contactItem['valor'],
        'activo' => $contactItem['activo'] === 't' || $contactItem['activo'] === true || $contactItem['activo'] === '1' ? true : false
      ];
    }
    return $items;
  }


  /**
   * Retrieves social media items associated with a given basic information ID.
   *
   * This function queries the database for social media items linked to the specified
   * basic information ID, processes the results, and returns an array of social media items.
   *
   * @param int $basic_info_id The ID of the basic information record to retrieve social media items for.
   * @return array An array of social media items, each containing:
   *               - 'rrss': The social media name.
   *               - 'icono': The icon associated with the social media.
   *               - 'link': The link to the social media profile.
   *               - 'activo': A boolean indicating whether the social media item is active.
   */
  private function getSocialMediaItems($basic_info_id)
  {
    $query = "SELECT * FROM social_media_items WHERE basic_info_id = :id ORDER BY id";
    $stmt = $this->db->prepare($query);
    $stmt->bindParam(':id', $basic_info_id);
    $stmt->execute();
    $socialMediaItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $items = [];
    foreach ($socialMediaItems as $socialItem) {
      $items[] = [
        'rrss' => $socialItem['rrss'],
        'icono' => $socialItem['icono'],
        'link' => $socialItem['link'],
        'activo' => $socialItem['activo'] === 't' || $socialItem['activo'] === true || $socialItem['activo'] === '1' ? true : false
      ];
    }
    return $items;
  }



  /**
   * Adds items to the basic_info table based on the provided data.
   *
   * This function checks if a record exists in the basic_info table with the specified type and active status.
   * If a record is found, it adds related data to the appropriate table based on the type.
   * Currently, it supports adding data to the hero_info table for the 'hero' type.
   *
   * @param array $data An associative array containing the following keys:
   *  - 'tipo' (string): The type of the basic_info record.
   *  - 'activo' (bool): The active status of the basic_info record.
   *  - 'titulo' (string): The title for the hero_info record (required if 'tipo' is 'hero').
   *  - 'parrafo' (string): The paragraph for the hero_info record (required if 'tipo' is 'hero').
   *
   * @return void Outputs a JSON response with the result of the operation.
   *
   * @throws PDOException If there is a database error.
   */
  public function addItemsToBasicInfo($data)
  {
    try {
      // Verificar si existe un registro en basic_info con el tipo especificado
      $query = "SELECT id FROM basic_info WHERE tipo = :tipo AND activo = :activo";
      $stmt = $this->db->prepare($query);
      $stmt->bindParam(':tipo', $data['tipo']);
      $stmt->bindParam(':activo', $data['activo'], PDO::PARAM_BOOL);
      $stmt->execute();
      $basicInfo = $stmt->fetch(PDO::FETCH_ASSOC);

      if (!$basicInfo) {
        http_response_code(404); // Not Found
        echo json_encode(['message' => 'No record found for the given tipo and activo.']);
        return;
      }

      $basicInfoId = $basicInfo['id']; // Usar el ID existente de basic_info

      // Insertar datos relacionados según el tipo
      switch ($data['tipo']) {
        case 'hero':
          // Agregar un nuevo registro en hero_info
          $this->createHeroInfo($basicInfoId, $data['titulo'], $data['parrafo']);
          break;

        default:
          http_response_code(400); // Bad Request
          echo json_encode(['message' => 'Invalid tipo for adding items.']);
          return;
      }

      http_response_code(201); // Created
      echo json_encode(['message' => 'Hero added successfully.', 'basic_info_id' => $basicInfoId]);
    } catch (PDOException $e) {
      http_response_code(500); // Internal Server Error
      echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
    }
  }


  /**
   * Creates menu items for a given basic information ID.
   *
   * This function inserts multiple menu items into the `menu_items` table
   * for a specified basic information ID. It iterates through the provided
   * items, which are grouped by language, and inserts each menu item with
   * its corresponding details.
   *
   * @param int $basicInfoId The ID of the basic information to associate with the menu items.
   * @param array $items An associative array of menu items grouped by language. 
   *                     Each language key contains an array of menu items, where each menu item 
   *                     is an associative array with the following keys:
   *                     - 'link': The URL link of the menu item.
   *                     - 'texto': The text description of the menu item.
   *                     - 'activo': A boolean indicating whether the menu item is active.
   *
   * @return void
   */
  private function createMenuItems($basicInfoId, $items)
  {
    $query = "INSERT INTO menu_items (basic_info_id, language, link, texto, activo) VALUES (:id, :language, :link, :texto, :activo)";
    $stmt = $this->db->prepare($query);
    foreach ($items as $language => $menuItems) {
      foreach ($menuItems as $menuItem) {
        $stmt->bindParam(':id', $basicInfoId);
        $stmt->bindParam(':language', $language);
        $stmt->bindParam(':link', $menuItem['link']);
        $stmt->bindParam(':texto', $menuItem['texto']);
        $stmt->bindParam(':activo', $menuItem['activo'], PDO::PARAM_BOOL);
        $stmt->execute();
      }
    }
  }




  /**
   * Inserts a new hero information record into the hero_info table.
   *
   * @param int $basicInfoId The ID of the basic information record.
   * @param array $titulo An associative array containing the title in Spanish ('esp') and English ('eng').
   * @param array $parrafo An associative array containing the paragraph in Spanish ('esp') and English ('eng').
   * @throws Exception If there is an error executing the insert statement.
   */
  private function createHeroInfo($basicInfoId, $titulo, $parrafo)
  {
    $query = "INSERT INTO hero_info (basic_info_id, titulo_esp, titulo_eng, parrafo_esp, parrafo_eng, activo)
              VALUES (:id, :tituloEsp, :tituloEng, :parrafoEsp, :parrafoEng, :activo)";
    $stmt = $this->db->prepare($query);
    $stmt->bindParam(':id', $basicInfoId);
    $stmt->bindParam(':tituloEsp', $titulo['esp']);
    $stmt->bindParam(':tituloEng', $titulo['eng']);
    $stmt->bindParam(':parrafoEsp', $parrafo['esp']);
    $stmt->bindParam(':parrafoEng', $parrafo['eng']);
    $stmt->bindValue(':activo', true, PDO::PARAM_BOOL);

    if (!$stmt->execute()) {
      throw new Exception("Error al insertar en hero_info: " . implode(", ", $stmt->errorInfo()));
    }
  }

  /**
   * Inserts multiple contact items into the contact_items table.
   *
   * @param int $basicInfoId The ID of the basic information record to associate with the contact items.
   * @param array $items An array of contact items, where each item is an associative array with keys:
   *                     - 'tipo' (string): The type of contact item.
   *                     - 'valor' (string): The value of the contact item.
   *                     - 'activo' (bool): Whether the contact item is active.
   *
   * @return void
   */
  private function createContactItems($basicInfoId, $items)
  {
    $query = "INSERT INTO contact_items (basic_info_id, tipo, valor, activo) VALUES (:id, :tipo, :valor, :activo)";
    $stmt = $this->db->prepare($query);
    foreach ($items as $item) {
      $stmt->bindParam(':id', $basicInfoId);
      $stmt->bindParam(':tipo', $item['tipo']);
      $stmt->bindParam(':valor', $item['valor']);
      $stmt->bindParam(':activo', $item['activo'], PDO::PARAM_BOOL);
      $stmt->execute();
    }
  }

  /**
   * Inserts multiple social media items into the database for a given basic information ID.
   *
   * @param int $basicInfoId The ID of the basic information to associate with the social media items.
   * @param array $items An array of social media items, where each item is an associative array with keys:
   *                     - 'rrss': The name of the social media platform.
   *                     - 'icono': The icon associated with the social media platform.
   *                     - 'link': The URL link to the social media profile.
   *                     - 'activo': A boolean indicating whether the social media item is active.
   *
   * @return void
   */
  private function createSocialMediaItems($basicInfoId, $items)
  {
    $query = "INSERT INTO social_media_items (basic_info_id, rrss, icono, link, activo) VALUES (:id, :rrss, :icono, :link, :activo)";
    $stmt = $this->db->prepare($query);
    foreach ($items as $item) {
      $stmt->bindParam(':id', $basicInfoId);
      $stmt->bindParam(':rrss', $item['rrss']);
      $stmt->bindParam(':icono', $item['icono']);
      $stmt->bindParam(':link', $item['link']);
      $stmt->bindParam(':activo', $item['activo'], PDO::PARAM_BOOL);
      $stmt->execute();
    }
  }
}
