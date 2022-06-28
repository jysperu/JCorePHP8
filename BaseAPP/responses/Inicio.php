<?php
namespace Response;

class Inicio
{
	public function index ()
	{
		use_structure ('General', [
			'title'         => 'Oficina &raquo; By JCore',
			'content_title' => 'Oficina',
		]);

		?>

<div class="container py-5">
	<div class="card">
		<div class="card-body">
			<h1>JCore Compiled Aplication</h1>
			<p>v<?= filemtime(JCA_PATH); ?></p>
		</div>
	</div>
</div>

		<?php
	}
}