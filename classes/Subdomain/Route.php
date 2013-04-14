<?php defined('SYSPATH') or die('No direct script access.');

class Subdomain_Route extends Kohana_Route {

	const SUBDOMAIN_WILDCARD = '*';

	/**
	 * @var  string  route SUBDOMAIN
	 */
	protected $_subdomain = array();
	protected $has_subdomain = FALSE;
	protected $restrict = FALSE;

	public function __construct($uri = NULL, $regex = NULL)
	{
		parent::__construct($uri, $regex);

		// Set default subdomains in this route rule
		$this->has_subdomain = FALSE;
		$this->restrict = FALSE;
		$this->_subdomain = array();
	}


	/**
	 * Set one or more subdomains to execute this route
	 *
	 *     Route::set('default', '(<controller>(/<action>(/<id>)))')
	 *         ->subdomains(array(Route::SUBDOMAIN_EMPTY, 'www1', 'foo', 'bar'))
	 *         ->defaults(array(
	 *             'controller' => 'welcome',
	 *         ));
	 *
	 * @param   array  $name  name(s) of subdomain(s) to apply in route
	 *
	 * @param bool $restrict
	 *
	 * @return Route
	 */
	public function subdomains(array $name, $restrict = FALSE)
	{
		$this->_subdomain = $name;
		$this->has_subdomain = true;
		$this->restrict = (bool)$restrict;

		return $this;
	}

	public function matches(Request $request, $subdomain = NULL)
	{
		$subdomain = (!isset($subdomain) OR $subdomain === NULL) ? $request->subdomain : $subdomain;


		if ($this->has_subdomain === FALSE)
		{
			return parent::matches($request);
		}

		if ($this->restrict === TRUE)
		{
			if (!empty($subdomain) && (in_array(self::SUBDOMAIN_WILDCARD, $this->_subdomain) || in_array($subdomain, $this->_subdomain)))
			{
				return parent::matches($request);
			}
		}
		else
		{
			if (in_array(self::SUBDOMAIN_WILDCARD, $this->_subdomain) || in_array($subdomain, $this->_subdomain))
			{
				return parent::matches($request);
			}
		}

		return FALSE;
	}
}
