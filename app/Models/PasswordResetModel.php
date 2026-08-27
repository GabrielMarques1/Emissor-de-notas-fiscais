<?php

namespace App\Models;

class PasswordResetModel extends BaseAppModel
{
    protected $table = 'password_resets';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'id',
        'id_login',
        'token',
        'expires_at',
        'used_at',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}


