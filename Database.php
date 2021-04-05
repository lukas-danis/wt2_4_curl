<?php
require_once("config.php");


class Database{

    public  $conn = null;
    public function createConnection(){
    try {
        $this->conn = new PDO("mysql:host=". SERVERNAME. ";dbname=".DBNAME,USERNAME,PASSWORD);
        // set the PDO error mode to exception
        $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        // echo "Connected successfully";
        return $this->conn;
      } catch(PDOException $e) {
        echo "Connection failed: " . $e->getMessage();
      }
    }
}
