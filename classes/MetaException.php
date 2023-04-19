<?php

class MetaException extends Exception
{
	public static function cloneException (Exception $ex)
	{
		return (new static ($ex -> getMessage(), [], $ex -> getCode(), $ex))
		-> setFile($ex -> getFile())
		-> setLine($ex -> getLine());
	}

	public static function quickInstance (string $message = null, array $metadata = [])
	{
		return new static ($message, $metadata);
	}

	public function __construct($message = null, array $metadata = [], int $code = 0, Exception $previous = null)
	{
		if (is_array($message))
		{
			$metadata = array_merge($message, $metadata);
			$message = null;
		}

		$message = (string) $message;
		empty($message) and $message = 'Se produjo una excepción Desconocida';

		parent::__construct($message, $code, $previous);

		$this -> _metadata = $metadata;
	}

	/** Hash of the exception */
	public function getHash()
	{
		$text = $this -> getMessagePattern() ?? $this -> getMessage() ?? 'VACIO';
		return md5(get_called_class() . '::' . $text);
	}

	/** Pattern of the message */
	protected $_message_pattern = null;

	public function getMessagePattern()
	{
		return $this -> _message_pattern;
	}

	public function setMessagePattern ($pattern):MetaException
	{
		$this -> _message_pattern = $pattern;
		return $this;
	}

	/** Metadata */
	protected $_metadata = [];

	public function getMetaData():array
	{
		return $this -> _metadata;
	}

	public function setMetaData ($metadata):MetaException
	{
		$this -> _metadata = $metadata;
		return $this;
	}

	public function setFile ($filename):MetaException
	{
		$this -> file = $filename;
		return $this;
	}

	public function setLine ($line):MetaException
	{
		$this -> line = $line;
		return $this;
	}

	public function logger ():void
	{
		ErrorControl :: logger ($this);
	}
}