
<?php
/* 
  This file contains the function isAuthorized that checks if the request is authorized. The function receives the headers of the request and returns true if the request is authorized. 
*/
function isAuthorized($headers)
{
  if (!isset($headers['Authorization'])) {
    return false;
  }
  $authHeader = $headers['Authorization'];
  list($type, $token) = explode(" ", $authHeader, 2);
  return ($type === 'Bearer' && $token === 'ciisa');
}
