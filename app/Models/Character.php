<?php

namespace App\Models;

use App\Core\Database;

class Character
{
    public static function getByBookId($bookId)
    {
        $db = Database::getInstance();
        return $db->query("SELECT * FROM characters WHERE book_id = ?", [$bookId])->fetchAll();
    }

    public static function create(array $data)
    {
        $db = Database::getInstance();
        $db->query(
            "INSERT INTO characters (book_id, name, description, traits, image_url, role, source) VALUES (?, ?, ?, ?, ?, ?, ?)",
            [
                $data['book_id'],
                $data['name'],
                $data['description'] ?? '',
                $data['traits'] ?? '[]',
                $data['image_url'] ?? '',
                $data['role'] ?? 'Principal',
                $data['source'] ?? 'AI'
            ]
        );
        return $db->getConnection()->lastInsertId();
    }

    public static function deleteAllByBookId($bookId)
    {
        $db = Database::getInstance();
        $db->query("DELETE FROM characters WHERE book_id = ?", [$bookId]);
    }
}
