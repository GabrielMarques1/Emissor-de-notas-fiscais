<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database as DatabaseConfig;
use CodeIgniter\Database\BaseConnection;

class SyncCloud extends BaseCommand
{
    protected $group = 'sync';
    protected $name = 'sync:cloud';
    protected $description = 'Sincroniza dados do banco local (local_backup) para a nuvem (cloud).';
    protected $usage = 'sync:cloud [--tables clientes,empresas] [--limit 0] [--dry] [--use-outbox]';

    public function run(array $params)
    {
        $tablesOption = CLI::getOption('tables');
        $limitOption  = CLI::getOption('limit');
        $dryRun       = CLI::getOption('dry') !== null;
        $useOutbox    = CLI::getOption('use-outbox') !== null;

        $tables = $tablesOption ? array_filter(array_map('trim', explode(',', $tablesOption))) : $this->defaultTables();
        $limit  = is_numeric($limitOption) ? (int) $limitOption : 0; // 0 = sem limite

        $this->executeSync($tables, $limit, $dryRun, $useOutbox);
    }

    public function runWithOptions(array $options = []): void
    {
        $tables    = isset($options['tables']) && is_array($options['tables']) && $options['tables'] ? $options['tables'] : $this->defaultTables();
        $limit     = isset($options['limit']) ? (int) $options['limit'] : 0;
        $dryRun    = !empty($options['dry']);
        $useOutbox = !empty($options['useOutbox']);
        $this->executeSync($tables, $limit, $dryRun, $useOutbox);
    }

    private function executeSync(array $tables, int $limit, bool $dryRun, bool $useOutbox): void
    {
        if (! $this->acquireLock()) {
            if (class_exists(CLI::class)) {
                CLI::write('Outro processo de sync está em execução. Abortando.', 'yellow');
            }
            return;
        }

        $this->log("SYNC START tables=" . implode(',', $tables) . " limit={$limit} dry=" . ($dryRun ? '1' : '0') . " outbox=" . ($useOutbox ? '1' : '0'));

        try {
            $cloud = DatabaseConfig::connect('cloud');
            $local = DatabaseConfig::connect('local_backup');
        } catch (\Throwable $e) {
            $cloud = null; $local = null;
        }

        if (! $cloud || ! $local) {
            if (class_exists(CLI::class)) {
                CLI::error('Não foi possível conectar aos bancos cloud/local_backup. Verifique sua configuração em .env');
            }
            $this->log('SYNC ERROR: conexão indisponível');
            $this->releaseLock();
            return;
        }

        if ($useOutbox) {
            $this->syncOutbox($local, $cloud, $limit, $dryRun);
        } else {
            foreach ($tables as $table) {
                $this->syncTable($local, $cloud, $table, $limit, $dryRun);
            }
        }

        $this->log('SYNC END');
        $this->releaseLock();
    }

    private function defaultTables(): array
    {
        return [
            'clientes',
            'empresas',
            'produtos',
            'configuracoes',
            'contadores',
            'fornecedores',
            'transportadoras',
            'municipios',
            'ufs',
            'pagamentos',
            'nfe',
            'nfce',
        ];
    }

    private function syncTable(BaseConnection $local, BaseConnection $cloud, string $table, int $limit, bool $dryRun): void
    {
        CLI::write("Sincronizando tabela: {$table}", 'yellow');
        $this->log("TABLE START {$table}");

        // Verifica existência das tabelas
        if (! $this->tableExists($local, $table)) {
            CLI::write("- Tabela inexistente no LOCAL: {$table}. Pulando.", 'red');
            $this->log("TABLE SKIP local missing {$table}");
            return;
        }
        if (! $this->tableExists($cloud, $table)) {
            CLI::write("- Tabela inexistente na NUVEM: {$table}. Pulando.", 'red');
            $this->log("TABLE SKIP cloud missing {$table}");
            return;
        }

        $columns = $this->getColumns($local, $table);
        $primaryKeys = $this->getPrimaryKeys($local, $table);
        $hasUpdatedAt = in_array('updated_at', $columns, true);
        $hasDeletedAt = in_array('deleted_at', $columns, true);

        if (empty($primaryKeys)) {
            CLI::write("- Não foi possível detectar chave primária para {$table}. Pulando.", 'red');
            $this->log("TABLE SKIP no primary key {$table}");
            return;
        }

        $builder = $local->table($table);
        if ($hasUpdatedAt) {
            $builder->orderBy('updated_at', 'ASC');
        } else {
            // Ordena por PK para consistência
            foreach ($primaryKeys as $pk) {
                $builder->orderBy($pk, 'ASC');
            }
        }

        $chunk = 500;
        $processed = 0;
        $offset = 0;

        while (true) {
            $q = clone $builder;
            if ($limit > 0) {
                $remaining = max(0, $limit - $processed);
                if ($remaining === 0) {
                    break;
                }
                $q->limit(min($chunk, $remaining), $offset);
            } else {
                $q->limit($chunk, $offset);
            }

            $rows = $q->get()->getResultArray();
            if (! $rows) {
                break;
            }

            foreach ($rows as $row) {
                $processed++;
                $pkWhere = [];
                foreach ($primaryKeys as $pk) {
                    if (! array_key_exists($pk, $row)) {
                        continue 2; // linha inválida sem PK completo
                    }
                    $pkWhere[$pk] = $row[$pk];
                }

                $cloudRow = $cloud->table($table)->where($pkWhere)->get()->getRowArray();

                $shouldInsert = $cloudRow === null;
                $shouldUpdate = false;

                if (! $shouldInsert) {
                    if ($hasUpdatedAt) {
                        $localUpdated   = $row['updated_at'] ?? null;
                        $cloudUpdated   = $cloudRow['updated_at'] ?? null;
                        $shouldUpdate = $localUpdated && (!$cloudUpdated || strtotime((string) $localUpdated) > strtotime((string) $cloudUpdated));
                    } else {
                        // Sem updated_at, força update para manter em sincronia
                        $shouldUpdate = true;
                    }
                }

                // Regras de soft delete: se local está deletado e cloud existe, propagamos delete
                if ($hasDeletedAt && ($row['deleted_at'] ?? null)) {
                    if (! $shouldInsert && $cloudRow && ($cloudRow['deleted_at'] ?? null) !== $row['deleted_at']) {
                        if ($dryRun) {
                            CLI::write("  - DELETE lógico em cloud {$table} " . json_encode($pkWhere), 'blue');
                            $this->log("DELETE {$table} " . json_encode($pkWhere));
                        } else {
                            $cloud->table($table)->where($pkWhere)->update(['deleted_at' => $row['deleted_at']]);
                            $this->log("DELETE OK {$table} " . json_encode($pkWhere));
                        }
                    }
                    // não insere linhas já deletadas no local
                    continue;
                }

                if ($shouldInsert) {
                    if ($dryRun) {
                        CLI::write("  + INSERT em cloud {$table} " . json_encode($pkWhere), 'green');
                        $this->log("INSERT {$table} " . json_encode($pkWhere));
                    } else {
                        $cloud->table($table)->insert($row);
                        $this->log("INSERT OK {$table} " . json_encode($pkWhere));
                    }
                    continue;
                }

                if ($shouldUpdate) {
                    $dataToUpdate = $row;
                    // Nunca altere campos de PK no update
                    foreach ($primaryKeys as $pk) {
                        unset($dataToUpdate[$pk]);
                    }

                    if ($dryRun) {
                        CLI::write("  ~ UPDATE em cloud {$table} " . json_encode($pkWhere), 'yellow');
                        $this->log("UPDATE {$table} " . json_encode($pkWhere));
                    } else {
                        $cloud->table($table)->where($pkWhere)->update($dataToUpdate);
                        $this->log("UPDATE OK {$table} " . json_encode($pkWhere));
                    }
                }
            }

            $offset += $chunk;
        }

        CLI::write("- Concluído {$table}", 'green');
        $this->log("TABLE END {$table}");
    }

    private function tableExists(BaseConnection $db, string $table): bool
    {
        try {
            return in_array($table, $db->listTables(), true);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function getColumns(BaseConnection $db, string $table): array
    {
        try {
            return array_map(static function ($field) { return $field->name; }, $db->getFieldData($table));
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function getPrimaryKeys(BaseConnection $db, string $table): array
    {
        try {
            $index = $db->getIndexData($table);
            foreach ($index as $idx) {
                if (! empty($idx->primary)) {
                    return $idx->fields ?? [];
                }
            }
        } catch (\Throwable $e) {
            // fallback via DESCRIBE
            try {
                $rows = $db->query("DESCRIBE `{$table}`")->getResultArray();
                $pks = [];
                foreach ($rows as $row) {
                    if (($row['Key'] ?? '') === 'PRI') {
                        $pks[] = $row['Field'];
                    }
                }
                return $pks;
            } catch (\Throwable $e2) {
                return [];
            }
        }
        return [];
    }

    private function syncOutbox(BaseConnection $local, BaseConnection $cloud, int $limit, bool $dryRun): void
    {
        if (! $local->tableExists('outbox_events')) {
            CLI::write('- outbox_events não existe no LOCAL. Pulando.', 'yellow');
            return;
        }
        $chunk = 500;
        $processed = 0;
        while (true) {
            // Buscar eventos pendentes ou com retry < max
            $q = $local->table('outbox_events')
                ->whereIn('status', ['pending', 'retry'])
                ->where('(retry_count < 5 OR retry_count IS NULL)')
                ->orderBy('id', 'ASC');
                
            if ($limit > 0) {
                $remaining = max(0, $limit - $processed);
                if ($remaining === 0) break;
                $q->limit(min($chunk, $remaining));
            } else {
                $q->limit($chunk);
            }

            $events = $q->get()->getResultArray();
            if (! $events) break;

            foreach ($events as $ev) {
                $processed++;
                $eventId   = (int) $ev['id'];
                $table     = $ev['table_name'];
                $pkWhere   = json_decode($ev['primary_key_json'] ?? '{}', true) ?: [];
                $operation = $ev['operation'];
                $payload   = $ev['payload'] ? (json_decode($ev['payload'], true) ?: null) : null;
                $idContador = (int) ($ev['id_contador'] ?? 0);
                $idEmpresa  = (int) ($ev['id_empresa'] ?? 0);

                try {
                    // Validação de tenant obrigatória
                    if ($idContador === 0 || $idEmpresa === 0) {
                        $this->log("OUTBOX SKIP invalid tenant {$table} event_id={$eventId}");
                        \App\Libraries\Outbox::markFailed($eventId, 'Tenant inválido');
                        continue;
                    }
                    
                    if (! $cloud->tableExists($table)) {
                        $this->log("OUTBOX SKIP missing table {$table}");
                        \App\Libraries\Outbox::markFailed($eventId, 'Tabela não existe na nuvem');
                        continue;
                    }
                    
                    // Adicionar tenant_id ao where para garantir isolamento
                    $tenantWhere = array_merge($pkWhere, [
                        'id_contador' => $idContador,
                        'id_empresa' => $idEmpresa
                    ]);
                    
                    if ($operation === 'delete') {
                        if ($dryRun) {
                            CLI::write("  - OUTBOX DELETE {$table} " . json_encode($pkWhere) . " tenant:{$idContador}:{$idEmpresa}", 'blue');
                            $this->log("OUTBOX DELETE {$table} " . json_encode($pkWhere) . " tenant:{$idContador}:{$idEmpresa}");
                        } else {
                            $affected = $cloud->table($table)->where($tenantWhere)->update(['deleted_at' => date('Y-m-d H:i:s')]);
                            $this->log("OUTBOX DELETE OK {$table} " . json_encode($pkWhere) . " affected={$affected}");
                            \App\Libraries\Outbox::markProcessed($eventId);
                        }
                    } elseif ($operation === 'insert') {
                        if ($dryRun) {
                            CLI::write("  + OUTBOX INSERT {$table} " . json_encode($pkWhere) . " tenant:{$idContador}:{$idEmpresa}", 'green');
                            $this->log("OUTBOX INSERT {$table} " . json_encode($pkWhere));
                        } else {
                            // Verificar conflito: registro já existe na nuvem?
                            $exists = $cloud->table($table)->where($tenantWhere)->get()->getRowArray();
                            
                            if ($exists) {
                                // Conflito: resolver com estratégia last-write-wins baseado em updated_at
                                $conflict = $this->resolveConflict($exists, $payload, $table);
                                
                                if ($conflict === 'local_wins') {
                                    $cloud->table($table)->where($tenantWhere)->update($payload ?? $pkWhere);
                                    $this->log("OUTBOX CONFLICT RESOLVED local_wins {$table} " . json_encode($pkWhere));
                                } else {
                                    $this->log("OUTBOX CONFLICT RESOLVED cloud_wins {$table} " . json_encode($pkWhere));
                                }
                            } else {
                                // Garantir tenant_id no payload
                                if ($payload) {
                                    $payload['id_contador'] = $idContador;
                                    $payload['id_empresa'] = $idEmpresa;
                                }
                                $cloud->table($table)->insert($payload ?? array_merge($pkWhere, ['id_contador' => $idContador, 'id_empresa' => $idEmpresa]));
                                $this->log("OUTBOX INSERT OK {$table} " . json_encode($pkWhere));
                            }
                            \App\Libraries\Outbox::markProcessed($eventId);
                        }
                    } else { // update
                        if ($dryRun) {
                            CLI::write("  ~ OUTBOX UPDATE {$table} " . json_encode($pkWhere) . " tenant:{$idContador}:{$idEmpresa}", 'yellow');
                            $this->log("OUTBOX UPDATE {$table} " . json_encode($pkWhere));
                        } else {
                            // Verificar se registro existe antes de atualizar
                            $exists = $cloud->table($table)->where($tenantWhere)->get()->getRowArray();
                            
                            if ($exists) {
                                // Resolver conflito se necessário
                                $conflict = $this->resolveConflict($exists, $payload, $table);
                                
                                if ($conflict === 'local_wins') {
                                    $affected = $cloud->table($table)->where($tenantWhere)->update($payload ?? $pkWhere);
                                    $this->log("OUTBOX UPDATE OK {$table} " . json_encode($pkWhere) . " affected={$affected}");
                                } else {
                                    $this->log("OUTBOX UPDATE SKIP cloud_newer {$table} " . json_encode($pkWhere));
                                }
                            } else {
                                // Registro não existe na nuvem, fazer insert
                                if ($payload) {
                                    $payload['id_contador'] = $idContador;
                                    $payload['id_empresa'] = $idEmpresa;
                                }
                                $cloud->table($table)->insert($payload ?? array_merge($pkWhere, ['id_contador' => $idContador, 'id_empresa' => $idEmpresa]));
                                $this->log("OUTBOX UPDATE->INSERT {$table} " . json_encode($pkWhere));
                            }
                            \App\Libraries\Outbox::markProcessed($eventId);
                        }
                    }

                } catch (\Throwable $e) {
                    $errorMsg = $e->getMessage();
                    $this->log("OUTBOX ERROR {$table} id={$eventId} " . $errorMsg);
                    \App\Libraries\Outbox::markFailed($eventId, $errorMsg);
                }
            }
        }
    }
    
    /**
     * Resolve conflito entre versão local e cloud
     * Estratégia: last-write-wins baseado em updated_at
     * 
     * @return string 'local_wins' ou 'cloud_wins'
     */
    private function resolveConflict(array $cloudData, ?array $localData, string $table): string
    {
        if (!$localData) {
            return 'cloud_wins';
        }
        
        $cloudUpdated = $cloudData['updated_at'] ?? $cloudData['created_at'] ?? null;
        $localUpdated = $localData['updated_at'] ?? $localData['created_at'] ?? null;
        
        if (!$cloudUpdated || !$localUpdated) {
            // Sem timestamps, preferir local (foi modificado offline)
            $this->log("CONFLICT no timestamps, preferring local for {$table}");
            return 'local_wins';
        }
        
        $cloudTime = strtotime((string) $cloudUpdated);
        $localTime = strtotime((string) $localUpdated);
        
        if ($localTime > $cloudTime) {
            $this->log("CONFLICT local newer for {$table}");
            return 'local_wins';
        } else {
            $this->log("CONFLICT cloud newer for {$table}");
            return 'cloud_wins';
        }
    }

    private function acquireLock(): bool
    {
        $lockFile = WRITEPATH . 'sync.lock';
        if (! is_dir(WRITEPATH)) {
            @mkdir(WRITEPATH, 0777, true);
        }
        if (file_exists($lockFile)) {
            $age = time() - filemtime($lockFile);
            if ($age < 3600) { // lock válido por 1h
                return false;
            }
        }
        @file_put_contents($lockFile, (string) getmypid());
        return true;
    }

    private function releaseLock(): void
    {
        $lockFile = WRITEPATH . 'sync.lock';
        if (file_exists($lockFile)) {
            @unlink($lockFile);
        }
    }

    private function log(string $message): void
    {
        $logDir = WRITEPATH . 'logs';
        if (! is_dir($logDir)) {
            @mkdir($logDir, 0777, true);
        }
        $file = $logDir . DIRECTORY_SEPARATOR . 'sync-cloud-' . date('Y-m-d') . '.log';
        $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
        @file_put_contents($file, $line, FILE_APPEND);
    }
}


