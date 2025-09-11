<?php
    function format($valor)
    {
        return number_format($valor, 2, '.', '');
    }

    function verificaPermissaoDeAcesso($tipo)
    {
        $session = session();
        $tipo_usuario_da_sessao = $session->get('tipo');
        $status = $session->get('status');

        // Caso exista sessão
        if(isset($tipo_usuario_da_sessao)) :
            // MASTER (tipo 1) tem acesso total
            if ((int) $tipo_usuario_da_sessao === 1) {
                return FALSE;
            }
            
            // Caso não tenha permissão de acessar a função
            if($tipo_usuario_da_sessao != $tipo || $status == "Desativado") :
                $session->setFlashdata(
                    'alert',
                    [
                        'type'  => 'error',
                        'title' => 'Você não tem permissão de acessar essa funcionalidade!'
                    ]
                );

                $prev = $session->get('_ci_previous_url');
                if (!is_string($prev) || $prev === '') {
                    $prev = function_exists('previous_url') ? previous_url() : null;
                    if (!$prev || !is_string($prev) || $prev === '') {
                        $prev = site_url('/login');
                    }
                }
                return $prev;
            // Caso tenha permissão de acessar a função
            else:
                return FALSE;
            endif;

        endif;

        // Caso não tenha uma sessão iniciada
        $session->setFlashdata(
            'alert',
            [
                'type'  => 'error',
                'title' => 'Acesse sua conta para continuar!'
            ]
        );

        return '/login';
    }

    function insereIDs($dados)
    {
        $session = session();

        $id_contador = $session->get('id_contador');
        $id_empresa  = $session->get('id_empresa');

        $dados['id_contador'] = $id_contador;
        $dados['id_empresa']  = $id_empresa;

        return $dados;
    }

    function removeMascaras($string)
    {
        $caracteres = ['/', '.', '-', ' ', '(', ')'];

        foreach($caracteres as $caracter) :
            $string = str_replace($caracter, "", $string);
        endforeach;

        return $string;
    }

    function converteMoney($valor)
    {
        $valor = str_replace('.', '', $valor);
        $valor = str_replace(',', '.', $valor);

        return $valor;
    }

    // ---- Offline helpers ----
    function is_offline_mode(): bool
    {
        try {
            $dbGroup = config('Database')->defaultGroup ?? 'cloud';
            // Se estiver usando local_backup (ou forçado), consideramos offline
            if ($dbGroup === 'local_backup') {
                return true;
            }
            // Se conexões falharem, também tratamos como offline
            $db = \Config\Database::connect();
            return $db->getPlatform() === null; // improvável, mas seguro
        } catch (\Throwable $e) {
            return true;
        }
    }

    function offline_banner_text(): string
    {
        return 'Sem conexão com a nuvem. Operando em modo offline (dados locais).';
    }
?>