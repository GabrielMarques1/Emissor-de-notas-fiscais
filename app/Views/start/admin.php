<?php 
    $session = session();
    $tipo = $session->get('tipo');

    if($tipo != 1) // Verifica se o tipo de usuário tem permissão para acessar a página
    {
        echo "<script>window.location.href = '/erro-permissao-de-acesso'; </script>";
    }
?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Main content -->
    <div class="content">
        <div class="container-fluid">
            <h4>Seja bem vindo!</h4>
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->