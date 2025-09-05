<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Main content -->
    <div class="content">
        <div class="container-fluid">

            <div class="row" style="margin-bottom: 15px">
                <div class="col-sm-6">
                    <h6 class="m-0 text-dark">Seja bem Vindo <?= $dados_do_contador['nome'] ?>!</h6>
                </div><!-- /.col -->
                <div class="col-sm-6 no-print">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item active">Inicio</li>
                    </ol>
                </div><!-- /.col -->
            </div>
            
            <div class="row">
                <div class="col-lg-12">
                    <?php if($dados_do_contador['dia_do_pagamento'] == date('d') || $dados_do_contador['status'] == "Vencido" || $dados_do_contador['status'] == "Desativado" ): ?>
                        <div class="card card-<?= ($dados_do_contador['status'] == "Desativado") ? "danger" : "warning"?>">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <?= ($dados_do_contador['dia_do_pagamento'] == date('d')) ? "A fatura vence hoje!" : "Ops, sistema bloqueado! Efetue o pagamento para continuar usando." ?>
                                </h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-lg-12">
                                        <p>
                                            <?= $config['outras_opcoes_de_pagamento'] ?>
                                        </p>
                                        
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- /.card -->
                    <?php endif; ?>

                </div>
            </div>

        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->