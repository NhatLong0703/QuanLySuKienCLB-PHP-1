<?php
class BaseRepository {
    protected $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function getDb() {
        return $this->db;
    }
}
