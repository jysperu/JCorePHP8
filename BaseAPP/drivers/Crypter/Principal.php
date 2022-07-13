<?php
namespace Driver\Crypter;

defined('APPPATH') or exit(0); // Acceso directo no autorizado

use Contract\Crypter as CrypterInterface;
use Controller\Crypt as CrypterTrait;

use function openssl_encrypt;
use function openssl_decrypt;
use function hash_hmac;
use function hash_equals;
use function hash_pbkdf2;
use function random_bytes;
use function mb_substr;

class Principal implements CrypterInterface
{
	use CrypterTrait;

	protected $_def_key;

	public function __construct (string $def_key = null)
	{
		is_null($def_key) and $def_key = static :: getSalt();
		$this -> _def_key = $def_key;
	}

	public function encrypt (string $to_encrypt, string $key = null):string
	{
		$iv  = random_bytes (16);
		$key = $this -> getKey($key ?? $this -> _def_key);

		$encrypted = $this -> sign(openssl_encrypt($to_encrypt, 'aes-256-ctr', $key, OPENSSL_RAW_DATA, $iv), $key);
		return bin2hex($iv) . bin2hex($encrypted);
	}

	public function decrypt (string $to_decrypt, string $key = null):string
	{
		$iv   = hex2bin(substr($to_decrypt, 0, 32));
		$data = hex2bin(substr($to_decrypt, 32));

		$key = $this -> getKey($key ?? $this -> _def_key);

		if ( ! $this -> verify($data, $key))
			return '';

		return openssl_decrypt(mb_substr($data, 64, null, '8bit'), 'aes-256-ctr', $key, OPENSSL_RAW_DATA, $iv);
	}

	private function sign ($message, $key)
	{
		return hash_hmac('sha256', $message, $key) . $message;
	}

	private function verify($bundle, $key)
	{
		return hash_equals(
		  hash_hmac('sha256', mb_substr($bundle, 64, null, '8bit'), $key),
		  mb_substr($bundle, 0, 64, '8bit')
		);
	}

	private function getKey($key, $keysize = 16)
	{
		return hash_pbkdf2('sha256', $key, 'some_token', 100000, $keysize, true);
	}
}