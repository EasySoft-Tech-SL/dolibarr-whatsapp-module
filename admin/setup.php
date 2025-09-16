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
 * \file    whatsapp/admin/setup.php
 * \ingroup whatsapp
 * \brief   Whatsapp setup page.
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

global $langs, $user;

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once '../lib/whatsapp.lib.php';
//require_once "../class/myclass.class.php";

// Translations
$langs->loadLangs(array("admin", "whatsapp@whatsapp"));

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('whatsappsetup', 'globalsetup'));

// Access control
if (!$user->admin) {
	accessforbidden();
}

// Parameters
$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');
$modulepart = GETPOST('modulepart', 'aZ09');	// Used by actions_setmoduleoptions.inc.php

$value = GETPOST('value', 'alpha');
$label = GETPOST('label', 'alpha');
$scandir = GETPOST('scan_dir', 'alpha');
$type = 'myobject';


$error = 0;
$setupnotempty = 0;

// Set this to 1 to use the factory to manage constants. Warning, the generated module will be compatible with version v15+ only
$useFormSetup = 1;

if (!class_exists('FormSetup')) {
	// For retrocompatibility Dolibarr < 16.0
	if (floatval(DOL_VERSION) < 16.0 && !class_exists('FormSetup')) {
		require_once __DIR__ . '/../backport/v16/core/class/html.formsetup.class.php';
	} else {
		require_once DOL_DOCUMENT_ROOT . '/core/class/html.formsetup.class.php';
	}
}

$formSetup = new FormSetup($db);
$item = $formSetup->newItem('WHATSAPP_SERVER_URL');
$item = $formSetup->newItem('WHATSAPP_SERVER_TOKEN');
$item = $formSetup->newItem('WHATSAPP_WEBHOOK_ALLOW')->setAsYesNo();
$sql = "SELECT rowid, login FROM " . MAIN_DB_PREFIX . "user";
$resql = $db->query($sql);
$users = array();
if ($resql) {
	while ($obj = $db->fetch_object($resql)) {
		$staticUser = new User($db);
		$staticUser->fetch($obj->rowid);
		$users[$obj->rowid] = $obj->login . ' - ' . $staticUser->getFullName($langs);
	}
}
$item = $formSetup->newItem('WHATSAPP_WEBHOOK_USER_ID')->setAsSelect($users);
$item = $formSetup->newItem('WHATSAPP_SHOW_ON_GETNOMURL')->setAsYesNo();
$item = $formSetup->newItem('MAIN_WHATSAPP_PHONE_PREFIX');
// // Hôte
// $item = $formSetup->newItem('NO_PARAM_JUST_TEXT');
// $item->fieldOverride = (empty($_SERVER['HTTPS']) ? 'http://' : 'https://') . $_SERVER['HTTP_HOST'];
// $item->cssClass = 'minwidth500';

// // Setup conf WHATSAPP_MYPARAM1 as a simple string input
// $item = $formSetup->newItem('WHATSAPP_MYPARAM1');
// $item->defaultFieldValue = 'default value';

// // Setup conf WHATSAPP_MYPARAM1 as a simple textarea input but we replace the text of field title
// $item = $formSetup->newItem('WHATSAPP_MYPARAM2');
// $item->nameText = $item->getNameText().' more html text ';

// // Setup conf WHATSAPP_MYPARAM3
// $item = $formSetup->newItem('WHATSAPP_MYPARAM3');
// $item->setAsThirdpartyType();

// // Setup conf WHATSAPP_MYPARAM4 : exemple of quick define write style
// $formSetup->newItem('WHATSAPP_MYPARAM4')->setAsYesNo();

// // Setup conf WHATSAPP_MYPARAM5
// $formSetup->newItem('WHATSAPP_MYPARAM5')->setAsEmailTemplate('thirdparty');

// // Setup conf WHATSAPP_MYPARAM6
// $formSetup->newItem('WHATSAPP_MYPARAM6')->setAsSecureKey()->enabled = 0; // disabled

// // Setup conf WHATSAPP_MYPARAM7
// $formSetup->newItem('WHATSAPP_MYPARAM7')->setAsProduct();

// $formSetup->newItem('Title')->setAsTitle();

// // Setup conf WHATSAPP_MYPARAM8
// $item = $formSetup->newItem('WHATSAPP_MYPARAM8');
// $TField = array(
// 	'test01' => $langs->trans('test01'),
// 	'test02' => $langs->trans('test02'),
// 	'test03' => $langs->trans('test03'),
// 	'test04' => $langs->trans('test04'),
// 	'test05' => $langs->trans('test05'),
// 	'test06' => $langs->trans('test06'),
// );
// $item->setAsMultiSelect($TField);
// $item->helpText = $langs->transnoentities('WHATSAPP_MYPARAM8');


// // Setup conf WHATSAPP_MYPARAM9
// $formSetup->newItem('WHATSAPP_MYPARAM9')->setAsSelect($TField);


// // Setup conf WHATSAPP_MYPARAM10
// $item = $formSetup->newItem('WHATSAPP_MYPARAM10');
// $item->setAsColor();
// $item->defaultFieldValue = '#FF0000';
// $item->nameText = $item->getNameText().' more html text ';
// $item->fieldInputOverride = '';
// $item->helpText = $langs->transnoentities('AnHelpMessage');
// //$item->fieldValue = '';
// //$item->fieldAttr = array() ; // fields attribute only for compatible fields like input text
// //$item->fieldOverride = false; // set this var to override field output will override $fieldInputOverride and $fieldOutputOverride too
// //$item->fieldInputOverride = false; // set this var to override field input
// //$item->fieldOutputOverride = false; // set this var to override field output


$setupnotempty = +count($formSetup->items);


$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);


/*
 * Actions
 */

// For retrocompatibility Dolibarr < 15.0
if (versioncompare(explode('.', DOL_VERSION), array(15)) < 0 && $action == 'update' && !empty($user->admin)) {
	$formSetup->saveConfFromPost();
}

include DOL_DOCUMENT_ROOT . '/core/actions_setmoduleoptions.inc.php';

if ($action == 'updateMask') {
	$maskconst = GETPOST('maskconst', 'alpha');
	$maskvalue = GETPOST('maskvalue', 'alpha');

	if ($maskconst) {
		$res = dolibarr_set_const($db, $maskconst, $maskvalue, 'chaine', 0, '', $conf->entity);
		if (!($res > 0)) {
			$error++;
		}
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
} elseif ($action == 'logout') {
	$whatConf = getWhatsappConf();
	// Acción para cerrar la sesión de WhatsApp
	if ($whatConf) {
		$result = logoutInstance();
		if ($result) {
			setEventMessages($langs->trans("WhatsappLogoutSuccess"), null, 'mesgs');
		} else {
			setEventMessages($langs->trans("WhatsappLogoutError"), null, 'errors');
		}
	}
} elseif ($action == 'specimen') {
	$modele = GETPOST('module', 'alpha');
	$tmpobjectkey = GETPOST('object');

	$tmpobject = new $tmpobjectkey($db);
	$tmpobject->initAsSpecimen();

	// Search template files
	$file = '';
	$classname = '';
	$filefound = 0;
	$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
	foreach ($dirmodels as $reldir) {
		$file = dol_buildpath($reldir . "core/modules/whatsapp/doc/pdf_" . $modele . "_" . strtolower($tmpobjectkey) . ".modules.php", 0);
		if (file_exists($file)) {
			$filefound = 1;
			$classname = "pdf_" . $modele . "_" . strtolower($tmpobjectkey);
			break;
		}
	}

	if ($filefound) {
		require_once $file;

		$module = new $classname($db);

		if ($module->write_file($tmpobject, $langs) > 0) {
			header("Location: " . DOL_URL_ROOT . "/document.php?modulepart=whatsapp-" . strtolower($tmpobjectkey) . "&file=SPECIMEN.pdf");
			return;
		} else {
			setEventMessages($module->error, null, 'errors');
			dol_syslog($module->error, LOG_ERR);
		}
	} else {
		setEventMessages($langs->trans("ErrorModuleNotFound"), null, 'errors');
		dol_syslog($langs->trans("ErrorModuleNotFound"), LOG_ERR);
	}
} elseif ($action == 'setmod') {
	// TODO Check if numbering module chosen can be activated by calling method canBeActivated
	$tmpobjectkey = GETPOST('object');
	if (!empty($tmpobjectkey)) {
		$constforval = 'WHATSAPP_' . strtoupper($tmpobjectkey) . "_ADDON";
		dolibarr_set_const($db, $constforval, $value, 'chaine', 0, '', $conf->entity);
	}
} elseif ($action == 'set') {
	// Activate a model
	$ret = addDocumentModel($value, $type, $label, $scandir);
} elseif ($action == 'del') {
	$ret = delDocumentModel($value, $type);
	if ($ret > 0) {
		$tmpobjectkey = GETPOST('object');
		if (!empty($tmpobjectkey)) {
			$constforval = 'WHATSAPP_' . strtoupper($tmpobjectkey) . '_ADDON_PDF';
			if ($conf->global->$constforval == "$value") {
				dolibarr_del_const($db, $constforval, $conf->entity);
			}
		}
	}
} elseif ($action == 'setdoc') {
	// Set or unset default model
	$tmpobjectkey = GETPOST('object');
	if (!empty($tmpobjectkey)) {
		$constforval = 'WHATSAPP_' . strtoupper($tmpobjectkey) . '_ADDON_PDF';
		if (dolibarr_set_const($db, $constforval, $value, 'chaine', 0, '', $conf->entity)) {
			// The constant that was read before the new set
			// We therefore requires a variable to have a coherent view
			$conf->global->$constforval = $value;
		}

		// We disable/enable the document template (into llx_document_model table)
		$ret = delDocumentModel($value, $type);
		if ($ret > 0) {
			$ret = addDocumentModel($value, $type, $label, $scandir);
		}
	}
} elseif ($action == 'unsetdoc') {
	$tmpobjectkey = GETPOST('object');
	if (!empty($tmpobjectkey)) {
		$constforval = 'WHATSAPP_' . strtoupper($tmpobjectkey) . '_ADDON_PDF';
		dolibarr_del_const($db, $constforval, $conf->entity);
	}
}



/*
 * View
 */

$form = new Form($db);

$help_url = '';
$page_name = "WhatsappSetup";

llxHeader('', $langs->trans($page_name), $help_url);

// Subheader
$linkback = '<a href="' . ($backtopage ? $backtopage : DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans("BackToModuleList") . '</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'title_setup');

// Configuration header
$head = whatsappAdminPrepareHead();
print dol_get_fiche_head($head, 'settings', $langs->trans($page_name), -1, "whatsapp@whatsapp");

// Setup page goes here
echo '<span class="opacitymedium">' . $langs->trans("WhatsappSetupPage") . '</span><br><br>';


if ($action == 'edit') {
	print $formSetup->generateOutput(true);
	print '<br>';
} elseif (!empty($formSetup->items)) {
	print $formSetup->generateOutput();
	print '<div class="tabsAction">';
	print '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?action=edit&token=' . newToken() . '">' . $langs->trans("Modify") . '</a>';
	print '</div>';
} else {
	print '<br>' . $langs->trans("NothingToSetup");
}

//veeirica que los 3 campos de la api estan cumplimentados
$whatConf = getWhatsappConf();
if (!$whatConf) {
	echo '<div class="error">Faltan datos de configuración de la API de Whatsapp</div>';
} else {
	try {

		$instanceStatus = getInstanceStatus($whatConf['server_token'], $whatConf['server_url']);
		$isConnected = $instanceStatus['connected'] ?? false;
	} catch (\Throwable $th) {
	}

	print '<br>';
	print load_fiche_titre($langs->trans("WhatsappAPIStatus"), '', '');

	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td width="30%">' . $langs->trans("Parameter") . '</td>';
	print '<td>' . $langs->trans("Value") . '</td>';
	print '</tr>';

	// Estado de la conexión
	print '<tr class="oddeven">';
	print '<td><strong>' . $langs->trans("WhatsappStatus") . '</strong></td>';
	print '<td>';
	if ($isConnected) {

		print '<span class="badge badge-status4 badge-status">' . $langs->trans("WhatsappConnected") . '</span>';
		// Añadir botón de logout
		print ' <a class="butActionDelete" href="' . $_SERVER["PHP_SELF"] . '?action=logout&token=' . newToken() . '">' . $langs->trans("Logout") . '</a>';
	} else {
		print '<span class="badge badge-status8 badge-status">' . $langs->trans("WhatsappDisconnected") . '</span>';
	}
	print '</td>';
	print '</tr>';

	// Botón para abrir la instancia si está cerrada, o mostrar QR si está abierta
	if (!$isConnected) {

		// Mostrar QR directamente
		print '<tr class="oddeven">';
		print '<td><strong>' . $langs->trans("QRCode") . '</strong></td>';
		print '<td class="center">';

		// Intentar obtener el QR Code de la API
		// Obtener QR Code
		try {
			$qrCode = $instanceStatus['qr_code'] ?? null;
			if ($qrCode) {
				$qrImageData = $qrCode;
				print  '<img src="' . $qrImageData . '" alt="QR Code" style="max-width:100%;height:auto;">';
				print  '<div style="margin-top:8px;font-size:12px;color:#666;">' . $langs->trans("Escanea el código QR para conectar") . '</div>';
			}
		} catch (Exception $e) {
			print  '<div style="color:#dc3545;padding:10px;border:1px solid #dc3545;border-radius:4px;">
                    <i class="fa fa-exclamation-circle"></i> ' . $langs->trans("Error al obtener código QR") . ':<br>' . $e->getMessage() . '
                </div>';
		}

		print '</td>';
		print '</tr>';
	} else {
		// Si está conectado, mostrar el formulario de prueba de envío
		print '</table>';
		print '<br>';

		// Procesar el envío del mensaje de prueba si se ha enviado el formulario
		if ($action == 'sendtestmessage' && !empty($_POST['test_phone']) && !empty($_POST['test_message'])) {
			$phone = GETPOST('test_phone', 'alpha');
			$message = GETPOST('test_message', 'restricthtml');

			$result = sendWhapiText(
				null,
				$phone,
				$message
			);
		}

		print load_fiche_titre($langs->trans("WhatsappTestMessage"), '', '');

		print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '?action=sendtestmessage">';
		print '<input type="hidden" name="token" value="' . newToken() . '">';

		print '<table class="noborder centpercent">';

		// Número de teléfono
		print '<tr class="oddeven">';
		print '<td width="30%">' . $langs->trans("PhoneNumber") . '</td>';
		print '<td>';
		print '<input type="text" name="test_phone" class="flat minwidth200" placeholder="" required>';
		print ' ' . $langs->trans("PhoneNumberFormat");
		print '</td>';
		print '</tr>';

		// Mensaje de texto
		print '<tr class="oddeven">';
		print '<td>' . $langs->trans("Message") . '</td>';
		print '<td>';
		print '<textarea name="test_message" class="flat" rows="4" cols="50" required></textarea>';
		print '</td>';
		print '</tr>';

		print '</table>';

		print '<div class="center">';
		print '<input type="submit" class="button" value="' . $langs->trans("SendTestMessage") . '">';
		print '</div>';

		print '</form>';
		print '<br>';

		print '<table class="noborder centpercent">';
	}

	print '</table>';
	print '<br>';
}

$moduledir = 'whatsapp';
$myTmpObjects = array();
$myTmpObjects['MyObject'] = array('includerefgeneration' => 0, 'includedocgeneration' => 0);


foreach ($myTmpObjects as $myTmpObjectKey => $myTmpObjectArray) {
	if ($myTmpObjectKey == 'MyObject') {
		continue;
	}
	if ($myTmpObjectArray['includerefgeneration']) {
		/*
		 * Orders Numbering model
		 */
		$setupnotempty++;

		print load_fiche_titre($langs->trans("NumberingModules", $myTmpObjectKey), '', '');

		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<td>' . $langs->trans("Name") . '</td>';
		print '<td>' . $langs->trans("Description") . '</td>';
		print '<td class="nowrap">' . $langs->trans("Example") . '</td>';
		print '<td class="center" width="60">' . $langs->trans("Status") . '</td>';
		print '<td class="center" width="16">' . $langs->trans("ShortInfo") . '</td>';
		print '</tr>' . "\n";

		clearstatcache();

		foreach ($dirmodels as $reldir) {
			$dir = dol_buildpath($reldir . "core/modules/" . $moduledir);

			if (is_dir($dir)) {
				$handle = opendir($dir);
				if (is_resource($handle)) {
					while (($file = readdir($handle)) !== false) {
						if (strpos($file, 'mod_' . strtolower($myTmpObjectKey) . '_') === 0 && substr($file, dol_strlen($file) - 3, 3) == 'php') {
							$file = substr($file, 0, dol_strlen($file) - 4);

							require_once $dir . '/' . $file . '.php';

							$module = new $file($db);

							// Show modules according to features level
							if ($module->version == 'development' && $conf->global->MAIN_FEATURES_LEVEL < 2) {
								continue;
							}
							if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) {
								continue;
							}

							if ($module->isEnabled()) {
								dol_include_once('/' . $moduledir . '/class/' . strtolower($myTmpObjectKey) . '.class.php');

								print '<tr class="oddeven"><td>' . $module->name . "</td><td>\n";
								print $module->info();
								print '</td>';

								// Show example of numbering model
								print '<td class="nowrap">';
								$tmp = $module->getExample();
								if (preg_match('/^Error/', $tmp)) {
									$langs->load("errors");
									print '<div class="error">' . $langs->trans($tmp) . '</div>';
								} elseif ($tmp == 'NotConfigured') {
									print $langs->trans($tmp);
								} else {
									print $tmp;
								}
								print '</td>' . "\n";

								print '<td class="center">';
								$constforvar = 'WHATSAPP_' . strtoupper($myTmpObjectKey) . '_ADDON';
								if (getDolGlobalString($constforvar) == $file) {
									print img_picto($langs->trans("Activated"), 'switch_on');
								} else {
									print '<a href="' . $_SERVER["PHP_SELF"] . '?action=setmod&token=' . newToken() . '&object=' . strtolower($myTmpObjectKey) . '&value=' . urlencode($file) . '">';
									print img_picto($langs->trans("Disabled"), 'switch_off');
									print '</a>';
								}
								print '</td>';

								$mytmpinstance = new $myTmpObjectKey($db);
								$mytmpinstance->initAsSpecimen();

								// Info
								$htmltooltip = '';
								$htmltooltip .= '' . $langs->trans("Version") . ': <b>' . $module->getVersion() . '</b><br>';

								$nextval = $module->getNextValue($mytmpinstance);
								if ("$nextval" != $langs->trans("NotAvailable")) {  // Keep " on nextval
									$htmltooltip .= '' . $langs->trans("NextValue") . ': ';
									if ($nextval) {
										if (preg_match('/^Error/', $nextval) || $nextval == 'NotConfigured') {
											$nextval = $langs->trans($nextval);
										}
										$htmltooltip .= $nextval . '<br>';
									} else {
										$htmltooltip .= $langs->trans($module->error) . '<br>';
									}
								}

								print '<td class="center">';
								print $form->textwithpicto('', $htmltooltip, 1, 0);
								print '</td>';

								print "</tr>\n";
							}
						}
					}
					closedir($handle);
				}
			}
		}
		print "</table><br>\n";
	}

	if ($myTmpObjectArray['includedocgeneration']) {
		/*
		 * Document templates generators
		 */
		$setupnotempty++;
		$type = strtolower($myTmpObjectKey);

		print load_fiche_titre($langs->trans("DocumentModules", $myTmpObjectKey), '', '');

		// Load array def with activated templates
		$def = array();
		$sql = "SELECT nom";
		$sql .= " FROM " . MAIN_DB_PREFIX . "document_model";
		$sql .= " WHERE type = '" . $db->escape($type) . "'";
		$sql .= " AND entity = " . $conf->entity;
		$resql = $db->query($sql);
		if ($resql) {
			$i = 0;
			$num_rows = $db->num_rows($resql);
			while ($i < $num_rows) {
				$array = $db->fetch_array($resql);
				array_push($def, $array[0]);
				$i++;
			}
		} else {
			dol_print_error($db);
		}

		print "<table class=\"noborder\" width=\"100%\">\n";
		print "<tr class=\"liste_titre\">\n";
		print '<td>' . $langs->trans("Name") . '</td>';
		print '<td>' . $langs->trans("Description") . '</td>';
		print '<td class="center" width="60">' . $langs->trans("Status") . "</td>\n";
		print '<td class="center" width="60">' . $langs->trans("Default") . "</td>\n";
		print '<td class="center" width="38">' . $langs->trans("ShortInfo") . '</td>';
		print '<td class="center" width="38">' . $langs->trans("Preview") . '</td>';
		print "</tr>\n";

		clearstatcache();

		foreach ($dirmodels as $reldir) {
			foreach (array('', '/doc') as $valdir) {
				$realpath = $reldir . "core/modules/" . $moduledir . $valdir;
				$dir = dol_buildpath($realpath);

				if (is_dir($dir)) {
					$handle = opendir($dir);
					if (is_resource($handle)) {
						while (($file = readdir($handle)) !== false) {
							$filelist[] = $file;
						}
						closedir($handle);
						arsort($filelist);

						foreach ($filelist as $file) {
							if (preg_match('/\.modules\.php$/i', $file) && preg_match('/^(pdf_|doc_)/', $file)) {
								if (file_exists($dir . '/' . $file)) {
									$name = substr($file, 4, dol_strlen($file) - 16);
									$classname = substr($file, 0, dol_strlen($file) - 12);

									require_once $dir . '/' . $file;
									$module = new $classname($db);

									$modulequalified = 1;
									if ($module->version == 'development' && $conf->global->MAIN_FEATURES_LEVEL < 2) {
										$modulequalified = 0;
									}
									if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) {
										$modulequalified = 0;
									}

									if ($modulequalified) {
										print '<tr class="oddeven"><td width="100">';
										print(empty($module->name) ? $name : $module->name);
										print "</td><td>\n";
										if (method_exists($module, 'info')) {
											print $module->info($langs);
										} else {
											print $module->description;
										}
										print '</td>';

										// Active
										if (in_array($name, $def)) {
											print '<td class="center">' . "\n";
											print '<a href="' . $_SERVER["PHP_SELF"] . '?action=del&token=' . newToken() . '&value=' . urlencode($name) . '">';
											print img_picto($langs->trans("Enabled"), 'switch_on');
											print '</a>';
											print '</td>';
										} else {
											print '<td class="center">' . "\n";
											print '<a href="' . $_SERVER["PHP_SELF"] . '?action=set&token=' . newToken() . '&value=' . urlencode($name) . '&scan_dir=' . urlencode($module->scandir) . '&label=' . urlencode($module->name) . '">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
											print "</td>";
										}

										// Default
										print '<td class="center">';
										$constforvar = 'WHATSAPP_' . strtoupper($myTmpObjectKey) . '_ADDON';
										if (getDolGlobalString($constforvar) == $name) {
											//print img_picto($langs->trans("Default"), 'on');
											// Even if choice is the default value, we allow to disable it. Replace this with previous line if you need to disable unset
											print '<a href="' . $_SERVER["PHP_SELF"] . '?action=unsetdoc&token=' . newToken() . '&object=' . urlencode(strtolower($myTmpObjectKey)) . '&value=' . urlencode($name) . '&scan_dir=' . urlencode($module->scandir) . '&label=' . urlencode($module->name) . '&amp;type=' . urlencode($type) . '" alt="' . $langs->trans("Disable") . '">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
										} else {
											print '<a href="' . $_SERVER["PHP_SELF"] . '?action=setdoc&token=' . newToken() . '&object=' . urlencode(strtolower($myTmpObjectKey)) . '&value=' . urlencode($name) . '&scan_dir=' . urlencode($module->scandir) . '&label=' . urlencode($module->name) . '" alt="' . $langs->trans("Default") . '">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
										}
										print '</td>';

										// Info
										$htmltooltip = '' . $langs->trans("Name") . ': ' . $module->name;
										$htmltooltip .= '<br>' . $langs->trans("Type") . ': ' . ($module->type ? $module->type : $langs->trans("Unknown"));
										if ($module->type == 'pdf') {
											$htmltooltip .= '<br>' . $langs->trans("Width") . '/' . $langs->trans("Height") . ': ' . $module->page_largeur . '/' . $module->page_hauteur;
										}
										$htmltooltip .= '<br>' . $langs->trans("Path") . ': ' . preg_replace('/^\//', '', $realpath) . '/' . $file;

										$htmltooltip .= '<br><br><u>' . $langs->trans("FeaturesSupported") . ':</u>';
										$htmltooltip .= '<br>' . $langs->trans("Logo") . ': ' . yn($module->option_logo, 1, 1);
										$htmltooltip .= '<br>' . $langs->trans("MultiLanguage") . ': ' . yn($module->option_multilang, 1, 1);

										print '<td class="center">';
										print $form->textwithpicto('', $htmltooltip, 1, 0);
										print '</td>';

										// Preview
										print '<td class="center">';
										if ($module->type == 'pdf') {
											$newname = preg_replace('/_' . preg_quote(strtolower($myTmpObjectKey), '/') . '/', '', $name);
											print '<a href="' . $_SERVER["PHP_SELF"] . '?action=specimen&module=' . urlencode($newname) . '&object=' . urlencode($myTmpObjectKey) . '">' . img_object($langs->trans("Preview"), 'pdf') . '</a>';
										} else {
											print img_object($langs->trans("PreviewNotAvailable"), 'generic');
										}
										print '</td>';

										print "</tr>\n";
									}
								}
							}
						}
					}
				}
			}
		}

		print '</table>';
	}
}

if (empty($setupnotempty)) {
	print '<br>' . $langs->trans("NothingToSetup");
}

// Page end
print dol_get_fiche_end();

llxFooter();
$db->close();
