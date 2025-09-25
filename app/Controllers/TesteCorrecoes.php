<?php

namespace App\Controllers;

class TesteCorrecoes extends BaseController
{
    public function index()
    {
        $tests = [];
        
        // Teste 1: Template default
        try {
            view('templates/default');
            $tests['template'] = '✅ Template carregado com sucesso';
        } catch (\Exception $e) {
            $tests['template'] = '❌ Erro no template: ' . $e->getMessage();
        }
        
        // Teste 2: BaseAppModel com multi-tenant
        try {
            $empresaModel = new \App\Models\EmpresaModel();
            // Simula uma consulta que pode ter JOINs ambíguos
            $empresas = $empresaModel->limit(1)->findAll();
            $tests['multitenant'] = '✅ Query multi-tenant funcionando';
        } catch (\Exception $e) {
            $tests['multitenant'] = '❌ Erro multi-tenant: ' . $e->getMessage();
        }
        
        // Teste 3: PosSaleItemModel (sem multi-tenant)
        try {
            $itemModel = new \App\Models\PosSaleItemModel();
            $items = $itemModel->limit(1)->findAll();
            $tests['pos_items'] = '✅ PosSaleItemModel funcionando';
        } catch (\Exception $e) {
            $tests['pos_items'] = '❌ Erro pos_items: ' . $e->getMessage();
        }
        
        // Teste 4: Variável $link
        $data = [
            'title' => 'Teste de Correções',
            'tests' => $tests,
            'link' => 'teste'
        ];
        
        return view('teste_correcoes', $data);
    }
}
