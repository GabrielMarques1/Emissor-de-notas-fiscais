<?php namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class MakeCertNullable extends Migration
{
    public function up()
    {
        $fields = [
            'certificado' => [
                'name'       => 'certificado',
                'type'       => 'VARCHAR',
                'constraint' => 128,
                'null'       => true,
            ],
            'senha_do_certificado' => [
                'name'       => 'senha_do_certificado',
                'type'       => 'VARCHAR',
                'constraint' => 128,
                'null'       => true,
            ],
        ];

        $this->forge->modifyColumn('empresas', $fields);
    }

    public function down()
    {
        $fields = [
            'certificado' => [
                'name'       => 'certificado',
                'type'       => 'VARCHAR',
                'constraint' => 128,
                'null'       => false,
            ],
            'senha_do_certificado' => [
                'name'       => 'senha_do_certificado',
                'type'       => 'VARCHAR',
                'constraint' => 128,
                'null'       => false,
            ],
        ];

        $this->forge->modifyColumn('empresas', $fields);
    }
}


