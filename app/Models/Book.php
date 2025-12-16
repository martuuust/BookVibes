<?php

namespace App\Models;

use App\Core\Database;

class Book
{
    public static function create(array $data)
    {
        $db = Database::getInstance();
        
        // 1. Check if exists
        $stmt = $db->query("SELECT id FROM books WHERE title = ? AND author = ?", [$data['title'], $data['author']]);
        $exists = $stmt->fetch();
        if ($exists) {
            $id = $exists['id'];
            $cover = $data['image_url'] ?? '';
            $synopsis = $data['synopsis'] ?? '';
            $genre = $data['genre'] ?? '';
            $mood = $data['mood'] ?? '';
            $filePath = $data['file_path'] ?? null; // Nuevo campo
            $db->query(
                "UPDATE books SET synopsis = ?, genre = ?, mood = ?, cover_url = ?, file_path = ? WHERE id = ?",
                [$synopsis, $genre, $mood, $cover, $filePath, $id]
            );
            return $id;
        }

        // 2. Insert
        $db->query(
            "INSERT INTO books (title, author, synopsis, genre, mood, cover_url, file_path) VALUES (?, ?, ?, ?, ?, ?, ?)",
            [
                $data['title'], 
                $data['author'], 
                $data['synopsis'] ?? '', 
                $data['genre'] ?? '', 
                $data['mood'] ?? '',
                $data['image_url'] ?? '',
                $data['file_path'] ?? null // Nuevo campo
            ]
        );
        
        return $db->getConnection()->lastInsertId();
    }

    public static function all()
    {
        $db = Database::getInstance();
        return $db->query("SELECT * FROM books ORDER BY created_at DESC")->fetchAll();
    }
    
    public static function find($id)
    {
        $db = Database::getInstance();
        return $db->query("SELECT * FROM books WHERE id = ?", [$id])->fetch();
    }

    public static function delete($id)
    {
        $db = Database::getInstance();
        $db->query("DELETE FROM books WHERE id = ?", [$id]);
    }
}
