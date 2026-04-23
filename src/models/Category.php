<?php

namespace App\Models;

use PDO;
use App\Config\Database;

class Category
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll(): array
    {
        $stmt = $this->db->query("SELECT * FROM categories ORDER BY name");
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        $category = $stmt->fetch();
        return $category ?: null;
    }

    public function getWithProductsCount(): array
    {
        $stmt = $this->db->query("
            SELECT c.*, COUNT(p.id) as products_count 
            FROM categories c
            LEFT JOIN products p ON c.id = p.category_id
            GROUP BY c.id
            ORDER BY c.name
        ");
        return $stmt->fetchAll();
    }

    public function create(string $name, string $slug, ?string $description = null): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO categories (name, slug, description) 
            VALUES (:name, :slug, :description)
        ");
        
        $stmt->execute([
            ':name' => $name,
            ':slug' => $slug,
            ':description' => $description
        ]);
        
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, string $name, string $slug, ?string $description = null): bool
    {
        $stmt = $this->db->prepare("
            UPDATE categories 
            SET name = ?, slug = ?, description = ? 
            WHERE id = ?
        ");
        
        return $stmt->execute([$name, $slug, $description, $id]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM categories WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
