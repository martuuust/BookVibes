<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Models\User;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        if ($request->getMethod() === 'post') {
            $body = $request->getBody();
            $user = User::findByEmail($body['email']);
            
            if ($user && $user->verifyPassword($body['password'])) {
                if (session_status() === PHP_SESSION_NONE) session_start();
                $_SESSION['user_id'] = $user->id;
                $_SESSION['user_name'] = $user->username;
                header('Location: /dashboard');
                exit;
            }
            
            return $this->render('auth/login', ['error' => 'Credenciales inválidas']);
        }
        
        return $this->render('auth/login');
    }

    public function register(Request $request)
    {
        if ($request->getMethod() === 'post') {
            $body = $request->getBody();
            $user = new User();
            $user->username = $body['username'];
            $user->email = $body['email'];
            $user->password_hash = password_hash($body['password'], PASSWORD_DEFAULT);
            
            try {
                $user->save();
                header('Location: /login');
                exit;
            } catch (\Exception $e) {
                return $this->render('auth/register', ['error' => 'Error al registrar: ' . $e->getMessage()]);
            }
        }
        
        return $this->render('auth/register');
    }

    public function logout()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        session_destroy();
        header('Location: /');
    }
}
