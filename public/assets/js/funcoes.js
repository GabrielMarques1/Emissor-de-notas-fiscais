function confirmaAcaoExcluir(msg, rota) {
    if (confirm(msg)) {
        window.location.href = rota;
    }
}

function trocaVirguraPorPonto(id) {
    var valor = document.getElementById(id).value;
    document.getElementById(id).value = valor.replace(',', '.')
}

function uppercase(id)
{
    doc = document.getElementById(id);
    doc.value = doc.value.toUpperCase();
}

function semNumero(id)
{
    var campo = document.getElementById(id);

    if(campo.disabled)
    {
        // campo.value = "";
        campo.disabled = false;
    }
    else
    {
        campo.value = "S/N";
        campo.disabled = true;
    }
}

function formataMascara(string, x)
{
    if(x == 'ncm')
    {
        string = string.slice(0, 4) + "." + string.slice(4, 6) + "." + string.slice(6, 9);
    }
    else if(x == 'cfop')
    {
        string = string.slice(0, 1) + "." + string.slice(1, 4);
    }
    else if(x == 'valor')
    {
        string = string.toLocaleString('pt-br', {minimumFractionDigits: 2});
    }

    return string;
}

function selecionaUF(id)
{
    var id_uf = document.getElementById(id).value;

    $.post(
        "/UF/preparaMunicipios", {
            id_uf: id_uf
        },
        function(data, status) {
            if (status == "success") {
                $('#id_municipio').html(data);
            }
        }
    );
}

function verificaUsuarioNobanco(id)
{
    var doc = document.getElementById(id);
    var usuario = doc.value;

    $.post(
        "/login/verificaUsuario", {
            usuario: usuario
        },
        function(data, status) {
            if (status == "success") {
                if(data == "1")
                {
                    alert('Esse usuário não pode ser cadastrado. Por favor, escolha outro.');
                    doc.value = "";
                    doc.focus();
                }
            }
        }
    );
}

function pegaDadosDoCNPJ(cnpj)
{
	$.post(
        "/receitaWS/pegaDadosDoCNPJ", {
            cnpj: cnpj
        },
        function(data, status) {
            if (status == "success") {
                
                var obj = JSON.parse(data);
  
                if(obj.status != "ERROR")
                {
                    $("#input-razao-social").val(obj.nome);

                    $("#cep").val(obj.cep);
                    $("#logradouro").val(obj.logradouro);
                    
                    if(obj.numero == "SN")
                    {
                        $("#numero").val("S/N");
                    }
                    else
                    {
                        $("#numero").val(obj.numero);
                    }

                    $("#complemento").val(obj.complemento);
                    $("#bairro").val(obj.bairro);

                    // Seleciona o UF pelo nome e não pelo option value.
                    $("#id_uf").val($('option:contains("' + obj.uf + '")').val() );
                    $('#id_uf').trigger('change'); // Depois de selecionado mostra a seleção na tela

                    window.setTimeout( function(){
                        // Seleciona o MUNICIPIO pelo nome e não pelo option value.
                        $("#id_municipio").val($('option:contains("' + obj.municipio + '")').val());
                        $('#id_municipio').trigger('change'); // Depois de selecionado mostra a seleção na tela
                    }, 3000 );
                }
            }
        }
    );
}