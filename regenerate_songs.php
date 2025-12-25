<?php
/**
 * Script para regenerar canciones AI
 * 
 * Este script elimina todas las canciones AI generadas anteriormente
 * para que se regeneren con el nuevo sistema mejorado
 */

require_once __DIR__ . '/app/autoload.php';

use App\Core\Database;

echo "=== Regenerador de Canciones AI ===\n\n";

try {
    $db = Database::getInstance();
    
    // Contar canciones AI actuales
    $result = $db->query("SELECT COUNT(*) as count FROM songs WHERE is_ai_generated = 1")->fetch();
    $count = $result['count'] ?? 0;
    
    echo "Canciones AI encontradas: $count\n";
    
    if ($count > 0) {
        echo "\n¿Deseas eliminar estas canciones para regenerarlas? (s/n): ";
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        fclose($handle);
        
        if (trim(strtolower($line)) === 's') {
            // Eliminar canciones AI
            $db->query("DELETE FROM songs WHERE is_ai_generated = 1");
            echo "\n✅ $count canciones AI eliminadas correctamente.\n";
            echo "\n📝 Ahora:\n";
            echo "   1. Ve a la página de cualquier libro\n";
            echo "   2. Las canciones se regenerarán automáticamente\n";
            echo "   3. Cada libro tendrá canciones únicas\n\n";
        } else {
            echo "\nOperación cancelada.\n";
        }
    } else {
        echo "\n✅ No hay canciones AI para eliminar.\n";
        echo "Las nuevas canciones se generarán automáticamente al visitar los libros.\n\n";
    }
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n\n";
}

echo "=== Fin ===\n";
