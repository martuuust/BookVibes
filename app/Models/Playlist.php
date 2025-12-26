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
            $isAi = isset($track['is_ai_generated']) ? 1 : 0;
            $lyrics = $track['lyrics'] ?? null;
            $melody = $track['melody_description'] ?? null;
            
            $db->query(
                "INSERT INTO songs (playlist_id, title, artist, url, is_ai_generated, lyrics, melody_description) VALUES (?, ?, ?, ?, ?, ?, ?)",
                [$playlistId, $track['title'], $track['artist'], $track['url'], $isAi, $lyrics, $melody]
            );
        }
        
        return $playlistId;
    }

    public static function getByBookId($bookId)
    {
        $db = Database::getInstance();
        $playlist = $db->query("SELECT * FROM playlists WHERE book_id = ?", [$bookId])->fetch();
        
        if ($playlist) {
            // Sort by is_ai_generated DESC so AI songs appear first
            $songs = $db->query("SELECT * FROM songs WHERE playlist_id = ? ORDER BY is_ai_generated DESC, id ASC", [$playlist['id']])->fetchAll();
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
}
