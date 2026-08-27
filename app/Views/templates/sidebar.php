<?php
    $session = session();
    $xApp = $session->get('xApp');
    $tipo = $session->get('tipo');
    $status = $session->get('status');
?>

<aside class="main-sidebar elevation-4 sidebar-light-info">
    <a href="#" class="brand-link" style="text-align: center">
        <span class="brand-text font-weight-light"><b><?= $xApp ?></b></span>
    </a>

    <div class="sidebar">

        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column nav-flat" data-widget="treeview" role="menu" data-accordion="false">
                <?php if($tipo): ?>
                    <?php if($tipo == 1): ?>
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
                            <a id="6" href="/admin/empresas" class="nav-link">
                                <i class="nav-icon fas fa-city"></i>
                                <p>
                                    Empresas
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
                            <a id="5" href="/cobranca/admin" class="nav-link">
                                <i class="nav-icon fas fa-file-invoice-dollar"></i>
                                <p>
                                    Cobranças
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
                        <li class="nav-header">DASHBOARDS ADMINISTRATIVOS</li>
                        <li class="nav-item">
                            <a id="dashboard_master" href="/inicio/admin" class="nav-link">
                                <i class="nav-icon fas fa-tachometer-alt"></i>
                                <p>
                                    Dashboard Master
                                </p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a id="backup_dashboard" href="/admin/backup-dashboard" class="nav-link">
                                <i class="nav-icon fas fa-shield-alt"></i>
                                <p>
                                    Monitor de Backup
                                </p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a id="cache_monitor" href="/admin/cache-monitor" class="nav-link">
                                <i class="nav-icon fas fa-memory"></i>
                                <p>
                                    Monitor de Cache
                                </p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a id="audit_dashboard" href="/admin/audit-dashboard" class="nav-link">
                                <i class="nav-icon fas fa-search"></i>
                                <p>
                                    Dashboard de Auditoria
                                </p>
                            </a>
                        </li>
                    <?php elseif($tipo == 2): ?>
                        <li class="nav-header"></li>
                        <li class="nav-item">
                            <a id="1" href="/inicio/contador" class="nav-link">
                                <i class="nav-icon fas fa-home"></i>
                                <p>
                                    Inicio
                                </p>
                            </a>
                        </li>

                        <?php if($status == "Ativo" || $status == "Vencido"): ?>
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
                    
                    <?php elseif($tipo == 3): ?>
                        <li class="nav-header"></li>
                        <li class="nav-item">
                            <a id="1" href="/painel/empresa" class="nav-link">
                                <i class="nav-icon fas fa-home"></i>
                                <p>
                                    Painel ERP
                                </p>
                            </a>
                        </li>
                        <li class="nav-header">GESTÃO DE PESSOAL</li>
                        <li class="nav-item">
                            <a id="usuarios" href="/usuarios-caixa" class="nav-link">
                                <i class="nav-icon fas fa-users"></i>
                                <p>
                                    Usuários Caixa
                                </p>
                            </a>
                        </li>
                        <li class="nav-header">VENDAS</li>
                        <li class="nav-item">
                            <a id="pdv" href="/pdv" class="nav-link">
                                <i class="nav-icon fas fa-cash-register"></i>
                                <p>
                                    PDV (Caixa)
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
                            <a id="clientes" href="/clientes" class="nav-link">
                                <i class="nav-icon fas fa-users"></i>
                                <p>
                                    Clientes
                                </p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a id="produtos" href="/produtos" class="nav-link">
                                <i class="nav-icon fas fa-box-open"></i>
                                <p>
                                    Produtos
                                </p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a id="fornecedores" href="/fornecedores" class="nav-link">
                                <i class="nav-icon fas fa-dolly"></i>
                                <p>
                                    Fornecedores
                                </p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a id="transportadoras" href="/transportadoras" class="nav-link">
                                <i class="nav-icon fas fa-truck"></i>
                                <p>
                                    Transportadoras
                                </p>
                            </a>
                        </li>
                        <li class="nav-header">RELATÓRIOS</li>
                        <li class="nav-item">
                            <a id="relatorios" href="/relatorios-empresa" class="nav-link">
                                <i class="nav-icon fas fa-chart-line"></i>
                                <p>
                                    Relatórios Gerenciais
                                </p>
                            </a>
                        </li>
                        <li class="nav-header">CONTROLE FISCAL</li>
                        <li class="nav-item">
                            <a id="10" href="/emissor/listaXMLsNFe" class="nav-link">
                                <i class="nav-icon fas fa-code"></i>
                                <p>
                                    NFe
                                </p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a id="11" href="/emissor/listaXMLsNFCe" class="nav-link">
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
