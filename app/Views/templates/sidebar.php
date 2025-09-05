<?php
    $session = session();
    $xApp = $session->get('xApp');
?>

<aside class="main-sidebar elevation-4 sidebar-light-info">
    <a href="#" class="brand-link" style="text-align: center">
        <span class="brand-text font-weight-light"><b><?= $xApp ?></b></span>
    </a>

    <div class="sidebar">

        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column nav-flat" data-widget="treeview" role="menu" data-accordion="false">
                <?php if(isset($dados['tipo'])): ?>
                    <?php if($dados['tipo'] == 1): ?>
                        <li class="nav-header"></li>
                        <li class="nav-item">
                            <a id="1" href="/inicio/admin" class="nav-link">
                                <i class="nav-icon fas fa-home"></i>
                                <p>
                                    Inicio
                                </p>
                            </a>
                        </li>
                        <li class="nav-header">CONTROLE</li>
                        <li class="nav-item">
                            <a id="2" href="/contadores" class="nav-link">
                                <i class="nav-icon fas fa-building"></i>
                                <p>
                                    Contadores
                                </p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a id="3" href="/relatorios/contadores" class="nav-link">
                                <i class="nav-icon fas fa-file-pdf"></i>
                                <p>
                                    Relatório
                                </p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a id="4" href="/configuracoes/edit" class="nav-link">
                                <i class="nav-icon fas fa-cog"></i>
                                <p>
                                    Configurações
                                </p>
                            </a>
                        </li>
                    <?php elseif($dados['tipo'] == 2): ?>
                        <li class="nav-header"></li>
                        <li class="nav-item">
                            <a id="1" href="/inicio/contador" class="nav-link">
                                <i class="nav-icon fas fa-home"></i>
                                <p>
                                    Inicio
                                </p>
                            </a>
                        </li>

                        <?php if($dados['status'] == "Ativo" || $dados['status'] == "Vencido"): ?>
                            <li class="nav-header">CONTROLE</li>
                            <li class="nav-item">
                                <a id="2" href="/empresas" class="nav-link">
                                    <i class="nav-icon fas fa-building"></i>
                                    <p>
                                        Empresas
                                    </p>
                                </a>
                            </li>
                            <li class="nav-header">RELATÓRIOS</li>
                            <li class="nav-item">
                                <a id="3" href="/relatorios/empresas" class="nav-link">
                                    <i class="nav-icon fas fa-file-pdf"></i>
                                    <p>
                                        Empresas
                                    </p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a id="4" href="/relatorios/pagamentos" class="nav-link">
                                    <i class="nav-icon fas fa-file-pdf"></i>
                                    <p>
                                        Pagamentos
                                    </p>
                                </a>
                            </li>
                            <li class="nav-header"></li>
                            <li class="nav-item" style="background: rgba(99, 218, 125);">
                                <a id="5" href="/suporte" class="nav-link" style="padding-left: 70px; color: white; font-weight: bold">
                                    <i class="nav-icon fas fa-headset"></i>
                                    <p>
                                        SUPORTE
                                    </p>
                                </a>
                            </li>
                        <?php endif;?>
                    
                    <?php elseif($dados['tipo'] == 3): ?>
                        <li class="nav-header"></li>
                        <li class="nav-item">
                            <a id="1" href="/inicio/emissor" class="nav-link">
                                <i class="nav-icon fas fa-home"></i>
                                <p>
                                    Inicio
                                </p>
                            </a>
                        </li>
                        <li class="nav-header">EMITIR NOTAS</li>
                        <li class="nav-item">
                            <a id="2" href="/notaDeEntrada/emitir" class="nav-link">
                                <i class="nav-icon far fa-circle text-success"></i>
                                <p>
                                    Nota de Entrada
                                </p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a id="3" href="/notaDeSaida/emitir" class="nav-link">
                                <i class="nav-icon far fa-circle text-primary"></i>
                                <p>
                                    Nota de Saída
                                </p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a id="4" href="/notaDeDevolucao/emitir" class="nav-link">
                                <i class="nav-icon far fa-circle text-warning"></i>
                                <p>
                                    Nota de Devolução
                                </p>
                            </a>
                        </li>
                        <li class="nav-header">CONTROLE GERAL</li>
                        <li class="nav-item">
                            <a id="5" href="/clientes" class="nav-link">
                                <i class="nav-icon fas fa-users"></i>
                                <p>
                                    Clientes
                                </p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a id="6" href="/produtos" class="nav-link">
                                <i class="nav-icon fas fa-box-open"></i>
                                <p>
                                    Produtos
                                </p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a id="7" href="/fornecedores" class="nav-link">
                                <i class="nav-icon fas fa-dolly"></i>
                                <p>
                                    Fornecedores
                                </p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a id="8" href="/transportadoras" class="nav-link">
                                <i class="nav-icon fas fa-truck"></i>
                                <p>
                                    Transportadoras
                                </p>
                            </a>
                        </li>
                        <li class="nav-header">CONTROLE FISCAL</li>
                        <li class="nav-item">
                            <a id="9" href="/emissor/listaXMLsNFe" class="nav-link">
                                <i class="nav-icon fas fa-code"></i>
                                <p>
                                    NFe
                                </p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a id="10" href="/emissor/listaXMLsNFCe" class="nav-link">
                                <i class="nav-icon fas fa-code"></i>
                                <p>
                                    NFCe
                                </p>
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</aside>
