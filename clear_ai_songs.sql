-- Script para eliminar canciones AI existentes y permitir regeneración
-- Ejecuta este script para borrar las canciones antiguas y generar nuevas únicas

-- Ver cuántas canciones AI hay actualmente
SELECT COUNT(*) as 'Canciones AI actuales' FROM songs WHERE is_ai_generated = 1;

-- DESCOMENTAR LA SIGUIENTE LÍNEA PARA ELIMINAR LAS CANCIONES AI
-- DELETE FROM songs WHERE is_ai_generated = 1;

-- Verificar que se eliminaron
-- SELECT COUNT(*) as 'Canciones AI después' FROM songs WHERE is_ai_generated = 1;

-- INSTRUCCIONES:
-- 1. Ejecuta la primera consulta SELECT para ver cuántas canciones AI tienes
-- 2. Descomenta la línea DELETE (quita los --) 
-- 3. Ejecuta el DELETE para eliminar las canciones antiguas
-- 4. Ve a la página de cualquier libro en tu aplicación
-- 5. Las canciones se regenerarán automáticamente con el nuevo sistema
-- 6. Cada libro tendrá canciones completamente únicas
