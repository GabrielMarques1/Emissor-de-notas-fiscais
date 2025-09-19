<?php

namespace App\Models;

class ProdutoModel extends BaseAppModel
{
    protected $table = 'produtos';
    protected $primaryKey = 'id_produto';
    protected $allowedFields = [
        'id_produto',
        'nome',
        'codigo_de_barras',
        'valor_unitario',
        'estoque',
        'CFOP_NFe',
        'CFOP_NFCe',
        'CFOP_Externo',
        'NCM',
        'CSOSN',
        'id_unidade',
        'id_contador',
        'id_empresa'
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    public function baixarEstoque(array $itens): void
    {
        $db = $this->db;
        $db->transStart();
        foreach ($itens as $it) {
            if (!isset($it['id_produto']) || !isset($it['quantidade'])) continue;
            $id = (int) $it['id_produto'];
            $qtd = (float) $it['quantidade'];
            if ($id <= 0 || $qtd <= 0) continue;
            // leitura atual
            $row = $this->asArray()->find($id);
            if (! $row) continue;
            $estoqueAtual = (float) ($row['estoque'] ?? 0);
            $novo = $estoqueAtual - $qtd;
            if ($novo < 0) {
                $db->transRollback();
                throw new \RuntimeException('Estoque insuficiente para o produto #' . $id);
            }
            // update otimista por WHERE id e estoque atual
            $this->builder()->where('id_produto', $id)->set('estoque', $novo)->update();
        }
        $db->transComplete();
        if ($db->transStatus() === false) {
            throw new \RuntimeException('Falha na transação de baixa de estoque');
        }
    }

    public function estornarEstoque(array $itens): void
    {
        $db = $this->db;
        $db->transStart();
        foreach ($itens as $it) {
            if (!isset($it['id_produto']) || !isset($it['quantidade'])) continue;
            $id = (int) $it['id_produto'];
            $qtd = (float) $it['quantidade'];
            if ($id <= 0 || $qtd <= 0) continue;
            $row = $this->asArray()->find($id);
            if (! $row) continue;
            $estoqueAtual = (float) ($row['estoque'] ?? 0);
            $novo = $estoqueAtual + $qtd;
            $this->builder()->where('id_produto', $id)->set('estoque', $novo)->update();
        }
        $db->transComplete();
        if ($db->transStatus() === false) {
            throw new \RuntimeException('Falha na transação de estorno de estoque');
        }
    }
}
