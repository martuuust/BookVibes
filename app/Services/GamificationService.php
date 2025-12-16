<?php

namespace App\Services;

use App\Core\Database;

class GamificationService
{
    public function ensureAchievementsSeed()
    {
        $db = Database::getInstance();
        $list = [
            ['Lector Novato','Registra tu primer libro',10,'bi-book'],
            ['Explorador Musical','Escucha 3 playlists',50,'bi-music-note'],
            ['Maratón Literaria','Completa 3 libros',100,'bi-trophy'],
            ['Lector Casual','Completa 5 libros',150,'bi-book-half'],
            ['Lector Constante','Completa 10 libros',300,'bi-journals'],
            ['Lector Voraz','Completa 25 libros',800,'bi-bookmark-heart'],
            ['Lector Legendario','Completa 50 libros',1600,'bi-award'],
            ['Bibliófilo','Completa 100 libros',3200,'bi-trophy-fill'],
            ['Bibliotecario','Añade 10 libros',120,'bi-journal-plus'],
            ['Coleccionista','Añade 25 libros',300,'bi-archive'],
            ['Explorador de Géneros','Lee 5 géneros distintos',200,'bi-grid'],
            ['Trilogía','Completa 3 libros',180,'bi-bookmark-star'],
            ['Saga Máxima','Completa 20 libros',1200,'bi-bookmark-check'],
            ['Creador de Playlists','Crea 3 playlists',60,'bi-collection-play'],
            ['DJ Literario','Crea 10 playlists',200,'bi-music-note-list'],
            ['Melómano','Escucha 10 playlists',200,'bi-headphones'],
            ['Personajista','Crea 5 personajes',100,'bi-person'],
            ['Elenco Completo','Crea 15 personajes',300,'bi-people'],
            ['Mood Explorer','Analiza el mood de 10 libros',150,'bi-emoji-smile'],
            ['Autor Fetiche','Completa 5 libros',300,'bi-person-vcard'],
            ['Streak 3 días','Lee 3 días seguidos',90,'bi-calendar3'],
            ['Streak 7 días','Lee 7 días seguidos',210,'bi-calendar-week'],
            ['Streak 14 días','Lee 14 días seguidos',420,'bi-calendar2-week'],
            ['Streak 30 días','Lee 30 días seguidos',900,'bi-calendar-check'],
            ['Maestro de Fantasía','Completa 5 libros de fantasía',350,'bi-stars'],
            ['Detective de Misterio','Completa 5 libros de misterio',350,'bi-search'],
            ['Romántico Empedernido','Completa 5 libros de romance',350,'bi-heart']
        ];
        foreach ($list as $row) {
            $exists = $db->query("SELECT id FROM achievements WHERE name = ?", [$row[0]])->fetch();
            if (!$exists) {
                $db->query(
                    "INSERT INTO achievements (name, description, points_required, icon_class) VALUES (?, ?, ?, ?)",
                    [$row[0], $row[1], $row[2], $row[3]]
                );
            }
        }
        foreach ($list as $row) {
            $db->query("UPDATE achievements SET icon_class = ? WHERE name = ?", [$row[3], $row[0]]);
        }
    }

    public function awardPoints($userId, $action, $points)
    {
        $db = Database::getInstance();
        $this->ensureAchievementsSeed();
        
        // 1. Record History
        $db->query(
            "INSERT INTO points_history (user_id, action, points) VALUES (?, ?, ?)",
            [$userId, $action, $points]
        );

        // 2. Check for newly unlocked achievements
        $this->checkAchievements($userId);
    }

    private function checkAchievements($userId)
    {
        $db = Database::getInstance();
        
        // Calculate total points
        $stmt = $db->query("SELECT SUM(points) as total FROM points_history WHERE user_id = ?", [$userId]);
        $totalPoints = $stmt->fetch()['total'] ?? 0;

        // Get all achievements
        $achievements = $db->query("SELECT * FROM achievements")->fetchAll();

        foreach ($achievements as $achievement) {
            // Check if already unlocked
            $unlocked = $db->query(
                "SELECT * FROM user_achievements WHERE user_id = ? AND achievement_id = ?",
                [$userId, $achievement['id']]
            )->fetch();

            if (!$unlocked && $totalPoints >= $achievement['points_required']) {
                // Unlock!
                $db->query(
                    "INSERT INTO user_achievements (user_id, achievement_id) VALUES (?, ?)",
                    [$userId, $achievement['id']]
                );
            } elseif ($unlocked && $totalPoints < $achievement['points_required']) {
                // Revoke if points fell below requirement
                $db->query(
                    "DELETE FROM user_achievements WHERE user_id = ? AND achievement_id = ?",
                    [$userId, $achievement['id']]
                );
            }
        }
    }

    public function getUserStats($userId)
    {
        $db = Database::getInstance();
        $this->ensureAchievementsSeed();
        $stmt = $db->query("SELECT SUM(points) as total FROM points_history WHERE user_id = ?", [$userId]);
        $total = $stmt->fetch()['total'] ?? 0;
        
        // Get unlocked achievements
        $unlocked = $db->query(
            "SELECT a.* FROM achievements a 
             JOIN user_achievements ua ON a.id = ua.achievement_id 
             WHERE ua.user_id = ?",
            [$userId]
        )->fetchAll();

        $all = $db->query("SELECT * FROM achievements ORDER BY points_required ASC")->fetchAll();
        $unlockedIds = array_map(function ($a) { return (int)$a['id']; }, $unlocked);
        $locked = [];
        foreach ($all as $ach) {
            $achId = (int)$ach['id'];
            if (!in_array($achId, $unlockedIds, true)) {
                $req = max(1, (int)$ach['points_required']);
                $p = (int)floor(($total / $req) * 100);
                if ($p > 100) $p = 100;
                if ($p < 0) $p = 0;
                $ach['progress'] = $p;
                $ach['remaining'] = max(0, $req - (int)$total);
                $desc = strtolower((string)($ach['description'] ?? ''));
                $booksRequired = 0;
                if (preg_match('/(\d+)\s+libros?/', $desc, $m)) {
                    $booksRequired = (int)$m[1];
                }
                if ($booksRequired > 0) {
                    $booksDone = (int)floor(($p / 100) * $booksRequired);
                    $ach['books_required'] = $booksRequired;
                    $ach['books_remaining'] = max(0, $booksRequired - $booksDone);
                }
                $label = null;
                if (strpos($desc, 'libro') !== false) $label = 'Libros';
                else if (strpos($desc, 'playlist') !== false) $label = 'Playlists';
                else if (strpos($desc, 'personaje') !== false) $label = 'Personajes';
                else if (strpos($desc, 'mood') !== false) $label = 'Mood';
                else if (strpos($desc, 'género') !== false || strpos($desc, 'genero') !== false) $label = 'Géneros';
                else if (strpos($desc, 'día') !== false || strpos($desc, 'dias') !== false || strpos($desc, 'días') !== false) $label = 'Días';
                if ($label) $ach['progress_label'] = $label;
                $unitsRequired = 0;
                $unitsLabel = null;
                if (preg_match('/(\d+)\s+playlists?/', $desc, $m)) { $unitsRequired = (int)$m[1]; $unitsLabel = 'Playlists'; }
                else if (preg_match('/(\d+)\s+personajes?/', $desc, $m)) { $unitsRequired = (int)$m[1]; $unitsLabel = 'Personajes'; }
                else if (preg_match('/(\d+)\s+mood(s)?/', $desc, $m)) { $unitsRequired = (int)$m[1]; $unitsLabel = 'Mood'; }
                else if (preg_match('/(\d+)\s+géneros?/', $desc, $m) || preg_match('/(\d+)\s+generos?/', $desc, $m)) { $unitsRequired = (int)$m[1]; $unitsLabel = 'Géneros'; }
                else if (preg_match('/(\d+)\s+d[ií]as?/', $desc, $m)) { $unitsRequired = (int)$m[1]; $unitsLabel = 'Días'; }
                if ($unitsRequired > 0) {
                    $unitsDone = (int)floor(($p / 100) * $unitsRequired);
                    $ach['units_required'] = $unitsRequired;
                    $ach['units_remaining'] = max(0, $unitsRequired - $unitsDone);
                    if ($unitsLabel) $ach['progress_label'] = $unitsLabel;
                }
                $locked[] = $ach;
            }
        }
        foreach ($unlocked as &$ua) {
            $ua['progress'] = 100;
            $ua['remaining'] = 0;
            $desc = strtolower((string)($ua['description'] ?? ''));
            if (preg_match('/(\d+)\s+libros?/', $desc, $m)) {
                $ua['books_required'] = (int)$m[1];
                $ua['books_remaining'] = 0;
            }
            $label = null;
            if (strpos($desc, 'libro') !== false) $label = 'Libros';
            else if (strpos($desc, 'playlist') !== false) $label = 'Playlists';
            else if (strpos($desc, 'personaje') !== false) $label = 'Personajes';
            else if (strpos($desc, 'mood') !== false) $label = 'Mood';
            else if (strpos($desc, 'género') !== false || strpos($desc, 'genero') !== false) $label = 'Géneros';
            else if (strpos($desc, 'día') !== false || strpos($desc, 'dias') !== false || strpos($desc, 'días') !== false) $label = 'Días';
            if ($label) $ua['progress_label'] = $label;
            if (preg_match('/(\d+)\s+playlists?/', $desc, $m)) { $ua['units_required'] = (int)$m[1]; $ua['units_remaining'] = 0; $ua['progress_label'] = 'Playlists'; }
            else if (preg_match('/(\d+)\s+personajes?/', $desc, $m)) { $ua['units_required'] = (int)$m[1]; $ua['units_remaining'] = 0; $ua['progress_label'] = 'Personajes'; }
            else if (preg_match('/(\d+)\s+mood(s)?/', $desc, $m)) { $ua['units_required'] = (int)$m[1]; $ua['units_remaining'] = 0; $ua['progress_label'] = 'Mood'; }
            else if (preg_match('/(\d+)\s+géneros?/', $desc, $m) || preg_match('/(\d+)\s+generos?/', $desc, $m)) { $ua['units_required'] = (int)$m[1]; $ua['units_remaining'] = 0; $ua['progress_label'] = 'Géneros'; }
            else if (preg_match('/(\d+)\s+d[ií]as?/', $desc, $m)) { $ua['units_required'] = (int)$m[1]; $ua['units_remaining'] = 0; $ua['progress_label'] = 'Días'; }
        }
        unset($ua);

        $next = $db->query(
            "SELECT * FROM achievements 
             WHERE points_required > ? 
             AND id NOT IN (SELECT achievement_id FROM user_achievements WHERE user_id = ?) 
             ORDER BY points_required ASC 
             LIMIT 1",
            [$total, $userId]
        )->fetch();

        $nextProgress = 100;
        $nextRemaining = 0;
        $xpCap = 0;
        $xpProgressValue = (int)$total;
        if ($next) {
            $req = max(1, (int)$next['points_required']);
            $nextProgress = (int)floor(($total / $req) * 100);
            if ($nextProgress > 100) $nextProgress = 100;
            if ($nextProgress < 0) $nextProgress = 0;
            $nextRemaining = max(0, $req - (int)$total);
            $xpCap = $req;
            $xpProgressValue = min((int)$total, $req);
        } else {
            // No next achievement: cap equals current total, progress full
            $xpCap = (int)$total;
            $nextProgress = 100;
            $nextRemaining = 0;
        }

        return [
            'total_points' => $total,
            'achievements' => $unlocked,
            'achievements_unlocked' => $unlocked,
            'achievements_locked' => $locked,
            'next_achievement' => $next ?: null,
            'next_progress' => $nextProgress,
            'next_remaining' => $nextRemaining,
            'xp_cap' => $xpCap,
            'xp_progress_value' => $xpProgressValue
        ];
    }
}
