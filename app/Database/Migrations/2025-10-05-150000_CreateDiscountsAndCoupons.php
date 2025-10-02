<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDiscountsAndCoupons extends Migration
{
    public function up()
    {
        // Tabela de cupons de desconto
        $this->forge->addField([
            'id_coupon' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'code' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'comment'    => 'Código do cupom (ex: PROMO10)',
            ],
            'description' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'comment'    => 'Descrição da promoção',
            ],
            'type' => [
                'type'       => 'ENUM',
                'constraint' => ['percentage', 'fixed', 'free_shipping'],
                'comment'    => 'Tipo de desconto',
            ],
            'value' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'comment'    => 'Valor do desconto (% ou R$)',
            ],
            'min_purchase' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => '0.00',
                'comment'    => 'Compra mínima para usar cupom',
            ],
            'max_discount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => true,
                'comment'    => 'Desconto máximo em R$ (para % ilimitado)',
            ],
            'usage_limit' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
                'comment'    => 'Limite de usos (null = ilimitado)',
            ],
            'used_count' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
                'comment'    => 'Quantidade de vezes usado',
            ],
            'valid_from' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'Válido a partir de',
            ],
            'valid_until' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'Válido até',
            ],
            'is_active' => [
                'type'    => 'BOOLEAN',
                'default' => true,
            ],
            'id_contador' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            'id_empresa' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            'created_at' => [
                'type' => 'DATETIME',
            ],
            'updated_at' => [
                'type' => 'DATETIME',
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        
        $this->forge->addKey('id_coupon', true);
        $this->forge->addKey(['id_contador', 'id_empresa']);
        $this->forge->addKey(['code', 'id_empresa'], false, true); // UNIQUE por empresa
        $this->forge->addForeignKey('id_contador', 'contadores', 'id_contador', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('id_empresa', 'empresas', 'id_empresa', 'CASCADE', 'CASCADE');
        $this->forge->createTable('coupons');
        
        // Tabela de descontos aplicados (auditoria)
        $this->forge->addField([
            'id_discount' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'id_pos_sale' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'comment'    => 'FK para venda',
            ],
            'id_pos_sale_item' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'comment'    => 'FK para item (se desconto por item)',
            ],
            'id_coupon' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'comment'    => 'FK para cupom (se usado)',
            ],
            'type' => [
                'type'       => 'ENUM',
                'constraint' => ['percentage', 'fixed', 'coupon'],
                'comment'    => 'Tipo de desconto aplicado',
            ],
            'value' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'comment'    => 'Valor do desconto (% ou R$)',
            ],
            'amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'comment'    => 'Valor em R$ descontado',
            ],
            'applied_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'comment'    => 'ID do operador que aplicou',
            ],
            'reason' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'comment'    => 'Motivo do desconto manual',
            ],
            'id_contador' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            'id_empresa' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            'created_at' => [
                'type' => 'DATETIME',
            ],
        ]);
        
        $this->forge->addKey('id_discount', true);
        $this->forge->addKey(['id_contador', 'id_empresa']);
        $this->forge->addKey('id_pos_sale');
        $this->forge->addKey('id_coupon');
        $this->forge->addForeignKey('id_pos_sale', 'pos_sales', 'id_pos_sale', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('id_pos_sale_item', 'pos_sale_items', 'id_item', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('id_coupon', 'coupons', 'id_coupon', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('id_contador', 'contadores', 'id_contador', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('id_empresa', 'empresas', 'id_empresa', 'CASCADE', 'CASCADE');
        $this->forge->createTable('discounts');
        
        // Adicionar configurações de desconto em empresas
        $this->forge->addColumn('empresas', [
            'max_discount_percentage' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'default'    => '50.00',
                'comment'    => 'Desconto percentual máximo permitido',
                'after'      => 'max_suspended_sales',
            ],
            'max_discount_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => true,
                'comment'    => 'Desconto máximo em R$ (null = sem limite)',
                'after'      => 'max_discount_percentage',
            ],
            'require_discount_approval' => [
                'type'    => 'BOOLEAN',
                'default' => false,
                'comment' => 'Exige aprovação de gerente para descontos',
                'after'   => 'max_discount_amount',
            ],
            'discount_approval_threshold' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'default'    => '20.00',
                'comment'    => 'Desconto % acima deste valor requer aprovação',
                'after'      => 'require_discount_approval',
            ],
        ]);
        
        // Adicionar campos de desconto em pos_sales
        $this->forge->addColumn('pos_sales', [
            'total_discount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => '0.00',
                'comment'    => 'Total de descontos aplicados',
                'after'      => 'total',
            ],
        ]);
    }
    
    public function down()
    {
        $this->forge->dropTable('discounts');
        $this->forge->dropTable('coupons');
        
        $this->forge->dropColumn('empresas', [
            'max_discount_percentage',
            'max_discount_amount',
            'require_discount_approval',
            'discount_approval_threshold',
        ]);
        
        $this->forge->dropColumn('pos_sales', 'total_discount');
    }
}

