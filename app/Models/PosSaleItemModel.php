<?php

namespace App\Models;

class PosSaleItemModel extends BaseAppModel
{
    protected $table = 'pos_sale_items';
    protected $primaryKey = 'id_item';
    protected $returnType = 'array';
    protected $allowedFields = [
        'id_item','id_pos_sale','id_produto','nome','codigo_de_barras','unidade','quantidade','valor_unitario','desconto','CFOP_NFe','CFOP_NFCe','CFOP_Externo','NCM','CSOSN','id_contador','id_empresa'
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    
    // Desabilitar enforcement automático pois itens são sempre acessados via pos_sales
    // que já aplica o filtro de tenant
    protected $enforceTenant = false;
    
    // Campos de tenant para rastreamento
    protected $tenantEmpresaField = 'id_empresa';
    protected $tenantContadorField = 'id_contador';
}


