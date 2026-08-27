<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class CleanupSessions extends BaseCommand
{
    protected $group       = 'Debug';
    protected $name        = 'cleanup:sessions';
    protected $description = 'Limpa sessões órfãs e sincroniza estado';

    public function run(array $params)
    {
        $db = \Config\Database::connect();

        CLI::write('=== LIMPEZA DE SESSÕES ===', 'yellow');

        // Fechar todas as sessões órfãs (sem shifts correspondentes)
        $sessoes = $db->query("
            SELECT c.* FROM caixa_sessoes c 
            LEFT JOIN shifts s ON (c.id_contador = s.id_contador AND c.id_empresa = s.id_empresa AND s.status = 'open')
            WHERE c.status = 'aberto' AND s.id_shift IS NULL
        ")->getResultArray();

        CLI::write('Sessões órfãs encontradas: ' . count($sessoes), 'cyan');

        foreach ($sessoes as $sessao) {
            CLI::write("Fechando sessão órfã ID: {$sessao['id']} (Empresa: {$sessao['id_empresa']}, Contador: {$sessao['id_contador']})");
            
            $db->table('caixa_sessoes')
                ->where('id', $sessao['id'])
                ->update([
                    'status' => 'fechado',
                    'data_fechamento' => date('Y-m-d H:i:s'),
                    'id_usuario_fechamento' => 1
                ]);
        }

        // Criar sessões para shifts que não têm
        $shiftsOrfaos = $db->query("
            SELECT s.* FROM shifts s 
            LEFT JOIN caixa_sessoes c ON (s.id_contador = c.id_contador AND s.id_empresa = c.id_empresa AND c.status = 'aberto')
            WHERE s.status = 'open' AND c.id IS NULL
        ")->getResultArray();

        CLI::write('Shifts sem sessão encontrados: ' . count($shiftsOrfaos), 'cyan');

        foreach ($shiftsOrfaos as $shift) {
            CLI::write("Criando sessão para Shift ID: {$shift['id_shift']} (Empresa: {$shift['id_empresa']}, Contador: {$shift['id_contador']})");
            
            $db->table('caixa_sessoes')->insert([
                'id_contador' => $shift['id_contador'],
                'id_empresa' => $shift['id_empresa'],
                'id_usuario_abertura' => 1,
                'data_abertura' => $shift['opened_at'],
                'valor_inicial' => $shift['opening_amount'],
                'status' => 'aberto'
            ]);
        }

        CLI::newLine();
        CLI::write('Limpeza concluída!', 'green');
        
        // Mostrar estado final
        CLI::newLine();
        CLI::write('=== ESTADO FINAL ===', 'yellow');
        
        $openSessions = $db->query("SELECT COUNT(*) as count FROM caixa_sessoes WHERE status = 'aberto'")->getFirstRow()->count;
        $openShifts = $db->query("SELECT COUNT(*) as count FROM shifts WHERE status = 'open'")->getFirstRow()->count;
        
        CLI::write("Sessões abertas: $openSessions", 'cyan');
        CLI::write("Shifts abertos: $openShifts", 'cyan');
    }
}
