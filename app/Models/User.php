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
    public $account_type;

    public function save()
    {
        $db = Database::getInstance();
        $stmt = $db->query(
            "INSERT INTO users (username, email, password_hash, account_type) VALUES (?, ?, ?, ?)",
            [$this->username, $this->email, $this->password_hash, 'Basic']
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
            $user->account_type = $data['account_type'];
            return $user;
        }
        return null;
    }

    public function verifyPassword($password)
    {
        return password_verify($password, $this->password_hash);
    }

    public static function updateAccountType($userId, $accountType)
    {
        $db = Database::getInstance();
        $stmt = $db->query(
            "UPDATE users SET account_type = ? WHERE id = ?",
            [$accountType, $userId]
        );
        return $stmt->rowCount() > 0;
    }
}
