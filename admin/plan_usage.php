<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2025 Alberto SuperAdmin <aluquerivasdev@gmail.com>
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
 * \file    whatsapp/admin/about.php
 * \ingroup whatsapp
 * \brief   About page of module Whatsapp.
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"] . "/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
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
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

// Libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
require_once '../lib/whatsapp.lib.php';

// Translations
$langs->loadLangs(array("errors", "admin", "whatsapp@whatsapp"));



// Parameters
$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');


/*
 * Actions
 */

// None


/*
 * View
 */

$form = new Form($db);

$help_url = '';
$page_name = "PlanUsage";

llxHeader('', $langs->trans($page_name), $help_url);

// Subheader
if ($user->admin) {
	$linkback = '<a href="' . ($backtopage ? $backtopage : DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans("BackToModuleList") . '</a>';
} else {
	$linkback = '';
}

print load_fiche_titre($langs->trans($page_name), $linkback, 'title_setup');

// Configuration header
$head = whatsappAdminPrepareHead();
print dol_get_fiche_head($head, 'plan_usage', $langs->trans($page_name), 0, 'whatsapp@whatsapp');

// Obtener configuración de WhatsApp
$whatConf = getWhatsappConf();

if ($whatConf === false) {
	print '<div class="error">';
	print $langs->trans("WhatsappConfigurationMissing");
	print '</div>';
} else {
	$whapi_base_url = $whatConf['server_url'];
	$api_key = $whatConf['server_token'];

	// Construir la URL de consulta para obtener el plan y uso
	$query_url = rtrim($whapi_base_url, '/') . '/api-lookup/search?' . http_build_query([
		'api_key' => $api_key,
		'outputHTML' => 'true'
	]);

	// Hacer la consulta HTTP usando getURLContent de Dolibarr
	$response = getURLContent(
		$query_url,
		'GET',
		'',
		0,
		array('User-Agent: Dolibarr-WhatsApp/1.0', 'Accept: text/html,application/json'),
		array('http', 'https'),
		2  // Permite tanto URLs externas como locales
	);

	// Verificar si hubo errores
	if (!empty($response['curl_error_no'])) {
		print '<div class="error">';
		print 'Error de conexión: ' . $response['curl_error_msg'];
		print '</div>';
	} elseif ($response['http_code'] >= 200 && $response['http_code'] < 300) {
		// Mostrar el contenido HTML si la respuesta es exitosa
		if (!empty($response['content'])) {
			print $response['content'];
		} else {
			print '<div class="info">';
			print 'No hay datos de uso disponibles.';
			print '</div>';
		}
	} else {
		print '<div class="error">';
		print 'Error al obtener datos del plan: HTTP ' . $response['http_code'];
		if (!empty($response['content'])) {
			print '<br>Detalles: ' . htmlspecialchars($response['content']);
		}
		print '</div>';
	}
}

// Page end
print dol_get_fiche_end();
llxFooter();
$db->close();
