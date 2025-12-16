<?php

namespace App\Models;

use App\Core\Database;

class UserBook
{
    public static function add($userId, $bookId)
    {
        $db = Database::getInstance();
        
        // Check if already added
        $check = $db->query("SELECT id FROM user_books WHERE user_id = ? AND book_id = ?", [$userId, $bookId])->fetch();
        if ($check) return false;

        $db->query(
            "INSERT INTO user_books (user_id, book_id, status) VALUES (?, ?, 'reading')",
            [$userId, $bookId]
        );
        return true;
    }

    public static function getByUser($userId)
    {
        $db = Database::getInstance();
        return $db->query(
            "SELECT b.*, ub.status, ub.progress, ub.added_at 
             FROM books b 
             JOIN user_books ub ON b.id = ub.book_id 
             WHERE ub.user_id = ? 
             ORDER BY ub.added_at DESC",
            [$userId]
        )->fetchAll();
    }
    
    public static function remove($userId, $bookId)
    {
        $db = Database::getInstance();
        
        // Delete linkage
        $stmt = $db->query("DELETE FROM user_books WHERE user_id = ? AND book_id = ?", [$userId, $bookId]);
        
        // Check if book is orphaned (optional cleanup)
        // $orphaned = $db->query("SELECT COUNT(*) as c FROM user_books WHERE book_id = ?", [$bookId])->fetch()['c'];
        // if ($orphaned == 0) { ... }
        
        return $stmt->rowCount() > 0;
    }

    public static function getReadingStats($userId)
    {
         $db = Database::getInstance();
         // Count by mood for chart
         return $db->query(
             "SELECT b.mood, COUNT(*) as count 
              FROM books b 
              JOIN user_books ub ON b.id = ub.book_id 
              WHERE ub.user_id = ? 
              GROUP BY b.mood",
             [$userId]
         )->fetchAll();
    }
}
