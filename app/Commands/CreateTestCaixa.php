<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class CreateTestCaixa extends BaseCommand
{
    protected $group = 'Database';
    protected $name = 'test:caixa';
    protected $description = 'Cria um usuário caixa de teste';

    public function run(array $params)
    {
        $db = \Config\Database::connect();

        try {
            // 1. Verificar se já existe
            $existente = $db->table('logins')->where('usuario', 'caixa.teste')->get()->getRowArray();
            if ($existente) {
                CLI::error('Usuário caixa.teste já existe!');
                return;
            }

            // 2. Criar login tipo 4 (caixa)
            $loginData = [
                'usuario' => 'caixa.teste',
                'senha' => password_hash('123456', PASSWORD_DEFAULT),
                'tipo' => 4,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $db->table('logins')->insert($loginData);
            $idLogin = $db->insertID();
            CLI::write("Login criado com ID: $idLogin", 'green');
            
            // 3. Buscar uma empresa existente
            $empresa = $db->table('empresas')
                         ->where('deleted_at', '0000-00-00 00:00:00')
                         ->limit(1)
                         ->get()
                         ->getRowArray();
            
            if (!$empresa) {
                CLI::error('Nenhuma empresa encontrada');
                return;
            }
            
            CLI::write("Empresa encontrada: {$empresa['xFant']} (ID: {$empresa['id_empresa']})", 'blue');
            
            // 4. Criar registro na tabela usuarios_caixa
            $caixaData = [
                'id_login' => $idLogin,
                'id_empresa' => $empresa['id_empresa'],
                'nome_completo' => 'Caixa de Teste',
                'status' => 'ativo',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $db->table('usuarios_caixa')->insert($caixaData);
            
            CLI::write('=====================================', 'yellow');
            CLI::write('Usuário caixa criado com sucesso!', 'green');
            CLI::write('=====================================', 'yellow');
            CLI::write('Usuário: caixa.teste', 'white');
            CLI::write('Senha: 123456', 'white');
            CLI::write("Empresa: {$empresa['xFant']}", 'white');
            CLI::write('=====================================', 'yellow');
            CLI::write('Acesse: /login-pdv para testar', 'cyan');
            
        } catch (\Exception $e) {
            CLI::error('Erro: ' . $e->getMessage());
        }
    }
}
