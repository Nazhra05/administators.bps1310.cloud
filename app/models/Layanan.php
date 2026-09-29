<?php

require_once __DIR__ . '/../../config/config.php';

class Layanan
{
    private $conn;

    public function __construct()
    {
        global $conn;
        $this->conn = $conn;
    }

    public function getAll()
    {
        $sql = "SELECT 
                    l.id_layanan,
                    l.id_kategori,
                    l.nama_layanan,
                    l.url,
                    l.logo,
                    k.nama_kategori
                FROM layanan l
                LEFT JOIN kategori k 
                    ON l.id_kategori = k.id_kategori
                ORDER BY l.id_layanan DESC";

        return $this->conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function count()
    {
        return $this->conn
            ->query("SELECT COUNT(*) FROM layanan")
            ->fetchColumn();
    }
}