<?php
use App\Models\CobrancaModel;

// Pega dados da sessão diretamente
$session = session();
$tipo = $session->get('tipo');
$id_empresa = $session->get('id_empresa');
$id_contador = $session->get('id_contador');
$xFant = $session->get('xFant');

// Calcula quantidade de cobranças vencidas para contador (tipo 2) e empresa (tipo 3)
$qtdCobrancasVencidas = 0;
$linkCobrancas = '#';

if ($tipo) {
	$cobrancaModel = new CobrancaModel();
	if ($tipo == 3 && $id_empresa) {
		$qtdCobrancasVencidas = $cobrancaModel
			->where('id_empresa', $id_empresa)
			->where('status', 'Vencido')
			->countAllResults();
		$linkCobrancas = base_url('cobranca/minhas');
	} elseif ($tipo == 2 && $id_contador) {
		$qtdCobrancasVencidas = $cobrancaModel
			->where('id_contador', $id_contador)
			->where('status', 'Vencido')
			->countAllResults();
		$linkCobrancas = base_url('cobranca/empresas');
	}
}
?>

<!-- Navbar -->
<nav class="main-header navbar navbar-expand navbar-white navbar-light">
  <!-- Left navbar links -->
  <ul class="navbar-nav">
    <li class="nav-item">
      <a class="nav-link" data-widget="pushmenu" href="#"><i class="fas fa-bars"></i></a>
    </li>
  </ul>

  <ul class="navbar-nav ml-auto">
    <?php if ($qtdCobrancasVencidas > 0): ?>
    <li class="nav-item">
      <a class="nav-link" href="<?= $linkCobrancas ?>" title="Cobranças em atraso">
        <i class="fas fa-bell"></i>
        <span class="badge badge-danger navbar-badge"><?= $qtdCobrancasVencidas ?></span>
      </a>
    </li>
    <?php endif; ?>
    <li class="nav-item" style="font-size: 20px; font-weight: bold; color: rgba(23, 162, 184)">
      <?php if($xFant):
        echo $xFant;
      endif; ?>
    </li>
  </ul>

  <!-- Right navbar links -->
  <ul class="navbar-nav ml-auto">
    <li class="nav-item">
      <a class="nav-link" href="/login/logout">
        <i class="fas fa-sign-out-alt"></i>
      </a>
    </li>
  </ul>
</nav>
<!-- /.navbar -->
