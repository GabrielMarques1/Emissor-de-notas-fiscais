<?php

namespace App\Libraries;

use Config\Database;

class Outbox
{
    public static function record(string $table, array $primaryKey, string $operation, ?array $payload = null): void
    {
        try {
            $db = Database::connect();
            if (! $db->tableExists('outbox_events')) {
                return; // sem tabela, não falha a operação original
            }
            $db->table('outbox_events')->insert([
                'table_name'       => $table,
                'primary_key_json' => json_encode($primaryKey, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'operation'        => $operation,
                'payload'          => $payload ? json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
                'created_at'       => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            // não interrompe fluxo do app
        }
    }
}


