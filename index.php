<?php

/**
 * Main entry point for the application.
 */
header('Content-Type: application/json');

require_once 'controllers/ServicesController.php';
require_once 'controllers/AboutUsController.php';
require_once 'controllers/BasicInfoController.php';
require_once 'utils/auth.php';

$headers = getallheaders();

try {
  // Verificar autorización
  if (!isAuthorized($headers)) {
    http_response_code(401);
    echo json_encode(['message' => 'Unauthorized']);
    exit();
  }

  $request_method = $_SERVER['REQUEST_METHOD'];
  $request_uri = $_SERVER['REQUEST_URI'];

  if (preg_match('/\/v1\/services\/?$/', $request_uri)) {
    $controller = new ServicesController();
    if ($request_method === 'GET') {
      $controller->getServices();
    } elseif ($request_method === 'POST') {
      $data = json_decode(file_get_contents('php://input'), true);

      if (!$data) {
        http_response_code(400);
        echo json_encode(['message' => 'Invalid or missing JSON body']);
        exit();
      }

      $controller->createService($data);
    } else {
      http_response_code(405);
      echo json_encode(['message' => 'Method Not Allowed']);
    }
  } elseif (preg_match('/\/v1\/about-us\/?$/', $request_uri)) {
    $controller = new AboutUsController();
    if ($request_method === 'GET') {
      $controller->getAboutUs();
    } elseif ($request_method === 'POST') {
      $data = json_decode(file_get_contents('php://input'), true);

      if (!$data) {
        http_response_code(400);
        echo json_encode(['message' => 'Invalid or missing JSON body']);
        exit();
      }

      $controller->createAboutUs($data);
    } else {
      http_response_code(405);
      echo json_encode(['message' => 'Method Not Allowed']);
    }
  } elseif (preg_match('/\/v1\/basic-info\/items\/?$/', $request_uri)) {
    $controller = new BasicInfoController();
    if ($request_method === 'POST') {
      $data = json_decode(file_get_contents('php://input'), true);

      if (!$data) {
        http_response_code(400);
        echo json_encode(['message' => 'Invalid or missing JSON body']);
        exit();
      }

      $validation = validateBasicInfoBody($data);
      if (!$validation['valid']) {
        http_response_code(400);
        echo json_encode(['message' => $validation['message']]);
        exit();
      }

      $controller->addItemsToBasicInfo($data);
    } else {
      http_response_code(405);
      echo json_encode(['message' => 'Method Not Allowed']);
    }
  } elseif (preg_match('/\/v1\/basic-info\/?$/', $request_uri)) {
    $controller = new BasicInfoController();
    if ($request_method === 'GET') {
      $controller->getBasicInfo();
    } else {
      http_response_code(405);
      echo json_encode(['message' => 'Method Not Allowed']);
    }
  } else {
    http_response_code(404);
    echo json_encode(['message' => 'Endpoint Not Found']);
  }
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['message' => 'Internal Server Error', 'error' => $e->getMessage()]);
}
