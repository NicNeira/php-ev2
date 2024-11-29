<?php
require_once 'config/database.php';
require_once 'utils/auth.php';

class BasicInfoController
{
  private $db;

  public function __construct()
  {
    $database = new Database();
    $this->db = $database->getConnection();
  }

  public function getBasicInfo()
  {
    $query = "SELECT * FROM basic_info ORDER BY id";
    $stmt = $this->db->prepare($query);
    $stmt->execute();
    $basicInfos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $data = [];

    foreach ($basicInfos as $basicInfo) {
      $tipo = $basicInfo['tipo'];
      $item = [
        'tipo' => $tipo,
        'activo' => $basicInfo['activo'] === 't' ? true : false
      ];

      if ($tipo === 'menu-principal') {
        $item['items'] = $this->getMenuItems($basicInfo['id']);
      } elseif ($tipo === 'hero') {
        $item = array_merge($item, $this->getHeroInfo($basicInfo['id']));
      } elseif ($tipo === 'contacto') {
        $item['items'] = $this->getContactItems($basicInfo['id']);
      } elseif ($tipo === 'rrss') {
        $item['items'] = $this->getSocialMediaItems($basicInfo['id']);
      }

      $data[] = $item;
    }

    echo json_encode(['data' => $data]);
  }

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
        'activo' => $menuItem['activo'] === 't' ? true : false
      ];
    }
    return $items;
  }

  private function getHeroInfo($basic_info_id)
  {
    $query = "SELECT * FROM hero_info WHERE basic_info_id = :id";
    $stmt = $this->db->prepare($query);
    $stmt->bindParam(':id', $basic_info_id);
    $stmt->execute();
    $hero = $stmt->fetch(PDO::FETCH_ASSOC);

    return [
      'titulo' => [
        'esp' => $hero['titulo_esp'],
        'eng' => $hero['titulo_eng']
      ],
      'parrafo' => [
        'esp' => $hero['parrafo_esp'],
        'eng' => $hero['parrafo_eng']
      ]
    ];
  }

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
        'activo' => $contactItem['activo'] === 't' ? true : false
      ];
    }
    return $items;
  }

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
        'activo' => $socialItem['activo'] === 't' ? true : false
      ];
    }
    return $items;
  }
}
