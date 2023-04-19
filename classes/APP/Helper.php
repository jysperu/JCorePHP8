<?php
/*!
 * APPPATH/classes/APP/Helper.php
 * @filesource
 */
namespace APP;

defined('APPPATH') or exit(0); // Acceso directo no autorizado

/**
 * APP\Helper
 * > Este trait solo puede ser utilizado por la clase APP
 * Aloja funciones que ayudan a la clase APP en distintos escenarios
 */
use MetaException;

trait Helper
{
	/**
	 * clearBuffer()
	 * Limpia toda el buffer de salida
	 * @param	bool	$report_content_if_exists	En caso de requerir que la información que contiene se reporte como error
	 * @return	void
	 */
	public static function clearBuffer (bool $report_content_if_exists = true):void
	{
		$buffer = '';
		while (ob_get_level())
		{
			$buffer .= ob_get_contents();
			ob_end_clean();
		}

		if ($report_content_if_exists and ! empty($buffer))
			MetaException :: quickInstance ('Contenido de buffer encontrado previo a limpiarlo', [
				'buffer' => $buffer,
			]) -> logger ();
	}

	/**
	 * getInstantBuffer()
	 * Obtiene el buffer de salida hasta ese momento
	 * @param	bool|null	$end_ob_level @optional, Se limpiará el buffer hasta el nivel indicado
	 * @return	string
	 */
	public static function getInstantBuffer ( ? int $end_ob_level = null) ## 0 = clear ## null = no limpia nada
	{
		$content = ob_get_contents();

		if ( ! is_null($end_ob_level))
		{
			while(ob_get_level() > $end_ob_level)
			{
				$content = ob_get_contents(); ## Por siaca
				ob_end_clean();
			}
		}

		return $content;
	}
}