<?php

namespace Tests\Support;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

abstract class MultiTenantTestCase extends CIUnitTestCase
{
    use DatabaseTestTrait;
    
    protected $refresh = true;
    protected $namespace = 'App';
    
    /**
     * IDs de tenants para testes
     */
    protected int $tenant1Contador = 1;
    protected int $tenant1Empresa = 100;
    
    protected int $tenant2Contador = 1;
    protected int $tenant2Empresa = 200;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Criar tenants de teste
        $this->createTestTenants();
    }
    
    protected function createTestTenants(): void
    {
        $db = \Config\Database::connect();
        
        // Verificar se contador já existe
        $contador = $db->table('contadores')
                       ->where('id_contador', $this->tenant1Contador)
                       ->get()
                       ->getRowArray();
        
        if (!$contador) {
            $db->table('contadores')->insert([
                'id_contador' => $this->tenant1Contador,
                'nome' => 'Contador Teste',
            ]);
        }
        
        // Verificar se empresas já existem
        $empresa1 = $db->table('empresas')
                       ->where('id_empresa', $this->tenant1Empresa)
                       ->get()
                       ->getRowArray();
        
        if (!$empresa1) {
            $db->table('empresas')->insert([
                'id_empresa' => $this->tenant1Empresa,
                'id_contador' => $this->tenant1Contador,
                'xFant' => 'Empresa Tenant 1',
                'CNPJ' => '11111111000111',
            ]);
        }
        
        $empresa2 = $db->table('empresas')
                       ->where('id_empresa', $this->tenant2Empresa)
                       ->get()
                       ->getRowArray();
        
        if (!$empresa2) {
            $db->table('empresas')->insert([
                'id_empresa' => $this->tenant2Empresa,
                'id_contador' => $this->tenant2Contador,
                'xFant' => 'Empresa Tenant 2',
                'CNPJ' => '22222222000122',
            ]);
        }
    }
    
    /**
     * Simula sessão de tenant
     */
    protected function actingAsTenant(int $idContador, int $idEmpresa): void
    {
        $_SESSION['id_contador'] = $idContador;
        $_SESSION['id_empresa'] = $idEmpresa;
        $_SESSION['tipo'] = 1; // Admin
        $_SESSION['usuario'] = 'teste';
        $_SESSION['id_login'] = 1;
        
        session()->set([
            'id_contador' => $idContador,
            'id_empresa' => $idEmpresa,
            'tipo' => 1,
            'usuario' => 'teste',
            'id_login' => 1,
        ]);
    }
    
    /**
     * Valida que recurso pertence ao tenant
     */
    protected function assertTenantOwnership(array $resource, int $idContador, int $idEmpresa): void
    {
        $this->assertEquals($idContador, $resource['id_contador'], 'Recurso não pertence ao contador correto');
        $this->assertEquals($idEmpresa, $resource['id_empresa'], 'Recurso não pertence à empresa correta');
    }
    
    /**
     * Valida que query não retorna dados de outro tenant
     */
    protected function assertNoTenantLeakage(array $results, int $idContador, int $idEmpresa): void
    {
        foreach ($results as $item) {
            $this->assertTenantOwnership($item, $idContador, $idEmpresa);
        }
    }
    
    /**
     * Valida tenant ownership e retorna bool
     */
    protected function validateTenantOwnership(array $resource, int $idContador, int $idEmpresa): bool
    {
        return isset($resource['id_contador'], $resource['id_empresa']) 
            && $resource['id_contador'] == $idContador 
            && $resource['id_empresa'] == $idEmpresa;
    }
}

