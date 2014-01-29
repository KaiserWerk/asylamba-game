<?php
# réglage de l'encodage
header('Content-type: text/html; charset=utf-8');

# unlimited time
set_time_limit(0);
ini_set('display_errors', TRUE);

if (DEVMODE || CTR::$get->exist('password')) {
	switch (CTR::$get->get('a')) {
		case 'dbinstall': 				include SCRIPT . 'scripts/dbinstall.php'; break;

		case 'newgalaxy': 				include SCRIPT . 'scripts/newgalaxy.php'; break;
		case 'testgalaxy': 				include SCRIPT . 'scripts/testgalaxy.php'; break;
		case 'testchangecolor': 		include SCRIPT . 'scripts/changecolor.php'; break;

		case 'dump':					include SCRIPT . 'scripts/dump.php'; break;
		case 'apitest':					include SCRIPT . 'scripts/apitest.php'; break;

		case 'addbugtracker':			include SCRIPT . 'scripts/addbugtracker.php'; break;
		case 'addtransaction':			include SCRIPT . 'scripts/addtransaction.php'; break;
		case 'addcommercialshipping': 	include SCRIPT . 'scripts/addcommercialshipping.php'; break;

		case 'dailycron':				include SCRIPT . 'scripts/cron/daily.php'; break;

		default: echo 'Script inconnu ou non-référencé'; break;
	}
} else {
	echo 'Accès refusé';
}
?>