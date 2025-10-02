<?php

namespace App\Controllers;

use App\Models\EmpresaModel;

class PainelEmpresa extends BaseController
{
    private $empresaModel;

    public function __construct()
    {
        $this->empresaModel = new EmpresaModel();
    }

    public function index()
    {
        $session = session();
        $tipo = (int) ($session->get('tipo') ?? 0);
        $idEmpresa = (int) ($session->get('id_empresa') ?? 0);

        // Apenas gerentes/empresas (tipo 3) podem acessar
        if ($tipo !== 3 || $idEmpresa <= 0) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Acesso negado');
        }

        $empresa = $this->empresaModel->find($idEmpresa);
        
        if (!$empresa) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Empresa não encontrada');
        }

        $data = [
            'title' => 'Painel ERP - ' . $empresa['xFant'],
            'empresa' => $empresa,
            'usuario' => $session->get('usuario'),
            'nome_fantasia' => $empresa['xFant'],
            'link' => '1'
        ];

        return view('painel_empresa/index', $data);
    }

    public function pdv()
    {
        $session = session();
        $tipo = (int) ($session->get('tipo') ?? 0);
        $idEmpresa = (int) ($session->get('id_empresa') ?? 0);

        // Permite acesso ao PDV para gerentes (tipo 3)
        if ($tipo !== 3 || $idEmpresa <= 0) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Acesso negado');
        }

        // Redireciona para o PDV moderno
        return redirect()->to('/index.php/pdv');
    }
}
