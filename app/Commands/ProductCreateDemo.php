<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class ProductCreateDemo extends BaseCommand
{
    protected $group       = 'Demo';
    protected $name        = 'product:create-demo';
    protected $description = 'Cria um produto de demonstração no ERP para testar o PDV.';

    public function run(array $params)
    {
        try {
            $db = \Config\Database::connect();
            $empresa = $db->table('empresas')->orderBy('id_empresa','ASC')->get()->getRowArray();
            $contador = $db->table('contadores')->orderBy('id_contador','ASC')->get()->getRowArray();
            if (! $empresa || ! $contador) {
                CLI::error('Empresa/Contador não encontrados. Rode: php spark db:seed TestUserSeeder');
                return;
            }
            $un = $db->table('unidades')->orderBy('id_unidade','ASC')->get()->getRowArray();
            if (! $un) { $db->table('unidades')->insert(['sigla'=>'UN','descricao'=>'Unidade']); $un = $db->table('unidades')->orderBy('id_unidade','ASC')->get()->getRowArray(); }

            $prodModel = model('App\\Models\\ProdutoModel');
            $id = $prodModel->insert([
                'nome' => 'Produto Demo PDV',
                'codigo_de_barras' => '7890000000012',
                'valor_unitario' => 9.90,
                'estoque' => 100,
                'CFOP_NFe' => '5102',
                'CFOP_NFCe' => '5102',
                'CFOP_Externo' => '6102',
                'NCM' => '00000000',
                'CSOSN' => '102',
                'id_unidade' => (int) $un['id_unidade'],
                'id_contador' => (int) $contador['id_contador'],
                'id_empresa' => (int) $empresa['id_empresa'],
            ]);
            CLI::write('Produto criado: #'.$id.' - Produto Demo PDV', 'green');
        } catch (\Throwable $e) {
            CLI::error('Falha ao criar produto: ' . $e->getMessage());
        }
    }
}


