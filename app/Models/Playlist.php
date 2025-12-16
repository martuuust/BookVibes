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
            $songs = $db->query("SELECT * FROM songs WHERE playlist_id = ?", [$playlist['id']])->fetchAll();
            $playlist['songs'] = $songs;
        }
        
        return $playlist;
    }
}
