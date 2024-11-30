<?php
class Database
{
  private $host = 'localhost';
  private $db_name = 'backend_ev2';
  private $username = 's71semana2';
  private $password = 's71semana2';
  public $conn;

  /**
   * Establishes a connection to the PostgreSQL database using PDO.
   *
   * @return PDO|null Returns a PDO instance representing a connection to the database, or null if the connection fails.
   */
  public function getConnection()
  {
    $this->conn = null;
    try {
      $this->conn = new PDO(
        "pgsql:host=" . $this->host . ";dbname=" . $this->db_name,
        $this->username,
        $this->password
      );
    } catch (PDOException $exception) {
      echo "Error de conexión: " . $exception->getMessage();
    }
    return $this->conn;
  }
}
