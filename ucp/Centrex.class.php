<?php

namespace UCP\Modules;
use \UCP\Modules as Modules;

class Centrex extends Modules{
	protected $module = 'Centrex';

	function __construct($Modules) {
		$this->Modules = $Modules;
	}

	/*
	 * Used by Ajax Class to determine what commands are allowed by this class
	 *
	 * @param string $command The command something is trying to perform
	 * @param string $settings The Settings being passed through $_POST or $_PUT
	 * @return bool True if pass
	 */
	function ajaxRequest($command, $settings) {
		return false;

	}
}
