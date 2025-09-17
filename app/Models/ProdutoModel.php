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
        foreach ($itens as $it) {
            if (!isset($it['id_produto']) || !isset($it['quantidade'])) continue;
            $id = (int) $it['id_produto'];
            $qtd = (float) $it['quantidade'];
            if ($id > 0 && $qtd > 0) {
                // campo hipotético "estoque"; ajuste se seu schema for diferente
                $this->db->query('UPDATE produtos SET estoque = estoque - ? WHERE id_produto = ?', [$qtd, $id]);
            }
        }
    }

    public function estornarEstoque(array $itens): void
    {
        foreach ($itens as $it) {
            if (!isset($it['id_produto']) || !isset($it['quantidade'])) continue;
            $id = (int) $it['id_produto'];
            $qtd = (float) $it['quantidade'];
            if ($id > 0 && $qtd > 0) {
                $this->db->query('UPDATE produtos SET estoque = estoque + ? WHERE id_produto = ?', [$qtd, $id]);
            }
        }
    }
}
