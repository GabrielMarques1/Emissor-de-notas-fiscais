// PDV Frontend - módulo desacoplado inicial
// Sobrescreve handlers de fechamento de caixa com diagnóstico e fallback

window.fecharCaixa = async function fecharCaixa() {
  try {
    const shifts = await fetch('/api/shifts', { headers: { 'Accept': 'application/json' } }).then(r => r.json());
    if (!Array.isArray(shifts) || !shifts.length) { if (window.Swal) Swal.fire({ type: 'error', title: 'Sem turnos' }); return; }
    const shift = shifts[0];
    if (shift.status !== 'open') { if (window.Swal) Swal.fire({ type: 'info', title: 'Turno já fechado' }); return; }
    const amount = await (window.Swal ? Swal.fire({ title: 'Valor no Caixa', input: 'text', inputValue: '0,00', showCancelButton: true }) : { isConfirmed: true, value: '0,00' });
    if (!amount.isConfirmed) return;
    const amtStr = String(amount.value || '0').trim();
    const payload = { closed_by: 'pdv', closing_amount: amtStr, id_cash_register: (shift.id_cash_register || shift.id_cash || null) };
    console.log('[PDV] fecharCaixa - payload:', payload);
    const urlShifts = '/api/shifts/close/' + shift.id_shift;
    console.log('[PDV] fecharCaixa - tentando URL:', urlShifts);
    let respClose = await fetch(urlShifts, { method: 'POST', headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
    let jClose = null; try { jClose = await respClose.clone().json(); } catch (e) { }
    console.log('[PDV] fecharCaixa - status:', respClose.status, 'response:', jClose);
    if (!respClose.ok) {
      // Fallback direto no controller de caixa
      const urlCaixa = '/api/caixa/fechar';
      const payloadCaixa = { valor_final_contado_dinheiro: amtStr };
      console.warn('[PDV] fecharCaixa - fallback URL:', urlCaixa, 'payload:', payloadCaixa);
      const respCaixa = await fetch(urlCaixa, { method: 'POST', headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' }, body: JSON.stringify(payloadCaixa) });
      let jCx = null; try { jCx = await respCaixa.clone().json(); } catch (e) { }
      console.log('[PDV] fecharCaixa - fallback status:', respCaixa.status, 'response:', jCx);
      if (!respCaixa.ok) {
        let j = jCx; try { if (!j) j = await respCaixa.json(); } catch (e) { }
        throw new Error(j?.messages?.error || j?.error || 'Falha ao fechar caixa');
      }
    }
    if (window.Swal) Swal.fire({ type: 'success', title: 'Caixa fechado' });
    try { window.PDV = window.PDV || {}; window.PDV.hasOpenShift = false; if (window.atualizarIndicadorCaixa) atualizarIndicadorCaixa(); } catch (e) { }
  } catch (e) {
    console.error('[PDV] fecharCaixa - erro:', e);
    if (window.Swal) Swal.fire({ type: 'error', title: 'Erro ao fechar', text: e.message });
  }
};

window.fecharCaixaBtn = async function fecharCaixaBtn() {
  console.log('[PDV] fecharCaixaBtn - INICIADO');
  try {
    console.log('[PDV] fecharCaixaBtn - Verificando PDV.hasOpenShift:', window.PDV?.hasOpenShift);
    
    // Buscar shifts sempre, independente do estado do PDV
    console.log('[PDV] fecharCaixaBtn - Buscando shifts...');
    const shifts = await fetch('/api/shifts', { headers: { 'Accept': 'application/json' } }).then(r => r.json());
    console.log('[PDV] fecharCaixaBtn - Shifts retornados:', shifts);
    
    const sel = Array.isArray(shifts) ? shifts.find(s => String(s.status).toLowerCase() === 'open') : null;
    console.log('[PDV] fecharCaixaBtn - Shift selecionado:', sel);
    
    if (!sel) { 
      console.log('[PDV] fecharCaixaBtn - Nenhum shift aberto encontrado');
      window.PDV = window.PDV || {}; 
      window.PDV.hasOpenShift = false; 
      if (window.atualizarIndicadorCaixa) atualizarIndicadorCaixa(); 
      if (window.Swal) Swal.fire({ type: 'info', title: 'Nenhum caixa aberto' });
      return; 
    }
    
    console.log('[PDV] fecharCaixaBtn - Mostrando modal de valor...');
    const amount = await (window.Swal ? Swal.fire({ 
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
    }) : { isConfirmed: true, value: '0,00' });
    
    console.log('[PDV] fecharCaixaBtn - Resposta do modal:', amount);
    // Verificação mais robusta para diferentes versões do SweetAlert2
    const isConfirmed = amount.isConfirmed !== false && amount.value !== undefined && amount.dismiss === undefined;
    console.log('[PDV] fecharCaixaBtn - Modal confirmado:', isConfirmed);
    if (!isConfirmed) {
      console.log('[PDV] fecharCaixaBtn - Modal cancelado pelo usuário');
      return;
    }
    
    const amtStr = String(amount.value || '0').trim();
    console.log('[PDV] fecharCaixaBtn - Valor processado:', amtStr);
    
    const payload = { closed_by: 'pdv', closing_amount: amtStr, id_cash_register: (sel.id_cash_register || sel.id_cash || null) };
    console.log('[PDV] fecharCaixaBtn - payload:', payload);
    
    const urlShifts = '/api/shifts/close/' + (sel.id_shift || sel.id);
    console.log('[PDV] fecharCaixaBtn - tentando URL:', urlShifts);
    
    let rc = await fetch(urlShifts, { method: 'POST', headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
    let jRC = null; try { jRC = await rc.clone().json(); } catch (e) { }
    console.log('[PDV] fecharCaixaBtn - status:', rc.status, 'response:', jRC);
    
    if (!rc.ok) {
      console.warn('[PDV] fecharCaixaBtn - Primeira tentativa falhou, tentando fallback...');
      const urlCaixa = '/api/caixa/fechar';
      const payloadCaixa = { valor_final_contado_dinheiro: amtStr };
      console.warn('[PDV] fecharCaixaBtn - fallback URL:', urlCaixa, 'payload:', payloadCaixa);
      const respCaixa = await fetch(urlCaixa, { method: 'POST', headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' }, body: JSON.stringify(payloadCaixa) });
      let jCx = null; try { jCx = await respCaixa.clone().json(); } catch (e) { }
      console.log('[PDV] fecharCaixaBtn - fallback status:', respCaixa.status, 'response:', jCx);
      if (!respCaixa.ok) {
        let j = jCx; try { if (!j) j = await respCaixa.json(); } catch (e) { }
        throw new Error(j?.messages?.error || j?.error || 'Falha ao fechar caixa');
      }
    }
    
    console.log('[PDV] fecharCaixaBtn - SUCESSO! Atualizando UI...');
    window.PDV = window.PDV || {}; 
    window.PDV.hasOpenShift = false; 
    if (window.atualizarIndicadorCaixa) atualizarIndicadorCaixa();
    if (window.Swal) Swal.fire({ type: 'success', title: 'Caixa fechado com sucesso!' });
    
  } catch (e) {
    console.error('[PDV] fecharCaixaBtn - ERRO:', e);
    if (window.Swal) Swal.fire({ type: 'error', title: 'Erro ao fechar caixa', text: e.message });
  }
};

console.log('[PDV] app.js carregado');


