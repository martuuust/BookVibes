<?php

namespace App\Core;

/**
 * Logger - Sistema de registro de eventos y errores
 * 
 * Crea archivos de log diarios en formato legible
 * Uso: Logger::info('mensaje'), Logger::error('mensaje', ['datos' => 'extra'])
 */
class Logger
{
    // Niveles de log disponibles
    public const DEBUG = 'DEBUG';
    public const INFO = 'INFO';
    public const WARNING = 'WARNING';
    public const ERROR = 'ERROR';
    public const CRITICAL = 'CRITICAL';
    
    private static ?string $logsPath = null;
    
    /**
     * Obtener ruta de logs (lazy initialization)
     */
    private static function getLogsPath(): string
    {
        if (self::$logsPath === null) {
            self::$logsPath = dirname(__DIR__, 2) . '/logs';
            
            // Crear carpeta si no existe
            if (!is_dir(self::$logsPath)) {
                @mkdir(self::$logsPath, 0755, true);
            }
        }
        return self::$logsPath;
    }
    
    /**
     * Obtener nombre del archivo de log del día
     */
    private static function getLogFile(): string
    {
        $date = date('Y-m-d');
        return self::getLogsPath() . "/app-{$date}.log";
    }
    
    /**
     * Escribir una línea de log
     * 
     * @param string $level Nivel del log (DEBUG, INFO, WARNING, ERROR, CRITICAL)
     * @param string $message Mensaje descriptivo
     * @param array $context Datos adicionales opcionales
     */
    public static function log(string $level, string $message, array $context = []): bool
    {
        try {
            $time = date('H:i:s');
            
            // Formatear datos extra como JSON compacto
            $contextStr = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
            
            // Formato: [14:30:05] [ERROR] Mensaje {"datos": "extra"}
            $logLine = "[{$time}] [{$level}] {$message}{$contextStr}" . PHP_EOL;
            
            // Escribir al archivo (append mode)
            $result = @file_put_contents(self::getLogFile(), $logLine, FILE_APPEND | LOCK_EX);
            
            return $result !== false;
        } catch (\Throwable $e) {
            // Silenciar error para no romper la aplicación
            // Opcionalmente, escribir al error_log de PHP
            @error_log("[Logger] Failed to write log: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * ============================================================
     * MÉTODOS DE CONVENIENCIA (Estáticos)
     * ============================================================
     */
    
    /**
     * Log nivel DEBUG - Para información de desarrollo
     */
    public static function debug(string $message, array $context = []): bool
    {
        return self::log(self::DEBUG, $message, $context);
    }
    
    /**
     * Log nivel INFO - Eventos normales de la aplicación
     */
    public static function info(string $message, array $context = []): bool
    {
        return self::log(self::INFO, $message, $context);
    }
    
    /**
     * Log nivel WARNING - Situaciones que merecen atención
     */
    public static function warning(string $message, array $context = []): bool
    {
        return self::log(self::WARNING, $message, $context);
    }
    
    /**
     * Log nivel ERROR - Errores que afectan funcionalidad
     */
    public static function error(string $message, array $context = []): bool
    {
        return self::log(self::ERROR, $message, $context);
    }
    
    /**
     * Log nivel CRITICAL - Errores críticos del sistema
     */
    public static function critical(string $message, array $context = []): bool
    {
        return self::log(self::CRITICAL, $message, $context);
    }
    
    /**
     * ============================================================
     * MÉTODOS ESPECIALIZADOS
     * ============================================================
     */
    
    /**
     * Log de excepción con stack trace
     */
    public static function exception(\Throwable $e, string $context = ''): bool
    {
        $message = $context ? "[{$context}] " : '';
        $message .= get_class($e) . ': ' . $e->getMessage();
        
        return self::error($message, [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => array_slice($e->getTrace(), 0, 5), // Solo primeras 5 líneas del trace
        ]);
    }
    
    /**
     * Log de llamada a API externa
     */
    public static function api(string $apiName, string $status, array $details = []): bool
    {
        $level = ($status === 'success') ? self::INFO : self::ERROR;
        return self::log($level, "[API:{$apiName}] {$status}", $details);
    }
    
    /**
     * Log de llamada a servicio de IA
     */
    public static function ai(string $provider, string $status, string $model = '', array $details = []): bool
    {
        $level = ($status === 'success') ? self::INFO : self::ERROR;
        $modelInfo = $model ? " ({$model})" : '';
        return self::log($level, "[AI:{$provider}{$modelInfo}] {$status}", $details);
    }
    
    /**
     * Log de autenticación de usuario
     */
    public static function auth(string $action, bool $success, array $details = []): bool
    {
        $level = $success ? self::INFO : self::WARNING;
        $status = $success ? 'SUCCESS' : 'FAILED';
        return self::log($level, "[AUTH] {$action} - {$status}", $details);
    }
    
    /**
     * Log de base de datos
     */
    public static function db(string $action, bool $success, array $details = []): bool
    {
        $level = $success ? self::DEBUG : self::ERROR;
        $status = $success ? 'OK' : 'FAILED';
        return self::log($level, "[DB] {$action} - {$status}", $details);
    }
    
    /**
     * ============================================================
     * UTILIDADES
     * ============================================================
     */
    
    /**
     * Obtener contenido del log de hoy
     */
    public static function getTodayLog(): string
    {
        $file = self::getLogFile();
        if (file_exists($file)) {
            return file_get_contents($file);
        }
        return '';
    }
    
    /**
     * Obtener las últimas N líneas del log de hoy
     */
    public static function getLastLines(int $count = 50): array
    {
        $content = self::getTodayLog();
        if (empty($content)) {
            return [];
        }
        
        $lines = explode(PHP_EOL, trim($content));
        return array_slice($lines, -$count);
    }
    
    /**
     * Limpiar logs antiguos (más de X días)
     */
    public static function cleanOldLogs(int $daysToKeep = 30): int
    {
        $deleted = 0;
        $threshold = time() - ($daysToKeep * 24 * 60 * 60);
        
        $files = glob(self::getLogsPath() . '/app-*.log');
        foreach ($files as $file) {
            if (filemtime($file) < $threshold) {
                if (@unlink($file)) {
                    $deleted++;
                }
            }
        }
        
        return $deleted;
    }
}
