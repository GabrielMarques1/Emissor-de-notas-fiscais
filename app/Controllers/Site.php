<?php

namespace App\Controllers;

class Site extends BaseController
{
    public function precos()
    {
        echo view('templates/header');
        echo view('site/pricing');
        echo view('templates/footer');
    }
}


