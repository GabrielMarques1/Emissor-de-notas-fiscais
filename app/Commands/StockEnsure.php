<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

class StockEnsure extends BaseCommand
{
    protected $group       = 'DB';
    protected $name        = 'stock:ensure';
    protected $description = 'Garante a coluna estoque em produtos, padroniza tipo/valores e corrige nulos.';

    public function run(array $params)
    {
        $db = Database::connect();
        if (! $db->tableExists('produtos')) {
            CLI::error('Tabela produtos não encontrada.');
            return; 
        }

        // Descobrir campos
        $fields = [];
        foreach ($db->getFieldData('produtos') as $f) { $fields[$f->name] = $f; }

        // Adiciona coluna se não existir
        if (!isset($fields['estoque'])) {
            CLI::write('Adicionando coluna estoque...', 'yellow');
            try {
                $db->query("ALTER TABLE produtos ADD COLUMN estoque DECIMAL(14,4) NOT NULL DEFAULT 0.0000 AFTER valor_unitario");
            } catch (\Throwable $e) {
                CLI::error('Falha ao adicionar coluna estoque: ' . $e->getMessage());
                return;
            }
        }

        // Normalizar tipo/default/not null
        CLI::write('Normalizando tipo e default da coluna estoque...', 'yellow');
        try {
            $db->query("ALTER TABLE produtos MODIFY COLUMN estoque DECIMAL(14,4) NOT NULL DEFAULT 0.0000");
        } catch (\Throwable $e) {
            // prossegue mesmo se não suportado
        }

        // Substituir nulos por 0
        CLI::write('Atualizando valores nulos para 0...', 'yellow');
        try {
            $db->query("UPDATE produtos SET estoque = 0.0000 WHERE estoque IS NULL");
        } catch (\Throwable $e) {
            // ignora
        }

        CLI::write('Coluna estoque garantida e normalizada.', 'green');
    }
}


