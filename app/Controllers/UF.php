<?php

namespace App\Controllers;

use App\Models\MunicipioModel;
use CodeIgniter\Controller;

class UF extends Controller
{
    private $municipio_model;

    function __construct()
    {
        $this->municipio_model = new MunicipioModel();
    }

    public function carregaMunicipios($id_uf)
    {
        $municipios = $this->municipio_model
                           ->where('id_uf', $id_uf)
                           ->orderBy('municipio', 'ASC')
                           ->findAll();

        return $this->response->setJSON($municipios);
    }
}