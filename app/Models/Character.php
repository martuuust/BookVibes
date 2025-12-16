<?php

namespace App\Models;

use App\Core\Database;

class Character
{
    public static function create($bookId, $data)
    {
        $db = Database::getInstance();
        $traits = json_encode($data['traits'] ?? []);
        
        $db->query(
            "INSERT INTO characters (book_id, name, description, traits, image_url) VALUES (?, ?, ?, ?, ?)",
            [
                $bookId,
                $data['name'],
                $data['description'],
                $traits,
                $data['image_url']
            ]
        );
    }

    public static function getByBookId($bookId)
    {
        $db = Database::getInstance();
        return $db->query("SELECT * FROM characters WHERE book_id = ?", [$bookId])->fetchAll();
    }

    public static function deleteByBookId($bookId)
    {
        $db = Database::getInstance();
        $db->query("DELETE FROM characters WHERE book_id = ?", [$bookId]);
    }
}
