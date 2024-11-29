<?php
header('Content-Type: application/json');

require_once 'controllers/ServicesController.php';
require_once 'controllers/AboutUsController.php';
require_once 'controllers/BasicInfoController.php';
require_once 'utils/auth.php';

$headers = getallheaders();
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
    $controller->createAboutUs($data);
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
