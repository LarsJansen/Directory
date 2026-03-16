<?php

namespace App\Models;

use App\Core\Model;

class User extends Model
{
    public function findByUsername(string $username): ?array
    {
        return $this->db->first(
            'SELECT * FROM users WHERE username = :username AND is_active = 1 LIMIT 1',
            ['username' => $username]
        );
    }
}
