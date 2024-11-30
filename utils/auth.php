
<?php


/**
 * Checks if the request is authorized based on the provided headers.
 *
 * This function verifies the presence of an 'Authorization' header and checks
 * if it contains a valid Bearer token. The token is considered valid if it
 * matches the predefined value 'ciisa'.
 *
 * @param array $headers An associative array of request headers.
 * @return bool Returns true if the request is authorized, false otherwise.
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
