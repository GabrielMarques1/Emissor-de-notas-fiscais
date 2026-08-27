<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class Dashboard extends BaseController
{
    public function index()
    {
        return '<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Principal</title>
    <meta charset="utf-8">
</head>
<body>
    <h1>🎯 Dashboard Principal</h1>
    <p><strong>Data/Hora:</strong> ' . date('Y-m-d H:i:s') . '</p>
    <p><strong>Status:</strong> Sistema Operacional</p>
    
    <h2>Links dos Dashboards Admin:</h2>
    <ul>
        <li><a href="/admin/backup-dashboard">Monitor de Backup</a></li>
        <li><a href="/admin/cache-monitor">Monitor de Cache</a></li>
        <li><a href="/admin/audit-dashboard">Dashboard de Auditoria</a></li>
        <li><a href="/admin/security-dashboard">Dashboard de Segurança</a></li>
    </ul>
    
    <hr>
    <p><a href="/simple-test">← Voltar para teste simples</a></p>
</body>
</html>';
    }
}
