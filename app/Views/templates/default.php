<?php
$session = session();
$dados = $session->get();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="x-ua-compatible" content="ie=edge">

    <title><?= $this->renderSection('title') ?> :: <?= $dados['xApp'] ?? 'Sistema ERP' ?></title>

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="<?= base_url('theme/plugins/fontawesome-free/css/all.css') ?>">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="<?= base_url('theme/plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.css') ?>">
    <!-- DataTables -->
    <link rel="stylesheet" href="<?= base_url('theme/plugins/datatables-bs4/css/dataTables.bootstrap4.css') ?>">
    <!-- Select2 -->
    <link rel="stylesheet" href="<?= base_url('theme/plugins/select2/css/select2.css') ?>">
    <link rel="stylesheet" href="<?= base_url('theme/plugins/select2-bootstrap4-theme/select2-bootstrap4.css') ?>">
    <!-- iCheck for checkboxes and radio inputs -->
    <link rel="stylesheet" href="<?= base_url('theme/plugins/icheck-bootstrap/icheck-bootstrap.css') ?>">
    <!-- Theme style -->
    <link rel="stylesheet" href="<?= base_url('theme/dist/css/adminlte.css') ?>">
    <!-- overlayScrollbars -->
    <link rel="stylesheet" href="<?= base_url('theme/plugins/overlayScrollbars/css/OverlayScrollbars.min.css') ?>">
    <!-- Style -->
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
    <!-- Google Font: Source Sans Pro -->
    <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet">

    <meta name="<?= csrf_header() ?>" content="<?= csrf_hash() ?>">
    <script>
        window.CI = window.CI || {};
        window.CI.csrf = { name: '<?= csrf_token() ?>', hash: '<?= csrf_hash() ?>', header: '<?= csrf_header() ?>', cookie: '<?= config('Security')->cookieName ?>' };
    </script>

    <!-- jQuery -->
    <script src="<?= base_url('theme/plugins/jquery/jquery.js') ?>"></script>
    <!-- SweetAlert2 -->
    <script src="<?= base_url('theme/plugins/sweetalert2/sweetalert2.js') ?>"></script>
    <!-- OPTIONAL SCRIPTS -->
    <script src="<?= base_url('theme/plugins/chart.js/Chart.min.js') ?>"></script>
</head>

<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

  <!-- Preloader -->
  <div class="preloader flex-column justify-content-center align-items-center">
    <img class="animation__shake" src="<?= base_url('assets/images/logo.png') ?>" alt="Logo" height="60" width="60">
  </div>

  <!-- Navbar -->
  <?= $this->include('templates/navbar') ?>
  <!-- /.navbar -->

  <!-- Main Sidebar Container -->
  <?= $this->include('templates/sidebar') ?>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0"><?= $this->renderSection('title') ?></h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?= base_url() ?>">Home</a></li>
              <li class="breadcrumb-item active"><?= $this->renderSection('title') ?></li>
            </ol>
          </div><!-- /.col -->
        </div><!-- /.row -->
      </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    <div class="content">
      <div class="container-fluid">
        <?= $this->renderSection('content') ?>
      </div><!-- /.container-fluid -->
    </div>
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->

  <!-- Control Sidebar -->
  <aside class="control-sidebar control-sidebar-dark">
    <!-- Control sidebar content goes here -->
  </aside>
  <!-- /.control-sidebar -->

  <!-- Main Footer -->
  <?= $this->include('templates/footer') ?>

</div>
<!-- ./wrapper -->

<!-- REQUIRED SCRIPTS -->

<!-- Bootstrap 4 -->
<script src="<?= base_url('theme/plugins/bootstrap/js/bootstrap.bundle.js') ?>"></script>
<!-- Select2 -->
<script src="<?= base_url('theme/plugins/select2/js/select2.full.js') ?>"></script>
<!-- DataTables -->
<script src="<?= base_url('theme/plugins/datatables/jquery.dataTables.js') ?>"></script>
<script src="<?= base_url('theme/plugins/datatables-bs4/js/dataTables.bootstrap4.js') ?>"></script>
<!-- Bootstrap Switch -->
<script src="<?= base_url('theme/plugins/bootstrap-switch/js/bootstrap-switch.min.js') ?>"></script>
<!-- overlayScrollbars -->
<script src="<?= base_url('theme/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js') ?>"></script>
<!-- AdminLTE App -->
<script src="<?= base_url('theme/dist/js/adminlte.js') ?>"></script>

<!-- Scripts internos do sistema -->
<script src="<?= base_url('assets/js/funcoes.js') ?>"></script>
<!-- ViaCep -->
<script src="<?= base_url('assets/js/viaCep.js') ?>"></script>
<!-- Plugin Mascaras -->
<script src="<?= base_url('assets/js/jquery.mask.js') ?>"></script>
<!-- Scripts Mascaras -->
<script src="<?= base_url('assets/js/mascaras.js') ?>"></script>
<!-- Scripts validação CPF e CNPJ -->
<script src="<?= base_url('assets/js/validador.js') ?>"></script>

<script>
    <?php
        $session = session();
        $alert = $session->getFlashdata('alert');

        if (isset($alert)) : ?>

            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 5000
            });

            Toast.fire({
                type: '<?= $alert['type'] ?>',
                title: '<?= $alert['title']?>'
            })
            
        <?php endif;
    ?>

    $(function() {
        // CSRF: attach header on every AJAX request and update from response
        if (window.CI && CI.csrf) {
            function getCookie(name) {
                var nameEQ = name + '=';
                var ca = document.cookie.split(';');
                for (var i = 0; i < ca.length; i++) {
                    var c = ca[i];
                    while (c.charAt(0) === ' ') c = c.substring(1, c.length);
                    if (c.indexOf(nameEQ) === 0) return decodeURIComponent(c.substring(nameEQ.length, c.length));
                }
                return null;
            }

            // Initial sync from cookie if available (cookie method)
            try {
                if (CI.csrf.cookie) {
                    var cookieHash = getCookie(CI.csrf.cookie);
                    if (cookieHash) CI.csrf.hash = cookieHash;
                }
            } catch (e) {}

            $.ajaxSetup({
                beforeSend: function(xhr, settings) {
                    if (settings.type && settings.type.toUpperCase() !== 'GET') {
                        try {
                            var header = CI.csrf.header || 'X-CSRF-TOKEN';
                            xhr.setRequestHeader(header, CI.csrf.hash);

                            var tokenName = CI.csrf.name;
                            var tokenPair = encodeURIComponent(tokenName) + '=' + encodeURIComponent(CI.csrf.hash);
                            if (typeof settings.data === 'string') {
                                var re = new RegExp('(?:^|&)' + tokenName.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '=[^&]*');
                                settings.data = settings.data.replace(re, '').replace(/^&|&$/g, '');
                                settings.data = settings.data ? settings.data + '&' + tokenPair : tokenPair;
                            } else if (settings.data && typeof settings.data === 'object') {
                                settings.data[tokenName] = CI.csrf.hash;
                            } else if (!settings.data) {
                                settings.data = tokenPair;
                            }
                        } catch (e) {}
                    }
                },
                complete: function(xhr) {
                    try {
                        var header = CI.csrf.header || 'X-CSRF-TOKEN';
                        var newHash = xhr.getResponseHeader(header);
                        if (!newHash && CI.csrf.cookie) {
                            newHash = getCookie(CI.csrf.cookie);
                        }
                        if (newHash) {
                            CI.csrf.hash = newHash;
                            var meta = document.querySelector('meta[name="' + header + '"]');
                            if (meta) meta.setAttribute('content', newHash);
                            if (CI.csrf.name) {
                                $('input[name="' + CI.csrf.name + '"]').val(newHash);
                            }
                        }
                    } catch (e) {}
                }
            });

            $(document).on('submit', 'form', function() {
                try {
                    if (CI.csrf && CI.csrf.name && CI.csrf.hash) {
                        if (CI.csrf.cookie) {
                            var cookieHash2 = getCookie(CI.csrf.cookie);
                            if (cookieHash2) CI.csrf.hash = cookieHash2;
                        }
                        var $input = $(this).find('input[name="' + CI.csrf.name + '"]');
                        if ($input.length) {
                            $input.val(CI.csrf.hash);
                        } else {
                            $(this).prepend('<input type="hidden" name="' + CI.csrf.name + '" value="' + CI.csrf.hash + '">');
                        }
                    }
                } catch (e) {}
            });
        }

        // DataTables
        $("#example1").DataTable();
        $("#example1-2").DataTable();
        $("#example1-3").DataTable();
        $("#example1-4").DataTable();
        $('#example2').DataTable({
            "paging": true,
            "lengthChange": false,
            "searching": false,
            "ordering": true,
            "info": true,
            "autoWidth": false,
        });

        //Initialize Select2 Elements
        $('.select2').select2();

        //Initialize Select2 Elements
        $('.select2bs4').select2({
            theme: 'bootstrap4'
        });
    });
</script>

</body>
</html>
