<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Logger;
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
                $_SESSION['account_type'] = $user->account_type;
                $_SESSION['pro'] = ($user->account_type === 'Pro');
                
                Logger::auth('Login', true, [
                    'user_id' => $user->id,
                    'email' => $body['email']
                ]);
                
                header('Location: /dashboard');
                exit;
            }
            
            Logger::auth('Login', false, [
                'email' => $body['email'],
                'reason' => $user ? 'password_mismatch' : 'user_not_found'
            ]);
            
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
                
                Logger::auth('Register', true, [
                    'email' => $body['email'],
                    'username' => $body['username']
                ]);
                
                header('Location: /login');
                exit;
            } catch (\Exception $e) {
                Logger::auth('Register', false, [
                    'email' => $body['email'],
                    'error' => $e->getMessage()
                ]);
                
                return $this->render('auth/register', ['error' => 'Error al registrar: ' . $e->getMessage()]);
            }
        }
        
        return $this->render('auth/register');
    }

    public function logout()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        $userId = $_SESSION['user_id'] ?? null;
        
        session_destroy();
        
        Logger::auth('Logout', true, ['user_id' => $userId]);
        
        header('Location: /');
    }
}

