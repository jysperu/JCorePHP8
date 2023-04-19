<?php
namespace Structure;

use APP;

/** General */
class General extends DashBoard
{
	public function loadAssets():void
	{
		APP :: addCSS ('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css');
		APP :: addJS  ('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js');
	}
}