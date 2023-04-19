<?php
namespace Response;

class Inicio
{
	public function index ()
	{
		use_structure ('General', [
			'title'         => APPNAME . ' &raquo; By JCore',
			'content_title' => APPNAME,
		]);

		$filemtime = filemtime(APPPATH);
		?>

<div class="container py-5">
	<div class="card">
		<div class="card-body">
			<h1><?= APPNAME; ?></h1>
			<h2>Another JCore Aplication</h2>
			<p>v<?= $filemtime . ' — ' . moment($filemtime); ?></p>
			<p>Exceution: <br>- Time: <?= (string) get_execution_time(); ?><br>- Memory: <?= (string) get_execution_memory_consumption(); ?></p>
		</div>
	</div>
</div>

		<?php
	}
}