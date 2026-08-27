<?php

namespace App\Models;

class DashboardConfigModel extends BaseAppModel
{
    protected $table = 'dashboard_configs';
    protected $primaryKey = 'id_config';
    protected $allowedFields = [
        'id_config',
        'id_empresa',
        'id_login',
        'widgets',
        'layout',
        'theme',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
