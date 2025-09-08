<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class Upload extends Controller
{
    public function __construct(){
    }

    public function index()
    {
        echo View('upload/index');
    }

    public function store()
    {
        // Pega os dados do formulário
        $file = $this->request->getFile('file');
        if (!$file || !$file->isValid()) {
            return redirect()->back()->with('alert', ['type' => 'error', 'title' => 'Arquivo inválido.']);
        }
        $ext = strtolower($file->getClientExtension());
        if ($ext !== 'zip') {
            return redirect()->back()->with('alert', ['type' => 'error', 'title' => 'Apenas .zip permitido.']);
        }
        if ($file->getSize() > 50 * 1024 * 1024) { // 50MB
            return redirect()->back()->with('alert', ['type' => 'error', 'title' => 'Arquivo muito grande.']);
        }

        // UPLOAD DO NOVO CERTIFICADO //
        $name = date("d-m-Y") ."_". date("H-i-s") . ".zip";
        $local = "../../writable/uploads/update/";

        if (!$file->store($local, $name)) {
            return redirect()->back()->with('alert', ['type' => 'error', 'title' => 'Falha ao salvar o arquivo.']);
        }
        return redirect()->back()->with('alert', ['type' => 'success', 'title' => 'Upload concluído.']);
        // --------------------- //
    }
}
