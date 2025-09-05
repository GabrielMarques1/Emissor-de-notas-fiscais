<!doctype html>
<html lang="pt-br">
<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-giJF6kkoqNQ00vy+HMDP7azOuL0xtbfIcaT9wjKHr8RbDVddVHyTfAAsrekwKmP1" crossorigin="anonymous">

    <title>xFiscal | Sistema de emissão de documentos fiscais para contadores</title>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light bg-light fixed-top">
    <div class="container">
        <a class="navbar-brand" href="/">xFiscal</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" aria-current="page" href="/">Início</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" aria-current="page" href="#">Blog</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" aria-current="page" href="#">Ajuda</a>
                </li>
            </ul>
            <!-- Right navbar links -->
            <ul class="navbar-nav ml-auto">
                <li class="nav-item" style="margin-right: 20px">
                    <a class="btn btn-info" href="/login" style="color: white">
                        Login
                    </a>
                </li>
                <li class="nav-item">
                    <!-- <a class="btn btn-info" href="#" style="color: white">
                        Cadastre-se
                    </a> -->
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container">
    <div class="row">
        <div class="col-lg-6" style="margin-top: 100px">
            <h1>Tecnologia fiscal completa para contadores empresariais</h1>
            <p>Com a xFiscal, você não precisa se preocupar em qual ferramenta seus clientes usam para emissão de documentos fiscais. Você contador está no controle de tudo e sempre acompanhando seu cliente.</p>
            <!-- <button class="btn btn-info">Abra sua conta agora mesmo</button> -->
        </div>
        <div class="col-lg-6">
            <img class="img-fluid" src="<?= base_url('site/gestao-fiscal-completa.png') ?>" alt="">
        </div>
    </div>
</div>

<div>
    <img src="<?= base_url('site/xfiscal-functions-screens.png') ?>" class="img-fluid" alt="">
</div>

<div class="container" style="margin-top: 25px">
    <div class="row">
        <div class="col-lg-12">
            <div class="jumbotron" style="background: #00A3A8; padding: 75px; color: white;">
                <h3 style="font-weight: bold">De contador > Para empresários</h3>
                <p>Nossas tecnologias são exclusivamente para você contador que busca ferramentas de qualidade para seus clientes. Aqui na xFiscal você tem o contole de tudo, além de gerar valor e uma nova renda para sua empresa.</p>
                <!-- <a class="btn btn-primary btn-lg" href="#" role="button">Cadastre-se agora mesmo</a> -->
            </div>
        </div>
    </div>
</div>

<div class="container" style="margin-top: 120px">
    <div class="row" style="text-align: center">
        <div class="col-lg-6 offset-lg-3">
            <h6>FUNCIONALIDADES</h6>
            <h1>Saiba o que a xFiscal oferece para seu negócio</h1>
        </div>
    </div>
    <div class="row" style="margin-top: 50px">
        <div class="col-lg-6">
            <div class="row">
                <div class="col-lg-4">
                    <img class="img-fluid" src="<?= base_url('site/funcionalidades/conta-digital-completa.png') ?>" alt="">
                </div>
                <div class="col-lg-8" style="margin-top: 25px">
                    <h3>Conta digital completa</h3>
                    <p>Serviços bancários sem burocracia e atendimento personalizado para você gerenciar a sua empresa.</p>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="row">
                <div class="col-lg-4">
                    <img class="img-fluid" src="<?= base_url('site/funcionalidades/mobilidade-e-agilidade.png') ?>" alt="">
                </div>
                <div class="col-lg-8" style="margin-top: 25px">
                    <h3>Mobilidade e Agilidade</h3>
                    <p>Tenha acesso a tudo direto da palma da sua mão ou da tela do seu computador.</p>
                </div>
            </div>
        </div>
    </div>
    <div class="row" style="margin-top: 50px">
        <div class="col-lg-6">
            <div class="row">
                <div class="col-lg-4">
                    <img class="img-fluid" src="<?= base_url('site/funcionalidades/completude.png') ?>" alt="">
                </div>
                <div class="col-lg-8" style="margin-top: 25px">
                    <h3>Completude</h3>
                    <p>Sistema de emissão de notas fiscais NFe e NFCe completo, fácil e veloz na palma da sua mão.</p>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="row">
                <div class="col-lg-4">
                    <img class="img-fluid" src="<?= base_url('site/funcionalidades/controle-total.png') ?>" alt="">
                </div>
                <div class="col-lg-8" style="margin-top: 25px">
                    <h3>Controle total</h3>
                    <p>Você no controle de tudo, de qualquer lugar do mundo.</p>
                </div>
            </div>
        </div>
    </div>
    <div class="row" style="margin-top: 50px">
        <div class="col-lg-6">
            <div class="row">
                <div class="col-lg-4">
                    <img class="img-fluid" src="<?= base_url('site/funcionalidades/geracao-de-valor.png') ?>" alt="">
                </div>
                <div class="col-lg-8" style="margin-top: 25px">
                    <h3>Geração de Valor</h3>
                    <p>Chega de indicar sistemas para emissão de notas. Aqui você disponibiliza a plataforma para seus clientes e cobra pelo serviço.</p>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="row">
                <div class="col-lg-4">
                    <img class="img-fluid" src="<?= base_url('site/funcionalidades/garantia-de-seguranca.png') ?>" alt="">
                </div>
                <div class="col-lg-8" style="margin-top: 25px">
                    <h3>Garantia de segurança</h3>
                    <p>Somos uma instituição focada na agilidade, simplicidade, e geração de valor para contadores de todo o brasil. Nosso maior foco é você que pensa em ter ferramentas de qualidade para seus clientes.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container" style="margin-top: 120px; color: white;">
    <div class="row" style="background: rgba(32, 62, 128); padding: 25px;">
        <div class="col-lg-12">
            <div class="row">
                <div class="col-lg-6">
                    <h4><b>Fique por dentro das novidades!</b></h4>
                    <p>Receba as melhores dicas para o seu negócio e saiba de todas as novidades da xFiscal com a nossa newsletter semanal</p>
                </div>
                <div class="col-lg-4">
                    <div class="form-group">
                        <label for="">Seu e-mail</label>
                        <input type="text" class="form-control" name="email" placeholder="Digite seu melhor email aqui">
                    </div>
                </div>
                <div class="col-lg-2">
                    <button class="btn btn-primary" style="margin-top: 24px">Assine agora</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid" style="margin-top: 120px">
    <div class="row">
        <div class="col-lg-12">
            <img src="<?= base_url('site/barra-final.png') ?>" class="img-fluid" alt="">
        </div>
        <div class="col-lg-12">
            <p style="text-align: center">
                <?= date('Y') ?> © xFiscal. Todos os direitos reservados. CNPJ 34.229.323.0001/73 <br>
                xFiscal é uma plataforma de Nx Sistemas
            </p>
        </div>
    </div>
</div>

<!-- Optional JavaScript; choose one of the two! -->

<!-- Option 1: Bootstrap Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/js/bootstrap.bundle.min.js" integrity="sha384-ygbV9kiqUc6oa4msXn9868pTtWMgiQaeYH7/t7LECLbyPA2x65Kgf80OJFdroafW" crossorigin="anonymous"></script>

<!-- Option 2: Separate Popper and Bootstrap JS -->
<!--
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js" integrity="sha384-q2kxQ16AaE6UbzuKqyBE9/u/KzioAlnx2maXQHiDX9d4/zp8Ok3f+M7DPm+Ib6IU" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/js/bootstrap.min.js" integrity="sha384-pQQkAEnwaBkjpqZ8RU1fF1AKtTcHJwFl3pblpTlHXybJjHpMYo79HY3hIi4NKxyj" crossorigin="anonymous"></script>
-->
</body>
</html>