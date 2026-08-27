<?php

namespace App\Models;

class LoginModel extends \CodeIgniter\Model
{
    protected $table = 'logins';
    protected $primaryKey = 'id_login';
    protected $allowedFields = [
        'id_login',
        'usuario',
        'senha',
        'tipo',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
}
