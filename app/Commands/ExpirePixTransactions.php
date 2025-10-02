<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Libraries\PixService;

class ExpirePixTransactions extends BaseCommand
{
    protected $group       = 'maintenance';
    protected $name        = 'pix:expire';
    protected $description = 'Expirar transações PIX pendentes que já ultrapassaram o tempo limite';
    
    public function run(array $params)
    {
        CLI::write('Buscando transações PIX expiradas...', 'yellow');
        
        try {
            $pixService = new PixService();
            $expired = $pixService->expireOld();
            
            if ($expired > 0) {
                CLI::write("✅ {$expired} transação(ões) PIX expirada(s) com sucesso", 'green');
            } else {
                CLI::write("ℹ️  Nenhuma transação PIX expirada encontrada", 'blue');
            }
            
        } catch (\Exception $e) {
            CLI::error('Erro ao expirar transações PIX: ' . $e->getMessage());
            return EXIT_ERROR;
        }
        
        return EXIT_SUCCESS;
    }
}

