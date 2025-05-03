
<?php
define('BASE_URL', 'https://qur.kz/');
class DBS{
private $host='localhost';
private $username='un469089_un469089';
private $dbname='un469089_qur';
private $password='Gulaiym2201#';
 private $conn;

public function __construct(){
	if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
	try{
		$this->conn=new PDO("mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4",
							$this->username,$this->password);
	$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}
catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
	
}
 public function getConnection() {
        return $this->conn;
    }

}
?>