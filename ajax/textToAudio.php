<?php
/* Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
 * Copyright (C) 2005-2013 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2007-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2020      Josep Lluís Amador   <joseplluis@lliuretic.cat>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file 	htdocs/product/ajax/products.php
 * \brief 	File to return Ajax response on product list request.
 */

if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', 1); // Disables token renewal
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
if (empty($_GET['keysearch']) && !defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1');
}


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
if (!$res && file_exists("../main.inc.php")) {
	$res = @include "../main.inc.php";
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
dol_include_once('/whatsapp/includes/TextToSpeech.php');
dol_include_once('/whatsapp/lib/whatsapp.lib.php');
top_httphead('application/json');

$text = htmlToPlainText(GETPOST('text', 'html'));

//Si el texto es vacío, devuelve un error
if (empty($text) || $text == '') {
	echo json_encode(['status' => 'error', 'message' => 'El texto está vacío.']);
	exit;
}
$fileName =  'TMPAUDIO' . date('YmdHis') . '.mp3';
$outputFile = dol_buildpath('/whatsapp/tmp/audio/') . $fileName; // Ruta del archivo de salida

// Configuración de voz y velocidad
$ttsOptions = [
	'voice' => 'es-ES',  // Voz española
	'speed' => 1         // Velocidad normal (0=lento, 1=rápido)
];
// Opción para limpiar el directorio de audios antes de crear uno nuevo
$clearAudioDirectory = true; // Cambiar a false para mantener archivos anteriores
if (TextToSpeech::googleTranslateTTSLong($text, 'es', $outputFile, $ttsOptions, $clearAudioDirectory)) {
	//Convierte el archivo de audio a base64 si existe
	if (file_exists($outputFile)) {
		$audioData = file_get_contents($outputFile);
		$base64Audio = base64_encode($audioData);
		unlink($outputFile);
		// Devuelve el audio en formato JSON
		/* header('Content-Type: application/json');
		echo json_encode(['status' => 'success', 'audio' => $base64Audio]); */
		//Pinta un div con el audio
		/* echo '<div class="audio-container">';
		echo '<audio controls>';
		echo '<source src="data:audio/mpeg;base64,' . $base64Audio . '" type="audio/mpeg">';
		echo 'Tu navegador no soporta el elemento de audio.';
		echo '</audio>';
		echo '</div>'; */
		echo json_encode(['status' => 'success', 'audio' => $base64Audio]);
	} else {
		echo json_encode(['status' => 'error', 'message' => 'Error al crear el archivo de audio.']);
	}
} else {
	echo json_encode(['status' => 'error', 'message' => 'Error al crear el archivo de audio.']);
}
