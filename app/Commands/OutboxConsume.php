<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

class OutboxConsume extends BaseCommand
{
    protected $group       = 'Outbox';
    protected $name        = 'outbox:consume';
    protected $description = 'Processa eventos outbox pendentes (demonstração).' ;

    public function run(array $params)
    {
        $db = Database::connect();
        if (! $db->tableExists('outbox_events')) {
            CLI::error('Tabela outbox_events não existe.');
            return;
        }
        $rows = $db->table('outbox_events')->where('processed_at IS NULL', null, false)->orderBy('id','ASC')->limit(50)->get()->getResultArray();
        if (empty($rows)) {
            CLI::write('Nenhum evento pendente.', 'yellow');
            return;
        }
        foreach ($rows as $ev) {
            // Aqui você poderia publicar em fila/webhook. Por ora, apenas loga.
            CLI::write('[Outbox] ' . ($ev['operation'] ?? '') . ' ' . ($ev['table_name'] ?? '') . ' ' . ($ev['primary_key_json'] ?? ''), 'green');
            $db->table('outbox_events')->where('id', (int) $ev['id'])->update(['processed_at' => date('Y-m-d H:i:s')]);
        }
        CLI::write('Processamento concluído.', 'green');
    }
}
