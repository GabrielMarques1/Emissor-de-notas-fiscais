<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Main content -->
    <div class="content">
        <div class="container-fluid">
            <div class="row" style="margin-bottom: 15px">
                <div class="col-sm-6">
                    <h6 class="m-0 text-dark"><i class="<?= $titulo['icone'] ?>"></i> <?= $titulo['modulo'] ?></h6>
                </div>
                <div class="col-sm-6 no-print">
                    <ol class="breadcrumb float-sm-right">
                        <?php foreach ($caminhos as $caminho) : ?>
                            <?php if (!$caminho['active']) : ?>
                                <li class="breadcrumb-item"><a href="<?= $caminho['rota'] ?>"><?= $caminho['titulo'] ?></a></li>
                            <?php else : ?>
                                <li class="breadcrumb-item active"><?= $caminho['titulo'] ?></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ol>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label for="codigo_de_barras">Ler código de barras</label>
                                        <input type="text" id="codigo_de_barras" class="form-control" placeholder="Aponte o leitor aqui e pressione Enter" onkeypress="pdvOnEnter(event)">
                                    </div>
                                </div>
                                <div class="col-lg-3">
                                    <div class="form-group">
                                        <label for="quantidade">Qtd.</label>
                                        <input type="number" id="quantidade" class="form-control" min="1" step="1" value="1">
                                    </div>
                                </div>
                                <div class="col-lg-3">
                                    <div class="form-group">
                                        <label for="desconto">Desconto (R$)</label>
                                        <input type="text" id="desconto" class="form-control" value="0,00">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-12">
                                    <button type="button" class="btn btn-success" onclick="adicionarItemPDV()"><i class="fas fa-plus-circle"></i> Adicionar</button>
                                    <a href="/pdv/limpar" class="btn btn-default"><i class="fas fa-broom"></i> Limpar</a>
                                    <a href="/pdv/finalizar" class="btn btn-primary"><i class="fas fa-receipt"></i> Finalizar</a>
                                    <button type="button" class="btn btn-warning" onclick="fecharCaixa()"><i class="fas fa-cash-register"></i> Fechar Caixa</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover text-nowrap table-bordered table-striped">
                        <thead>
                            <tr>
                                <th style="width: 35px">#</th>
                                <th>Produto</th>
                                <th>Cód. Barras</th>
                                <th>Unidade</th>
                                <th class="text-right">Qtd</th>
                                <th class="text-right">Vlr Unit</th>
                                <th class="text-right">Desc</th>
                                <th class="text-right">Subtotal</th>
                                <th class="no-print" style="width: 80px">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($itens)) : ?>
                                <?php foreach ($itens as $item) : ?>
                                    <?php $subtotal = ($item['valor_unitario'] * $item['quantidade']) - ($item['desconto'] ?? 0); ?>
                                    <tr>
                                        <td><?= $item['id_produto_provisorio'] ?></td>
                                        <td><?= $item['nome'] ?></td>
                                        <td><?= $item['codigo_de_barras'] ?></td>
                                        <td><?= $item['unidade'] ?></td>
                                        <td class="text-right"><?= number_format($item['quantidade'], 0, ',', '.') ?></td>
                                        <td class="text-right"><?= number_format($item['valor_unitario'], 2, ',', '.') ?></td>
                                        <td class="text-right"><?= number_format($item['desconto'] ?? 0, 2, ',', '.') ?></td>
                                        <td class="text-right"><?= number_format($subtotal, 2, ',', '.') ?></td>
                                        <td>
                                            <button type="button" class="btn btn-danger style-action" onclick="removerItemPDV(<?= $item['id_produto_provisorio'] ?>)"><i class="fas fa-trash"></i></button>
                                        </td>
                                    </tr>
                                <?php endforeach ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="9">Nenhum item no PDV!</td>
                                </tr>
                            <?php endif ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="7" class="text-right">Total</th>
                                <th class="text-right">R$ <?= number_format($total ?? 0, 2, ',', '.') ?></th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', async () => {
    try {
        const [shifts, cash, activeSale] = await Promise.all([
            fetch('/api/shifts', { headers: { 'Accept': 'application/json' }}).then(r=>r.json()),
            fetch('/api/cash-registers', { headers: { 'Accept': 'application/json' }}).then(r=>r.json()),
            fetch('/api/pos/active', { headers: { 'Accept': 'application/json' }}).then(r=>r.json()).catch(()=>null),
        ]);
        if (!Array.isArray(shifts) || !shifts.length || (shifts[0].status !== 'open')) {
            const ok = await Swal.fire({ icon: 'info', title: 'Abrir Caixa', text: 'Abra o caixa para iniciar as vendas.', showCancelButton:true, confirmButtonText:'Abrir agora' });
            if (ok.isConfirmed) {
                await fetch('/api/shifts/open', { method: 'POST', headers: { 'Accept':'application/json','Content-Type':'application/json' }, body: JSON.stringify({ opened_by: 'pdv', opening_amount: 0 }) });
            }
        }
        if (activeSale && (activeSale.id_pos_sale || activeSale.id)) {
            window.PDV = window.PDV || {}; window.PDV.saleId = activeSale.id_pos_sale || activeSale.id;
        }
        await atualizarCarrinho();
    } catch(e) { console.error(e); }
});

async function atualizarCarrinho() {
    try {
        const resp = await fetch('/api/cart', { headers: { 'Accept': 'application/json' }});
        const itens = await resp.json();
        const tbody = document.querySelector('table.table tbody');
        const tfootTotal = document.querySelector('table.table tfoot th.text-right');
        if (!tbody) return;
        tbody.innerHTML = '';
        let total = 0;
        if (Array.isArray(itens) && itens.length) {
            itens.forEach(item => {
                const subtotal = (parseFloat(item.valor_unitario||0) * parseFloat(item.quantidade||0)) - parseFloat(item.desconto||0);
                total += subtotal;
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${item.id_produto_provisorio}</td>
                    <td>${item.nome}</td>
                    <td>${item.codigo_de_barras||''}</td>
                    <td>${item.unidade||''}</td>
                    <td class="text-right">${(item.quantidade||0)}</td>
                    <td class="text-right">${Number(item.valor_unitario||0).toLocaleString('pt-BR',{minimumFractionDigits:2})}</td>
                    <td class="text-right">${Number(item.desconto||0).toLocaleString('pt-BR',{minimumFractionDigits:2})}</td>
                    <td class="text-right">${subtotal.toLocaleString('pt-BR',{minimumFractionDigits:2})}</td>
                    <td><button type="button" class="btn btn-danger style-action" onclick="removerItemPDV(${item.id_produto_provisorio})"><i class="fas fa-trash"></i></button></td>
                `;
                tbody.appendChild(tr);
            });
        } else {
            const tr = document.createElement('tr');
            tr.innerHTML = `<td colspan="9">Nenhum item no PDV!</td>`;
            tbody.appendChild(tr);
        }
        if (tfootTotal) { tfootTotal.textContent = 'R$ ' + total.toLocaleString('pt-BR',{minimumFractionDigits:2}); }
    } catch(e) { console.error(e); }
}

function pdvOnEnter(e) {
    if (e.key === 'Enter') adicionarItemPDV();
}

async function adicionarItemPDV() {
    const codigo = document.getElementById('codigo_de_barras').value.trim();
    const qtd = parseInt(document.getElementById('quantidade').value || '1');
    const desconto = document.getElementById('desconto').value || '0,00';
    if (!codigo) { Swal.fire({ icon: 'warning', title: 'Informe o código de barras' }); return; }
    try {
        const p = await fetch('/api/products/barcode/' + encodeURIComponent(codigo), { headers: { 'Accept': 'application/json' }});
        if (!p.ok) throw new Error('Produto não encontrado');
        const prod = await p.json();
        const payload = {
            nome: prod.nome,
            codigo_de_barras: prod.codigo_de_barras || 'SEM GTIN',
            unidade: prod.descricao || 'UN',
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
    } catch(e) { Swal.fire({ icon: 'error', title: 'Falha ao adicionar', text: e.message }); }
}

function removerItemPDV(id) {
    if (!id) return;
    fetch('/api/cart/' + id, { method: 'DELETE', headers: { 'Accept': 'application/json' }}).then(()=>atualizarCarrinho());
}

async function finalizarPDV() {
    try {
        const saleId = (window.PDV && window.PDV.saleId) ? window.PDV.saleId : null;
        if (!saleId) { Swal.fire({icon:'error', title:'Sem venda ativa'}); return; }
        const resp = await fetch('/api/pos/' + saleId + '/finalize', { method: 'POST', headers: { 'Accept': 'application/json' }});
        const data = await resp.json();
        if (!resp.ok) throw new Error(data.messages?.error || 'Falha ao finalizar');
        Swal.fire({ icon: 'success', title: 'Venda finalizada!' });
        await atualizarCarrinho();
        window.open('/api/pos/' + saleId + '/receipt/html', '_blank');
    } catch(e) { Swal.fire({ icon: 'error', title: 'Erro ao finalizar', text: e.message }); }
}

async function fecharCaixa() {
    try {
        // obter último turno aberto
        const shifts = await fetch('/api/shifts', { headers: {'Accept':'application/json'} }).then(r=>r.json());
        if (!Array.isArray(shifts) || !shifts.length) { Swal.fire({icon:'error',title:'Sem turnos'}); return; }
        const shift = shifts[0];
        if (shift.status !== 'open') { Swal.fire({icon:'info',title:'Turno já fechado'}); return; }
        const amount = await Swal.fire({ title: 'Valor no Caixa', input: 'text', inputValue: '0,00', showCancelButton:true });
        if (!amount.isConfirmed) return;
        // fechar
        await fetch('/api/shifts/close/' + shift.id_shift, { method: 'POST', headers: {'Accept':'application/json','Content-Type':'application/json'}, body: JSON.stringify({ closed_by: 'pdv', closing_amount: amount.value }) });
        const rep = await fetch('/api/shifts/' + shift.id_shift + '/report', { headers: {'Accept':'application/json'} }).then(r=>r.json());
        let html = '<b>Total:</b> R$ ' + Number(rep.total||0).toLocaleString('pt-BR',{minimumFractionDigits:2}) + '<br/><br/>';
        if (Array.isArray(rep.itens)) {
            html += '<table class="table table-sm"><thead><tr><th>Forma</th><th class="text-right">Qtd</th><th class="text-right">Valor</th></tr></thead><tbody>';
            rep.itens.forEach(i=>{ html += `<tr><td>${i.payment_type}</td><td class="text-right">${i.qtd}</td><td class="text-right">${Number(i.valor||0).toLocaleString('pt-BR',{minimumFractionDigits:2})}</td></tr>`; });
            html += '</tbody></table>';
        }
        Swal.fire({ icon:'success', title:'Caixa fechado', html });
    } catch(e) {
        Swal.fire({ icon:'error', title:'Erro ao fechar', text: e.message });
    }
}
</script>


