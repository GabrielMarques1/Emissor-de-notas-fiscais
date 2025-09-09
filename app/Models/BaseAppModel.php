<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Traits\OutboxTrait;

class BaseAppModel extends Model
{
    use OutboxTrait;

    protected $afterInsert = ['outboxAfterInsert'];
    protected $afterUpdate = ['outboxAfterUpdate'];
    protected $afterDelete = ['outboxAfterDelete'];

    protected function shouldRecordOutbox(): bool
    {
        if (function_exists('is_offline_mode')) {
            return is_offline_mode();
        }
        try {
            $dbGroup = config('Database')->defaultGroup ?? 'cloud';
            return $dbGroup === 'local_backup';
        } catch (\Throwable $e) {
            return true;
        }
    }

    protected function outboxAfterInsert(array $data)
    {
        if (! $this->shouldRecordOutbox()) {
            return $data;
        }
        $pkName = $this->primaryKey ?? null;
        if (! $pkName) {
            return $data;
        }
        $payload = $data['data'] ?? [];
        if (! isset($payload[$pkName]) && isset($data['id'])) {
            $payload[$pkName] = is_array($data['id']) ? ($data['id'][0] ?? null) : $data['id'];
        }
        if (isset($payload[$pkName])) {
            $this->outboxRecord('insert', $payload);
        }
        return $data;
    }

    protected function outboxAfterUpdate(array $data)
    {
        if (! $this->shouldRecordOutbox()) {
            return $data;
        }
        $pkName = $this->primaryKey ?? null;
        if (! $pkName) {
            return $data;
        }
        $ids = $data['id'] ?? null;
        $ids = is_array($ids) ? $ids : [$ids];
        foreach ($ids as $id) {
            if ($id === null) {
                continue;
            }
            $payload = $data['data'] ?? [];
            $payload[$pkName] = $id;
            $this->outboxRecord('update', $payload);
        }
        return $data;
    }

    protected function outboxAfterDelete(array $data)
    {
        if (! $this->shouldRecordOutbox()) {
            return $data;
        }
        $pkName = $this->primaryKey ?? null;
        if (! $pkName) {
            return $data;
        }
        $ids = $data['id'] ?? ($data['ids'] ?? null);
        $ids = is_array($ids) ? $ids : [$ids];
        foreach ($ids as $id) {
            if ($id === null) {
                continue;
            }
            $this->outboxRecord('delete', [$pkName => $id]);
        }
        return $data;
    }
}


