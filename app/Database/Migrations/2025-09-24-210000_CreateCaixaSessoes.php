<?php namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCaixaSessoes extends Migration
{
	public function up()
	{
		if (! $this->db->tableExists('caixa_sessoes')) {
			$this->forge->addField([
				'id' => [ 'type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true ],
				// Identificação do tenant (opcional, mantém compatibilidade multi-empresa)
				'id_contador' => [ 'type' => 'INT', 'constraint' => 11, 'null' => true ],
				'id_empresa'  => [ 'type' => 'INT', 'constraint' => 11, 'null' => true ],
				// Abertura
				'id_usuario_abertura' => [ 'type' => 'INT', 'constraint' => 11, 'null' => true ],
				'data_abertura'       => [ 'type' => 'DATETIME', 'null' => false ],
				'valor_inicial'        => [ 'type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0 ],
				// Fechamento
				'id_usuario_fechamento'          => [ 'type' => 'INT', 'constraint' => 11, 'null' => true ],
				'data_fechamento'                 => [ 'type' => 'DATETIME', 'null' => true ],
				'valor_final_contado_dinheiro'    => [ 'type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0 ],
				'valor_final_calculado_dinheiro'  => [ 'type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0 ],
				'total_vendas_cartao'             => [ 'type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0 ],
				'total_vendas_pix'                => [ 'type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0 ],
				'total_vendas_outros'             => [ 'type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0 ],
				'diferenca_dinheiro'              => [ 'type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0 ],
				'status'                           => [ 'type' => 'VARCHAR', 'constraint' => 16, 'default' => 'aberto' ],
				'created_at' => [ 'type' => 'DATETIME', 'null' => true ],
				'updated_at' => [ 'type' => 'DATETIME', 'null' => true ],
				'deleted_at' => [ 'type' => 'DATETIME', 'null' => true ],
			]);
			$this->forge->addKey('id', true);
			$this->forge->addKey('status');
			$this->forge->createTable('caixa_sessoes');
		}
	}

	public function down()
	{
		if ($this->db->tableExists('caixa_sessoes')) {
			$this->forge->dropTable('caixa_sessoes');
		}
	}
}
