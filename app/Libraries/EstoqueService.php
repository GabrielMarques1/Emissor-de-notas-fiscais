<?php

namespace App\Libraries;

/**
 * Serviço de estoque: grava itens da venda, registra movimentos e baixa estoque
 */
class EstoqueService
{
    /**
     * Registra itens de venda e baixa estoque.
     *
     * @param array $produtosDaVenda Lista de itens (nome, id_produto, quantidade, valor_unitario, etc.)
     * @param int $idPosSale ID da venda (pos_sales)
     * @param int $idContador Tenant: contador
     * @param int $idEmpresa Tenant: empresa
     */
    public function darBaixaPorVenda(array $produtosDaVenda, int $idPosSale, int $idContador, int $idEmpresa): void
    {
        $itemModel   = new \App\Models\PosSaleItemModel();
        $produtoModel= new \App\Models\ProdutoModel();
        $movModel    = new \App\Models\InventoryMovementModel();

        $baixar = [];
        foreach ($produtosDaVenda as $p) {
            $okItem = $itemModel->insert([
                'id_pos_sale'    => $idPosSale,
                'id_produto'     => isset($p['id_produto']) ? (int) $p['id_produto'] : null,
                'nome'           => $p['nome'] ?? '',
                'codigo_de_barras'=> $p['codigo_de_barras'] ?? 'SEM GTIN',
                'unidade'        => $p['unidade'] ?? 'UN',
                'quantidade'     => $p['quantidade'] ?? 1,
                'valor_unitario' => $p['valor_unitario'] ?? 0,
                'desconto'       => $p['desconto'] ?? 0,
                'CFOP_NFe'       => $p['CFOP_NFe'] ?? null,
                'CFOP_NFCe'      => $p['CFOP_NFCe'] ?? null,
                'CFOP_Externo'   => $p['CFOP_Externo'] ?? null,
                'NCM'            => $p['NCM'] ?? null,
                'CSOSN'          => $p['CSOSN'] ?? null,
            ]);
            if (! $okItem) {
                throw new \RuntimeException('Falha ao salvar item da venda');
            }

            if (isset($p['id_produto'])) {
                $baixar[] = [
                    'id_produto' => (int) $p['id_produto'],
                    'quantidade' => (float) ($p['quantidade'] ?? 0),
                ];
                $movModel->insert([
                    'id_produto'  => (int) $p['id_produto'],
                    'tipo'        => 'saida',
                    'quantidade'  => (float) ($p['quantidade'] ?? 0),
                    'motivo'      => 'PDV venda',
                    'id_pos_sale' => $idPosSale,
                    'id_contador' => $idContador,
                    'id_empresa'  => $idEmpresa,
                ]);
            }
        }
        if (!empty($baixar)) {
            $produtoModel->baixarEstoque($baixar);
        }
    }
}


