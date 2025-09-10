$(document).ready(function() {

    function limpa_formulário_cep() {
        // Limpa valores do formulário de cep.
        $("#logradouro").val("");
        $("#bairro").val("");
        $("#id_municipio").val("").trigger('change');
        // Para UF usamos select #id_uf
        // $("#id_uf").val("").trigger('change');
    }
    
    //Quando o campo cep perde o foco.
    $("#cep").blur(function() {

        //Nova variável "cep" somente com dígitos.
        var cep = $(this).val().replace(/\D/g, '');

        //Verifica se campo cep possui valor informado.
        if (cep != "") {

            //Expressão regular para validar o CEP.
            var validacep = /^[0-9]{8}$/;

            //Valida o formato do CEP.
            if(validacep.test(cep)) {

                //Preenche os campos com "..." enquanto consulta webservice.
                $("#logradouro").val("...");
                $("#bairro").val("...");

                //Consulta o webservice viacep.com.br/
                $.getJSON("https://viacep.com.br/ws/"+ cep +"/json/?callback=?", function(dados) {

                    if (!("erro" in dados)) {
                        //Atualiza os campos com os valores da consulta.
                        $("#logradouro").val(dados.logradouro);
                        $("#bairro").val(dados.bairro);
                        // Seleciona UF pelo texto visível
                        var ufOption = $('#id_uf option').filter(function(){ return $(this).text() === dados.uf; }).val();
                        if (ufOption) {
                            $('#id_uf').val(ufOption).trigger('change');
                            // Após carregar municípios via selecionaUF, seleciona o município retornado pelo CEP
                            setTimeout(function(){
                                var munOption = $('#id_municipio option').filter(function(){ return $(this).text() === dados.localidade; }).val();
                                if (munOption) {
                                    $('#id_municipio').val(munOption).trigger('change');
                                }
                            }, 800);
                        }
                        $("#logradouro").val(dados.logradouro);
                        $("#bairro").val(dados.bairro);
                    } //end if.
                    else {
                        //CEP pesquisado não foi encontrado.
                        limpa_formulário_cep();
                        alert("CEP não encontrado.");
                    }
                });
            } //end if.
            else {
                //cep é inválido.
                limpa_formulário_cep();
                alert("Formato de CEP inválido.");
            }
        } //end if.
        else {
            //cep sem valor, limpa formulário.
            limpa_formulário_cep();
        }
    });
});