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
            
            // Caso não tenha permissão de acessar a função
            if($tipo_usuario_da_sessao != $tipo || $status == "Desativado") :
                $session->setFlashdata(
                    'alert',
                    [
                        'type'  => 'error',
                        'title' => 'Você não tem permissão de acessar essa funcionalidade!'
                    ]
                );

                return $session->get('_ci_previous_url') ;
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
?>