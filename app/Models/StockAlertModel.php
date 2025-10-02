<?php

namespace App\Models;

class StockAlertModel extends BaseAppModel
{
    protected $table = 'stock_alerts';
    protected $primaryKey = 'id_alert';
    protected $allowedFields = [
        'id_alert',
        'id_empresa',
        'id_produto',
        'alert_type',
        'threshold',
        'current_stock',
        'status',
        'notified_at',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
