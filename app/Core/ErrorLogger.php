<?php

namespace App\Core;

/**
 * ErrorLogger - Sistema de registro de errores local para depuración
 * 
 * Guarda logs de errores en archivos JSON dentro de /logs
 * con información detallada sobre llamadas a APIs, servicios Y errores PHP
 */
class ErrorLogger
{
    private static ?ErrorLogger $instance = null;
    private string $logsPath;
    private static bool $handlersRegistered = false;
    
    private function __construct()
    {
        // Definir la ruta de logs en la raíz del proyecto
        $this->logsPath = dirname(__DIR__, 2) . '/logs';
        
        // Crear carpeta si no existe
        $this->ensureLogsDirectoryExists();
    }
    
    /**
     * Obtener instancia singleton
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Verificar y crear carpeta de logs si no existe
     */
    private function ensureLogsDirectoryExists(): void
    {
        if (!is_dir($this->logsPath)) {
            mkdir($this->logsPath, 0755, true);
            
            // Crear .gitignore para no subir logs al repositorio
            file_put_contents(
                $this->logsPath . '/.gitignore',
                "# Ignorar todos los archivos de log\n*.json\n*.log\n*.txt\n"
            );
        }
    }
    
    /**
     * ============================================================
     * REGISTRO DE MANEJADORES GLOBALES DE ERRORES
     * ============================================================
     */
    
    /**
     * Registrar manejadores globales para capturar TODOS los errores PHP
     */
    public static function registerGlobalHandlers(): void
    {
        if (self::$handlersRegistered) {
            return;
        }
        
        // Asegurar que la instancia existe
        self::getInstance();
        
        // 1. Capturar errores PHP (warnings, notices, etc.)
        set_error_handler([self::class, 'handlePhpError']);
        
        // 2. Capturar excepciones no manejadas
        set_exception_handler([self::class, 'handleUncaughtException']);
        
        // 3. Capturar errores fatales en shutdown
        register_shutdown_function([self::class, 'handleShutdown']);
        
        self::$handlersRegistered = true;
        error_log("[ErrorLogger] Global handlers registered");
    }
    
    /**
     * Manejador de errores PHP (warnings, notices, etc.)
     */
    public static function handlePhpError(int $errno, string $errstr, string $errfile, int $errline): bool
    {
        // Convertir nivel de error a texto
        $errorTypes = [
            E_ERROR => 'E_ERROR',
            E_WARNING => 'E_WARNING',
            E_PARSE => 'E_PARSE',
            E_NOTICE => 'E_NOTICE',
            E_CORE_ERROR => 'E_CORE_ERROR',
            E_CORE_WARNING => 'E_CORE_WARNING',
            E_COMPILE_ERROR => 'E_COMPILE_ERROR',
            E_COMPILE_WARNING => 'E_COMPILE_WARNING',
            E_USER_ERROR => 'E_USER_ERROR',
            E_USER_WARNING => 'E_USER_WARNING',
            E_USER_NOTICE => 'E_USER_NOTICE',
            E_STRICT => 'E_STRICT',
            E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
            E_DEPRECATED => 'E_DEPRECATED',
            E_USER_DEPRECATED => 'E_USER_DEPRECATED',
        ];
        
        $errorType = $errorTypes[$errno] ?? "E_UNKNOWN ($errno)";
        
        // Ignorar notices y deprecated en producción (opcional)
        $ignoredLevels = [E_NOTICE, E_DEPRECATED, E_USER_DEPRECATED, E_STRICT];
        if (in_array($errno, $ignoredLevels)) {
            return false; // Dejar que PHP lo maneje normalmente
        }
        
        self::logPhpError($errorType, $errstr, $errfile, $errline);
        
        // Devolver false para que PHP también lo maneje
        return false;
    }
    
    /**
     * Manejador de excepciones no capturadas
     */
    public static function handleUncaughtException(\Throwable $exception): void
    {
        self::logException($exception, 'Uncaught Exception');
        
        // Re-lanzar para que PHP muestre el error (en desarrollo)
        // En producción podrías mostrar una página de error amigable
        throw $exception;
    }
    
    /**
     * Manejador de shutdown para errores fatales
     */
    public static function handleShutdown(): void
    {
        $error = error_get_last();
        
        if ($error !== null) {
            $fatalErrors = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR];
            
            if (in_array($error['type'], $fatalErrors)) {
                self::logPhpError(
                    'FATAL_ERROR',
                    $error['message'],
                    $error['file'],
                    $error['line']
                );
            }
        }
    }
    
    /**
     * ============================================================
     * MÉTODOS DE LOGGING
     * ============================================================
     */
    
    /**
     * Log de error PHP genérico
     */
    public static function logPhpError(
        string $errorType,
        string $message,
        string $file,
        int $line,
        array $context = []
    ): string {
        return self::saveErrorLog(
            endpoint: "[PHP:$errorType] $file:$line",
            requestPayload: [
                'error_type' => $errorType,
                'file' => $file,
                'line' => $line,
                'context' => $context,
                'request_uri' => $_SERVER['REQUEST_URI'] ?? 'CLI',
                'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
            ],
            errorMessage: $message,
            exception: null,
            additionalData: [
                'error_type' => $errorType,
                'category' => 'php_error',
            ]
        );
    }
    
    /**
     * Log de excepción
     */
    public static function logException(\Throwable $exception, string $context = 'Exception'): string
    {
        return self::saveErrorLog(
            endpoint: "[$context] " . get_class($exception),
            requestPayload: [
                'exception_class' => get_class($exception),
                'exception_code' => $exception->getCode(),
                'request_uri' => $_SERVER['REQUEST_URI'] ?? 'CLI',
                'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
                'get_params' => $_GET ?? [],
                'post_params' => self::sanitizePayload($_POST ?? []),
            ],
            errorMessage: $exception->getMessage(),
            exception: $exception,
            additionalData: [
                'category' => 'exception',
            ]
        );
    }
    
    /**
     * Log genérico para cualquier evento/error
     */
    public static function log(string $context, string $message, array $data = []): string
    {
        return self::saveErrorLog(
            endpoint: "[LOG] $context",
            requestPayload: $data,
            errorMessage: $message,
            exception: null,
            additionalData: [
                'category' => 'general_log',
            ]
        );
    }
    
    /**
     * Guardar log de error completo
     * 
     * @param string $endpoint URL o contexto de la función/API
     * @param mixed $requestPayload Cuerpo de la petición enviada (muy importante para prompts de IA)
     * @param string $errorMessage Mensaje de error de la API
     * @param \Throwable|null $exception Excepción para obtener stack trace
     * @param array $additionalData Datos extra opcionales
     * @return string Ruta del archivo de log creado
     */
    public static function saveErrorLog(
        string $endpoint,
        $requestPayload,
        string $errorMessage,
        ?\Throwable $exception = null,
        array $additionalData = []
    ): string {
        $logger = self::getInstance();
        
        // Generar timestamp y nombre de archivo
        $timestamp = date('Y-m-d H:i:s');
        $fileTimestamp = date('Y-m-d_H-i-s');
        $uniqueId = substr(uniqid(), -4);
        $filename = "error_{$fileTimestamp}_{$uniqueId}.json";
        $filepath = $logger->logsPath . '/' . $filename;
        
        // Construir contenido del log
        $logData = [
            'TIMESTAMP' => $timestamp,
            'TIMEZONE' => date_default_timezone_get(),
            'ENDPOINT_CONTEXT' => $endpoint,
            'REQUEST_PAYLOAD' => self::sanitizePayload($requestPayload),
            'ERROR_MESSAGE' => $errorMessage,
            'HTTP_STATUS' => $additionalData['http_status'] ?? null,
            'CATEGORY' => $additionalData['category'] ?? 'unknown',
            'STACK_TRACE' => $exception ? self::formatStackTrace($exception) : null,
            'EXCEPTION_CLASS' => $exception ? get_class($exception) : null,
            'EXCEPTION_FILE' => $exception ? $exception->getFile() : null,
            'EXCEPTION_LINE' => $exception ? $exception->getLine() : null,
            'ADDITIONAL_DATA' => !empty($additionalData) ? $additionalData : null,
            'SERVER_INFO' => [
                'request_uri' => $_SERVER['REQUEST_URI'] ?? null,
                'request_method' => $_SERVER['REQUEST_METHOD'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'remote_ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            ],
            'PHP_VERSION' => PHP_VERSION,
            'MEMORY_USAGE' => memory_get_usage(true),
        ];
        
        // Eliminar valores nulos para un log más limpio
        $logData = array_filter($logData, fn($v) => $v !== null);
        $logData['SERVER_INFO'] = array_filter($logData['SERVER_INFO'] ?? [], fn($v) => $v !== null);
        if (empty($logData['SERVER_INFO'])) {
            unset($logData['SERVER_INFO']);
        }
        
        // Guardar como JSON formateado legible
        $jsonContent = json_encode($logData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        file_put_contents($filepath, $jsonContent);
        
        // También escribir al error_log de PHP para visibilidad inmediata
        error_log("[ErrorLogger] Saved: $filename | $endpoint | $errorMessage");
        
        return $filepath;
    }
    
    /**
     * Log rápido para APIs (versión simplificada)
     */
    public static function logApiError(
        string $apiName,
        string $url,
        array $payload,
        int $httpStatus,
        string $response,
        ?\Throwable $exception = null
    ): string {
        return self::saveErrorLog(
            endpoint: "[$apiName] $url",
            requestPayload: $payload,
            errorMessage: "HTTP $httpStatus: $response",
            exception: $exception,
            additionalData: [
                'api_name' => $apiName,
                'http_status' => $httpStatus,
                'raw_response' => substr($response, 0, 2000), // Limitar respuesta
                'category' => 'api_error',
            ]
        );
    }
    
    /**
     * Log específico para errores de IA/LLM
     */
    public static function logAiError(
        string $provider,
        string $model,
        string $prompt,
        string $errorMessage,
        ?int $httpStatus = null,
        ?\Throwable $exception = null
    ): string {
        return self::saveErrorLog(
            endpoint: "[AI:$provider] Model: $model",
            requestPayload: [
                'provider' => $provider,
                'model' => $model,
                'prompt_preview' => substr($prompt, 0, 500) . (strlen($prompt) > 500 ? '...' : ''),
                'prompt_full_length' => strlen($prompt),
                'prompt_full' => $prompt, // Prompt completo para debugging
            ],
            errorMessage: $errorMessage,
            exception: $exception,
            additionalData: [
                'ai_provider' => $provider,
                'ai_model' => $model,
                'http_status' => $httpStatus,
                'category' => 'ai_error',
            ]
        );
    }
    
    /**
     * Sanitizar payload para evitar exponer datos sensibles
     */
    private static function sanitizePayload($payload): mixed
    {
        if (is_string($payload)) {
            return $payload;
        }
        
        if (is_array($payload)) {
            $sensitiveKeys = ['api_key', 'apikey', 'key', 'secret', 'password', 'token', 'authorization'];
            
            array_walk_recursive($payload, function (&$value, $key) use ($sensitiveKeys) {
                if (is_string($key)) {
                    foreach ($sensitiveKeys as $sensitive) {
                        if (stripos($key, $sensitive) !== false && is_string($value)) {
                            $value = '[REDACTED:' . strlen($value) . ' chars]';
                        }
                    }
                }
            });
            
            return $payload;
        }
        
        return $payload;
    }
    
    /**
     * Formatear stack trace de forma legible
     */
    private static function formatStackTrace(\Throwable $exception): array
    {
        $trace = [];
        
        foreach ($exception->getTrace() as $index => $frame) {
            $trace[] = [
                'index' => $index,
                'file' => $frame['file'] ?? '[internal]',
                'line' => $frame['line'] ?? 0,
                'function' => $frame['function'] ?? '',
                'class' => $frame['class'] ?? '',
                'type' => $frame['type'] ?? '',
            ];
        }
        
        return $trace;
    }
    
    /**
     * Obtener lista de logs recientes
     */
    public static function getRecentLogs(int $limit = 20): array
    {
        $logger = self::getInstance();
        $files = glob($logger->logsPath . '/error_*.json');
        
        // Ordenar por fecha de modificación (más reciente primero)
        usort($files, fn($a, $b) => filemtime($b) - filemtime($a));
        
        $logs = [];
        foreach (array_slice($files, 0, $limit) as $file) {
            $content = file_get_contents($file);
            $data = json_decode($content, true);
            $logs[] = [
                'filename' => basename($file),
                'timestamp' => $data['TIMESTAMP'] ?? null,
                'endpoint' => $data['ENDPOINT_CONTEXT'] ?? null,
                'error' => substr($data['ERROR_MESSAGE'] ?? '', 0, 100),
                'category' => $data['CATEGORY'] ?? 'unknown',
            ];
        }
        
        return $logs;
    }
    
    /**
     * Limpiar logs antiguos (más de X días)
     */
    public static function cleanOldLogs(int $daysToKeep = 7): int
    {
        $logger = self::getInstance();
        $files = glob($logger->logsPath . '/error_*.json');
        $threshold = time() - ($daysToKeep * 24 * 60 * 60);
        $deleted = 0;
        
        foreach ($files as $file) {
            if (filemtime($file) < $threshold) {
                unlink($file);
                $deleted++;
            }
        }
        
        return $deleted;
    }
}

