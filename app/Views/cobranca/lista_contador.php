<div class="content-wrapper">
	<section class="content-header">
		<div class="container-fluid">
			<div class="row mb-2">
				<div class="col-sm-6">
					<h1><?= $titulo['modulo'] ?></h1>
				</div>
			</div>
		</div>
	</section>

	<section class="content">
		<div class="container-fluid">
			<div class="card">
				<div class="card-header">
					<h3 class="card-title"><i class="fa fa-list"></i> Cobranças de minhas empresas</h3>
				</div>
				<div class="card-body">
					<table class="table table-striped table-sm">
						<thead>
							<tr>
								<th>Empresa</th>
								<th>Mês/Ano</th>
								<th>Vencimento</th>
								<th>Valor</th>
								<th>Status</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach($cobrancas as $c): ?>
							<tr>
								<td>
									<?= esc($c['empresa_nome'] ?? '') ?>
									<?php if(($c['status_empresa'] ?? '') === 'Desativado'): ?>
										<span class="badge badge-secondary ml-2">Empresa bloqueada</span>
									<?php endif; ?>
								</td>
								<td><?= str_pad($c['mes_referencia'], 2, '0', STR_PAD_LEFT) ?>/<?= $c['ano_referencia'] ?></td>
								<td><?= date('d/m/Y', strtotime($c['data_vencimento'])) ?></td>
								<td>R$ <?= number_format($c['valor_cobranca'], 2, ',', '.') ?></td>
								<td>
									<?php if($c['status'] == 'Pendente'): ?>
										<span class="badge badge-warning">Pendente</span>
									<?php elseif($c['status'] == 'Vencido'): ?>
										<span class="badge badge-danger">Vencido</span>
									<?php elseif($c['status'] == 'Pago'): ?>
										<span class="badge badge-success">Pago</span>
									<?php else: ?>
										<span class="badge badge-secondary">Cancelado</span>
									<?php endif; ?>
								</td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</section>
</div>


