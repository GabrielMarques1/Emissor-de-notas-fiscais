<?php

namespace App\Controllers;

use App\Models\CobrancaModel;
use App\Models\EmpresaModel;
use App\Models\ContadorModel;

use CodeIgniter\Controller;

class Cobranca extends Controller
{
	private $tipo = 1; // Somente ADMIN

	private $link = '5';

	private $session;

	private $cobranca_model;
	private $empresa_model;
	private $contador_model;

	function __construct()
	{
		$this->helpers = ['app'];

		$this->session = session();

		$this->cobranca_model = new CobrancaModel();
		$this->empresa_model  = new EmpresaModel();
		$this->contador_model = new ContadorModel();
	}

	public function minhasCobrancasEmpresa()
	{
		$tipoPermitido = 3; // Empresa
		if($retorno = verificaPermissaoDeAcesso($tipoPermitido)) :
			return redirect()->to($retorno);
		endif;

		$id_empresa = $this->session->get('id_empresa');
		$data['cobrancas'] = $this->cobranca_model
							->where('id_empresa', $id_empresa)
							->orderBy('ano_referencia', 'DESC')
							->orderBy('mes_referencia', 'DESC')
							->findAll();

		$data['titulo'] = [
			'modulo' => 'Minhas Cobranças',
			'icone'  => 'fa fa-file-invoice-dollar'
		];

		$data['caminhos'] = [
			['titulo' => "Início", 'rota' => "/inicio/emissor", 'active' => false],
			['titulo' => "Cobranças", 'rota'   => "", 'active' => true]
		];

		$data['link'] = $this->link;

		echo view('templates/header');
		echo view('cobranca/lista_empresa', $data);
		echo view('templates/footer');
	}

	public function minhasCobrancasContador()
	{
		$tipoPermitido = 2; // Contador
		if($retorno = verificaPermissaoDeAcesso($tipoPermitido)) :
			return redirect()->to($retorno);
		endif;

		$id_contador = $this->session->get('id_contador');
		// Lista cobranças de todas as empresas do contador
		$data['cobrancas'] = $this->cobranca_model
							->select('cobrancas_mensais.*, empresas.xFant as empresa_nome, empresas.status as status_empresa')
							->join('empresas', 'empresas.id_empresa = cobrancas_mensais.id_empresa')
							->where('cobrancas_mensais.id_contador', $id_contador)
							->orderBy('ano_referencia', 'DESC')
							->orderBy('mes_referencia', 'DESC')
							->findAll();

		$data['titulo'] = [
			'modulo' => 'Cobranças das Empresas',
			'icone'  => 'fa fa-file-invoice-dollar'
		];

		$data['caminhos'] = [
			['titulo' => "Início", 'rota' => "/inicio/contador", 'active' => false],
			['titulo' => "Cobranças", 'rota'   => "", 'active' => true]
		];

		$data['link'] = $this->link;

		echo view('templates/header');
		echo view('cobranca/lista_contador', $data);
		echo view('templates/footer');
	}

	public function gerarCobrancasMensais()
	{
		if($retorno = verificaPermissaoDeAcesso($this->tipo)) :
			return redirect()->to($retorno);
		endif;

		$mes = intval(date('m'));
		$ano = intval(date('Y'));

		$empresas = $this->empresa_model
					->where('status', 'Ativo')
					->findAll();

		foreach($empresas as $empresa) :
			$existe = $this->cobranca_model
						->where('id_empresa', $empresa['id_empresa'])
						->where('mes_referencia', $mes)
						->where('ano_referencia', $ano)
						->first();

			if(!$existe) :
				$dia_venc = intval($this->contador_model
								->where('id_contador', $empresa['id_contador'])
								->first()['dia_do_pagamento'] ?? 1);

				$dia_venc = max(1, min(28, $dia_venc));
				$data_venc = date('Y-m-d', strtotime(sprintf('%04d-%02d-%02d', $ano, $mes, $dia_venc)));

				$valor = $empresa['valor_mensalidade'] ?? 0.00;

				$this->cobranca_model->insert([
					'mes_referencia'  => $mes,
					'ano_referencia'  => $ano,
					'data_vencimento' => $data_venc,
					'valor_cobranca'  => $valor,
					'status'          => 'Pendente',
					'id_contador'     => $empresa['id_contador'],
					'id_empresa'      => $empresa['id_empresa'],
				]);
			endif;
		endforeach;

		$this->session->setFlashdata('alert', [
			'type' => 'success',
			'title' => 'Cobranças do mês geradas com sucesso!'
		]);

		return redirect()->back();
	}

	public function verificarInadimplencia()
	{
		if($retorno = verificaPermissaoDeAcesso($this->tipo)) :
			return redirect()->to($retorno);
		endif;

		$hoje = date('Y-m-d');
		$cobrancas = $this->cobranca_model
						->where('status', 'Pendente')
						->where('data_vencimento <', $hoje)
						->findAll();

		foreach($cobrancas as $c) :
			// Apenas marca como Vencido. Bloqueio fica a cargo do MASTER manualmente.
			$this->cobranca_model->update($c['id_cobranca'], ['status' => 'Vencido']);
		endforeach;

		$this->session->setFlashdata('alert', [
			'type' => 'warning',
			'title' => 'Cobranças vencidas marcadas. Bloqueio deve ser feito pelo administrador.'
		]);

		return redirect()->back();
	}

	public function bloquearEmpresa($id_empresa)
	{
		if($retorno = verificaPermissaoDeAcesso($this->tipo)) :
			return redirect()->to($retorno);
		endif;

		$hoje = date('Y-m-d');
		$this->empresa_model
				->where('id_empresa', $id_empresa)
				->set('status', 'Desativado')
				->set('data_bloqueio', $hoje)
				->set('motivo_bloqueio', 'Bloqueio manual pelo administrador')
				->update();

		$this->session->setFlashdata('alert', [
			'type' => 'warning',
			'title' => 'Empresa bloqueada com sucesso.'
		]);

		return redirect()->back();
	}

	public function desbloquearEmpresa($id_empresa)
	{
		if($retorno = verificaPermissaoDeAcesso($this->tipo)) :
			return redirect()->to($retorno);
		endif;

		$this->empresa_model
				->where('id_empresa', $id_empresa)
				->set('status', 'Ativo')
				->set('data_bloqueio', null)
				->set('motivo_bloqueio', null)
				->update();

		$this->session->setFlashdata('alert', [
			'type' => 'success',
			'title' => 'Empresa desbloqueada com sucesso.'
		]);

		return redirect()->back();
	}

	public function bloquearContador($id_contador)
	{
		if($retorno = verificaPermissaoDeAcesso($this->tipo)) :
			return redirect()->to($retorno);
		endif;

		$hoje = date('Y-m-d');

		$this->contador_model
				->where('id_contador', $id_contador)
				->set('status', 'Desativado')
				->update();

		$this->empresa_model
				->where('id_contador', $id_contador)
				->set('status', 'Desativado')
				->set('data_bloqueio', $hoje)
				->set('motivo_bloqueio', 'Bloqueio do contador pelo administrador')
				->update();

		$this->session->setFlashdata('alert', [
			'type' => 'warning',
			'title' => 'Contador e todas as suas empresas foram bloqueados.'
		]);

		return redirect()->back();
	}

	public function desbloquearContador($id_contador)
	{
		if($retorno = verificaPermissaoDeAcesso($this->tipo)) :
			return redirect()->to($retorno);
		endif;

		$this->contador_model
				->where('id_contador', $id_contador)
				->set('status', 'Ativo')
				->update();

		$this->empresa_model
				->where('id_contador', $id_contador)
				->set('status', 'Ativo')
				->set('data_bloqueio', null)
				->set('motivo_bloqueio', null)
				->update();

		$this->session->setFlashdata('alert', [
			'type' => 'success',
			'title' => 'Contador e todas as suas empresas foram desbloqueados.'
		]);

		return redirect()->back();
	}

	public function adminLista()
	{
		// Apenas admin
		if($retorno = verificaPermissaoDeAcesso($this->tipo)) :
			return redirect()->to($retorno);
		endif;

		// Filtros simples
		$status = $this->request->getVar('status'); // Pendente, Vencido, Pago, Cancelado ou vazio

		$builder = $this->cobranca_model
						->select('cobrancas_mensais.*, empresas.xFant as empresa_nome, empresas.status as status_empresa')
						->join('empresas', 'empresas.id_empresa = cobrancas_mensais.id_empresa')
						->orderBy('ano_referencia', 'DESC')
						->orderBy('mes_referencia', 'DESC');

		if(!empty($status)) {
			$builder->where('cobrancas_mensais.status', $status);
		} else {
			$builder->groupStart()
					->where('cobrancas_mensais.status', 'Pendente')
					->orWhere('cobrancas_mensais.status', 'Vencido')
					->groupEnd();
		}

		$data['cobrancas'] = $builder->findAll();

		$data['titulo'] = [
			'modulo' => 'Cobranças (Master)',
			'icone'  => 'fa fa-file-invoice-dollar'
		];

		$data['caminhos'] = [
			['titulo' => "Início", 'rota' => "/inicio/admin", 'active' => false],
			['titulo' => "Cobranças", 'rota'   => "", 'active' => true]
		];

		$data['link'] = $this->link;

		echo view('templates/header');
		echo view('cobranca/admin_lista', $data);
		echo view('templates/footer');
	}

	public function marcarComoPago($id_cobranca)
	{
		if($retorno = verificaPermissaoDeAcesso($this->tipo)) :
			return redirect()->to($retorno);
		endif;

		$cobranca = $this->cobranca_model->find($id_cobranca);
		if(!$cobranca) return redirect()->back();

		$this->cobranca_model->update($id_cobranca, [
			'status' => 'Pago',
			'data_pagamento' => date('Y-m-d')
		]);

		// Desbloqueia empresa se estava bloqueada por inadimplência
		$this->empresa_model
				->where('id_empresa', $cobranca['id_empresa'])
				->set('status', 'Ativo')
				->set('motivo_bloqueio', null)
				->set('data_bloqueio', null)
				->update();

		$this->session->setFlashdata('alert', [
			'type' => 'success',
			'title' => 'Cobrança marcada como paga e empresa desbloqueada.'
		]);

		return redirect()->back();
	}
}


