<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class CobrancaAutomatica extends BaseCommand
{
	protected $group = 'cobranca';
	protected $name = 'cobranca:processar';
	protected $description = 'Gera cobranças mensais e verifica inadimplência para bloqueio automático.';

	public function run(array $params)
	{
		$cobrancaController = new \App\Controllers\Cobranca();

		$cobrancaController->gerarCobrancasMensais();
		CLI::write('Cobranças mensais geradas.', 'green');

		$cobrancaController->verificarInadimplencia();
		CLI::write('Inadimplência verificada e empresas bloqueadas quando necessário.', 'yellow');
	}
}


