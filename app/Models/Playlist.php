<?php

namespace App\Models;

use App\Core\Database;

class Playlist
{
    public static function create($bookId, $data)
    {
        $db = Database::getInstance();
        
        // Insert Playlist
        $db->query(
            "INSERT INTO playlists (book_id, name, description) VALUES (?, ?, ?)",
            [$bookId, "Playlist: " . $data['mood'], "Curated for mood: " . $data['mood']]
        );
        $playlistId = $db->getConnection()->lastInsertId();

        // Insert Songs
        foreach ($data['suggested_tracks'] as $track) {
            $db->query(
                "INSERT INTO songs (playlist_id, title, artist, url) VALUES (?, ?, ?, ?)",
                [$playlistId, $track['title'], $track['artist'], $track['url']]
            );
        }
        
        return $playlistId;
    }

    public static function getByBookId($bookId)
    {
        $db = Database::getInstance();
        $playlist = $db->query("SELECT * FROM playlists WHERE book_id = ?", [$bookId])->fetch();
        
        if ($playlist) {
            // Sort by id ASC, excluding legacy AI songs
            $songs = $db->query("SELECT * FROM songs WHERE playlist_id = ? AND (is_ai_generated = 0 OR is_ai_generated IS NULL) ORDER BY id ASC", [$playlist['id']])->fetchAll();
            $playlist['songs'] = $songs;
        }
        
        return $playlist;
    }

    public static function deleteByBookId($bookId)
    {
        $db = Database::getInstance();
        $playlist = $db->query("SELECT id FROM playlists WHERE book_id = ?", [$bookId])->fetch();

        if ($playlist) {
            $db->query("DELETE FROM songs WHERE playlist_id = ?", [$playlist['id']]);
            $db->query("DELETE FROM playlists WHERE id = ?", [$playlist['id']]);
            return true;
        }
        return false;
    }

    public static function clearSongs($bookId)
    {
        $db = Database::getInstance();
        $playlist = $db->query("SELECT id FROM playlists WHERE book_id = ?", [$bookId])->fetch();

        if ($playlist) {
            $db->query("DELETE FROM songs WHERE playlist_id = ?", [$playlist['id']]);
            return true;
        }
        return false;
    }

    public static function getAllUserSongs($userId)
    {
        $db = Database::getInstance();
        return $db->query(
            "SELECT s.title, s.artist 
             FROM songs s
             JOIN playlists p ON s.playlist_id = p.id
             JOIN user_books ub ON p.book_id = ub.book_id
             WHERE ub.user_id = ?",
            [$userId]
        )->fetchAll();
    }
}
