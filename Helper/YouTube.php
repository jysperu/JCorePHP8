<?php
/**
 * JCore/Helper/YouTube.php
 * @filesource
 */

namespace JCore\Helper;
defined('JCA_PATH') or exit(0); // Se requiere la ruta del JCore Compiled Aplication

class YouTube
{
	/**
	 * getCodeFromLink
	 * Obtener el código de YouTube
	 */
	public static function getCodeFromLink ($link)
	{
		static $_regex = '%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i';

		if ( ! preg_match($_regex, $link, $v))
			return preg_match('youtu', $link) ? NULL : $link;

		return $v[1];
	}
}