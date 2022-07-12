<?php
namespace JCore\JPart;

use Helper;

class ComposerJSON 
{
	protected $_config = [];

	public function __construct (array $json = [])
	{
		$time = time();

		$this -> mergeConfig([
			'name'        => 'jcorephp8/' . str_replace('.', '', $_SERVER['HTTP_HOST']),
			'description' => 'Compilación autónoma por JCore' . PHP_EOL . 'Fecha y Hora: ' . date('Y-m-d h:i:s A', $time),
			'version'     => '1.0.' . $time . '.' . filemtime(__FILE__),
//			'type'        => 'library',
//			'homepage'    => 'https://' . $_SERVER['HTTP_HOST'],
			'time'        => date('Y-m-d H:i:s', $time),
			'authors'     => [
				[
					'name'     => 'JCore',
					'email'    => 'jcore@jys.pe',
					'homepage' => 'https://github.com/jysperu/JCorePHP8',
					'role'     => 'JCore\'s Developer',
				],
			],
		], true);

		$this -> mergeConfig((array) $json, true);
	}

	public function getConfig ():array
	{
		return $this -> _config;
	}

	public function mergeConfig (array $json, bool $all = false):ComposerJSON
	{
		if ($all)
		{
			foreach ([
				'name',              # string
				'description',       # string
				'version',           # version
				'type',              # string
				'homepage',          # url
				'time',              # datetime YYYY-MM-DD HH:MM:SS
				'support',           # object   
				'minimum-stability', # string   stable, dev, alpha, beta, RC
				'prefer-stable',     # bool     true, false
				'config',            # object
			] as $key)
			{
				if ( ! isset($json[$key]) or Helper::isEmpty($json[$key]))
					continue;

				$val = $this -> _reparo_val_for($key, $json[$key]);

				if (Helper::isEmpty($val))
					continue;

				$this -> _config[$key] = $val;
			}
		}

		if (isset($json['keywords']) and ! Helper::isEmpty($json['keywords']))
		{
			$this -> addKeyword ($json['keywords']);
		}

		if (isset($json['license']) and ! Helper::isEmpty($json['license']))
		{
			$this -> addLicense ($json['license']);
		}

		if (isset($json['authors']) and ! Helper::isEmpty($json['authors']))
		{
			$this -> addAuthor ($json['authors']);
		}

		if (isset($json['funding']) and ! Helper::isEmpty($json['funding']))
		{
			$this -> addFunding ($json['funding']);
		}

		if (isset($json['require']) and ! Helper::isEmpty($json['require']))
		{
			$this -> addRequire ($json['require']);
		}

		if (isset($json['repositories']) and ! Helper::isEmpty($json['repositories']))
		{
			$this -> addRepository ($json['repositories']);
		}

		return $this;
	}

	protected function _reparo_val_for (string $key, mixed $val)
	{
		if (in_array($key, ['name', 'description', 'version', 'type', 'homepage', 'minimum-stability']))
		{
			return (string) $val;
		}

		if ($key === 'time')
		{
			return date('Y-m-d H:i:s', strtotime($val));
		}

		if ($key === 'prefer-stable')
		{
			return (bool) $val;
		}

		if ($key === 'config')
		{
			return (array) $val;
		}

		if ($key === 'support')
		{
			$val = (array) $val;
			$object = [];
			foreach(['email', 'issues', 'forum', 'wiki', 'irc', 'source', 'docs', 'rss', 'chat'] as $object_key)
			{
				if (isset($val[$object_key]) and ! Helper::isEmpty($val[$object_key]))
				{
					$object[$object_key] = $val[$object_key];
				}
			}
			return $object;
		}

		if ($key === 'authors')
		{
			$val = (array) $val;
			$object = [];
			foreach(['name', 'email', 'homepage', 'role'] as $object_key)
			{
				if (isset($val[$object_key]) and ! Helper::isEmpty($val[$object_key]))
				{
					$object[$object_key] = $val[$object_key];
				}
			}

			if ( ! isset($object['name']) and ! isset($object['email']))
				return [];

			return $object;
		}

		if ($key === 'funding')
		{
			$val = (array) $val;
			$object = ['type' => 'other'];
			foreach(['type', 'url'] as $object_key)
			{
				if (isset($val[$object_key]) and ! Helper::isEmpty($val[$object_key]))
				{
					$object[$object_key] = $val[$object_key];
				}
			}

			if ( ! isset($object['url']))
				return [];

			return $object;
		}

		if ($key === 'repositories')
		{
			$val = (array) $val;

			if ( ! isset($val['url']) or Helper::isEmpty($val['url']))
				return [];

			return $val;
		}

		return $val;
	}

	public function addKeywords (...$keywords)
	{
		foreach($keywords as $keyword)
		{
			$this -> addKeyword ($keyword);
		}
		return $this;
	}

	public function addKeyword ($keyword)
	{
		if(is_array($keyword))
		{
			foreach($keyword as $str)
			{
				$this -> addKeyword ($str);
			}
			return $this;
		}

		$keyword = (string) $keyword;

		if (isset($this -> _config['keywords']) and in_array($keyword, $this -> _config['keywords']))
			return $this; ## Prevent duplicate

		isset($this -> _config['keywords']) or
		$this -> _config['keywords'] = [];

		$this -> _config['keywords'][] = $keyword;
		return $this;
	}

	public function addLicenses (...$licenses)
	{
		foreach($licenses as $license)
		{
			$this -> addLicense ($license);
		}
		return $this;
	}

	public function addLicense ($license)
	{
		if(is_array($license))
		{
			foreach($license as $str)
			{
				$this -> addLicense ($str);
			}
			return $this;
		}

		$license = (string) $license;

		if (isset($this -> _config['license']) and in_array($license, $this -> _config['license']))
			return $this; ## Prevent duplicate

		isset($this -> _config['license']) or
		$this -> _config['license'] = [];

		$this -> _config['license'][] = $license;
		return $this;
	}

	public function addAuthors (...$authors)
	{
		foreach($authors as $author)
		{
			$this -> addAuthor ($author);
		}
		return $this;
	}

	public function addAuthor ($author)
	{
		$author = (array) $author;
		
		if(Helper :: isList($author))
		{
			foreach($author as $str)
			{
				$this -> addAuthor ($str);
			}
			return $this;
		}

		$author = $this -> _reparo_val_for('authors', $author);

		if (Helper::isEmpty($author))
			return $this; ## No es un author valido

		
		if (isset($this -> _config['authors']))
		{
			$emails = array_map(function($o){
				return isset($o['email']) ? $o['email'] : null;
			}, $this -> _config['authors']);
			
			if (isset($author['email']) and in_array($author['email'], $emails))
				return $this; ## Prevent duplicate

			$names = array_map(function($o){
				return isset($o['name']) ? $o['name'] : null;
			}, $this -> _config['authors']);
			
			if (isset($author['name']) and in_array($author['name'], $names))
				return $this; ## Prevent duplicate
		}

		isset($this -> _config['authors']) or
		$this -> _config['authors'] = [];

		$this -> _config['authors'][] = $author;
		return $this;
	}

	public function addFundings (...$fundings)
	{
		foreach($fundings as $funding)
		{
			$this -> addFunding ($funding);
		}
		return $this;
	}

	public function addFunding ($funding)
	{
		$funding = (array) $funding;
		
		if(Helper :: isList($funding))
		{
			foreach($funding as $str)
			{
				$this -> addFunding ($str);
			}
			return $this;
		}

		$funding = $this -> _reparo_val_for('funding', $funding);

		if (Helper::isEmpty($funding))
			return $this; ## No es un author valido

		
		if (isset($this -> _config['funding']))
		{
			$urls = array_map(function($o){
				return isset($o['url']) ? $o['url'] : null;
			}, $this -> _config['funding']);
			
			if (isset($funding['url']) and in_array($funding['url'], $urls))
				return $this; ## Prevent duplicate
		}

		isset($this -> _config['funding']) or
		$this -> _config['funding'] = [];

		$this -> _config['funding'][] = $funding;
		return $this;
	}

	public function addRequires (...$requires)
	{
		foreach($requires as $require)
		{
			$this -> addRequire ($require);
		}
		return $this;
	}

	public function addRequire ($require)
	{
		$require = (array) $require;
		
		if(Helper :: isList($require))
		{
			foreach($require as $str)
			{
				$this -> addRequire ($str);
			}
			return $this;
		}

		isset($this -> _config['require']) or
		$this -> _config['require'] = [];

		foreach($require as $key => $val)
		{
			$this -> _config['require'][$key] = $val;
		}

		return $this;
	}

	public function addRepositories (...$repositories)
	{
		foreach($repositories as $repository)
		{
			$this -> addRepository ($repository);
		}
		return $this;
	}

	public function addRepository ($repository)
	{
		$repository = (array) $repository;
		
		if(Helper :: isList($repository))
		{
			foreach($repository as $str)
			{
				$this -> addRepository ($str);
			}
			return $this;
		}

		$repository = $this -> _reparo_val_for('repositories', $repository);

		if (Helper::isEmpty($repository))
			return $this; ## No es un author valido

		
		if (isset($this -> _config['repositories']))
		{
			$urls = array_map(function($o){
				return isset($o['url']) ? $o['url'] : null;
			}, $this -> _config['repositories']);
			
			if (isset($repository['url']) and in_array($repository['url'], $urls))
				return $this; ## Prevent duplicate
		}

		isset($this -> _config['repositories']) or
		$this -> _config['repositories'] = [];

		$this -> _config['repositories'][] = $repository;
		return $this;
	}
}