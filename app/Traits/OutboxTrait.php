<?php

namespace App\Traits;

use App\Libraries\Outbox;

trait OutboxTrait
{
    protected function outboxRecord(string $operation, array $data): void
    {
        $table = property_exists($this, 'table') ? $this->table : null;
        $primaryKeyName = property_exists($this, 'primaryKey') ? $this->primaryKey : null;
        if (! $table || ! $primaryKeyName || ! isset($data[$primaryKeyName])) {
            return;
        }
        $primaryKey = [$primaryKeyName => $data[$primaryKeyName]];
        Outbox::record($table, $primaryKey, $operation, $data);
    }
}


