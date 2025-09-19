<?php

namespace App\Models;

class InventoryMovementModel extends BaseAppModel
{
    protected $table = 'inventory_movements';
    protected $primaryKey = 'id_inventory_movement';
    protected $allowedFields = [
        'id_inventory_movement',
        'id_produto',
        'tipo',
        'quantidade',
        'motivo',
        'id_pos_sale',
        'id_contador',
        'id_empresa',
        'created_at',
        'updated_at',
        'deleted_at',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
}



