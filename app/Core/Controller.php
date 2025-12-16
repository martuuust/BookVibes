<?php

namespace App\Core;

class Controller
{
    public function render($view, $params = [])
    {
        // Simple View Engine
        foreach ($params as $key => $value) {
            $$key = $value;
        }
        
        // Start buffering
        ob_start();
        include __DIR__ . "/../Views/$view.php";
        return ob_get_clean();
    }

    public function json($data, $status = 200)
    {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode($data);
    }
}
