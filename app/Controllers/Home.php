<?php namespace App\Controllers;

class Home extends BaseController
{
	public function index()
	{
		//echo View('site/index');
		return redirect()->to('/login');
	}
}
