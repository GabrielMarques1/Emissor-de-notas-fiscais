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

        // UPLOAD DO NOVO CERTIFICADO //
        $name = date("d-m-Y") ."_". date("H-i-s") . ".zip";
        $local = "../../writable/uploads/update/";

        $file->store($local, $name);
        // --------------------- //
    }
}
