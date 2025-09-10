<?php namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddStripeToEmpresas extends Migration
{
    public function up()
    {
        $fields = [
            'stripe_customer_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => TRUE,
            ],
            'stripe_subscription_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => TRUE,
            ],
            'stripe_price_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => TRUE,
            ],
            'stripe_product_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => TRUE,
            ],
            'stripe_status' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => TRUE,
            ],
            'trial_ends_at' => [
                'type' => 'DATETIME',
                'null' => TRUE,
            ],
        ];

        $this->forge->addColumn('empresas', $fields);

        // Indexes to speed up lookups from webhooks
        try {
            $this->db->query("CREATE INDEX IF NOT EXISTS idx_empresas_stripe_customer ON `empresas` (`stripe_customer_id`)");
        } catch (\Throwable $e) {}
        try {
            $this->db->query("CREATE INDEX IF NOT EXISTS idx_empresas_stripe_subscription ON `empresas` (`stripe_subscription_id`)");
        } catch (\Throwable $e) {}
    }

    public function down()
    {
        $this->forge->dropColumn('empresas', 'stripe_customer_id');
        $this->forge->dropColumn('empresas', 'stripe_subscription_id');
        $this->forge->dropColumn('empresas', 'stripe_price_id');
        $this->forge->dropColumn('empresas', 'stripe_product_id');
        $this->forge->dropColumn('empresas', 'stripe_status');
        $this->forge->dropColumn('empresas', 'trial_ends_at');
    }
}


