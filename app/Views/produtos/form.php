<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Main content -->
    <div class="content">
        <div class="container-fluid">
            <form action="/produtos/store" method="post">
                <?= csrf_field() ?>
                
                <div class="card">
                    <div class="card-header">
                        <div class="row">
                            <div class="col-sm-6">
                                <h6 class="m-0 text-dark"><i class="<?= $titulo['icone'] ?>"></i> <?= $titulo['modulo'] ?></h6>
                            </div><!-- /.col -->
                            <div class="col-sm-6">
                                <ol class="breadcrumb float-sm-right">
                                    <a href="/produtos" class="btn btn-success button-voltar"><i class="fa fa-arrow-alt-circle-left"></i> Voltar</a>
                                    <?php foreach ($caminhos as $caminho) : ?>
                                        <?php if (!$caminho['active']) : ?>
                                            <li class="breadcrumb-item"><a href="<?= $caminho['rota'] ?>"><?= $caminho['titulo'] ?></a></li>
                                        <?php else : ?>
                                            <li class="breadcrumb-item active"><?= $caminho['titulo'] ?></li>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </ol>
                            </div><!-- /.col -->
                        </div>
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label for="">Nome</label>
                                    <input type="text" class="form-control" id="nome" name="nome" onblur="uppercase('nome')" value="<?= (isset($produto)) ? $produto['nome'] : "" ?>" required="">
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <label for="">Cód. de Barras</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="codigo_de_barras" name="codigo_de_barras" value="<?= (isset($produto)) ? $produto['codigo_de_barras'] : "SEM GTIN" ?>" required disabled>
                                    <span class="input-group-append">
                                        <button type="button" class="btn btn-info btn-flat" onclick="semCodigoDeBarras('codigo_de_barras')">SEM GTIN</button>
                                    </span>
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="form-group">
                                    <label for="">Unidade</label>
                                    <select class="form-control select2" name="id_unidade" style="width: 100%" required>
                                        <option value="">Selecione</option>
                                        <?php foreach($unidades as $unidade) : ?>
                                            <option value="<?= $unidade['id_unidade'] ?>" <?= (isset($produto) && $produto['id_unidade'] == $unidade['id_unidade']) ? "selected" : "" ?>><?= $unidade['descricao'] ?></option>

                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="form-group">
                                    <label for="">Valor Unitário</label>
                                    <input type="text" class="form-control money" id="valor_unitario" name="valor_unitario" value="<?= (isset($produto)) ? number_format($produto['valor_unitario'], 2, ',', '.') : "" ?>" required="">
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="form-group">
                                    <label for="">CFOP NFe</label>
                                    <input type="text" class="form-control cfop" name="CFOP_NFe" value="<?= (isset($produto)) ? $produto['CFOP_NFe'] : "5403" ?>" required="">
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="form-group">
                                    <label for="">CFOP NFCe</label>
                                    <input type="text" class="form-control cfop" name="CFOP_NFCe" value="<?= (isset($produto)) ? $produto['CFOP_NFCe'] : "5102" ?>" required="">
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="form-group">
                                    <label for="">CFOP Externo</label>
                                    <input type="text" class="form-control cfop" name="CFOP_Externo" value="<?= (isset($produto)) ? $produto['CFOP_Externo'] : "6104" ?>" required="">
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="form-group">
                                    <label for="">NCM</label>
                                    <input type="text" class="form-control ncm" name="NCM" min="8" max="8" value="<?= (isset($produto)) ? $produto['NCM'] : "" ?>" required="">
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="form-group">
                                    <label for="">CSOSN</label>
                                    <input type="text" class="form-control" name="CSOSN" min="3" max="3" value="<?= (isset($produto)) ? $produto['CSOSN'] : "103" ?>" required="">
                                </div>
                            </div>

                            <?php if(isset($produto)): ?>
                                <input type="hidden" class="form-control" name="id_produto" value="<?= $produto['id_produto'] ?>">
                            <?php endif ?>

                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-12"><h6>Controle de Estoque</h6></div>
                            <div class="col-lg-3">
                                <div class="form-group">
                                    <label>Estoque Mínimo</label>
                                    <input type="number" step="0.01" class="form-control" name="estoque_minimo" value="<?= isset($produto)?($produto['estoque_minimo']??0):0 ?>">
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="form-group">
                                    <label>Quantidade Atual</label>
                                    <input type="number" step="0.01" class="form-control" value="<?= isset($produto)?($produto['estoque']??0):0 ?>" disabled>
                                </div>
                            </div>
                            <div class="col-lg-6 d-flex align-items-end">
                                <small class="text-muted">Ajustes de estoque devem ser feitos na listagem de produtos (Ajustar Estoque) ou via Nota de Entrada.</small>
                            </div>
						<?php if(isset($produto)): ?>
						<div class="col-lg-12 mt-2">
							<button type="button" class="btn btn-secondary" onclick="openKardexModal(<?= (int)$produto['id_produto'] ?>, <?= (float)($produto['estoque'] ?? 0) ?>)"><i class="fa fa-history"></i> Histórico de Movimentações</button>
						</div>
						<?php endif; ?>
                        </div>
                    </div>
                    <!-- /.card-body -->
                    <div class="card-footer">
                        <div class="row">
                            <div class="col-lg-12" style="text-align: right">
                                <button type="submit" class="btn btn-primary"><?= (isset($contador)) ? "Atualizar" : "Cadastrar" ?></button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /.card -->

            </form>
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->

<!-- Modal: Histórico de Movimentações (Kardex) -->
<div class="modal fade" id="kardex-modal" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Histórico de Movimentações</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				<div class="form-row mb-2">
					<div class="col-md-3">
						<label>De</label>
						<input type="date" class="form-control" id="kdx-de">
					</div>
					<div class="col-md-3">
						<label>Até</label>
						<input type="date" class="form-control" id="kdx-ate">
					</div>
					<div class="col-md-6 d-flex align-items-end">
						<button type="button" class="btn btn-primary mr-2" onclick="loadKardex()">Atualizar</button>
						<div class="ml-auto"><small class="text-muted">Estoque atual: <span id="kdx-estoque-atual">0</span></small></div>
					</div>
				</div>
				<div class="table-responsive">
					<table class="table table-striped table-sm">
						<thead>
							<tr>
								<th>Data/Hora</th>
								<th>Tipo</th>
								<th>Origem</th>
								<th class="text-right">Quantidade</th>
								<th class="text-right">Saldo Resultante</th>
							</tr>
						</thead>
						<tbody id="kardex-tbody"></tbody>
					</table>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
			</div>
		</div>
	</div>
</div>

<script>
    function semCodigoDeBarras(id)
    {
        var campo = document.getElementById(id);

        if(campo.disabled)
        {
            campo.value = "";
            campo.disabled = false;
        }
        else
        {
            campo.value = "SEM GTIN";
            campo.disabled = true;
        }
    }

    <?php if(isset($produto)) : ?>
        
        var campo = document.getElementById('codigo_de_barras');

        if(campo.value != "SEM GTIN")
        {
            campo.disabled = false;
        }
        
    <?php endif;  ?>

let KDX = { id_produto: null, estoque_atual: 0 };

function openKardexModal(id_produto, estoqueAtual)
{
	KDX.id_produto = id_produto;
	KDX.estoque_atual = Number(estoqueAtual||0);
	// Datas padrão: últimos 30 dias
	const ate = new Date();
	const de = new Date(); de.setDate(ate.getDate()-30);
	document.getElementById('kdx-de').value = de.toISOString().slice(0,10);
	document.getElementById('kdx-ate').value = ate.toISOString().slice(0,10);
	document.getElementById('kdx-estoque-atual').textContent = (KDX.estoque_atual).toLocaleString('pt-BR');
	$('#kardex-modal').modal('show');
	loadKardex();
}

async function loadKardex()
{
	try {
		if (!KDX.id_produto) return;
		const de = document.getElementById('kdx-de').value || '';
		const ate = document.getElementById('kdx-ate').value || '';
		const qs = new URLSearchParams({ id_produto: String(KDX.id_produto) });
		if (de) qs.append('de', de);
		if (ate) qs.append('ate', ate);
		const resp = await fetch('/api/products/inventory-movements?' + qs.toString(), { headers: { 'Accept': 'application/json' }});
		const rows = await resp.json();
		const tbody = document.getElementById('kardex-tbody');
		tbody.innerHTML = '';
		if (!Array.isArray(rows) || rows.length === 0) {
			tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Sem movimentações no período.</td></tr>';
			return;
		}
		// Ordena por data ascendente para calcular saldo progressivo
		rows.sort((a,b)=> new Date(a.created_at) - new Date(b.created_at));
		let saldo = 0;
		for (const r of rows) {
			const tipo = (r.tipo||'').toLowerCase() === 'saida' ? 'Saída' : 'Entrada';
			const sinal = (r.tipo||'').toLowerCase() === 'saida' ? -1 : 1;
			const qtd = Number(r.quantidade||0);
			saldo += sinal * qtd;
			let origem = r.motivo || '';
			if (r.id_pos_sale) origem = 'PDV venda #'+ r.id_pos_sale;
			if (origem === '' && tipo === 'Saída') origem = 'PDV venda';
			const tr = document.createElement('tr');
			tr.innerHTML =
				'<td>' + (r.created_at ? new Date(r.created_at).toLocaleString('pt-BR') : '') + '</td>' +
				'<td>' + (
					(r.id_pos_sale && tipo==='Saída') ? 'Venda no PDV' : (
					 (origem.indexOf('Nota de Entrada') !== -1) ? 'Nota de Entrada' : (
					 tipo === 'Entrada' ? 'Ajuste Manual - Entrada' : 'Ajuste Manual - Saída'
					)
					)
				) + '</td>' +
				'<td>' + (origem || '-') + '</td>' +
				'<td class="text-right ' + (sinal<0?'text-danger':'text-success') + '">' + (sinal>0?'+':'-') + qtd.toLocaleString('pt-BR') + '</td>' +
				'<td class="text-right">' + saldo.toLocaleString('pt-BR') + '</td>';
			tbody.appendChild(tr);
		}
	} catch (e) {
		console.error(e);
		const tbody = document.getElementById('kardex-tbody');
		tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Falha ao carregar histórico.</td></tr>';
	}
}
</script>