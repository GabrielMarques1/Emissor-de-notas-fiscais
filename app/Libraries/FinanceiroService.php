<?php

namespace App\Libraries;

class FinanceiroService
{
    /**
     * Cria lançamento de recebimento em caixa para a venda
     */
    public function criarLancamentoPorVenda(int $idPosSale, float $valor, int $idContador, int $idEmpresa): void
    {
        if ($valor <= 0) { return; }
        $pag = new \App\Models\PagamentoModel();
        $pag->insert([
            'data_do_pagamento' => date('Y-m-d'),
            'valor'             => $valor,
            'observacoes'       => 'PDV venda #' . $idPosSale,
            'id_contador'       => $idContador,
            'id_empresa'        => $idEmpresa,
        ]);
    }
}


