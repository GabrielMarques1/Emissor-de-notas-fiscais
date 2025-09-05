<!-- Navbar -->
<nav class="main-header navbar navbar-expand navbar-white navbar-light">
  <!-- Left navbar links -->
  <ul class="navbar-nav">
    <li class="nav-item">
      <a class="nav-link" data-widget="pushmenu" href="#"><i class="fas fa-bars"></i></a>
    </li>
  </ul>

  <ul class="navbar-nav ml-auto">
    <li class="nav-item" style="font-size: 20px; font-weight: bold; color: rgba(23, 162, 184)">
      <?php if(isset($dados['tipo'])):
        echo $dados['xFant'];
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
