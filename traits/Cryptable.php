<?php
/**
 * Cryptable.php
 * @filesource
 */

defined('APPPATH') or exit(0); // Acceso directo no autorizado

use Helper\Random;
use Modelo\Crypter as CrypterInterface;
use Driver\Crypter\Principal as CrypterPrincipal;

trait Cryptable # implements CrypterInterface
{
	public static function randomSalt ():string
	{
		return Random :: salt (
			64,   # digitos
			true, # min
			true, # may
			true, # num
			true, # tildes
			true, # symbolos
			false # espacios
		);
	}

	protected static $_salt = '';

	public static function getSalt ():string
	{
		if (empty(static :: $_salt))
			static :: setSavedSalt ();

		return static :: $_salt;
	}

	public static function setSalt (string $salt):void
	{
		static :: $_salt = $salt;
	}

	public static function setSavedSalt (string $filename = null):void
	{
		if (is_null($filename))
		{
			$path = APPPATH; # Por defecto el salt se guarda en el APPPATH
			defined('JCA_PATH') and $path = JCA_PATH; # El JCA_PATH es el directorio donde se compiló la aplicación
			defined('ROOTPATH') and $path = ROOTPATH; # Es preferible guardarlo en el ROOTPATH ya que puede ser usado por otro entorno de la misma aplicación y de ese modo el descifrado de información sería la correcta

			$filename = $path . DS . '.htapp-xonk-salt';
		}

		file_exists($filename) or
		file_put_contents($filename, static :: randomSalt());

		static :: setSalt(file_get_contents($filename));
	}

	/**
	 * Encrypt a message
	 * 
	 * @param string $to_encrypt	message to encrypt
	 * @param string $key			encryption key
	 * @return string
	 */
	function encrypt(string $to_encrypt, string $key = null, CrypterInterface $driver = null): string
	{
		is_null ($driver) and $driver = new CrypterPrincipal(static :: getSalt());
		return (string) $driver -> encrypt($to_encrypt, $key);
	}

	/**
	 * Decrypt a message
	 * 
	 * @param string to_decrypt		message encrypted
	 * @param string $key			encryption key
	 * @return string
	 */
	function decrypt(string $to_decrypt, string $key = null, CrypterInterface $driver = null): string
	{
		is_null ($driver) and $driver = new CrypterPrincipal(static :: getSalt());
		return (string) $driver -> decrypt($to_decrypt, $key);
	}
}