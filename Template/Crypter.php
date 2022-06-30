<?php
/**
 * JCore/Template/Crypter.php
 * @filesource
 */

namespace JCore\Template;
defined('APPPATH') or exit(0); // Acceso directo no autorizado

interface Crypter
{
	public function encrypt (string $to_encrypt, string $key = null):string;

	public function decrypt (string $to_decrypt, string $key = null):string;
}