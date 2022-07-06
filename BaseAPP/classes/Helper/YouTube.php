<?php
/**
 * Helper/YouTube.php
 * @filesource
 */

namespace Helper;
defined('APPPATH') or exit(0); // Acceso directo no autorizado

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