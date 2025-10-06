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
			<button class="pdv-menu-btn" title="Pedidos" onclick="openPedidos()"><i class="fas fa-file-invoice"></i><span>Pedidos</span></button>
			<button class="pdv-menu-btn" title="Produtos" onclick="openProductPicker()"><i class="fas fa-box"></i><span>Produtos</span></button>
            <button class="pdv-menu-btn" title="Relatórios" onclick="openReports()"><i class="fas fa-chart-line"></i><span>Relatórios</span></button>
            <button class="pdv-menu-btn" title="Configurações" onclick="openSettings()"><i class="fas fa-cog"></i><span>Configurações</span></button>
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
                            <div class="btn-group btn-group-toggle d-flex" style="gap:8px">
                                <button class="btn btn-success flex-fill" onclick="finalizarPDV(false)"><i class="fas fa-check"></i> Finalizar (sem NFC-e)</button>
                                <button class="btn btn-primary flex-fill" onclick="finalizarPDV(true)"><i class="fas fa-file-invoice"></i> Emitir NFC-e e Finalizar</button>
                            </div>
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

// Locks para evitar cliques rápidos duplicados
window.PDV_LOCKS = window.PDV_LOCKS || {};

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
		console.log('[PDV] adicionarItemPDV - INICIADO com código:', codigo);
		let prod = null;
		const term = codigo.trim();
		// 1) Tenta por código de barras exato (apenas se parecer com EAN ou numérico curto)
		if (/^[0-9]{2,}$/.test(term)) {
			console.log('[PDV] adicionarItemPDV - Buscando por código de barras');
			const url = '/api/products/barcode/' + encodeURIComponent(term);
			console.log('[PDV] adicionarItemPDV - URL barcode:', url);
			let resp = await fetch(url, { headers: { 'Accept': 'application/json' }});
			console.log('[PDV] adicionarItemPDV - Status barcode:', resp.status);
			if (resp.ok) { 
				prod = await resp.json(); 
				console.log('[PDV] adicionarItemPDV - Produto por barcode:', prod);
			}
		}
		// 2) Busca por nome/ID
		if (!prod) {
			console.log('[PDV] adicionarItemPDV - Buscando por nome/ID');
			const url2 = '/api/products/search?q=' + encodeURIComponent(term);
			console.log('[PDV] adicionarItemPDV - URL search:', url2);
			const sr = await fetch(url2, { headers:{'Accept':'application/json'} });
			console.log('[PDV] adicionarItemPDV - Status search:', sr.status);
			if (sr.ok) {
				const list = await sr.json();
				console.log('[PDV] adicionarItemPDV - Lista search:', list);
				if (Array.isArray(list) && list.length) prod = list[0];
			}
		}
		console.log('[PDV] adicionarItemPDV - Produto final:', prod);
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
        if (window.PDV_LOCKS[id]) { return; }
		const item = (window.PDV_ITEMS||[]).find(i => String(i.id_produto_provisorio) === String(id));
		if (!item) return;
		const nova = Math.max(1, parseInt(item.quantidade||1) + parseInt(delta||0));
        // Otimista: atualiza cache para que segundo clique calcule sobre o novo valor
        try { item.quantidade = nova; } catch(e) {}
        const payload = { quantidade: nova };
        window.PDV_LOCKS[id] = true;
        const up = await fetch('/api/cart/' + id, { method: 'PATCH', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: JSON.stringify(payload) });
        if (!up.ok) { let j=null; try{ j = await up.json(); }catch(e){} throw new Error(j?.messages?.error || j?.error || 'Falha ao atualizar quantidade'); }
		await atualizarCarrinho();
        window.PDV_LOCKS[id] = false;
	} catch(e) { console.error(e); }
}

async function finalizarPDV(emitNfce) {
	try {
		const saleId = (window.PDV && window.PDV.saleId) ? window.PDV.saleId : null;
		if (!saleId) { Swal.fire({type:'error', title:'Sem venda ativa'}); return; }
		const totalText = (document.getElementById('sum-total')?.textContent||'0').replace(/[^0-9,.-]/g,'').replace('.','').replace(',','.');
		const total = parseFloat(totalText||'0');
		const paymentType = (window.PDV?.paymentType||'cash');
        const payload = { total: total, paid_amount: total, change_amount: 0, payment_type: paymentType, emit_nfce: !!emitNfce };
		
		// Mostrar loading
		Swal.fire({ title: 'Processando...', text: 'Aguarde', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
		
		const resp = await fetch('/api/pos/' + saleId + '/finalize', { method: 'POST', headers: { 'Accept': 'application/json', 'Content-Type':'application/json' }, body: JSON.stringify(payload) });
		const data = await resp.json();
		
		if (!resp.ok) throw new Error(data.messages?.error || (data.error||'Falha ao finalizar'));
		
		// Tratamento especial para PIX: mostrar QR Code
		if (paymentType === 'pix' && data.pix) {
			Swal.fire({
				title: 'PIX - Aguardando Pagamento',
				html: `
					<div style="text-align:center;">
						<p><strong>Valor: R$ ${total.toFixed(2).replace('.', ',')}</strong></p>
						<p>Escaneie o QR Code abaixo:</p>
						<div style="margin: 20px auto; padding: 10px; background: white; display: inline-block;">
							<img src="${data.pix.qr_code_image || 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='}" style="max-width: 300px;" />
						</div>
						<p style="font-size: 12px; word-break: break-all; background: #f5f5f5; padding: 10px; border-radius: 5px;">
							<strong>Código PIX:</strong><br/>
							<small>${data.pix.qr_code || ''}</small>
						</p>
						<p style="color: #666; font-size: 14px;">Expira em: ${data.pix.expires_at || ''}</p>
					</div>
				`,
				icon: 'info',
				confirmButtonText: 'OK',
				width: 600
			});
			await atualizarCarrinho();
			return;
		}
		
		// Tratamento para TEF: mostrar status
		if ((paymentType === 'credit' || paymentType === 'debit') && data.tef_transaction) {
			Swal.fire({ 
				type: 'success', 
				title: 'Pagamento Aprovado!',
				html: `
					<p><strong>Cartão ${paymentType === 'credit' ? 'Crédito' : 'Débito'}</strong></p>
					<p>NSU: ${data.tef_transaction.nsu || 'N/A'}</p>
					<p>Autorização: ${data.tef_transaction.authorization_code || 'N/A'}</p>
				`
			});
		} else {
			Swal.fire({ type: 'success', title: 'Venda finalizada!' });
		}
		
		await atualizarCarrinho();
        if (emitNfce) {
            window.open('/api/pos/' + saleId + '/receipt/html', '_blank');
        } else {
            try {
                // Carrega preferências de impressão para decidir impressão automática
                const pr = await fetch('/api/settings/printing', { headers:{'Accept':'application/json'} });
                let autoPrint = false;
                if (pr.ok) { const pj = await pr.json(); autoPrint = !!(pj?.auto_print); }
                if (autoPrint) {
                    // Imprime não fiscal automaticamente
                    window.open('/api/pos/' + saleId + '/receipt/non-fiscal', '_blank');
                } else {
                    const act = await Swal.fire({ title:'Imprimir recibo não fiscal?', type:'question', showCancelButton:true, confirmButtonText:'Imprimir', cancelButtonText:'Agora não' });
                    if (act.isConfirmed) {
                        window.open('/api/pos/' + saleId + '/receipt/non-fiscal', '_blank');
                    }
                }
            } catch(e) {}
        }
        // Atualiza histórico da última venda e limpa carrinho exibido
        try {
            const tb = document.getElementById('pdv-last-sale-tbody');
            if (tb) {
                const r = await fetch('/api/pos/' + saleId + '/items', { headers: { 'Accept': 'application/json' }});
                const itens = r.ok ? (await r.json()) : [];
                tb.innerHTML = '';
                let total = 0;
                if (Array.isArray(itens) && itens.length) {
                    itens.forEach(it => {
                        const subtotal = ((parseFloat(it.valor_unitario||0) * parseFloat(it.quantidade||0)) - parseFloat(it.desconto||0));
                        total += subtotal;
                        const tr = document.createElement('tr');
                        tr.innerHTML = `<td>${it.nome||''}</td><td class="text-right">${Number(it.quantidade||0).toLocaleString('pt-BR')}</td><td class="text-right">${Number(subtotal||0).toLocaleString('pt-BR',{minimumFractionDigits:2})}</td>`;
                        tb.appendChild(tr);
                    });
                    const trt = document.createElement('tr');
                    trt.innerHTML = `<td><strong>Total</strong></td><td></td><td class="text-right"><strong>${Number(total||0).toLocaleString('pt-BR',{minimumFractionDigits:2})}</strong></td>`;
                    tb.appendChild(trt);
                } else {
                    tb.innerHTML = '<tr><td colspan="3">Sem dados</td></tr>';
                }
            }
        } catch(e) { /* ignore */ }
        // Limpa visual do carrinho
        try { window.PDV_ITEMS = []; await atualizarCarrinho(); } catch(e) {}
	} catch(e) { Swal.fire({ type: 'error', title: 'Erro ao finalizar', text: e.message }); }
}

// fecharCaixa implementado em app.js

// Função gerenciarCaixa removida - funcionalidade agora nos botões específicos "Abrir Caixa" e "Fechar Caixa"

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

// Função de fallback caso app.js não carregue
if (typeof window.fecharCaixaBtn !== 'function') {
	window.fecharCaixaBtn = async function() {
		console.log('FALLBACK: fecharCaixaBtn executado');
		try {
			const shifts = await fetch('/api/shifts', { headers: {'Accept':'application/json'} }).then(r=>r.json());
			const sel = Array.isArray(shifts) ? shifts.find(s => String(s.status).toLowerCase() === 'open') : null;
			if (!sel) { 
				Swal.fire({ type: 'info', title: 'Nenhum caixa aberto' }); 
				return; 
			}
			const amount = await Swal.fire({ 
				title: 'Fechar Caixa', 
				text: 'Digite o valor em dinheiro que está no caixa:',
				input: 'text', 
				inputValue: '0,00',
				inputPlaceholder: 'Ex: 150,00',
				showCancelButton: true,
				confirmButtonText: 'Fechar Caixa',
				cancelButtonText: 'Cancelar',
				confirmButtonColor: '#28a745',
				cancelButtonColor: '#6c757d',
				inputValidator: (value) => {
					if (!value) {
						return 'Digite um valor!'
					}
				}
			});
			// Verificação mais robusta para diferentes versões do SweetAlert2
			const isConfirmed = amount.isConfirmed !== false && amount.value !== undefined && amount.dismiss === undefined;
			console.log('FALLBACK: Modal confirmado:', isConfirmed);
			if (!isConfirmed) {
				console.log('FALLBACK: Modal cancelado pelo usuário');
				return;
			}
			const amtStr = String(amount.value || '0').trim();
			const payload = { closed_by: 'pdv', closing_amount: amtStr, id_cash_register: sel.id_cash_register };
			const url = '/api/shifts/close/' + sel.id_shift;
			console.log('FALLBACK: Fechando caixa - URL:', url, 'Payload:', payload);
			const response = await fetch(url, { 
				method: 'POST', 
				headers: {'Accept':'application/json','Content-Type':'application/json'}, 
				body: JSON.stringify(payload) 
			});
			console.log('FALLBACK: Response status:', response.status);
			if (response.ok) {
				Swal.fire({ type: 'success', title: 'Caixa fechado com sucesso!' });
				window.location.reload(); // Recarregar página para atualizar estado
			} else {
				const error = await response.json().catch(() => ({}));
				throw new Error(error.message || 'Falha ao fechar caixa');
			}
		} catch(e) {
			console.error('FALLBACK: Erro:', e);
			Swal.fire({ type: 'error', title: 'Erro ao fechar caixa', text: e.message });
		}
	};
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
    // Reset paginação e carrega a primeira página por padrão
    window.PP_STATE = { page: 1, loading: false, done: false, q: '' };
    try { ppSearch(true); } catch(e) {}
}

async function ppSearch(reset=false) {
    const q = (document.getElementById('pp-q')?.value||'').trim();
	const tbody = document.getElementById('pp-tbody');
    if (reset && tbody) tbody.innerHTML = '';
    if (!window.PP_STATE) window.PP_STATE = { page: 1, loading: false, done: false, q: q };
    if (reset) { window.PP_STATE.page = 1; window.PP_STATE.done = false; }
    if (window.PP_STATE.loading || window.PP_STATE.done) return;
    window.PP_STATE.loading = true;
    const curPage = window.PP_STATE.page;
    const limit = 50;
    if (tbody) tbody.insertAdjacentHTML('beforeend','<tr class="pp-loading"><td colspan="6">Carregando...</td></tr>');
	try {
		let list = [];
        if (q && /^[0-9]{2,}$/.test(q)) {
            const r = await fetch('/api/products/barcode/' + encodeURIComponent(q), { headers:{'Accept':'application/json'} });
            if (r.ok) { const one = await r.json(); if (one) list = [one]; }
            window.PP_STATE.done = true;
        } else {
            const sr = await fetch('/api/products/search?q=' + encodeURIComponent(q) + '&page=' + curPage + '&limit=' + limit, { headers:{'Accept':'application/json'} });
            if (sr.ok) list = await sr.json();
            if (!Array.isArray(list) || list.length < limit) window.PP_STATE.done = true;
        }
        renderPickerRows(list||[]);
        window.PP_STATE.page = curPage + 1;
	} catch(e) {
		renderPickerRows([]);
	}
    finally {
        window.PP_STATE.loading = false;
        const sp = document.querySelector('#pp-tbody tr.pp-loading'); if (sp) sp.remove();
    }
}

function renderPickerRows(items) {
	const tbody = document.getElementById('pp-tbody');
	if (!tbody) return;
    if (!Array.isArray(items) || items.length===0) { if (!tbody.innerHTML) tbody.innerHTML = '<tr><td colspan="6">Nenhum produto encontrado</td></tr>'; return; }
	tbody.innerHTML = '';
    items.forEach(p => {
		const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${p.id_produto||p.id||''}</td>
            <td>${p.nome||''}</td>
            <td>${p.codigo_de_barras||''}</td>
            <td class="text-right">${Number((p.valor_unitario??p.preco_unitario)||0).toLocaleString('pt-BR',{minimumFractionDigits:2})}</td>
            <td class="text-right"><span class="${(Number(p.quantidade_estoque||p.estoque||0)===0)?'text-danger':(Number(p.quantidade_estoque||p.estoque||0)<=10?'text-warning':'')}">${Number(p.quantidade_estoque||p.estoque||0).toLocaleString('pt-BR')}</span></td>
            <td class="text-center"><button class="btn btn-sm btn-primary" onclick="ppAdd(${p.id_produto||p.id||0})"><i class=\"fas fa-plus\"></i></button></td>
        `;
		tbody.appendChild(tr);
	});
}

// Scroll infinito no seletor de produtos
document.getElementById('pdv-product-picker')?.addEventListener('shown.bs.modal', ()=>{
    const tbl = document.querySelector('#pdv-product-picker .table-responsive');
    if (!tbl) return;
    tbl.addEventListener('scroll', ()=>{
        if (!tbl) return;
        const nearBottom = (tbl.scrollTop + tbl.clientHeight + 50) >= tbl.scrollHeight;
        if (nearBottom) { ppSearch(false); }
    });
});

async function ppAdd(id) {
	try {
		console.log('[PDV] ppAdd - INICIADO com ID:', id);
		const tbody = document.getElementById('pp-tbody');
		if (!id) {
			console.log('[PDV] ppAdd - ID inválido');
			return;
		}
		// Buscar produto por ID através da busca genérica
		let p = null;
		const url = '/api/products/search?q=' + encodeURIComponent(String(id));
		console.log('[PDV] ppAdd - Buscando produto na URL:', url);
		const sr = await fetch(url, { headers:{'Accept':'application/json'} });
		console.log('[PDV] ppAdd - Status da resposta:', sr.status);
		if (sr.ok) { 
			const list = await sr.json(); 
			console.log('[PDV] ppAdd - Lista retornada:', list);
			p = Array.isArray(list) ? list.find(x => (x.id_produto||x.id)==id) || list[0] : null; 
			console.log('[PDV] ppAdd - Produto encontrado:', p);
		}
		if (!p) { 
			console.log('[PDV] ppAdd - Produto não encontrado!');
			Swal.fire({ type:'error', title:'Produto não encontrado' }); 
			return; 
		}
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

// Pedidos - UI e lógica
async function openPedidos() {
    $('#pdv-orders').modal('show');
    setTimeout(()=>{ document.getElementById('ord-q')?.focus(); }, 200);
    await fetchPedidos();
}

async function fetchPedidos(page=1) {
    const q = (document.getElementById('ord-q')?.value||'').trim();
    const status = (document.getElementById('ord-status')?.value||'').trim();
    const pay = (document.getElementById('ord-pay')?.value||'').trim();
    const period = (document.getElementById('ord-period')?.value||'').trim();
    const de = (document.getElementById('ord-de')?.value||'').trim();
    const ate = (document.getElementById('ord-ate')?.value||'').trim();
    const params = new URLSearchParams({ page: String(page), per_page: '20' });
    if (q) params.set('q', q);
    if (status) params.set('status', status);
    if (pay) params.set('payment_type', pay);
    if (period) params.set('period', period);
    if (de) params.set('de', de);
    if (ate) params.set('ate', ate);
    const tbody = document.getElementById('ord-tbody');
    if (tbody) tbody.innerHTML = '<tr><td colspan="6">Carregando...</td></tr>';
    try {
        const r = await fetch('/api/pos?' + params.toString(), { headers: { 'Accept': 'application/json' }});
        const j = await r.json();
        const rows = Array.isArray(j?.data) ? j.data : (Array.isArray(j) ? j : []);
        if (tbody) {
            tbody.innerHTML = '';
            if (!rows.length) { tbody.innerHTML = '<tr><td colspan="6">Sem pedidos</td></tr>'; return; }
			rows.forEach(x => {
				const tr = document.createElement('tr');
				tr.dataset.id = String(x.id_pos_sale || x.id || '');
				tr.style.cursor = 'pointer';
				const total = Number(x.total||0).toLocaleString('pt-BR',{minimumFractionDigits:2});
				const statusText = x.status === 'finalized' ? 'Finalizado' : x.status === 'draft' ? 'Em Aberto' : x.status === 'cancelled' ? 'Cancelado' : x.status || '';
				tr.innerHTML = `<td>#${x.id_pos_sale||x.id}</td><td>${x.cliente_nome||'-'}</td><td>${(x.created_at||'').replace('T',' ').replace('Z','')}</td><td class="text-right">R$ ${total}</td><td>${statusText}</td><td>${x.payment_type||''}</td>`;
				tr.addEventListener('click', () => {
					// highlight seleção
					Array.from(tbody.querySelectorAll('tr')).forEach(row => row.classList.remove('table-active'));
					tr.classList.add('table-active');
					openOrderDetail(x);
				});
				tbody.appendChild(tr);
			});
        }
    } catch(e) {
        if (tbody) tbody.innerHTML = '<tr><td colspan="6">Erro ao carregar</td></tr>';
    }
}

// Relatórios - UI e lógica
async function openReports() {
    $('#pdv-reports').modal('show');
    await fetchReports();
}

// Configurações - Empresa
async function openSettings() {
    $('#pdv-settings').modal('show');
    try {
        const r = await fetch('/api/settings/company', { headers: { 'Accept':'application/json' }});
        if (!r.ok) return;
        const j = await r.json();
        document.getElementById('cmp-nome').value = j?.nome||'';
        document.getElementById('cmp-razao').value = j?.razao_social||'';
        document.getElementById('cmp-cnpj').value = j?.cnpj||'';
        document.getElementById('cmp-fone').value = j?.telefone||'';
        document.getElementById('cmp-logradouro').value = j?.endereco?.logradouro||'';
        document.getElementById('cmp-numero').value = j?.endereco?.numero||'';
        document.getElementById('cmp-bairro').value = j?.endereco?.bairro||'';
        document.getElementById('cmp-cep').value = j?.endereco?.cep||'';
        const pv = document.getElementById('cmp-logo-preview'); if (pv && j?.logo_url) pv.src = j.logo_url; 
    } catch(e) {}
}

async function saveCompany() {
    try {
        // salva dados JSON
        const payload = {
            nome: document.getElementById('cmp-nome').value.trim(),
            razao_social: document.getElementById('cmp-razao').value.trim(),
            cnpj: document.getElementById('cmp-cnpj').value.trim(),
            telefone: document.getElementById('cmp-fone').value.trim(),
            endereco: {
                logradouro: document.getElementById('cmp-logradouro').value.trim(),
                numero: document.getElementById('cmp-numero').value.trim(),
                bairro: document.getElementById('cmp-bairro').value.trim(),
                cep: document.getElementById('cmp-cep').value.trim(),
            }
        };
        await fetch('/api/settings/company', { method:'POST', headers:{ 'Accept':'application/json','Content-Type':'application/json' }, body: JSON.stringify(payload) });
        // upload de logo, se houver
        const logo = document.getElementById('cmp-logo');
        if (logo && logo.files && logo.files.length) {
            const fd = new FormData(); fd.append('logo', logo.files[0]);
            const ru = await fetch('/api/settings/company', { method:'POST', body: fd });
            if (ru.ok) { const j = await ru.json(); if (j?.logo_url) document.getElementById('cmp-logo-preview').src = j.logo_url; }
        }
        Swal.fire({ type:'success', title:'Configurações salvas' });
    } catch(e) { Swal.fire({ type:'error', title:'Falha ao salvar' }); }
}

// Impressão - carregar/salvar e teste
document.getElementById('pdv-settings')?.addEventListener('shown.bs.modal', async ()=>{ await loadPrinting(); });

async function loadPrinting() {
    try {
        const r = await fetch('/api/settings/printing', { headers:{'Accept':'application/json'} });
        if (!r.ok) return;
        const j = await r.json();
        document.getElementById('prt-printer').value = j?.printer_name||'';
        document.getElementById('prt-auto').checked = !!(j?.auto_print);
        document.getElementById('prt-header').value = j?.header_text||'';
        document.getElementById('prt-footer').value = j?.footer_text||'';
    } catch(e) {}
}

async function savePrinting() {
    try {
        const payload = {
            printer_name: document.getElementById('prt-printer').value.trim(),
            auto_print: document.getElementById('prt-auto').checked,
            header_text: document.getElementById('prt-header').value,
            footer_text: document.getElementById('prt-footer').value,
        };
        await fetch('/api/settings/printing', { method:'POST', headers:{'Accept':'application/json','Content-Type':'application/json'}, body: JSON.stringify(payload) });
        Swal.fire({ type:'success', title:'Configurações de impressão salvas' });
    } catch(e) { Swal.fire({ type:'error', title:'Falha ao salvar impressão' }); }
}

function testPrint() {
    try {
        const header = (document.getElementById('prt-header').value||'');
        const footer = (document.getElementById('prt-footer').value||'');
        const w = window.open('', '_blank');
        if (!w) return;
        w.document.write('<html><head><title>Teste de Impressão</title><style>body{font-family:monospace;font-size:12px} .center{text-align:center}</style></head><body>');
        w.document.write(`<div class="center">${header.replace(/\n/g,'<br>')}</div><hr>`);
        w.document.write('<div>Teste de impressão do PDV</div>');
        w.document.write('<div>Data: ' + new Date().toLocaleString('pt-BR') + '</div><hr>');
        w.document.write(`<div class="center">${footer.replace(/\n/g,'<br>')}</div>`);
        w.document.write('</body></html>');
        w.document.close(); w.focus(); setTimeout(()=>{ w.print(); w.close(); }, 300);
    } catch(e) {}
}

// Meios de Pagamento - carregar/salvar
document.getElementById('pdv-settings')?.addEventListener('shown.bs.modal', async ()=>{ await loadPayments(); });

async function loadPayments() {
    try {
        const r = await fetch('/api/settings/payments', { headers:{'Accept':'application/json'} });
        if (!r.ok) return;
        const j = await r.json(); const m = j?.methods || {};
        document.getElementById('pm-cash').checked    = !!(m.cash?.enabled);
        document.getElementById('pm-credit').checked  = !!(m.credit?.enabled);
        document.getElementById('pm-credit-pct').value= Number(m.credit?.fee_percent||0);
        document.getElementById('pm-credit-fix').value= Number(m.credit?.fee_fixed||0);
        document.getElementById('pm-debit').checked   = !!(m.debit?.enabled);
        document.getElementById('pm-debit-pct').value = Number(m.debit?.fee_percent||0);
        document.getElementById('pm-debit-fix').value = Number(m.debit?.fee_fixed||0);
        document.getElementById('pm-pix').checked     = !!(m.pix?.enabled);
        document.getElementById('pm-voucher').checked = !!(m.voucher?.enabled);
    } catch(e) {}
}

// Usuários e Permissões
document.getElementById('pdv-settings')?.addEventListener('shown.bs.modal', async ()=>{ await loadUsers(); });

async function loadUsers() {
    try {
        const r = await fetch('/api/settings/users', { headers:{'Accept':'application/json'} });
        if (!r.ok) return;
        const j = await r.json();
        const tb = document.getElementById('users-tbody'); if (!tb) return;
        tb.innerHTML = '';
        const list = Array.isArray(j?.users) ? j.users : [];
        if (!list.length) { tb.innerHTML = '<tr><td colspan="4">Sem usuários</td></tr>'; return; }
        list.forEach(u => {
            const tr = document.createElement('tr');
            tr.innerHTML = `<td>${u.id}</td><td>${u.username}</td><td>${(u.role||'caixa')}</td><td class="text-right">
                <button class="btn btn-sm btn-outline-secondary" onclick="editUser(${u.id})">Editar</button>
                <button class="btn btn-sm btn-outline-danger" onclick="deleteUser(${u.id})">Remover</button>
            </td>`;
            tb.appendChild(tr);
        });
    } catch(e) {}
}

async function openUserModal(id) {
    const mode = id ? 'edit' : 'add';
    let username = '', role = 'caixa';
    if (id) {
        try {
            const r = await fetch('/api/settings/users', { headers:{'Accept':'application/json'} }); const j = await r.json();
            const u = (j.users||[]).find(x => String(x.id)===String(id)); if (u) { username = u.username||''; role = u.role||'caixa'; }
        } catch(e) {}
    }
    const { value: formValues } = await Swal.fire({
        title: mode==='add' ? 'Adicionar usuário' : 'Editar usuário',
        html: `
            <input id="sw-usr" class="swal2-input" placeholder="Usuário" value="${username}">
            <input id="sw-pwd" type="password" class="swal2-input" placeholder="Senha${mode==='edit'?' (deixe em branco p/ manter)':''}">
            <select id="sw-role" class="swal2-input">
                <option value="caixa" ${role==='caixa'?'selected':''}>Caixa</option>
                <option value="gerente" ${role==='gerente'?'selected':''}>Gerente</option>
            </select>
        `,
        focusConfirm: false,
        showCancelButton: true,
        preConfirm: () => ({ username: document.getElementById('sw-usr').value, password: document.getElementById('sw-pwd').value, role: document.getElementById('sw-role').value })
    });
    if (!formValues) return;
    try {
        if (mode==='add') {
            const r = await fetch('/api/settings/users', { method:'POST', headers:{'Accept':'application/json','Content-Type':'application/json'}, body: JSON.stringify(formValues) });
            if (!r.ok) throw new Error('Falha ao criar');
        } else {
            const body = { role: formValues.role }; if ((formValues.password||'')!=='') body.password = formValues.password;
            const r = await fetch('/api/settings/users?id='+encodeURIComponent(String(id)), { method:'PATCH', headers:{'Accept':'application/json','Content-Type':'application/json'}, body: JSON.stringify(body) });
            if (!r.ok) throw new Error('Falha ao salvar');
        }
        await loadUsers();
        Swal.fire({ type:'success', title:'Salvo' });
    } catch(e) { Swal.fire({ type:'error', title: e.message||'Erro' }); }
}

function editUser(id) { openUserModal(id); }

async function deleteUser(id) {
    const ok = await Swal.fire({ title: 'Remover usuário?', type:'warning', showCancelButton:true, confirmButtonText:'Remover' });
    if (!ok.isConfirmed) return;
    try {
        const r = await fetch('/api/settings/users?id='+encodeURIComponent(String(id)), { method:'DELETE', headers:{'Accept':'application/json'} });
        if (!r.ok) throw new Error('Falha ao remover');
        await loadUsers();
    } catch(e) { Swal.fire({ type:'error', title:'Erro ao remover' }); }
}

async function savePayments() {
    try {
        const payload = { methods: {
            cash:   { enabled: document.getElementById('pm-cash').checked },
            credit: { enabled: document.getElementById('pm-credit').checked, fee_percent: parseFloat(document.getElementById('pm-credit-pct').value||'0'), fee_fixed: parseFloat(document.getElementById('pm-credit-fix').value||'0') },
            debit:  { enabled: document.getElementById('pm-debit').checked,  fee_percent: parseFloat(document.getElementById('pm-debit-pct').value||'0'),  fee_fixed: parseFloat(document.getElementById('pm-debit-fix').value||'0') },
            pix:    { enabled: document.getElementById('pm-pix').checked },
            voucher:{ enabled: document.getElementById('pm-voucher').checked },
        } };
        await fetch('/api/settings/payments', { method:'POST', headers:{'Accept':'application/json','Content-Type':'application/json'}, body: JSON.stringify(payload) });
        Swal.fire({ type:'success', title:'Meios de pagamento salvos' });
    } catch(e) { Swal.fire({ type:'error', title:'Falha ao salvar meios de pagamento' }); }
}

async function fetchReports() {
    try {
        const de = (document.getElementById('rep-de')?.value||'').trim() || new Date().toISOString().slice(0,10);
        const ate = (document.getElementById('rep-ate')?.value||'').trim() || new Date().toISOString().slice(0,10);
        const r = await fetch(`/api/pos/stats?de=${encodeURIComponent(de)}&ate=${encodeURIComponent(ate)}`, { headers: { 'Accept': 'application/json' }});
        const j = await r.json();
        // KPIs
        document.getElementById('kpi-faturamento').textContent = 'R$ ' + Number(j?.faturamento||0).toLocaleString('pt-BR', { minimumFractionDigits: 2 });
        document.getElementById('kpi-vendas').textContent = String(j?.num_vendas||0);
        document.getElementById('kpi-ticket').textContent = 'R$ ' + Number(j?.ticket_medio||0).toLocaleString('pt-BR', { minimumFractionDigits: 2 });
        // Top produtos
        const tb = document.getElementById('rep-top-tbody');
        if (tb) {
            tb.innerHTML = '';
            const list = Array.isArray(j?.top_produtos) ? j.top_produtos : [];
            if (!list.length) { tb.innerHTML = '<tr><td colspan="3">Sem dados</td></tr>'; return; }
            list.forEach(p => {
                const tr = document.createElement('tr');
                tr.innerHTML = `<td>${p.nome||''}</td><td class="text-right">${Number(p.qtd||0).toLocaleString('pt-BR')}</td><td class="text-right">${Number(p.total||0).toLocaleString('pt-BR',{ minimumFractionDigits:2 })}</td>`;
                tb.appendChild(tr);
            });
        }
    } catch(e) {
        // noop
    }
}

async function openOrderDetail(order) {
	const id = order?.id_pos_sale || order?.id;
	if (!id) return;
	try {
		const r = await fetch(`/api/pos/${id}/items`, { headers: { 'Accept': 'application/json' }});
		const itens = r.ok ? (await r.json()) : [];
		let lines = '';
		let totalItens = 0;
		if (Array.isArray(itens) && itens.length) {
			lines = itens.map(it => {
				const subtotal = ((parseFloat(it.valor_unitario||0) * parseFloat(it.quantidade||0)) - parseFloat(it.desconto||0));
				totalItens += subtotal;
				return `<tr><td>${it.nome||''}</td><td class="text-right">${Number(it.quantidade||0).toLocaleString('pt-BR')}</td><td class="text-right">${Number(it.valor_unitario||0).toLocaleString('pt-BR',{minimumFractionDigits:2})}</td><td class="text-right">${Number(subtotal||0).toLocaleString('pt-BR',{minimumFractionDigits:2})}</td></tr>`;
			}).join('');
		} else {
			lines = '<tr><td colspan="4">Sem itens</td></tr>';
		}
		const header = `
			<div><strong>Pedido</strong> #${id}</div>
			<div>Situação: <strong>${order.status === 'finalized' ? 'Finalizado' : order.status === 'draft' ? 'Em Aberto' : order.status || ''}</strong></div>
			<div>Data/Hora: ${(order.created_at||'').replace('T',' ').replace('Z','')}</div>
			<div>Cliente: ${order.cliente_nome||'-'}</div>
			<div>Pagamento: ${order.payment_type||''}</div>
		`;
		const resumo = `
			<div class="mt-2"><strong>Total:</strong> R$ ${Number(order.total||0).toLocaleString('pt-BR',{minimumFractionDigits:2})}</div>
			<div><strong>Desconto:</strong> R$ ${Number(order.discount||0).toLocaleString('pt-BR',{minimumFractionDigits:2})}</div>
			<div><strong>Pago:</strong> R$ ${Number(order.paid_amount||0).toLocaleString('pt-BR',{minimumFractionDigits:2})}</div>
		`;
		const isFinalized = String(order.status||'').toLowerCase()==='finalized';
		let nfceStatus = 'NFC-e: Não emitida';
		if (order.id_nfce) nfceStatus = 'NFC-e: Emitida com Sucesso';
		else if ((order.notes||'').toLowerCase().includes('nfc-e: falha')) nfceStatus = 'NFC-e: Rejeitada';

		let printControls = `
			<div class="mt-3 d-flex justify-content-between align-items-center">
				<div class="text-muted">${nfceStatus}</div>
				<div class="text-right">
					<div class="btn-group" role="group">
						<button type="button" class="btn btn-success btn-sm dropdown-toggle" ${isFinalized?'':'disabled'} data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
							<i class="fas fa-print"></i> Imprimir
						</button>
						<div class="dropdown-menu dropdown-menu-right">
							<a class="dropdown-item" href="javascript:void(0)" onclick="printReceiptNonFiscal(${id})">
								<i class="fas fa-receipt"></i> Reimpressão Térmica
							</a>
							${order.id_nfce ? `
								<a class="dropdown-item" href="javascript:void(0)" onclick="printReceipt(${id}, 'thermal')">
									<i class="fas fa-print"></i> Imprimir DANFE Térmica
								</a>
								<a class="dropdown-item" href="javascript:void(0)" onclick="printReceipt(${id}, 'a4')">
									<i class="far fa-file-pdf"></i> Gerar PDF (DANFE)
								</a>
							` : `
								<a class="dropdown-item disabled" href="javascript:void(0)">
									<i class="fas fa-info-circle"></i> Sem NFC-e vinculada
								</a>
							`}
						</div>
					</div>
				</div>
			</div>`;
		const html = `
			<div>${header}</div>
			<div class="table-responsive mt-2" style="max-height:40vh; overflow:auto;">
				<table class="table table-sm"><thead><tr><th>Item</th><th class="text-right">Qtd</th><th class="text-right">Vlr Unit</th><th class="text-right">Subtotal</th></tr></thead><tbody>${lines}</tbody></table>
			</div>
			<div>${resumo}</div>
			${printControls}
		`;
		const result = await Swal.fire({ 
			width: 900, 
			html, 
			showCancelButton: true, 
			showCloseButton: true, 
			confirmButtonText: 'Fechar', 
			cancelButtonText: 'Cancelar Pedido',
			showConfirmButton: false,
			showCancelButton: true
		});
		if (result.dismiss === Swal.DismissReason.cancel) {
            const ok = await Swal.fire({ title: 'Confirmar cancelamento?', input: 'password', inputPlaceholder: 'Senha do gerente (opcional)', showCancelButton: true, confirmButtonText: 'Cancelar' });
			if (ok.isConfirmed) {
				const r2 = await fetch(`/api/pos/${id}/cancel`, { method: 'POST', headers: { 'Accept': 'application/json', 'Content-Type':'application/json' }, body: JSON.stringify({ justificativa: 'Cancelado via pedidos' }) });
                if (r2.ok) { Swal.fire({ type:'success', title: 'Pedido cancelado' }); fetchPedidos(); }
                else { let j=null; try{ j=await r2.json(); }catch(e){} Swal.fire({ type:'error', title: 'Falha ao cancelar', text: j?.error||'Erro' }); }
			}
		}
    } catch(e) { Swal.fire({ type:'error', title:'Erro ao abrir pedido' }); }
}
</script>

<script>
function printReceipt(id, layout) {
    try {
        const url = `/api/pos/${encodeURIComponent(id)}/receipt/html?layout=${encodeURIComponent(layout||'thermal')}`;
        console.log('Printing receipt URL:', url);
        if (layout === 'a4') {
            // PDF/A4: abrir em nova aba para salvar/imprimir
            window.open(url, '_blank');
            return;
        }
        // Térmica: abrir em janela com iframe e acionar print
        const w = window.open('', '_blank');
        if (!w) { window.open(url, '_blank'); return; }
        w.document.write('<!DOCTYPE html><html><head><meta charset="utf-8"><title>Impressão</title></head><body><iframe id="prt" style="width:0;height:0;border:0;" src="'+ url +'"></iframe></body></html>');
        w.document.close();
        const attempt = () => {
            try {
                const iframe = w.document.getElementById('prt');
                if (iframe && iframe.contentWindow && iframe.contentWindow.document && iframe.contentWindow.document.readyState === 'complete') {
                    iframe.contentWindow.focus();
                    iframe.contentWindow.print();
                    setTimeout(()=>{ try { w.close(); } catch(e) {} }, 1000);
                } else {
                    setTimeout(attempt, 300);
                }
            } catch(e) {
                // fallback: deixa a aba aberta
            }
        };
        setTimeout(attempt, 500);
    } catch(e) { console.error(e); }
}

function printReceiptNonFiscal(id) {
    try {
        const url = `/api/pos/${encodeURIComponent(id)}/receipt/non-fiscal`;
        console.log('Printing NON-FISCAL receipt URL:', url);
        // Abrir direto em nova aba; HTML já dispara window.print()
        window.open(url, '_blank');
    } catch(e) { console.error(e); }
}

// Log de carregamento do script principal
console.log('PDV index_modern.php script carregado');
console.log('fecharCaixaBtn disponível:', typeof window.fecharCaixaBtn);

// ==================== MODO OFFLINE ====================
// Registrar Service Worker
if ('serviceWorker' in navigator) {
	window.addEventListener('load', () => {
		navigator.serviceWorker.register('/offline-service-worker.js')
			.then(registration => {
				console.log('[Service Worker] Registrado com sucesso:', registration.scope);
			})
			.catch(error => {
				console.error('[Service Worker] Erro no registro:', error);
			});
	});
}

// Inicializar IndexedDB (OfflineManager)
(async () => {
	try {
		// Obter tenant da sessão
		const idEmpresa = <?= json_encode(session('id_empresa') ?? 0) ?>;
		const idContador = <?= json_encode(session('id_contador') ?? 0) ?>;
		
		if (idEmpresa && idContador) {
			await window.offlineManager.init(idEmpresa, idContador);
			console.log('[OfflineManager] Inicializado para tenant:', idEmpresa, idContador);
			
			// Cachear produtos se online
			if (navigator.onLine) {
				try {
					const response = await fetch('/api/products');
					if (response.ok) {
						const data = await response.json();
						const produtos = Array.isArray(data) ? data : (data.data || []);
						await window.offlineManager.saveProdutos(produtos);
						console.log('[OfflineManager] Produtos cacheados:', produtos.length);
					}
				} catch (e) {
					console.warn('[OfflineManager] Não foi possível cachear produtos:', e.message);
				}
			}
		}
	} catch (error) {
		console.error('[OfflineManager] Erro na inicialização:', error);
	}
})();
// ======================================================
</script>

<!-- Scripts de Modo Offline -->
<script src="<?= base_url('pdv-assets/js/offline-manager.js') ?>"></script>
<script src="<?= base_url('pdv-assets/js/connection-monitor.js') ?>"></script>

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
                        <thead><tr><th>Cód</th><th>Nome</th><th>Cod. Barras</th><th class="text-right">Vlr Unit</th><th class="text-right">Estoque</th><th class="text-center">Ação</th></tr></thead>
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

<script src="<?= base_url('pdv-assets/js/app.js') ?>"></script>

<!-- Modal Pedidos -->
<div class="modal fade" id="pdv-orders" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog modal-xl" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Pedidos</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			</div>
			<div class="modal-body">
				<div class="form-row mb-2">
					<div class="col-md-4 mb-2"><input id="ord-q" type="text" class="form-control form-control-sm" placeholder="Buscar por #id, cliente, item"></div>
					<div class="col-md-2 mb-2">
						<select id="ord-period" class="form-control form-control-sm">
							<option value="">Período</option>
							<option value="today">Hoje</option>
							<option value="yesterday">Ontem</option>
							<option value="last7">Últimos 7 dias</option>
							<option value="month">Mês atual</option>
						</select>
					</div>
					<div class="col-md-2 mb-2"><input id="ord-de" type="date" class="form-control form-control-sm" placeholder="De"></div>
					<div class="col-md-2 mb-2"><input id="ord-ate" type="date" class="form-control form-control-sm" placeholder="Até"></div>
					<div class="col-md-2 mb-2">
						<select id="ord-status" class="form-control form-control-sm">
							<option value="">Situação</option>
							<option value="finalized">Finalizado</option>
							<option value="draft">Em Aberto</option>
							<option value="cancelled">Cancelado</option>
						</select>
					</div>
				</div>
				<div class="form-row mb-2">
					<div class="col-md-2 mb-2">
						<select id="ord-pay" class="form-control form-control-sm">
							<option value="">Pagamento</option>
							<option value="cash">Dinheiro</option>
							<option value="credit">Cartão Crédito</option>
							<option value="debit">Cartão Débito</option>
							<option value="pix">PIX</option>
						</select>
					</div>
					<div class="col-md-10 mb-2">
						<button class="btn btn-primary btn-sm" onclick="fetchPedidos()"><i class="fas fa-search"></i> Buscar</button>
					</div>
				</div>
				<div class="table-responsive" style="max-height:55vh; overflow:auto;">
					<table class="table table-hover table-sm">
						<thead><tr><th>#ID</th><th>Cliente</th><th>Data/Hora</th><th class="text-right">Total</th><th>Situação</th><th>Pagamento</th></tr></thead>
						<tbody id="ord-tbody"></tbody>
					</table>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
			</div>
		</div>
	</div>
</div>

<script src="<?= base_url('pdv-assets/js/app.js') ?>"></script>

<!-- Modal Configurações -->
<div class="modal fade" id="pdv-settings" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Configurações - Empresa</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			</div>
			<div class="modal-body">
				<ul class="nav nav-tabs" role="tablist">
					<li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#tab-company" role="tab">Empresa</a></li>
					<li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab-print" role="tab">Dispositivos e Impressão</a></li>
					<li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab-payments" role="tab">Meios de Pagamento</a></li>
					<li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab-users" role="tab">Usuários e Permissões</a></li>
				</ul>
				<div class="tab-content p-2">
					<div class="tab-pane fade show active" id="tab-company" role="tabpanel">
						<form id="form-company" onsubmit="return false;" enctype="multipart/form-data">
					<div class="form-row">
						<div class="col-md-8 mb-2"><label>Nome da Empresa</label><input id="cmp-nome" type="text" class="form-control form-control-sm"></div>
						<div class="col-md-4 mb-2"><label>CNPJ/CPF</label><input id="cmp-cnpj" type="text" class="form-control form-control-sm"></div>
					</div>
					<div class="form-row">
						<div class="col-md-6 mb-2"><label>Razão Social</label><input id="cmp-razao" type="text" class="form-control form-control-sm"></div>
						<div class="col-md-6 mb-2"><label>Telefone</label><input id="cmp-fone" type="text" class="form-control form-control-sm"></div>
					</div>
					<div class="form-row">
						<div class="col-md-6 mb-2"><label>Endereço</label><input id="cmp-logradouro" type="text" class="form-control form-control-sm"></div>
						<div class="col-md-2 mb-2"><label>Número</label><input id="cmp-numero" type="text" class="form-control form-control-sm"></div>
						<div class="col-md-2 mb-2"><label>Bairro</label><input id="cmp-bairro" type="text" class="form-control form-control-sm"></div>
						<div class="col-md-2 mb-2"><label>CEP</label><input id="cmp-cep" type="text" class="form-control form-control-sm"></div>
					</div>
					<div class="form-row">
						<div class="col-md-8 mb-2"><label>Logo da Empresa</label><input id="cmp-logo" name="logo" type="file" class="form-control-file" accept="image/*"></div>
						<div class="col-md-4 mb-2"><img id="cmp-logo-preview" src="" alt="logo" class="img-fluid border"/></div>
					</div>
						</form>
					</div>
					<div class="tab-pane fade" id="tab-print" role="tabpanel">
						<form id="form-print" onsubmit="return false;">
							<div class="form-row">
								<div class="col-md-6 mb-2">
									<label>Impressora Padrão</label>
									<input id="prt-printer" type="text" class="form-control form-control-sm" placeholder="Ex.: EPSON TM-T20">
									<small class="text-muted">A impressão no navegador usa a impressora padrão do sistema.</small>
								</div>
								<div class="col-md-3 mb-2 d-flex align-items-end">
									<button type="button" class="btn btn-outline-secondary btn-sm" onclick="testPrint()"><i class="fas fa-print"></i> Testar Impressão</button>
								</div>
							</div>
							<div class="form-row">
								<div class="col-md-6 mb-2 form-check">
									<input id="prt-auto" type="checkbox" class="form-check-input">
									<label class="form-check-label" for="prt-auto">Imprimir automaticamente após cada venda</label>
								</div>
							</div>
							<div class="form-row">
								<div class="col-md-6 mb-2"><label>Cabeçalho do cupom</label><textarea id="prt-header" class="form-control form-control-sm" rows="2" placeholder="Texto no início do cupom"></textarea></div>
								<div class="col-md-6 mb-2"><label>Rodapé do cupom</label><textarea id="prt-footer" class="form-control form-control-sm" rows="2" placeholder="Mensagem promocional, site, etc."></textarea></div>
							</div>
						</form>
						<div class="mt-2 text-right">
							<button type="button" class="btn btn-primary btn-sm" onclick="savePrinting()">Salvar Impressão</button>
						</div>
					</div>
					<div class="tab-pane fade" id="tab-payments" role="tabpanel">
						<form id="form-payments" onsubmit="return false;">
							<div class="table-responsive">
								<table class="table table-sm">
									<thead><tr><th>Meio</th><th>Ativo</th><th class="text-right">Taxa %</th><th class="text-right">Taxa fixa</th></tr></thead>
									<tbody>
										<tr><td>Dinheiro</td><td><input id="pm-cash" type="checkbox"></td><td class="text-right">-</td><td class="text-right">-</td></tr>
										<tr><td>Crédito</td><td><input id="pm-credit" type="checkbox"></td><td class="text-right"><input id="pm-credit-pct" type="number" class="form-control form-control-sm text-right" step="0.01"></td><td class="text-right"><input id="pm-credit-fix" type="number" class="form-control form-control-sm text-right" step="0.01"></td></tr>
										<tr><td>Débito</td><td><input id="pm-debit" type="checkbox"></td><td class="text-right"><input id="pm-debit-pct" type="number" class="form-control form-control-sm text-right" step="0.01"></td><td class="text-right"><input id="pm-debit-fix" type="number" class="form-control form-control-sm text-right" step="0.01"></td></tr>
										<tr><td>PIX</td><td><input id="pm-pix" type="checkbox"></td><td class="text-right">-</td><td class="text-right">-</td></tr>
										<tr><td>Vale Alimentação</td><td><input id="pm-voucher" type="checkbox"></td><td class="text-right">-</td><td class="text-right">-</td></tr>
									</tbody>
								</table>
							</div>
						</form>
						<div class="mt-2 text-right">
							<button type="button" class="btn btn-primary btn-sm" onclick="savePayments()">Salvar Meios de Pagamento</button>
						</div>
					</div>
					<div class="tab-pane fade" id="tab-users" role="tabpanel">
						<div class="d-flex justify-content-between align-items-center mb-2">
							<h6 class="m-0">Funcionários</h6>
							<button class="btn btn-primary btn-sm" onclick="openUserModal()"><i class="fas fa-user-plus"></i> Adicionar</button>
						</div>
						<div class="table-responsive">
							<table class="table table-sm" id="users-table">
								<thead><tr><th>#</th><th>Usuário</th><th>Perfil</th><th class="text-right">Ações</th></tr></thead>
								<tbody id="users-tbody"><tr><td colspan="4">Sem usuários</td></tr></tbody>
							</table>
						</div>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
				<button type="button" class="btn btn-primary" onclick="saveCompany()">Salvar</button>
			</div>
		</div>
	</div>
</div>

<script src="<?= base_url('pdv-assets/js/app.js') ?>"></script>
<!-- Modal Relatórios -->
<div class="modal fade" id="pdv-reports" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog modal-xl" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Relatórios</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			</div>
			<div class="modal-body">
				<div class="form-row mb-3">
					<div class="col-md-2 mb-2"><input id="rep-de" type="date" class="form-control form-control-sm"></div>
					<div class="col-md-2 mb-2"><input id="rep-ate" type="date" class="form-control form-control-sm"></div>
					<div class="col-md-2 mb-2"><button class="btn btn-primary btn-sm" onclick="fetchReports()"><i class="fas fa-sync"></i> Atualizar</button></div>
					<div class="col-md-6 mb-2 text-right">
						<button class="btn btn-outline-secondary btn-sm" onclick="exportReportsCSV()"><i class="fas fa-file-csv"></i> Exportar CSV</button>
						<button class="btn btn-outline-secondary btn-sm" onclick="exportReportsPDF()"><i class="fas fa-file-pdf"></i> Exportar PDF</button>
					</div>
				</div>
				<div class="row">
					<div class="col-md-3 mb-3">
						<div class="card text-white bg-success">
							<div class="card-body">
								<div class="small">Faturamento (período)</div>
								<div id="kpi-faturamento" style="font-size:20px;font-weight:700">R$ 0,00</div>
							</div>
						</div>
					</div>
					<div class="col-md-3 mb-3">
						<div class="card">
							<div class="card-body">
								<div class="small">Número de Vendas</div>
								<div id="kpi-vendas" style="font-size:20px;font-weight:700">0</div>
							</div>
						</div>
					</div>
					<div class="col-md-3 mb-3">
						<div class="card">
							<div class="card-body">
								<div class="small">Ticket Médio</div>
								<div id="kpi-ticket" style="font-size:20px;font-weight:700">R$ 0,00</div>
							</div>
						</div>
					</div>
					<div class="col-md-3 mb-3">
						<div class="card">
							<div class="card-body">
								<div class="small">Produtos Mais Vendidos</div>
								<div class="small text-muted">Top 10 no período</div>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-6 mb-3">
						<div class="card">
							<div class="card-header p-2"><strong>Evolução de Vendas</strong></div>
							<div class="card-body"><canvas id="chart-sales-line" height="120"></canvas></div>
						</div>
					</div>
					<div class="col-md-6 mb-3">
						<div class="card">
							<div class="card-header p-2"><strong>Por Meio de Pagamento</strong></div>
							<div class="card-body"><canvas id="chart-payments-donut" height="120"></canvas></div>
						</div>
					</div>
				</div>

				<div class="card">
					<div class="card-header p-2"><strong>Top Produtos</strong></div>
					<div class="card-body p-0">
						<div class="p-2"><canvas id="chart-products-bar" height="100"></canvas></div>
						<div class="table-responsive" style="max-height:45vh; overflow:auto;">
							<table class="table table-hover table-sm mb-0">
								<thead><tr><th>Produto</th><th class="text-right">Qtd</th><th class="text-right">Total</th></tr></thead>
								<tbody id="rep-top-tbody"><tr><td colspan="3">Sem dados</td></tr></tbody>
							</table>
						</div>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
			</div>
		</div>
	</div>
</div>

<script src="<?= base_url('pdv-assets/js/app.js') ?>"></script>

