<?php

namespace App\Traits;

trait TenantAwareTrait
{
    /**
     * Obter IDs do tenant atual da sessão
     * 
     * @return array [id_contador, id_empresa]
     * @throws \RuntimeException Se tenant não estiver na sessão
     */
    protected function getTenantIds(): array
    {
        $idContador = (int) ($_SESSION['id_contador'] ?? session()->get('id_contador') ?? 0);
        $idEmpresa = (int) ($_SESSION['id_empresa'] ?? session()->get('id_empresa') ?? 0);
        
        if (!$idContador || !$idEmpresa) {
            log_message('error', 'Tenant IDs not found in session', [
                'session_keys' => array_keys($_SESSION ?? []),
            ]);
            
            throw new \RuntimeException('Tenant ID obrigatório. Faça login novamente.');
        }
        
        return [$idContador, $idEmpresa];
    }
    
    /**
     * Validar se um recurso pertence ao tenant atual
     * 
     * @param array|object $resource
     * @param int $idContador
     * @param int $idEmpresa
     * @return bool
     */
    protected function validateTenantOwnership($resource, int $idContador, int $idEmpresa): bool
    {
        $resourceArray = is_array($resource) ? $resource : (array) $resource;
        
        return isset($resourceArray['id_contador'], $resourceArray['id_empresa'])
            && (int) $resourceArray['id_contador'] === $idContador
            && (int) $resourceArray['id_empresa'] === $idEmpresa;
    }
    
    /**
     * Obter informações completas do tenant
     * 
     * @return array
     */
    protected function getTenantInfo(): array
    {
        [$idContador, $idEmpresa] = $this->getTenantIds();
        
        $db = \Config\Database::connect();
        
        $empresa = $db->table('empresas')
                      ->where('id_contador', $idContador)
                      ->where('id_empresa', $idEmpresa)
                      ->get()
                      ->getRowArray();
        
        if (!$empresa) {
            throw new \RuntimeException('Empresa não encontrada');
        }
        
        $contador = $db->table('contadores')
                       ->where('id_contador', $idContador)
                       ->get()
                       ->getRowArray();
        
        return [
            'id_contador' => $idContador,
            'id_empresa' => $idEmpresa,
            'empresa' => $empresa,
            'contador' => $contador,
        ];
    }
}

