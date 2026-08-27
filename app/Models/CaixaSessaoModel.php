<?php

namespace App\Models;

use CodeIgniter\Model;

class CaixaSessaoModel extends BaseAppModel
{
    protected $enforceTenant = false; // Desabilitar para evitar problemas com find()
	protected $table      = 'caixa_sessoes';
	protected $primaryKey = 'id';
	protected $returnType = 'array';
	protected $useSoftDeletes = true;

	protected $allowedFields = [
		'id_contador','id_empresa',
		'id_usuario_abertura','data_abertura','valor_inicial',
		'id_usuario_fechamento','data_fechamento',
		'valor_final_contado_dinheiro','valor_final_calculado_dinheiro',
		'total_vendas_cartao','total_vendas_pix','total_vendas_outros',
		'diferenca_dinheiro','status'
	];

	protected $useTimestamps = true;
	protected $createdField  = 'created_at';
	protected $updatedField  = 'updated_at';
	protected $deletedField  = 'deleted_at';

	/**
	 * Abre uma sessão de caixa com verificação de exclusividade por tenant.
	 */
	public function openSession(int $idContador, int $idEmpresa, int $idUsuario, float $valorInicial): array
	{
		$db = \Config\Database::connect();
		$db->transStart();
		$db->query(
			"SELECT id FROM caixa_sessoes WHERE status='aberto' AND id_contador=? AND id_empresa=? LIMIT 1 FOR UPDATE",
			[$idContador ?: 0, $idEmpresa ?: 0]
		);
		$exists = $db->table('caixa_sessoes')
			->select('id')
			->where('status', 'aberto')
			->where('id_contador', $idContador ?: 0)
			->where('id_empresa', $idEmpresa ?: 0)
			->get(1)->getFirstRow('array');
		if ($exists) {
			$db->transComplete();
			throw new \RuntimeException('Já existe um caixa aberto.');
		}
		$data = [
			'id_contador' => $idContador ?: null,
			'id_empresa'  => $idEmpresa  ?: null,
			'id_usuario_abertura' => $idUsuario ?: null,
			'data_abertura' => date('Y-m-d H:i:s'),
			'valor_inicial' => round($valorInicial, 2),
			'status' => 'aberto',
		];
		if (! $this->insert($data)) {
			$db->transComplete();
			throw new \RuntimeException('Falha ao abrir caixa: ' . json_encode($this->errors()));
		}
		$id = (int) $this->getInsertID();
		$db->transComplete();
		if ($db->transStatus() === false) {
			throw new \RuntimeException('Falha transacional ao abrir caixa');
		}
		return $this->find($id);
	}

	/**
	 * Alias PT-BR: abrirSessao. Retorna array do registro ou false em falha.
	 */
	public function abrirSessao(int $idUsuario, float $valorInicial, int $idContador = 0, int $idEmpresa = 0)
	{
		try {
			return $this->openSession($idContador, $idEmpresa, $idUsuario, $valorInicial);
		} catch (\Throwable $e) {
			return false;
		}
	}

	/**
	 * Fecha a sessão de caixa aberta do tenant, consolidando vendas por id_caixa_sessao.
	 */
	public function closeOpenSession(int $idContador, int $idEmpresa, int $idUsuario, float $valorContado): array
	{
		log_message('debug', 'CaixaSessaoModel::closeOpenSession - Início: contador={cont}, empresa={emp}, usuario={user}, valor={val}', [
			'cont' => $idContador, 'emp' => $idEmpresa, 'user' => $idUsuario, 'val' => $valorContado
		]);
		
		$db = \Config\Database::connect();
		
		// Buscar sessão aberta sem transação para evitar deadlocks
		$caixaRow = $db->query(
			"SELECT * FROM caixa_sessoes WHERE status='aberto' AND id_contador=? AND id_empresa=? ORDER BY id DESC LIMIT 1",
			[$idContador ?: 0, $idEmpresa ?: 0]
		)->getFirstRow('array');
		
		log_message('debug', 'CaixaSessaoModel::closeOpenSession - Resultado query: {result}', ['result' => json_encode($caixaRow)]);
		
		if (! $caixaRow) {
			throw new \RuntimeException('Nenhum caixa aberto encontrado.');
		}
		$idCaixa = (int) $caixaRow['id'];
		$valorInicial = (float) ($caixaRow['valor_inicial'] ?? 0);
		
		log_message('debug', 'CaixaSessaoModel::closeOpenSession - Caixa encontrado: id={id}, valor_inicial={val}', ['id' => $idCaixa, 'val' => $valorInicial]);
		$rows = $db->table('pos_sales')
			->select('payment_type, SUM(total) as valor')
			->where('status', 'finalized')
			->where('id_caixa_sessao', $idCaixa)
			->groupBy('payment_type')
			->get()->getResultArray();
		$totais = ['cash'=>0.0,'credit'=>0.0,'debit'=>0.0,'pix'=>0.0,'voucher'=>0.0,'others'=>0.0];
		foreach ($rows as $r) {
			$pt = strtolower((string) ($r['payment_type'] ?? ''));
			$v  = (float) ($r['valor'] ?? 0);
			if (array_key_exists($pt, $totais)) { $totais[$pt] += $v; } else { $totais['others'] += $v; }
		}
		$totalCartao   = round($totais['credit'] + $totais['debit'], 2);
		$totalPix      = round($totais['pix'], 2);
		$totalOutros   = round($totais['voucher'] + $totais['others'], 2);
		$totalDinheiro = round($totais['cash'], 2);
		$valorCalculado = round($valorInicial + $totalDinheiro, 2);
		$diferenca = round(round($valorContado,2) - $valorCalculado, 2);
		$updateData = [
			'id_usuario_fechamento'          => $idUsuario ?: null,
			'data_fechamento'                => date('Y-m-d H:i:s'),
			'valor_final_contado_dinheiro'   => round($valorContado,2),
			'valor_final_calculado_dinheiro' => $valorCalculado,
			'total_vendas_cartao'            => $totalCartao,
			'total_vendas_pix'               => $totalPix,
			'total_vendas_outros'            => $totalOutros,
			'diferenca_dinheiro'             => $diferenca,
			'status'                         => 'fechado',
		];
		
		log_message('debug', 'CaixaSessaoModel::closeOpenSession - Fazendo update: id={id}, dados={dados}', [
			'id' => $idCaixa, 'dados' => json_encode($updateData)
		]);
		
		$ok = $db->table('caixa_sessoes')
			->where('id', (int) $idCaixa)
			->where('status', 'aberto')
			->update($updateData);
			
		$affectedRows = $db->affectedRows();
		log_message('debug', 'CaixaSessaoModel::closeOpenSession - Update result: ok={ok}, affected={aff}', [
			'ok' => $ok ? 'true' : 'false', 'aff' => $affectedRows
		]);
		
		if (! $ok || $affectedRows === 0) {
			log_message('error', 'CaixaSessaoModel::closeOpenSession - Falha no update: ok={ok}, affected={aff}', [
				'ok' => $ok ? 'true' : 'false', 'aff' => $affectedRows
			]);
			throw new \RuntimeException('Falha ao fechar o caixa.');
		}
		
		$result = $this->find($idCaixa);
		log_message('info', 'CaixaSessaoModel::closeOpenSession - Sucesso! Sessão fechada: id={id}', ['id' => $idCaixa]);
		return $result;
	}

	/**
	 * Fecha sessão por ID específico. Retorna registro atualizado ou false.
	 */
	public function fecharSessao(int $idSessao, int $idUsuario, float $valorContado)
	{
		$db = \Config\Database::connect();
		$db->transStart();
		$caixaRow = $db->table('caixa_sessoes')
			->where('id', (int) $idSessao)
			->where('status', 'aberto')
			->get(1)->getFirstRow('array');
		if (! $caixaRow) { $db->transComplete(); return false; }
		$idContador = (int) ($caixaRow['id_contador'] ?? 0);
		$idEmpresa  = (int) ($caixaRow['id_empresa'] ?? 0);
		$valorInicial = (float) ($caixaRow['valor_inicial'] ?? 0);
		$rows = $db->table('pos_sales')
			->select('payment_type, SUM(total) as valor')
			->where('status', 'finalized')
			->where('id_caixa_sessao', (int) $idSessao)
			->groupBy('payment_type')
			->get()->getResultArray();
		$totais = ['cash'=>0.0,'credit'=>0.0,'debit'=>0.0,'pix'=>0.0,'voucher'=>0.0,'others'=>0.0];
		foreach ($rows as $r) {
			$pt = strtolower((string) ($r['payment_type'] ?? ''));
			$v  = (float) ($r['valor'] ?? 0);
			if (array_key_exists($pt, $totais)) { $totais[$pt] += $v; } else { $totais['others'] += $v; }
		}
		$totalCartao   = round($totais['credit'] + $totais['debit'], 2);
		$totalPix      = round($totais['pix'], 2);
		$totalOutros   = round($totais['voucher'] + $totais['others'], 2);
		$totalDinheiro = round($totais['cash'], 2);
		$valorCalculado = round($valorInicial + $totalDinheiro, 2);
		$diferenca = round(round($valorContado,2) - $valorCalculado, 2);
		$ok = $db->table('caixa_sessoes')
			->where('id', (int) $idSessao)
			->where('status', 'aberto')
			->update([
				'id_usuario_fechamento'          => $idUsuario ?: null,
				'data_fechamento'                => date('Y-m-d H:i:s'),
				'valor_final_contado_dinheiro'   => round($valorContado,2),
				'valor_final_calculado_dinheiro' => $valorCalculado,
				'total_vendas_cartao'            => $totalCartao,
				'total_vendas_pix'               => $totalPix,
				'total_vendas_outros'            => $totalOutros,
				'diferenca_dinheiro'             => $diferenca,
				'status'                         => 'fechado',
			]);
		$db->transComplete();
		log_message('debug', 'CaixaSessaoModel::closeOpenSession - Update result: ok={ok}, affected={aff}, transStatus={trans}', [
			'ok' => $ok ? 'true' : 'false', 'aff' => $db->affectedRows(), 'trans' => $db->transStatus() ? 'true' : 'false'
		]);
		
		if (! $ok || $db->affectedRows() === 0 || $db->transStatus() === false) { 
			log_message('error', 'CaixaSessaoModel::closeOpenSession - Falha no update');
			return false; 
		}
		
		$result = $this->find((int) $idSessao);
		log_message('info', 'CaixaSessaoModel::closeOpenSession - Sucesso! Sessão fechada: id={id}', ['id' => $idSessao]);
		return $result;
	}

	/**
	 * Retorna o ID da sessão aberta para o tenant, ou null.
	 */
	public function findOpenSessionId(int $idContador, int $idEmpresa): ?int
	{
		$row = $this->asArray()
			->where('status', 'aberto')
			->where('id_contador', $idContador ?: 0)
			->where('id_empresa',  $idEmpresa  ?: 0)
			->orderBy('id', 'DESC')
			->first();
		if (!is_array($row)) return null;
		return (int) ($row['id'] ?? 0) ?: null;
	}
}
