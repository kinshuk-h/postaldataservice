<?php

require_once "config.php";

class PostalDatabaseConnection {
    private $db_conn;

    public function __construct() {
        $this->db_conn = new PDO("mysql:host=".DBHOST.";dbname=".DBNAME, DBUSER, DBPASS);
        $this->db_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    public function __destruct() {
        $this->db_conn = null;
    }

    public function save_post_offices($pincode, $post_offices) {
        $this->db_conn->beginTransaction();
        foreach($post_offices as $office) {
            $name = $office["Name"]; $type = $office["BranchType"];
            $this->db_conn->exec("INSERT INTO post_offices VALUES ($pincode, '$name', '$type')");
        }
        $this->db_conn->commit();
    }
    public function get_post_offices($pincode) {
        $stmt = $this->db_conn->prepare("SELECT * FROM post_offices WHERE Pincode=$pincode");
        $stmt->execute(); return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function save_pincode($pincode, $data) {
        $this->db_conn->beginTransaction();
        $district = $data["District"]; $division = $data["Division"]; $region = $data["Region"];
        $block = $data["Block"]; $circle = $data["Circle"]; $state = $data["State"];
        $this->db_conn->exec(
            "INSERT INTO indices VALUES($pincode, '$district', '$division', ".
            "'$region', '$block', '$circle', '$state')"
        );
        $this->db_conn->commit();
    }
    public function get_pincode($pincode) {
        $stmt = $this->db_conn->prepare("SELECT * FROM indices WHERE Pincode=$pincode");
        $stmt->execute(); return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function get_pincodes() {
        $stmt = $this->db_conn->prepare("SELECT * FROM indices ORDER BY State ASC, Pincode ASC");
        $stmt->execute(); return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

?>