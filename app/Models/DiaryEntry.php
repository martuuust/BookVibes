<?php

namespace App\Models;

use App\Core\Database;

class DiaryEntry
{
    /**
     * Get all diary entries for a user, ordered by date (newest first)
     */
    public static function getByUser(int $userId): array
    {
        $db = Database::getInstance();
        
        // Ensure table exists
        self::ensureTableExists($db);
        
        $stmt = $db->query(
            "SELECT * FROM diary_entries WHERE user_id = ? ORDER BY created_at DESC",
            [$userId]
        );
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Create a new diary entry
     */
    public static function create(int $userId, string $bookTitle, string $content): int
    {
        $db = Database::getInstance();
        
        // Ensure table exists
        self::ensureTableExists($db);
        
        $db->query(
            "INSERT INTO diary_entries (user_id, book_title, content) VALUES (?, ?, ?)",
            [$userId, $bookTitle, $content]
        );
        
        return (int) $db->lastInsertId();
    }

    /**
     * Update an existing diary entry
     */
    public static function update(int $id, int $userId, string $bookTitle, string $content): bool
    {
        $db = Database::getInstance();
        
        $stmt = $db->query(
            "UPDATE diary_entries SET book_title = ?, content = ? WHERE id = ? AND user_id = ?",
            [$bookTitle, $content, $id, $userId]
        );
        
        return $stmt->rowCount() > 0;
    }

    /**
     * Delete a diary entry (only if owned by user)
     */
    public static function delete(int $id, int $userId): bool
    {
        $db = Database::getInstance();
        
        $stmt = $db->query(
            "DELETE FROM diary_entries WHERE id = ? AND user_id = ?",
            [$id, $userId]
        );
        
        return $stmt->rowCount() > 0;
    }

    /**
     * Ensure the diary_entries table exists
     */
    private static function ensureTableExists(Database $db): void
    {
        static $checked = false;
        if ($checked) return;
        
        // Always try to create - IF NOT EXISTS handles the case where it already exists
        try {
            $db->query("
                CREATE TABLE IF NOT EXISTS diary_entries (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    book_title VARCHAR(255) NOT NULL,
                    content TEXT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_user_id (user_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        } catch (\Exception $e) {
            // Table might already exist with different structure, ignore
        }
        
        $checked = true;
    }
}
