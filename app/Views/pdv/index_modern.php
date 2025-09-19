<!-- PDV Modern UI -->
<div class="content-wrapper pdv-wrapper">
	<div class="pdv-header">
		<div class="pdv-header-left">
			<i class="fas fa-shopping-basket mr-2"></i>
			<strong><?= esc(session('xFant') ?: 'Loja') ?></strong>
			<span class="ml-2 text-muted">Unidade</span>
		</div>
		<div class="pdv-header-right">
			<div class="pdv-operator d-none d-sm-flex">
				<img src="<?= base_url('assets/img/user.png') ?>" alt="op" class="pdv-op-avatar">
				<div class="ml-2 small">
					<div><?= esc(session('usuario') ?: 'Operador') ?></div>
					<div class="text-success" id="pdv-status"><span class="pdv-dot"></span> Online</div>
				</div>
			</div>
			<div class="pdv-clock"><span id="pdv-date"></span> <strong id="pdv-time"></strong></div>
			<span id="pdv-shift-pill" class="badge badge-secondary ml-2">Caixa: Fechado</span>
			<button class="btn btn-sm btn-outline-secondary ml-2" onclick="window.open('/suporte','_blank')"><i class="far fa-question-circle"></i></button>
		</div>
	</div>

	<div class="pdv-body">
		<!-- Sidebar -->
		<aside class="pdv-sidebar">
			<button class="pdv-menu-btn active" title="Venda Rápida"><i class="fas fa-bolt"></i><span>Venda Rápida</span></button>
			<button class="pdv-menu-btn" title="Pedidos"><i class="fas fa-file-invoice"></i><span>Pedidos</span></button>
			<button class="pdv-menu-btn" title="Produtos" onclick="openProductPicker()"><i class="fas fa-box"></i><span>Produtos</span></button>
			<button class="pdv-menu-btn" title="Abrir/Fechar Caixa" onclick="gerenciarCaixa()"><i class="fas fa-cash-register"></i><span>Caixa</span></button>
			<button class="pdv-menu-btn" title="Relatórios" onclick="openRelatorios()"><i class="fas fa-chart-line"></i><span>Relatórios</span></button>
			<button class="pdv-menu-btn" title="Configurações" onclick="openConfiguracoes()"><i class="fas fa-cog"></i><span>Configurações</span></button>
			<div class="pdv-search mt-3">
				<input type="text" class="form-control form-control-sm" placeholder="Buscar...">
			</div>
		</aside>

		<!-- Items center -->
		<main class="pdv-center">
			<div class="pdv-scan">
				<div class="input-group input-group-lg">
					<input type="text" id="codigo_de_barras" class="form-control" placeholder="Buscar Produto por Código/Nome" onkeypress="pdvOnEnter(event)">
					<div class="input-group-append">
						<span class="input-group-text"><i class="fas fa-barcode"></i></span>
					</div>
				</div>
			</div>
			<div class="pdv-items card">
				<div class="table-responsive">
					<table class="table table-hover table-striped mb-0">
						<thead>
							<tr>
								<th>CÓDIGO</th>
								<th>DESCRIÇÃO</th>
								<th class="text-center">QTD</th>
								<th class="text-right">PREÇO UNIT.</th>
								<th class="text-right">SUBTOTAL</th>
								<th class="text-center">AÇÕES</th>
							</tr>
						</thead>
						<tbody id="pdv-tbody"></tbody>
					</table>
				</div>
				<div class="pdv-discounts p-2">
					<button class="btn btn-xs btn-outline-secondary" disabled>Descontos/Cupons</button>
				</div>
			</div>
		</main>

		<!-- Summary right -->
		<aside class="pdv-summary">
			<div class="card">
				<div class="card-body">
					<div class="d-flex justify-content-between mb-1"><span>SUBTOTAL:</span><strong id="sum-subtotal">R$ 0,00</strong></div>
					<div class="d-flex justify-content-between mb-1"><span>DESCONTOS (-):</span><strong id="sum-discount">R$ 0,00</strong></div>
					<div class="d-flex justify-content-between mb-3"><span>IMPOSTOS (+):</span><strong id="sum-tax">R$ 0,00</strong></div>
					<div class="pdv-total">TOTAL A PAGAR: <span id="sum-total">R$ 0,00</span></div>
					<div class="pdv-payments mt-3">
						<div class="btn-group btn-group-sm d-flex flex-wrap" role="group">
							<button class="btn btn-outline-secondary flex-fill mb-2" onclick="selecionarPagamento('credit')">Cartão Crédito</button>
							<button class="btn btn-outline-secondary flex-fill mb-2" onclick="selecionarPagamento('debit')">Cartão Débito</button>
							<button class="btn btn-outline-secondary flex-fill mb-2" onclick="selecionarPagamento('cash')">Dinheiro</button>
							<button class="btn btn-outline-secondary flex-fill mb-2" onclick="selecionarPagamento('pix')">PIX</button>
							<button class="btn btn-outline-secondary flex-fill mb-2" onclick="selecionarPagamento('others')">Outros</button>
						</div>
						<div class="mt-2">
							<button class="btn btn-success btn-block" onclick="finalizarPDV()"><i class="fas fa-check"></i> Finalizar Venda</button>
						</div>
						<div class="btn-group btn-group-sm d-flex flex-wrap mt-3">
							<button class="btn btn-outline-secondary flex-fill mb-2" onclick="suspenderVenda()">Suspender Venda</button>
							<button class="btn btn-outline-danger flex-fill mb-2" onclick="cancelarVenda()">Cancelar Venda</button>
						</div>
						<div class="btn-group btn-group-sm d-flex flex-wrap mt-2">
							<button class="btn btn-outline-primary flex-fill mb-2" id="btn-open-shift" onclick="abrirCaixa()">Abrir Caixa</button>
							<button class="btn btn-outline-warning flex-fill mb-2" id="btn-close-shift" onclick="fecharCaixaBtn()">Fechar Caixa</button>
							<button class="btn btn-outline-success flex-fill mb-2" id="btn-create-cash" onclick="criarCaixaRapido()">Criar Caixa Rápido</button>
						</div>
						<div class="mt-3 small">
							<label>Identificar Cliente</label>
							<input type="text" class="form-control form-control-sm" placeholder="CPF/CNPJ ou nome" />
						</div>
					</div>
				</div>
			</div>
		</aside>
	</div>

	<!-- Footer removido para maximizar a área útil -->
</div>

<script>
document.addEventListener('DOMContentLoaded', async () => {
	try {
		const [shifts, cashResp, activeSale] = await Promise.all([
			fetch('/api/shifts', { headers: { 'Accept': 'application/json' }}).then(r=>r.json()),
			fetch('/api/cash-registers', { headers: { 'Accept': 'application/json' }}).then(r=>r.json()),
			fetch('/api/pos/active', { headers: { 'Accept': 'application/json' }})
				.then(async r => {
					if (r.status === 409) return { _noShift: true };
					if (!r.ok) return null;
					try { return await r.json(); } catch { return null; }
				})
				.catch(()=>null),
		]);
		const cashList = Array.isArray(cashResp?.data) ? cashResp.data : (Array.isArray(cashResp) ? cashResp : []);
		// Não solicitar abertura automaticamente. Apenas informar status atual.
		window.PDV = window.PDV || {}; window.PDV.hasOpenShift = Array.isArray(shifts) && shifts.some(s => String(s.status).toLowerCase() === 'open');
		window.PDV.cashId = cashList.length ? (cashList[0].id_cash_register || cashList[0].id || cashList[0].id_cash) : null;
		atualizarIndicadorCaixa();
		if (activeSale && activeSale._noShift) {
			// Sem turno aberto: apenas informar e habilitar ação manual
			if (!window.PDV.hasOpenShift) {
				try { Swal.fire({ type: 'info', title: 'Abra o caixa para iniciar', timer: 1800, showConfirmButton: false }); } catch(e){}
			}
		} else if (activeSale && (activeSale.id_pos_sale || activeSale.id)) {
			window.PDV = window.PDV || {}; window.PDV.saleId = activeSale.id_pos_sale || activeSale.id;
		}
		window.PDV = window.PDV || {}; window.PDV.paymentType = 'cash';
		atualizarRelogio(); setInterval(atualizarRelogio, 1000);
		atualizarStatus(); window.addEventListener('online', atualizarStatus); window.addEventListener('offline', atualizarStatus);
		await atualizarCarrinho();
	} catch(e) { console.error(e); }
});

async function atualizarCarrinho() {
	try {
		const resp = await fetch('/api/cart', { headers: { 'Accept': 'application/json' }});
		const itens = await resp.json();
		window.PDV_ITEMS = Array.isArray(itens) ? itens : [];
		const tbody = document.getElementById('pdv-tbody');
		if (!tbody) return;
		tbody.innerHTML = '';
		let total = 0, descontoTotal = 0, impostos = 0;
		if (Array.isArray(window.PDV_ITEMS) && window.PDV_ITEMS.length) {
			window.PDV_ITEMS.forEach(item => {
				const subtotal = (parseFloat(item.valor_unitario||0) * parseFloat(item.quantidade||0)) - parseFloat(item.desconto||0);
				total += subtotal; descontoTotal += parseFloat(item.desconto||0);
				const tr = document.createElement('tr');
				tr.innerHTML = `
					<td>${item.id_produto_provisorio}</td>
					<td>${item.nome}</td>
					<td class="text-center">
						<div class="btn-group btn-group-sm">
							<button class="btn btn-light" onclick="alterarQtd(${item.id_produto_provisorio}, -1)"><i class="fas fa-minus"></i></button>
							<button class="btn btn-outline-secondary" disabled style="width:48px">${(item.quantidade||0)}</button>
							<button class="btn btn-light" onclick="alterarQtd(${item.id_produto_provisorio}, 1)"><i class="fas fa-plus"></i></button>
						</div>
					</td>
					<td class="text-right">${Number(item.valor_unitario||0).toLocaleString('pt-BR',{minimumFractionDigits:2})}</td>
					<td class="text-right">${subtotal.toLocaleString('pt-BR',{minimumFractionDigits:2})}</td>
					<td class="text-center">
						<button type="button" class="btn btn-outline-danger btn-sm" onclick="removerItemPDV(${item.id_produto_provisorio})"><i class="fas fa-trash"></i></button>
					</td>
				`;
				tbody.appendChild(tr);
			});
		} else {
			const tr = document.createElement('tr');
			tr.innerHTML = `<td colspan="6">Nenhum item no PDV!</td>`;
			tbody.appendChild(tr);
		}
		document.getElementById('sum-subtotal').textContent = 'R$ ' + (total + descontoTotal).toLocaleString('pt-BR',{minimumFractionDigits:2});
		document.getElementById('sum-discount').textContent = 'R$ ' + descontoTotal.toLocaleString('pt-BR',{minimumFractionDigits:2});
		document.getElementById('sum-tax').textContent = 'R$ ' + Number(impostos).toLocaleString('pt-BR',{minimumFractionDigits:2});
		document.getElementById('sum-total').textContent = 'R$ ' + total.toLocaleString('pt-BR',{minimumFractionDigits:2});
	} catch(e) { console.error(e); }
}

function pdvOnEnter(e) { if (e.key === 'Enter') adicionarItemPDV(); }

async function adicionarItemPDV() {
	const codigo = document.getElementById('codigo_de_barras').value.trim();
	const qtd = 1;
	if (!codigo) { Swal.fire({ type: 'warning', title: 'Digite código ou nome do produto' }); return; }
	try {
		let prod = null;
		const term = codigo.trim();
		// 1) Tenta por código de barras exato (apenas se parecer com EAN ou numérico curto)
		if (/^[0-9]{2,}$/.test(term)) {
			let resp = await fetch('/api/products/barcode/' + encodeURIComponent(term), { headers: { 'Accept': 'application/json' }});
			if (resp.ok) { prod = await resp.json(); }
		}
		// 2) Busca por nome/ID
		if (!prod) {
			const sr = await fetch('/api/products/search?q=' + encodeURIComponent(term), { headers:{'Accept':'application/json'} });
			if (sr.ok) {
				const list = await sr.json();
				if (Array.isArray(list) && list.length) prod = list[0];
			}
		}
		if (!prod) throw new Error('Produto não encontrado');
		const payload = {
			id_produto: (prod.id_produto || prod.id) ?? null,
			nome: prod.nome,
			codigo_de_barras: prod.codigo_de_barras || 'SEM GTIN',
			unidade: (prod.unidade || 'UN'),
			quantidade: qtd,
			valor_unitario: prod.valor_unitario,
			desconto: 0,
			CFOP_NFCe: prod.CFOP_NFCe || '5102',
			CFOP_NFe: prod.CFOP_NFe || '5102',
			CFOP_Externo: prod.CFOP_Externo || '6102',
			NCM: prod.NCM || '00000000',
			CSOSN: prod.CSOSN || '102'
		};
		const r = await fetch('/api/cart', { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: JSON.stringify(payload) });
		if (!r.ok) { const j = await r.json().catch(()=>({error:'Erro ao adicionar'})); throw new Error(j.error || JSON.stringify(j)); }
		await atualizarCarrinho();
		document.getElementById('codigo_de_barras').value = '';
	} catch(e) { Swal.fire({ type: 'error', title: 'Falha ao adicionar', text: e.message }); }
}

function removerItemPDV(id) { if (!id) return; fetch('/api/cart/' + id, { method: 'DELETE', headers: { 'Accept': 'application/json' }}).then(()=>atualizarCarrinho()); }

async function alterarQtd(id, delta) {
	try {
		const item = (window.PDV_ITEMS||[]).find(i => String(i.id_produto_provisorio) === String(id));
		if (!item) return;
		const nova = Math.max(1, parseInt(item.quantidade||1) + parseInt(delta||0));
		await fetch('/api/cart/' + id, { method: 'DELETE', headers: { 'Accept': 'application/json' }});
        const payload = {
            id_produto: item.id_produto || null,
            nome: item.nome,
            codigo_de_barras: item.codigo_de_barras || 'SEM GTIN',
            unidade: item.unidade || 'UN',
            quantidade: nova,
            valor_unitario: item.valor_unitario,
            desconto: item.desconto || 0,
            CFOP_NFCe: item.CFOP_NFCe || '5102',
            CFOP_NFe: item.CFOP_NFe || '5102',
            CFOP_Externo: item.CFOP_Externo || '6102',
            NCM: item.NCM || '00000000',
            CSOSN: item.CSOSN || '102'
        };
		await fetch('/api/cart', { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: JSON.stringify(payload) });
		await atualizarCarrinho();
	} catch(e) { console.error(e); }
}

async function finalizarPDV() {
	try {
		const saleId = (window.PDV && window.PDV.saleId) ? window.PDV.saleId : null;
		if (!saleId) { Swal.fire({type:'error', title:'Sem venda ativa'}); return; }
		const totalText = (document.getElementById('sum-total')?.textContent||'0').replace(/[^0-9,.-]/g,'').replace('.','').replace(',','.');
		const total = parseFloat(totalText||'0');
		const payload = { total: total, paid_amount: total, change_amount: 0, payment_type: (window.PDV?.paymentType||'cash') };
		const resp = await fetch('/api/pos/' + saleId + '/finalize', { method: 'POST', headers: { 'Accept': 'application/json', 'Content-Type':'application/json' }, body: JSON.stringify(payload) });
		const data = await resp.json();
		if (!resp.ok) throw new Error(data.messages?.error || (data.error||'Falha ao finalizar'));
		Swal.fire({ type: 'success', title: 'Venda finalizada!' });
		await atualizarCarrinho();
		window.open('/api/pos/' + saleId + '/receipt/html', '_blank');
	} catch(e) { Swal.fire({ type: 'error', title: 'Erro ao finalizar', text: e.message }); }
}

async function fecharCaixa() {
	try {
		const shifts = await fetch('/api/shifts', { headers: {'Accept':'application/json'} }).then(r=>r.json());
		if (!Array.isArray(shifts) || !shifts.length) { Swal.fire({type:'error',title:'Sem turnos'}); return; }
		const shift = shifts[0];
		if (shift.status !== 'open') { Swal.fire({type:'info',title:'Turno já fechado'}); return; }
		const amount = await Swal.fire({ title: 'Valor no Caixa', input: 'text', inputValue: '0,00', showCancelButton:true });
		if (!amount.isConfirmed) return;
		await fetch('/api/shifts/close/' + shift.id_shift, { method: 'POST', headers: {'Accept':'application/json','Content-Type':'application/json'}, body: JSON.stringify({ closed_by: 'pdv', closing_amount: amount.value }) });
		const rep = await fetch('/api/shifts/' + shift.id_shift + '/report', { headers: {'Accept':'application/json'} }).then(r=>r.json());
		let html = '<b>Total:</b> R$ ' + Number(rep.total||0).toLocaleString('pt-BR',{minimumFractionDigits:2}) + '<br/><br/>';
		if (Array.isArray(rep.itens)) {
			html += '<table class="table table-sm"><thead><tr><th>Forma</th><th class="text-right">Qtd</th><th class="text-right">Valor</th></tr></thead><tbody>';
			rep.itens.forEach(i=>{ html += `<tr><td>${i.payment_type}</td><td class="text-right">${i.qtd}</td><td class="text-right">${Number(i.valor||0).toLocaleString('pt-BR',{minimumFractionDigits:2})}</td></tr>`; });
			html += '</tbody></table>';
		}
		Swal.fire({ type:'success', title:'Caixa fechado', html });
	} catch(e) { Swal.fire({ type:'error', title:'Erro ao fechar', text: e.message }); }
}

async function gerenciarCaixa() {
	try {
		const [shifts, cash] = await Promise.all([
			fetch('/api/shifts', { headers: {'Accept':'application/json'} }).then(r=>r.json()),
			fetch('/api/cash-registers', { headers: {'Accept':'application/json'} }).then(r=>r.json())
		]);
		const hasOpen = Array.isArray(shifts) && shifts.some(s => String(s.status).toLowerCase() === 'open');
		if (hasOpen) {
			// Mostrar opções para fechar (manual), nunca fechar automaticamente
			const sel = shifts.find(s => String(s.status).toLowerCase() === 'open');
			const act = await Swal.fire({ title:'Caixa aberto', text:'Deseja fechar o caixa agora?', showCancelButton:true, confirmButtonText:'Fechar caixa', cancelButtonText:'Manter aberto' });
			if (act.isConfirmed && sel && (sel.id_shift || sel.id)) {
				const amount = await Swal.fire({ title: 'Valor no Caixa', input: 'text', inputValue: '0,00', showCancelButton:true });
				if (!amount.isConfirmed) return;
				await fetch('/api/shifts/close/' + (sel.id_shift || sel.id), { method:'POST', headers:{'Accept':'application/json','Content-Type':'application/json'}, body: JSON.stringify({ closed_by:'pdv', closing_amount: amount.value }) });
				Swal.fire({ type:'success', title:'Caixa fechado' });
			}
			return;
		}
		// Nenhum aberto: permitir abrir manualmente
		const open = await Swal.fire({ type:'info', title:'Abrir Caixa', showCancelButton:true, confirmButtonText:'Abrir agora' });
		if (!open.isConfirmed) return;
		const cashId = Array.isArray(cash) && cash.length ? (cash[0].id_cash_register || cash[0].id || cash[0].id_cash) : null;
		if (!cashId) { await Swal.fire({ type:'error', title:'Nenhum caixa cadastrado' }); return; }
		await fetch('/api/shifts/open', { method:'POST', headers:{'Accept':'application/json','Content-Type':'application/json'}, body: JSON.stringify({ id_cash_register: cashId, opened_by:'pdv', opening_amount: 0 }) });
		Swal.fire({ type:'success', title:'Caixa aberto' });
	} catch(e) { Swal.fire({ type:'error', title:'Erro ao gerenciar caixa', text:e.message }); }
}

function selecionarPagamento(tipo) {
	window.PDV = window.PDV || {}; window.PDV.paymentType = tipo;
	document.querySelectorAll('.pdv-summary .btn-group .btn').forEach(b=>b.classList.remove('btn-primary'));
	const map = {credit:'Cartão Crédito',debit:'Cartão Débito',cash:'Dinheiro',pix:'PIX',others:'Outros'};
	const buttons = Array.from(document.querySelectorAll('.pdv-summary .btn-group .btn'));
	buttons.forEach(btn=>{ if (btn.textContent.trim() === (map[tipo]||'')) btn.classList.add('btn-primary'); });
}

function atualizarRelogio() { const d = new Date(); document.getElementById('pdv-date').textContent = d.toLocaleDateString('pt-BR'); document.getElementById('pdv-time').textContent = d.toLocaleTimeString('pt-BR', { hour12: false }); }
function atualizarStatus() { const online = navigator.onLine; const el = document.getElementById('pdv-status'); if (!el) return; el.classList.toggle('text-success', online); el.classList.toggle('text-danger', !online); el.innerHTML = `<span class="pdv-dot" style="background:${online?'#28a745':'#dc3545'}"></span> ${online?'Online':'Offline'}`; }
function suspenderVenda() { Swal.fire({ type:'info', title:'Suspender venda', text:'Funcionalidade em breve.' }); }
function cancelarVenda() { fetch('/api/cart', { method:'DELETE', headers:{'Accept':'application/json'} }).then(()=>atualizarCarrinho()); }

function atualizarIndicadorCaixa() {
	const pill = document.getElementById('pdv-shift-pill');
	const open = !!(window.PDV && window.PDV.hasOpenShift);
	if (!pill) return;
	pill.classList.remove('badge-secondary','badge-success');
	pill.classList.add(open ? 'badge-success' : 'badge-secondary');
	pill.textContent = 'Caixa: ' + (open ? 'Aberto' : 'Fechado');
	document.getElementById('btn-open-shift')?.classList.toggle('disabled', open);
	document.getElementById('btn-close-shift')?.classList.toggle('disabled', !open);
	document.getElementById('btn-create-cash')?.classList.toggle('d-none', !!(window.PDV && window.PDV.cashId));
}

async function abrirCaixa() {
	try {
		if (window.PDV?.hasOpenShift) return;
		let cashId = window.PDV?.cashId;
		if (!cashId) {
			const cashResp = await fetch('/api/cash-registers', { headers:{'Accept':'application/json'} }).then(r=>r.json());
			const list = Array.isArray(cashResp?.data) ? cashResp.data : (Array.isArray(cashResp) ? cashResp : []);
			cashId = list.length ? (list[0].id_cash_register || list[0].id || list[0].id_cash) : null;
			window.PDV.cashId = cashId;
		}
		if (!cashId) { Swal.fire({ type:'error', title:'Nenhum caixa cadastrado' }); return; }
		await fetch('/api/shifts/open', { method:'POST', headers:{'Accept':'application/json','Content-Type':'application/json'}, body: JSON.stringify({ id_cash_register: cashId, opened_by:'pdv', opening_amount: 0 }) });
		window.PDV.hasOpenShift = true; atualizarIndicadorCaixa();
		// Buscar/abrir venda ativa após abrir o turno
		try {
			const r = await fetch('/api/pos/active', { headers:{'Accept':'application/json'} });
			if (r.ok) {
				const data = await r.json();
				if (data && (data.id_pos_sale || data.id)) { window.PDV.saleId = data.id_pos_sale || data.id; }
			}
		} catch(e) {}
		Swal.fire({ type:'success', title:'Caixa aberto' });
	} catch(e) { Swal.fire({ type:'error', title:'Erro ao abrir caixa', text:e.message }); }
}

async function fecharCaixaBtn() {
	try {
		if (!window.PDV?.hasOpenShift) return;
		const shifts = await fetch('/api/shifts', { headers:{'Accept':'application/json'} }).then(r=>r.json());
		const sel = Array.isArray(shifts) ? shifts.find(s => String(s.status).toLowerCase() === 'open') : null;
		if (!sel) { window.PDV.hasOpenShift = false; atualizarIndicadorCaixa(); return; }
		const amount = await Swal.fire({ title: 'Valor no Caixa', input: 'text', inputValue: '0,00', showCancelButton:true });
		if (!amount.isConfirmed) return;
		await fetch('/api/shifts/close/' + (sel.id_shift || sel.id), { method:'POST', headers:{'Accept':'application/json','Content-Type':'application/json'}, body: JSON.stringify({ closed_by:'pdv', closing_amount: amount.value }) });
		window.PDV.hasOpenShift = false; atualizarIndicadorCaixa();
		// Limpa venda ativa após fechar turno
		delete window.PDV.saleId;
		Swal.fire({ type:'success', title:'Caixa fechado' });
	} catch(e) { Swal.fire({ type:'error', title:'Erro ao fechar caixa', text:e.message }); }
}

async function criarCaixaRapido() {
	try {
		const body = { name: 'Caixa 1', location: 'Loja', status: 'closed' };
		const resp = await fetch('/api/cash-registers', { method:'POST', headers:{'Accept':'application/json','Content-Type':'application/json'}, body: JSON.stringify(body) });
		if (!resp.ok) { const j = await resp.json().catch(()=>({})); throw new Error(j?.error || 'Falha ao criar caixa'); }
		const data = await resp.json();
		window.PDV.cashId = data?.id_cash_register || data?.id || null;
		atualizarIndicadorCaixa();
		Swal.fire({ type:'success', title:'Caixa criado' });
	} catch(e) { Swal.fire({ type:'error', title:'Erro ao criar caixa', text:e.message }); }
}

function openProductPicker() {
	$('#pdv-product-picker').modal('show');
	setTimeout(()=>{ document.getElementById('pp-q')?.focus(); }, 200);
}

async function ppSearch() {
	const q = (document.getElementById('pp-q')?.value||'').trim();
	if (!q) { return; }
	const tbody = document.getElementById('pp-tbody');
	if (tbody) tbody.innerHTML = '<tr><td colspan="5">Buscando...</td></tr>';
	try {
		let list = [];
		const isNum = /^[0-9]{2,}$/.test(q);
		if (isNum) {
			const r = await fetch('/api/products/barcode/' + encodeURIComponent(q), { headers:{'Accept':'application/json'} });
			if (r.ok) { const one = await r.json(); list = [one]; }
		}
		if (list.length === 0) {
			// Usar o novo endpoint index com fallback all=1 para abranger dados existentes
			let sr = await fetch('/api/products?q=' + encodeURIComponent(q), { headers:{'Accept':'application/json'} });
			if (sr.ok) list = await sr.json();
			if (!Array.isArray(list) || list.length===0) {
				sr = await fetch('/api/products?q=' + encodeURIComponent(q) + '&all=1', { headers:{'Accept':'application/json'} });
				if (sr.ok) list = await sr.json();
			}
		}
		renderPickerRows(list||[]);
	} catch(e) {
		renderPickerRows([]);
	}
}

function renderPickerRows(items) {
	const tbody = document.getElementById('pp-tbody');
	if (!tbody) return;
	if (!Array.isArray(items) || items.length===0) { tbody.innerHTML = '<tr><td colspan="5">Nenhum produto encontrado</td></tr>'; return; }
	tbody.innerHTML = '';
	items.forEach(p => {
		const tr = document.createElement('tr');
		tr.innerHTML = `
			<td>${p.id_produto||p.id||''}</td>
			<td>${p.nome||''}</td>
			<td>${p.codigo_de_barras||''}</td>
			<td class="text-right">${Number(p.valor_unitario||0).toLocaleString('pt-BR',{minimumFractionDigits:2})}</td>
			<td class="text-center"><button class="btn btn-sm btn-primary" onclick="ppAdd(${p.id_produto||p.id||0})"><i class=\"fas fa-plus\"></i></button></td>
		`;
		tbody.appendChild(tr);
	});
}

async function ppAdd(id) {
	try {
		const tbody = document.getElementById('pp-tbody');
		if (!id) return;
		// Buscar produto por ID através da busca genérica
		let p = null;
		const sr = await fetch('/api/products/search?q=' + encodeURIComponent(String(id)), { headers:{'Accept':'application/json'} });
		if (sr.ok) { const list = await sr.json(); p = Array.isArray(list) ? list.find(x => (x.id_produto||x.id)==id) || list[0] : null; }
		if (!p) { Swal.fire({ type:'error', title:'Produto não encontrado' }); return; }
		const payload = {
			id_produto: (p.id_produto||p.id)||null,
			nome: p.nome,
			codigo_de_barras: p.codigo_de_barras||'SEM GTIN',
			unidade: (p.unidade||'UN'),
			quantidade: 1,
			valor_unitario: p.valor_unitario,
			desconto: 0,
			CFOP_NFCe: p.CFOP_NFCe||'5102',
			CFOP_NFe: p.CFOP_NFe||'5102',
			CFOP_Externo: p.CFOP_Externo||'6102',
			NCM: p.NCM||'00000000',
			CSOSN: p.CSOSN||'102'
		};
		const r = await fetch('/api/cart', { method:'POST', headers:{'Accept':'application/json','Content-Type':'application/json'}, body: JSON.stringify(payload) });
		if (!r.ok) { const j = await r.json().catch(()=>({})); throw new Error(j?.error||'Falha ao adicionar'); }
		await atualizarCarrinho();
		Swal.fire({ type:'success', title:'Produto adicionado' });
	} catch(e) {
		Swal.fire({ type:'error', title:'Falha ao adicionar', text:e.message });
	}
}
</script>

<style>
.pdv-wrapper { background: #f5f6f8; }
.pdv-header { display:flex; justify-content:space-between; align-items:center; padding:10px 16px; background:#fff; border-bottom:1px solid #e5e7eb; }
.pdv-header-left { display:flex; align-items:center; font-size:16px; }
.pdv-header-right { display:flex; align-items:center; }
.pdv-op-avatar { width:32px; height:32px; border-radius:50%; object-fit:cover; }
.pdv-dot { display:inline-block; width:8px; height:8px; border-radius:50%; background:#28a745; margin-right:6px; }
.pdv-clock { margin-left:12px; font-size:12px; color:#6b7280; }
.pdv-body { display:flex; gap:16px; padding:12px 16px; box-sizing: border-box; align-items:flex-start; }
.pdv-sidebar { width:96px; background:#fff; border:1px solid #e5e7eb; border-radius:6px; padding:8px; display:flex; flex-direction:column; align-items:stretch; align-self:flex-start; position:sticky; top:12px; max-height:calc(100vh - 80px); overflow:auto; }
.pdv-menu-btn { display:flex; flex-direction:column; align-items:center; justify-content:center; border:1px solid #e5e7eb; background:#fff; border-radius:6px; padding:10px 6px; margin-bottom:8px; font-size:12px; color:#374151; }
.pdv-menu-btn i { font-size:18px; margin-bottom:6px; }
.pdv-menu-btn.active, .pdv-menu-btn:hover { background:#e6f7ff; border-color:#bae7ff; color:#0366d6; }
.pdv-center { flex:1 1 auto; min-width:0; display:flex; flex-direction:column; }
.pdv-scan { margin-bottom:8px; }
.pdv-items { background:#fff; border:1px solid #e5e7eb; display:flex; flex-direction:column; min-height:0; }
.pdv-items .table-responsive { flex:1 1 auto; overflow:auto; }
.pdv-items table thead th { font-size:12px; color:#6b7280; border-bottom:1px solid #e5e7eb; }
.pdv-items table tbody td { vertical-align:middle; }
.pdv-discounts { border-top:1px solid #e5e7eb; }
.pdv-summary { width:300px; align-self:flex-start; position:sticky; top:12px; max-height:calc(100vh - 80px); }
.pdv-summary .card { border:1px solid #e5e7eb; }
.pdv-total { font-weight:700; font-size:14px; color:#111827; background:#f7fee7; padding:8px; border-radius:6px; border:1px dashed #bbf7d0; }
.pdv-total span { font-size:22px; color:#16a34a; }
.pdv-footer { display:none; }
@media (max-width: 1200px) { .pdv-summary { width:280px; } .pdv-sidebar { width:88px; } }
@media (max-width: 992px) { .pdv-body { flex-direction:column; } .pdv-summary, .pdv-sidebar { width:100%; } }
</style>

<!-- Modal seletor rápido de produtos -->
<div class="modal fade" id="pdv-product-picker" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Adicionar Produto</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			</div>
			<div class="modal-body">
				<div class="input-group mb-2">
					<input id="pp-q" type="text" class="form-control" placeholder="Buscar por nome, código ou barras...">
					<div class="input-group-append"><button class="btn btn-primary" onclick="ppSearch()"><i class="fas fa-search"></i></button></div>
				</div>
				<div class="table-responsive" style="max-height:50vh; overflow:auto;">
					<table class="table table-hover table-sm mb-0">
						<thead><tr><th>Cód</th><th>Nome</th><th>Cod. Barras</th><th class="text-right">Vlr Unit</th><th class="text-center">Ação</th></tr></thead>
						<tbody id="pp-tbody"></tbody>
					</table>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
			</div>
		</div>
	</div>
</div>


