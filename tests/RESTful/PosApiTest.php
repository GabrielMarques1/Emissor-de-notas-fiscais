<?php

use CodeIgniter\Test\FeatureTestTrait;

class PosApiTest extends \CodeIgniter\Test\CIUnitTestCase
{
    use FeatureTestTrait;

    public function testIndexResponds()
    {
        $result = $this->withHeaders(['Accept' => 'application/json'])
                       ->get('api/pos');

        $result->assertStatus(200);
    }
}


