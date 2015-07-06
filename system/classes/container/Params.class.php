<?php
class Params {
	const LIST_ALL_FLEET = 1;
	const SHOW_MAP_MINIMAP = 2;
	const SHOW_MAP_RC = 3;
	const SHOW_MAP_ANTISPY = 4;
	const SHOW_MAP_FLEETOUT = 5;
	const SHOW_MAP_FLEETIN = 6;
	const SHOW_ATTACK_REPORT = 7;
	const SHOW_REBEL_REPORT = 8;

	private static $params = [
		self::LIST_ALL_FLEET 	=> TRUE,
		self::SHOW_MAP_MINIMAP 	=> TRUE,
		self::SHOW_MAP_RC 		=> FALSE,
		self::SHOW_MAP_ANTISPY 	=> TRUE,
		self::SHOW_MAP_FLEETOUT => TRUE,
		self::SHOW_MAP_FLEETIN 	=> TRUE,
		self::SHOW_ATTACK_REPORT=> TRUE,
		self::SHOW_REBEL_REPORT => TRUE,
	];

	public static function check($params) {
		return CTR::$cookie->exist('p' . $params)
			? (bool)CTR::$cookie->get('p' . $params)
			: self::$params[$params];
	}

	public static function update($params, $value) {
		if (in_array($params, self::$params)) {
			CTR::$cookie->add('p' . $params, $value);
		}
	}

	public static function getParams() {
		return self::$params;
	}
}
?>