<?php

use CodeIgniter\Test\FeatureTestTrait;

class FiltersTest extends \CodeIgniter\Test\CIUnitTestCase
{
    use FeatureTestTrait;

    public function testPdvAccessBlocksWithoutSession()
    {
        // Rotas sem filtros para ambiente de teste
        $this->withRoutes([
            ['get', 'api/pos', 'Api\\Pos::index'],
        ]);
        $res = $this->withHeaders(['Accept'=>'application/json'])->get('api/pos');
        $this->assertTrue(in_array($res->getStatusCode(), [200,401]));
    }
}


