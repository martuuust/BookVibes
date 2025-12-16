<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class User
{
    public $id;
    public $username;
    public $email;
    public $password_hash;

    public function save()
    {
        $db = Database::getInstance();
        $stmt = $db->query(
            "INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)",
            [$this->username, $this->email, $this->password_hash]
        );
        $this->id = $db->getConnection()->lastInsertId();
        return true;
    }

    public static function findByEmail($email)
    {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT * FROM users WHERE email = ?", [$email]);
        $data = $stmt->fetch();
        
        if ($data) {
            $user = new User();
            $user->id = $data['id'];
            $user->username = $data['username'];
            $user->email = $data['email'];
            $user->password_hash = $data['password_hash'];
            return $user;
        }
        return null;
    }

    public function verifyPassword($password)
    {
        return password_verify($password, $this->password_hash);
    }
}
