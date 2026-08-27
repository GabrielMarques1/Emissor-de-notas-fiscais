<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class DebugCaixaSessao extends BaseCommand
{
    protected $group       = 'Debug';
    protected $name        = 'debug:caixa-sessao';
    protected $description = 'Debug detalhado da sessão de caixa';

    public function run(array $params)
    {
        $db = \Config\Database::connect();

        CLI::write('=== DEBUG DETALHADO CAIXA SESSÃO ===', 'yellow');

        // Listar todas as sessões
        $sessoes = $db->query("SELECT * FROM caixa_sessoes ORDER BY id DESC LIMIT 10")->getResultArray();
        
        CLI::write('Últimas 10 sessões:', 'cyan');
        foreach ($sessoes as $sessao) {
            $status = $sessao['status'] === 'aberto' ? CLI::color('ABERTO', 'red') : CLI::color('FECHADO', 'green');
            CLI::write("ID: {$sessao['id']}, Status: $status, Contador: {$sessao['id_contador']}, Empresa: {$sessao['id_empresa']}, Valor Inicial: {$sessao['valor_inicial']}");
        }

        // Testar update manual na sessão aberta
        CLI::newLine();
        CLI::write('=== TESTE DE UPDATE MANUAL ===', 'yellow');
        
        $aberta = $db->query("SELECT * FROM caixa_sessoes WHERE status = 'aberto' ORDER BY id DESC LIMIT 1")->getFirstRow('array');
        if ($aberta) {
            CLI::write("Sessão aberta encontrada: ID {$aberta['id']}", 'green');
            
            // Testar update
            $affected = $db->table('caixa_sessoes')
                ->where('id', (int) $aberta['id'])
                ->where('status', 'aberto')
                ->update(['data_fechamento' => date('Y-m-d H:i:s')]);
                
            CLI::write("Resultado do update: affected = " . $db->affectedRows(), 'cyan');
            
            // Reverter
            $db->table('caixa_sessoes')
                ->where('id', (int) $aberta['id'])
                ->update(['data_fechamento' => null]);
                
        } else {
            CLI::write('Nenhuma sessão aberta encontrada', 'red');
        }
    }
}
