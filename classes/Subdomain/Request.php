<?php defined('SYSPATH') or die('No direct script access.');

class Subdomain_Request extends Kohana_Request {

	/**
	 * @var  string  request Subdomain
	 */
	public $subdomain;

	public function __construct($uri, $client_params = array(), $allow_external = TRUE, $injected_routes = array())
	{
		$this->subdomain = Request::catch_subdomain();

		parent::__construct($uri, $client_params, $allow_external, $injected_routes);
	}

	/**
	 * @param string $domain Pass $_SERVER['SERVER_NAME'] here
	 * @param bool $debug
	 *
	 * @debug bool $debug
	 * @return string
	 */
	public static function get_domain($domain, $debug = false)
	{
		$original = $domain = strtolower($domain);

		if (filter_var($domain, FILTER_VALIDATE_IP))
		{
			return $domain;
		}

		$debug ? print('<strong style="color:green">&raquo;</strong> Parsing: ' . $original) : false;

		$arr = array_slice(array_filter(explode('.', $domain, 4), function ($value)
		{
			return $value !== 'www';
		}), 0); //rebuild array indexes

		if (count($arr) > 2)
		{
			$count = count($arr);
			$_sub = explode('.', $count === 4 ? $arr[3] : $arr[2]);

			$debug ? print(" (parts count: {$count})") : false;

			if (count($_sub) === 2) // two level TLD
			{
				$removed = array_shift($arr);
				if ($count === 4) // got a subdomain acting as a domain
				{
					$removed = array_shift($arr);
				}
				$debug ? print("<br>\n" . '[*] Two level TLD: <strong>' . join('.', $_sub) . '</strong> ') : false;
			}
			elseif (count($_sub) === 1) // one level TLD
			{
				$removed = array_shift($arr); //remove the subdomain

				if (strlen($_sub[0]) === 2 && $count === 3) // TLD domain must be 2 letters
				{
					array_unshift($arr, $removed);
				}
				else
				{
					// non country TLD according to IANA
					$tlds = array(
						'aero',
						'arpa',
						'asia',
						'biz',
						'cat',
						'com',
						'coop',
						'edu',
						'gov',
						'info',
						'jobs',
						'mil',
						'mobi',
						'museum',
						'name',
						'net',
						'org',
						'post',
						'pro',
						'tel',
						'travel',
						'xxx',
					);

					if (count($arr) > 2 && in_array($_sub[0], $tlds) !== false) //special TLD don't have a country
					{
						array_shift($arr);
					}
				}
				$debug ? print("<br>\n" . '[*] One level TLD: <strong>' . join('.', $_sub) . '</strong> ') : false;
			}
			else // more than 3 levels, something is wrong
			{
				for ($i = count($_sub); $i > 1; $i--)
				{
					$removed = array_shift($arr);
				}
				$debug ? print("<br>\n" . '[*] Three level TLD: <strong>' . join('.', $_sub) . '</strong> ') : false;
			}
		}
		elseif (count($arr) === 2)
		{
			$arr0 = array_shift($arr);

			if (strpos(join('.', $arr), '.') === false
			&& in_array($arr[0], array('localhost', 'test', 'invalid')) === false
			) // not a reserved domain
			{
				$debug ? print("<br>\n" . 'Seems invalid domain: <strong>' . join('.', $arr) . '</strong> re-adding: <strong>' . $arr0 . '</strong> ') : false;
				// seems invalid domain, restore it
				array_unshift($arr, $arr0);
			}
		}

		$debug ? print("<br>\n" . '<strong style="color:gray">&laquo;</strong> Done parsing: <span style="color:red">' . $original . '</span> as <span style="color:blue">' . join('.', $arr) . "</span><br>\n") : false;

		return join('.', $arr);
	}

	public static function catch_subdomain($base_url = NULL, $host = NULL)
	{
		if ($base_url === NULL)
		{
			$base_url = self::get_domain($_SERVER['SERVER_NAME']);
		}

		if ($host === NULL)
		{
			if (php_sapi_name() == 'cli' AND empty($_SERVER['REMOTE_ADDR']))
			{
				return FALSE;
			}

			$host = $_SERVER['HTTP_HOST'];
		}

		if (empty($base_url) OR empty($host) OR in_array($host, Route::$localhosts) OR Valid::ip($host))
		{
			return FALSE;
		}

		$sub_pos = (int)strpos($host, $base_url) - 1;

		if ($sub_pos > 0)
		{
			$subdomain = substr($host, 0, $sub_pos);

			if (!empty($subdomain))
			{
				return $subdomain;
			}
		}

		return FALSE;
	}
}
