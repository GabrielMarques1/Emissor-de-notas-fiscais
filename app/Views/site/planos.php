<div class="container">
	<h2>Planos</h2>
	<p>Assine para liberar o acesso.</p>

	<form id="checkoutForm">
		<?= csrf_field() ?>
		<div>
			<label>E-mail da Empresa</label>
			<input type="email" name="email_empresa" required />
		</div>
		<div>
			<label>Nome Fantasia</label>
			<input type="text" name="nome_fantasia" required />
		</div>
		<div>
			<label>CNPJ (opcional)</label>
			<input type="text" name="cnpj" />
		</div>
		<div>
			<label>E-mail do Contador (opcional)</label>
			<input type="email" name="contador_email" />
		</div>
		<div>
			<label>Plano</label>
			<select name="price_id" required>
				<option value="<?= esc(getenv('stripe.price') ?: getenv('STRIPE_PRICE') ?: '') ?>">Padrão</option>
			</select>
		</div>
		<button type="submit">Assinar</button>
	</form>

	<script>
	(function(){
		const form = document.getElementById('checkoutForm');
		form.addEventListener('submit', async function(e){
			e.preventDefault();
			const fd = new FormData(form);
			const body = new URLSearchParams(fd);
			try {
				const resp = await fetch('<?= site_url('stripe/checkout') ?>', {
					method: 'POST',
					headers: { 'Accept': 'application/json' },
					body
				});
				const data = await resp.json();
				if (data && data.url) {
					window.location.href = data.url;
					return;
				}
				alert((data && data.error) ? data.error : 'Falha ao iniciar checkout.');
			} catch(err) {
				alert('Erro de rede ao iniciar checkout.');
			}
		});
	})();
	</script>
</div>


