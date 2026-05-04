<?php
/* Copyright (C) 2025 Alberto SuperAdmin <aluquerivasdev@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    whatsapp/ajax/checkInstanceStatus.php
 * \ingroup whatsapp
 * \brief   AJAX endpoint to check WhatsApp instance connection status and return QR code.
 */
if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', 1);
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1');
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}
if (!defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}
if (!defined('NOCSRFCHECK')) {
	define('NOCSRFCHECK', '1');
}

// Load Dolibarr environment
$res = 0;
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"] . "/main.inc.php";
}
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1)) . "/main.inc.php";
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/../main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1)) . "/../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

require_once '../lib/whatsapp.lib.php';

// Access control - only admin
if (!$user->admin) {
	http_response_code(403);
	echo json_encode(array('status' => 'error', 'message' => 'Access denied'));
	exit;
}

header('Content-Type: application/json; charset=UTF-8');

$whatConf = getWhatsappConf();
if (!$whatConf) {
	echo json_encode(array('status' => 'error', 'message' => 'Missing API configuration'));
	exit;
}

try {
	$instanceStatus = getInstanceStatus($whatConf['server_token'], $whatConf['server_url']);
	$isConnected = $instanceStatus['connected'] ?? false;

	// Refresh the session cache used by printTopRightMenu so subsequent page loads
	// don't need to hit the WhatsApp HTTP server again for ~60s.
	$_SESSION['whatsapp_status_cache'] = array(
		'key' => md5($whatConf['server_url'] . '|' . $whatConf['server_token']),
		'ts' => time(),
		'data' => is_array($instanceStatus) ? $instanceStatus : array(),
	);

	if ($isConnected) {
		echo json_encode(array(
			'status' => 'ok',
			'connected' => true,
			'qr_code' => null
		));
	} else {
		$qrCode = $instanceStatus['qr_code'] ?? null;
		echo json_encode(array(
			'status' => 'ok',
			'connected' => false,
			'qr_code' => $qrCode
		));
	}
} catch (Exception $e) {
	echo json_encode(array(
		'status' => 'error',
		'message' => $e->getMessage()
	));
}
