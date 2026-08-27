<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Libraries\SuspensionService;

class ExpireSuspendedSales extends BaseCommand
{
    protected $group       = 'maintenance';
    protected $name        = 'sales:expire-suspended';
    protected $description = 'Expirar vendas suspensas que ultrapassaram o tempo limite';
    
    public function run(array $params)
    {
        CLI::write('Buscando vendas suspensas expiradas...', 'yellow');
        
        try {
            $suspensionService = new SuspensionService();
            $expired = $suspensionService->expireOld();
            
            if ($expired > 0) {
                CLI::write("✅ {$expired} venda(s) suspensa(s) expirada(s) e cancelada(s) com sucesso", 'green');
            } else {
                CLI::write("ℹ️  Nenhuma venda suspensa expirada encontrada", 'blue');
            }
            
        } catch (\Exception $e) {
            CLI::error('Erro ao expirar vendas suspensas: ' . $e->getMessage());
            return EXIT_ERROR;
        }
        
        return EXIT_SUCCESS;
    }
}

