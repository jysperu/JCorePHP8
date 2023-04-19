<?php
/**
 * APPPATH/classes/APP/Session.php
 * @filesource
 */
namespace APP;

defined('APPPATH') or exit(0); // Acceso directo no autorizado

/**
 * APP\Session
 */
trait Session
{
	protected static function startSessions ()
	{
		$_session_name = static :: getConfig('session_name');
		$_session_path = static :: getConfig('session_path');

		if ( ! is_empty($_session_name))
			session_name($_session_name);

		if ( ! is_empty($_session_path))
		{
			if ( ! file_exists($_session_path))
				mkdir($_session_path, 0777, true);

			@ini_set('session.save_handler', 'files');
			@ini_set('session.save_path', $_session_path);
		}

		session_start();
	}
}