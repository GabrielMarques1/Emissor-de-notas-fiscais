<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Traits\OutboxTrait;

class BaseAppModel extends Model
{
    use OutboxTrait;

    // Multi-tenant automático
    protected $enforceTenant = true;
    protected $tenantEmpresaField = 'id_empresa';
    protected $tenantContadorField = 'id_contador';

    protected $beforeFind   = ['applyTenantOnFind'];
    protected $beforeInsert = ['applyTenantOnInsert'];

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

    /**
     * Obtém ids de tenant da sessão (ou resolve_tenant_ids)
     */
    protected function resolveTenantIds(): array
    {
        $session = session();
        $idContador = (int) ($session->get('id_contador') ?? 0);
        $idEmpresa  = (int) ($session->get('id_empresa') ?? 0);
        if (($idContador === 0 || $idEmpresa === 0) && function_exists('resolve_tenant_ids')) {
            [$idContador,$idEmpresa] = resolve_tenant_ids();
        }
        return [$idContador, $idEmpresa];
    }

    protected function applyTenantOnFind(array $data)
    {
        if (! $this->enforceTenant) return $data;
        if (! isset($data['builder']) || ! is_object($data['builder'])) return $data;
        [$idContador,$idEmpresa] = $this->resolveTenantIds();
        
        // Verifica se a tabela possui as colunas antes de aplicar filtros
        $tableFields = $this->getTableFields();
        
        if ($this->tenantContadorField && $idContador > 0 && in_array($this->tenantContadorField, $tableFields)) {
            $data['builder']->where($this->tenantContadorField, $idContador);
        }
        if ($this->tenantEmpresaField && $idEmpresa > 0 && in_array($this->tenantEmpresaField, $tableFields)) {
            $data['builder']->where($this->tenantEmpresaField, $idEmpresa);
        }
        return $data;
    }

    protected function applyTenantOnInsert(array $data)
    {
        if (! $this->enforceTenant) return $data;
        [$idContador,$idEmpresa] = $this->resolveTenantIds();
        if (! isset($data['data']) || ! is_array($data['data'])) return $data;
        
        // Verifica se a tabela possui as colunas antes de aplicar valores
        $tableFields = $this->getTableFields();
        
        if ($this->tenantContadorField && empty($data['data'][$this->tenantContadorField]) && $idContador > 0 && in_array($this->tenantContadorField, $tableFields)) {
            $data['data'][$this->tenantContadorField] = $idContador;
        }
        if ($this->tenantEmpresaField && empty($data['data'][$this->tenantEmpresaField]) && $idEmpresa > 0 && in_array($this->tenantEmpresaField, $tableFields)) {
            $data['data'][$this->tenantEmpresaField] = $idEmpresa;
        }
        return $data;
    }

    /**
     * Obtém os campos da tabela atual (com cache)
     */
    private static $tableFieldsCache = [];
    
    protected function getTableFields(): array
    {
        if (!isset(self::$tableFieldsCache[$this->table])) {
            try {
                $fields = $this->db->getFieldNames($this->table);
                self::$tableFieldsCache[$this->table] = $fields;
            } catch (\Exception $e) {
                // Se houver erro ao obter campos, assume que não tem campos tenant
                self::$tableFieldsCache[$this->table] = [];
            }
        }
        return self::$tableFieldsCache[$this->table];
    }

    /**
     * Override findAll para garantir filtragem multi-tenant
     */
    public function findAll(int $limit = 0, int $offset = 0)
    {
        if ($this->enforceTenant) {
            [$idContador,$idEmpresa] = $this->resolveTenantIds();
            $builder = $this->builder();
            
            // Verifica se a tabela possui as colunas antes de aplicar filtros
            $tableFields = $this->getTableFields();
            
            if ($this->tenantContadorField && $idContador > 0 && in_array($this->tenantContadorField, $tableFields)) {
                $builder->where($this->table . '.' . $this->tenantContadorField, $idContador);
            }
            if ($this->tenantEmpresaField && $idEmpresa > 0 && in_array($this->tenantEmpresaField, $tableFields)) {
                $builder->where($this->table . '.' . $this->tenantEmpresaField, $idEmpresa);
            }
            
            if ($limit > 0) {
                $builder->limit($limit, $offset);
            }
            
            return $builder->get()->getResultArray();
        }
        
        return parent::findAll($limit, $offset);
    }

    /**
     * Override find para garantir filtragem multi-tenant
     */
    public function find($id = null)
    {
        if ($this->enforceTenant && $id !== null) {
            [$idContador,$idEmpresa] = $this->resolveTenantIds();
            $builder = $this->builder();
            
            // Verifica se a tabela possui as colunas antes de aplicar filtros
            $tableFields = $this->getTableFields();
            
            if ($this->tenantContadorField && $idContador > 0 && in_array($this->tenantContadorField, $tableFields)) {
                $builder->where($this->table . '.' . $this->tenantContadorField, $idContador);
            }
            if ($this->tenantEmpresaField && $idEmpresa > 0 && in_array($this->tenantEmpresaField, $tableFields)) {
                $builder->where($this->table . '.' . $this->tenantEmpresaField, $idEmpresa);
            }
            
            if (is_array($id)) {
                $builder->whereIn($this->primaryKey, $id);
                return $builder->get()->getResultArray();
            } else {
                $builder->where($this->primaryKey, $id);
                $result = $builder->get()->getFirstRow();
                return $result ? (array) $result : null;
            }
        }
        
        return parent::find($id);
    }

    /**
     * Garante que update respeita o tenant quando id for informado.
     */
    public function update($id = null, $data = null): bool
    {
        if ($this->enforceTenant && $id !== null) {
            $row = $this->asArray()->find(is_array($id) ? ($id[0] ?? null) : $id);
            if ($row) {
                [$idContador,$idEmpresa] = $this->resolveTenantIds();
                // Se não conseguiu resolver IDs de tenant, permite update (para compatibilidade com APIs)
                if ($idContador > 0 || $idEmpresa > 0) {
                    $ok = (
                        (!$this->tenantContadorField || (int) ($row[$this->tenantContadorField] ?? 0) === $idContador) &&
                        (!$this->tenantEmpresaField  || (int) ($row[$this->tenantEmpresaField]  ?? 0) === $idEmpresa)
                    );
                    if (! $ok) { return false; }
                }
            }
        }
        return parent::update($id, $data);
    }

    /**
     * Garante que delete respeita o tenant quando id for informado.
     */
    public function delete($id = null, bool $purge = false)
    {
        if ($this->enforceTenant && $id !== null) {
            $row = $this->asArray()->find(is_array($id) ? ($id[0] ?? null) : $id);
            if ($row) {
                [$idContador,$idEmpresa] = $this->resolveTenantIds();
                $ok = (
                    (!$this->tenantContadorField || (int) ($row[$this->tenantContadorField] ?? 0) === $idContador) &&
                    (!$this->tenantEmpresaField  || (int) ($row[$this->tenantEmpresaField]  ?? 0) === $idEmpresa)
                );
                if (! $ok) { return false; }
            }
        }
        return parent::delete($id, $purge);
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


