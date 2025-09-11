<?php
$publishableKey = getenv('STRIPE_PUBLISHABLE_KEY') ?: getenv('stripe.publishable');
?>
<div class="container">
    <h1>Planos</h1>
    <div>
        <button class="btn-checkout" data-price-id="<?= esc(getenv('STRIPE_PRICE') ?: '') ?>">Assinar Plano Padrão</button>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const csrfName = '<?= esc(csrf_token()) ?>';
  const csrfHash = '<?= esc(csrf_hash()) ?>';
  function postCheckout(priceId) {
    return fetch('<?= site_url('stripe/checkout') ?>', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ price_id: priceId, [csrfName]: csrfHash })
    }).then(r => r.json());
  }

  document.querySelectorAll('.btn-checkout').forEach(function(btn) {
    btn.addEventListener('click', async function() {
      const priceId = this.getAttribute('data-price-id');
      if (!priceId) { alert('Price ID não configurado'); return; }
      try {
        const resp = await postCheckout(priceId);
        if (resp && resp.url) {
          window.location.href = resp.url;
        } else {
          alert('Erro ao iniciar checkout');
        }
      } catch (e) {
        alert('Falha ao comunicar com o servidor');
      }
    });
  });
});
</script>


