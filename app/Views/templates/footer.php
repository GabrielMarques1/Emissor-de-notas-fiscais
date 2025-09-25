<!-- Main Footer -->
<footer id="footer" class="main-footer">
    <!-- To the right -->
    <div class="float-right d-none d-sm-inline">
        1.0.1
    </div>
    <!-- Default to the left -->
    <?php $session = session() ?>
    <strong><?= $session->get('xApp') ?> :: <?= $session->get('xFant') ?> &copy; <?= date('Y') ?> </strong> - Todos os direitos reservados.
</footer>
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
    <?php if (isset($link) && !empty($link)): ?>
    document.getElementById('<?= $link ?>').className = "nav-link active";
    <?php endif; ?>
    
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

                            // Ensure body carries current token too (for servers that read from POST body)
                            var tokenName = CI.csrf.name;
                            var tokenPair = encodeURIComponent(tokenName) + '=' + encodeURIComponent(CI.csrf.hash);
                            if (typeof settings.data === 'string') {
                                // remove any previous token
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
                            // update any hidden inputs in forms
                            if (CI.csrf.name) {
                                $('input[name="' + CI.csrf.name + '"]').val(newHash);
                            }
                        }
                    } catch (e) {}
                }
            });

            // Ensure latest token is present right before form submit
            $(document).on('submit', 'form', function() {
                try {
                    if (CI.csrf && CI.csrf.name && CI.csrf.hash) {
                        // try sync from cookie just before submit
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
        $('.select2').select2()

        //Initialize Select2 Elements
        $('.select2bs4').select2({
            theme: 'bootstrap4'
        })

    });
</script>
</body>

</html>