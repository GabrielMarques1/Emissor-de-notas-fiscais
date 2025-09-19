<?php

use CodeIgniter\Test\FeatureTestTrait;

class CartProductsTest extends \CodeIgniter\Test\CIUnitTestCase
{
    use FeatureTestTrait;

    public function testProductsSearchAndBarcode()
    {
        $this->withRoutes([
            ['get', 'api/products/search', 'Api\\Products::search'],
            ['get', 'api/products/barcode/(:any)', 'Api\\Products::barcode/$1'],
        ]);
        $seeder = \Config\Database::seeder();
        try { $seeder->call('App\\Database\\Seeds\\DatabaseSeeder'); } catch (\Throwable $e) {}
        $db = \Config\Database::connect();
        $empresa = $db->table('empresas')->orderBy('id_empresa','ASC')->get()->getRowArray();
        $contador = $db->table('contadores')->orderBy('id_contador','ASC')->get()->getRowArray();
        $sessionData = ['id_contador'=>(int) ($contador['id_contador'] ?? 1), 'id_empresa'=>(int) ($empresa['id_empresa'] ?? 1)];
        $resp1 = $this->withSession($sessionData)->withHeaders(['Accept'=>'application/json'])->get('api/products/search?q=Teste');
        $this->assertTrue(in_array($resp1->getStatusCode(), [200,404]));
        $resp2 = $this->withSession($sessionData)->withHeaders(['Accept'=>'application/json'])->get('api/products/barcode/123');
        $this->assertTrue(in_array($resp2->getStatusCode(), [200,404]));
    }

    public function testCartCreateUpdateDelete()
    {
        $this->withRoutes([
            ['get', 'api/cart', 'Api\\Cart::index'],
            ['post', 'api/cart', 'Api\\Cart::create'],
            ['delete', 'api/cart/(:num)', 'Api\\Cart::delete/$1'],
            ['patch', 'api/cart/(:num)', 'Api\\Cart::update/$1'],
        ]);
        // Seed e sessão (FKs)
        $seeder = \Config\Database::seeder();
        try { $seeder->call('App\\Database\\Seeds\\DatabaseSeeder'); } catch (\Throwable $e) {}
        $db = \Config\Database::connect();
        $empresa = $db->table('empresas')->orderBy('id_empresa','ASC')->get()->getRowArray();
        $contador = $db->table('contadores')->orderBy('id_contador','ASC')->get()->getRowArray();
        $sessionData = ['id_contador'=>(int) ($contador['id_contador'] ?? 1), 'id_empresa'=>(int) ($empresa['id_empresa'] ?? 1)];

        $payload = [
            'nome' => 'Teste',
            'unidade' => 'UN',
            'quantidade' => 1,
            'valor_unitario' => 10,
            'desconto' => 0,
            'CFOP_NFCe' => '5102',
            'NCM' => '00000000',
            'CSOSN' => '102'
        ];
        $res = $this->withSession($sessionData)->withHeaders(['Accept'=>'application/json'])->post('api/cart', $payload);
        $this->assertTrue(in_array($res->getStatusCode(), [201,200]));
        $item = json_decode($res->getJSON(), true);
        $id = $item['id_produto_provisorio'] ?? null;
        if ($id) {
            $u = $this->withSession($sessionData)->withHeaders(['Accept'=>'application/json'])->patch('api/cart/'.$id, json_encode(['quantidade'=>2]), ['CONTENT_TYPE'=>'application/json']);
            $this->assertTrue(in_array($u->getStatusCode(), [200]));
            $d = $this->withSession($sessionData)->withHeaders(['Accept'=>'application/json'])->delete('api/cart/'.$id);
            $this->assertTrue(in_array($d->getStatusCode(), [200]));
        }
    }
}


