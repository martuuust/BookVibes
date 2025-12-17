<?php
require_once __DIR__ . '/app/Core/Database.php';
require_once __DIR__ . '/app/Services/GamificationService.php';

use App\Services\GamificationService;
use App\Core\Database;

echo "Running ensureAchievementsSeed()...\n";
$service = new GamificationService();
$service->ensureAchievementsSeed();

echo "Checking 'Explorador de Géneros' in database...\n";
$db = Database::getInstance();
$ach = $db->query("SELECT * FROM achievements WHERE name = 'Explorador de Géneros'")->fetch();

print_r($ach);

if ($ach['points_required'] == 500 && $ach['icon_class'] == 'bi-compass') {
    echo "SUCCESS: Achievement updated correctly.\n";
} else {
    echo "FAILURE: Achievement not updated.\n";
}
