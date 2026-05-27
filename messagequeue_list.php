<?php
/* Copyright (C) 2025 Alberto Luque Rivas <aluquerivasdev@gmail.com>
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
 *   	\file       messagequeue_list.php
 *		\ingroup    whatsapp
 *		\brief      List page for the WhatsApp message queue
 */

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
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php";
}
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

require_once DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';

// load whatsapp libraries
require_once __DIR__ . '/class/whatsappqueue.class.php';

// Load translation files required by the page
$langs->loadLangs(array("whatsapp@whatsapp", "other"));

$action      = GETPOST('action', 'aZ09') ? GETPOST('action', 'aZ09') : 'view';
$massaction  = GETPOST('massaction', 'alpha');
$show_files  = GETPOST('show_files', 'int');
$confirm     = GETPOST('confirm', 'alpha');
$cancel      = GETPOST('cancel', 'alpha');
$toselect    = GETPOST('toselect', 'array');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : str_replace('_', '', basename(dirname(__FILE__)) . basename(__FILE__, '.php'));
$backtopage  = GETPOST('backtopage', 'alpha');
$optioncss   = GETPOST('optioncss', 'aZ');
$mode        = GETPOST('mode', 'aZ');

// Load variable for pagination
$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
	$page = 0;
}
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

// Initialize technical objects
$object = new WhatsappQueue($db);
$extrafields = new ExtraFields($db);
$hookmanager->initHooks(array('messagequeuelist'));

// Default sort order
if (!$sortfield) {
	$sortfield = "t.priority";
}
if (!$sortorder) {
	$sortorder = "ASC";
}

// Initialize array of search criterias
$search_all = GETPOST('search_all', 'alphanohtml');
$search = array();
foreach ($object->fields as $key => $val) {
	if (GETPOST('search_' . $key, 'alpha') !== '') {
		$search[$key] = GETPOST('search_' . $key, 'alpha');
	}
	if (preg_match('/^(date|timestamp|datetime)/', $val['type'])) {
		$search[$key . '_dtstart'] = dol_mktime(0, 0, 0, GETPOST('search_' . $key . '_dtstartmonth', 'int'), GETPOST('search_' . $key . '_dtstartday', 'int'), GETPOST('search_' . $key . '_dtstartyear', 'int'));
		$search[$key . '_dtend'] = dol_mktime(23, 59, 59, GETPOST('search_' . $key . '_dtendmonth', 'int'), GETPOST('search_' . $key . '_dtendday', 'int'), GETPOST('search_' . $key . '_dtendyear', 'int'));
	}
}

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array();
foreach ($object->fields as $key => $val) {
	if (!empty($val['searchall'])) {
		$fieldstosearchall['t.' . $key] = $val['label'];
	}
}

// Definition of array of fields for columns
$arrayfields = array();
foreach ($object->fields as $key => $val) {
	if (!empty($val['visible'])) {
		$visible = (int) dol_eval($val['visible'], 1);
		$arrayfields['t.' . $key] = array(
			'label' => $val['label'],
			'checked' => (($visible < 0) ? 0 : 1),
			'enabled' => (abs($visible) != 3 && dol_eval($val['enabled'], 1)),
			'position' => $val['position'],
			'help' => isset($val['help']) ? $val['help'] : ''
		);
	}
}

$object->fields = dol_sort_array($object->fields, 'position');
$arrayfields = dol_sort_array($arrayfields, 'position');

// Permissions
$permissiontoread = $user->rights->whatsapp->queue->read;
$permissiontoadd = $user->rights->whatsapp->queue->write;
$permissiontodelete = $user->rights->whatsapp->queue->delete;

// Security check
if ($user->socid > 0) accessforbidden();
if (empty($conf->whatsapp->enabled)) accessforbidden('Module not enabled');
if (!$permissiontoread) accessforbidden();


/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) {
	$action = 'list';
	$massaction = '';
}
if (!GETPOST('confirmmassaction', 'alpha') && !in_array($massaction, array('presend', 'confirm_presend', 'requeue', 'cancelqueue'))) {
	$massaction = '';
}

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action);
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	// Selection of new fields
	include DOL_DOCUMENT_ROOT . '/core/actions_changeselectedfields.inc.php';

	// Purge search criteria
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
		foreach ($object->fields as $key => $val) {
			$search[$key] = '';
			if (preg_match('/^(date|timestamp|datetime)/', $val['type'])) {
				$search[$key . '_dtstart'] = '';
				$search[$key . '_dtend'] = '';
			}
		}
		$toselect = array();
	}
	if (
		GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')
		|| GETPOST('button_search_x', 'alpha') || GETPOST('button_search.x', 'alpha') || GETPOST('button_search', 'alpha')
	) {
		$massaction = '';
	}

	// Custom mass actions for the queue: requeue (reset failed/cancelled to pending) and cancel
	if (in_array($massaction, array('requeue', 'cancelqueue')) && !empty($toselect) && ($permissiontoadd || $permissiontodelete)) {
		$nbok = 0;
		foreach ($toselect as $toselectid) {
			$tmpobject = new WhatsappQueue($db);
			if ($tmpobject->fetch((int) $toselectid) > 0) {
				$r = ($massaction == 'requeue') ? $tmpobject->requeue($user) : $tmpobject->cancel($user);
				if ($r > 0) {
					$nbok++;
				}
			}
		}
		if ($nbok > 0) {
			setEventMessages($langs->trans('WhatsappQueueRecordsUpdated', $nbok), null, 'mesgs');
		} else {
			setEventMessages($langs->trans('WhatsappQueueNoRecordUpdated'), null, 'warnings');
		}
		$massaction = '';
		$toselect = array();
	}

	// Mass actions (delete handled generically)
	$objectclass = 'WhatsappQueue';
	$objectlabel = 'WhatsappQueueMessage';
	$uploaddir = $conf->whatsapp->dir_output;
	include DOL_DOCUMENT_ROOT . '/core/actions_massactions.inc.php';
}


/*
 * View
 */

$form = new Form($db);

$now = dol_now();

$help_url = '';
$title = $langs->trans("WhatsappQueueList");
$morejs = array();
$morecss = array();


// Build and execute select
$sql = 'SELECT ';
$sql .= $object->getFieldList('t');
$sql = preg_replace('/,\s*$/', '', $sql);
$sql .= " FROM " . MAIN_DB_PREFIX . $object->table_element . " as t";
if ($object->ismultientitymanaged == 1) {
	$sql .= " WHERE t.entity IN (" . getEntity($object->element) . ")";
} else {
	$sql .= " WHERE 1 = 1";
}
foreach ($search as $key => $val) {
	if (array_key_exists($key, $object->fields)) {
		if ($key == 'status' && $search[$key] == -1) {
			continue;
		}
		$mode_search = (($object->isInt($object->fields[$key]) || $object->isFloat($object->fields[$key])) ? 1 : 0);
		if ((strpos($object->fields[$key]['type'], 'integer:') === 0) || (strpos($object->fields[$key]['type'], 'sellist:') === 0) || !empty($object->fields[$key]['arrayofkeyval'])) {
			if ($search[$key] == '-1' || ($search[$key] === '0' && (empty($object->fields[$key]['arrayofkeyval']) || !array_key_exists('0', $object->fields[$key]['arrayofkeyval'])))) {
				$search[$key] = '';
			}
			$mode_search = 2;
		}
		if ($search[$key] != '') {
			$sql .= natural_search("t." . $db->escape($key), $search[$key], (($key == 'status') ? 2 : $mode_search));
		}
	} else {
		if (preg_match('/(_dtstart|_dtend)$/', $key) && $search[$key] != '') {
			$columnName = preg_replace('/(_dtstart|_dtend)$/', '', $key);
			if (preg_match('/^(date|timestamp|datetime)/', $object->fields[$columnName]['type'])) {
				if (preg_match('/_dtstart$/', $key)) {
					$sql .= " AND t." . $db->escape($columnName) . " >= '" . $db->idate($search[$key]) . "'";
				}
				if (preg_match('/_dtend$/', $key)) {
					$sql .= " AND t." . $db->escape($columnName) . " <= '" . $db->idate($search[$key]) . "'";
				}
			}
		}
	}
}
if ($search_all) {
	$sql .= natural_search(array_keys($fieldstosearchall), $search_all);
}
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $object);
$sql .= $hookmanager->resPrint;

// Count total nb of records
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
	$sqlforcount = preg_replace('/^SELECT[a-zA-Z0-9\._\s\(\),=<>\:\-\']+\sFROM/', 'SELECT COUNT(*) as nbtotalofrecords FROM', $sql);
	$resql = $db->query($sqlforcount);
	if ($resql) {
		$objforcount = $db->fetch_object($resql);
		$nbtotalofrecords = $objforcount->nbtotalofrecords;
	} else {
		dol_print_error($db);
	}
	if (($page * $limit) > $nbtotalofrecords) {
		$page = 0;
		$offset = 0;
	}
	$db->free($resql);
}

// Complete request and execute it with limit
$sql .= $db->order($sortfield, $sortorder);
if ($limit) {
	$sql .= $db->plimit($limit + 1, $offset);
}

$resql = $db->query($sql);
if (!$resql) {
	dol_print_error($db);
	exit;
}

$num = $db->num_rows($resql);


// Output page
llxHeader('', $title, $help_url, '', 0, 0, $morejs, $morecss, '', 'bodyforlist');

$arrayofselected = is_array($toselect) ? $toselect : array();

$param = '';
if (!empty($mode)) {
	$param .= '&mode=' . urlencode($mode);
}
if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
	$param .= '&contextpage=' . urlencode($contextpage);
}
if ($limit > 0 && $limit != $conf->liste_limit) {
	$param .= '&limit=' . urlencode($limit);
}
foreach ($search as $key => $val) {
	if (is_array($search[$key])) {
		foreach ($search[$key] as $skey) {
			if ($skey != '') {
				$param .= '&search_' . $key . '[]=' . urlencode($skey);
			}
		}
	} elseif (preg_match('/(_dtstart|_dtend)$/', $key) && !empty($val)) {
		$param .= '&search_' . $key . 'month=' . ((int) GETPOST('search_' . $key . 'month', 'int'));
		$param .= '&search_' . $key . 'day=' . ((int) GETPOST('search_' . $key . 'day', 'int'));
		$param .= '&search_' . $key . 'year=' . ((int) GETPOST('search_' . $key . 'year', 'int'));
	} elseif ($search[$key] != '') {
		$param .= '&search_' . $key . '=' . urlencode($search[$key]);
	}
}
if ($optioncss != '') {
	$param .= '&optioncss=' . urlencode($optioncss);
}

// Status counters dashboard (flat cards on a single row)
$counts = $object->getCountsByStatus();
$statusboxes = array(
	WhatsappQueue::STATUS_PENDING    => 'WhatsappQueueStatusPending',
	WhatsappQueue::STATUS_PROCESSING => 'WhatsappQueueStatusProcessing',
	WhatsappQueue::STATUS_ERROR      => 'WhatsappQueueStatusError',
	WhatsappQueue::STATUS_FAILED     => 'WhatsappQueueStatusFailed',
	WhatsappQueue::STATUS_SENT       => 'WhatsappQueueStatusSent',
	WhatsappQueue::STATUS_CANCELLED  => 'WhatsappQueueStatusCancelled',
);
print '<style>
.wqstats{display:flex;flex-wrap:wrap;gap:6px;margin:4px 0 14px 0;}
.wqstats .wqstat{flex:1 1 0;min-width:110px;display:block;text-align:center;padding:9px 6px;border:1px solid #ccc;background:#fff;text-decoration:none;color:inherit;}
.wqstats .wqstat:hover{background:#f6f6f6;border-color:#999;}
.wqstats .wqstat-num{display:block;font-size:1.7em;font-weight:bold;line-height:1.1;color:#333;}
.wqstats .wqstat-lbl{display:block;margin-top:4px;font-size:0.85em;color:#666;}
.wqstats .wqstat-total{background:#f3f3f3;}
.wqstats .wqstat-total .wqstat-lbl{text-transform:uppercase;letter-spacing:.5px;}
</style>';
print '<div class="wqstats">';
foreach ($statusboxes as $st => $key) {
	$linkurl = $_SERVER["PHP_SELF"] . '?search_status=' . $st;
	print '<a class="wqstat" href="' . $linkurl . '" title="' . dol_escape_htmltag($langs->trans($key)) . '">';
	print '<span class="wqstat-num">' . ((int) $counts[$st]) . '</span>';
	print '<span class="wqstat-lbl">' . $langs->trans($key) . '</span>';
	print '</a>';
}
print '<a class="wqstat wqstat-total" href="' . $_SERVER["PHP_SELF"] . '" title="' . dol_escape_htmltag($langs->trans('Total')) . '">';
print '<span class="wqstat-num">' . ((int) $counts['total']) . '</span>';
print '<span class="wqstat-lbl">' . $langs->trans('Total') . '</span>';
print '</a>';
print '</div>';

// List of mass actions available
$arrayofmassactions = array(
	'requeue' => img_picto('', 'fa-redo', 'class="pictofixedwidth"') . $langs->trans("WhatsappQueueRequeue"),
	'cancelqueue' => img_picto('', 'fa-ban', 'class="pictofixedwidth"') . $langs->trans("WhatsappQueueCancel"),
);
if (!empty($permissiontodelete)) {
	$arrayofmassactions['predelete'] = img_picto('', 'delete', 'class="pictofixedwidth"') . $langs->trans("Delete");
}
if (GETPOST('nomassaction', 'int') || in_array($massaction, array('presend', 'predelete'))) {
	$arrayofmassactions = array();
}
$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

print '<form method="POST" id="searchFormList" action="' . $_SERVER["PHP_SELF"] . '">' . "\n";
if ($optioncss != '') {
	print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
}
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="' . $sortfield . '">';
print '<input type="hidden" name="sortorder" value="' . $sortorder . '">';
print '<input type="hidden" name="page" value="' . $page . '">';
print '<input type="hidden" name="contextpage" value="' . $contextpage . '">';
print '<input type="hidden" name="mode" value="' . $mode . '">';

$newcardbutton = '';
print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'object_' . $object->picto, 0, $newcardbutton, '', $limit, 0, 0, 1);

// Add code for pre mass action (confirmation or email presend form)
$topicmail = "";
$modelmail = "messagequeue";
$objecttmp = new WhatsappQueue($db);
$trackid = 'wq' . $object->id;
include DOL_DOCUMENT_ROOT . '/core/tpl/massactions_pre.tpl.php';

if ($search_all) {
	$setupstring = '';
	foreach ($fieldstosearchall as $key => $val) {
		$fieldstosearchall[$key] = $langs->trans($val);
		$setupstring .= $key . "=" . $val . ";";
	}
	print '<div class="divsearchfieldfilter">' . $langs->trans("FilterOnInto", $search_all) . join(', ', $fieldstosearchall) . '</div>' . "\n";
}

$moreforfilter = '';
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters, $object);
if (empty($reshook)) {
	$moreforfilter .= $hookmanager->resPrint;
} else {
	$moreforfilter = $hookmanager->resPrint;
}
if (!empty($moreforfilter)) {
	print '<div class="liste_titre liste_titre_bydiv centpercent">';
	print $moreforfilter;
	print '</div>';
}

$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage, getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN', ''));
$selectedfields .= (count($arrayofmassactions) ? $form->showCheckAddButtons('checkforselect', 1) : '');

print '<div class="div-table-responsive">';
print '<table class="tagtable nobottomiftotal liste' . ($moreforfilter ? " listwithfilterbefore" : "") . '">' . "\n";


// Fields title search
print '<tr class="liste_titre">';
if (!empty($conf->global->MAIN_CHECKBOX_LEFT_COLUMN)) {
	print '<td class="liste_titre maxwidthsearch">';
	print $form->showFilterButtons('left');
	print '</td>';
}
foreach ($object->fields as $key => $val) {
	$cssforfield = (empty($val['csslist']) ? (empty($val['css']) ? '' : $val['css']) : $val['csslist']);
	if ($key == 'status') {
		$cssforfield .= ($cssforfield ? ' ' : '') . 'center';
	} elseif (in_array($val['type'], array('date', 'datetime', 'timestamp'))) {
		$cssforfield .= ($cssforfield ? ' ' : '') . 'center';
	}
	if (!empty($arrayfields['t.' . $key]['checked'])) {
		print '<td class="liste_titre' . ($cssforfield ? ' ' . $cssforfield : '') . '">';
		if (!empty($val['arrayofkeyval']) && is_array($val['arrayofkeyval'])) {
			print $form->selectarray('search_' . $key, $val['arrayofkeyval'], (isset($search[$key]) ? $search[$key] : ''), $val['notnull'], 0, 0, '', 1, 0, 0, '', 'maxwidth100', 1);
		} elseif ((strpos($val['type'], 'integer:') === 0) || (strpos($val['type'], 'sellist:') === 0)) {
			print $object->showInputField($val, $key, (isset($search[$key]) ? $search[$key] : ''), '', '', 'search_', $cssforfield . ' maxwidth250', 1);
		} elseif (preg_match('/^(date|timestamp|datetime)/', $val['type'])) {
			print '<div class="nowrap">';
			print $form->selectDate($search[$key . '_dtstart'] ? $search[$key . '_dtstart'] : '', "search_" . $key . "_dtstart", 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
			print '</div>';
			print '<div class="nowrap">';
			print $form->selectDate($search[$key . '_dtend'] ? $search[$key . '_dtend'] : '', "search_" . $key . "_dtend", 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
			print '</div>';
		} else {
			print '<input type="text" class="flat maxwidth75" name="search_' . $key . '" value="' . dol_escape_htmltag(isset($search[$key]) ? $search[$key] : '') . '">';
		}
		print '</td>';
	}
}
if (empty($conf->global->MAIN_CHECKBOX_LEFT_COLUMN)) {
	print '<td class="liste_titre maxwidthsearch">';
	print $form->showFilterButtons();
	print '</td>';
}
print '</tr>' . "\n";

$totalarray = array();
$totalarray['nbfield'] = 0;

// Fields title label
print '<tr class="liste_titre">';
if (!empty($conf->global->MAIN_CHECKBOX_LEFT_COLUMN)) {
	print getTitleFieldOfList(($mode != 'kanban' ? $selectedfields : ''), 0, $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ') . "\n";
}
foreach ($object->fields as $key => $val) {
	$cssforfield = (empty($val['csslist']) ? (empty($val['css']) ? '' : $val['css']) : $val['csslist']);
	if ($key == 'status') {
		$cssforfield .= ($cssforfield ? ' ' : '') . 'center';
	} elseif (in_array($val['type'], array('date', 'datetime', 'timestamp'))) {
		$cssforfield .= ($cssforfield ? ' ' : '') . 'center';
	}
	$cssforfield = preg_replace('/small\s*/', '', $cssforfield);
	if (!empty($arrayfields['t.' . $key]['checked'])) {
		print getTitleFieldOfList($arrayfields['t.' . $key]['label'], 0, $_SERVER['PHP_SELF'], 't.' . $key, '', $param, ($cssforfield ? 'class="' . $cssforfield . '"' : ''), $sortfield, $sortorder, ($cssforfield ? $cssforfield . ' ' : '')) . "\n";
		$totalarray['nbfield']++;
	}
}
$parameters = array('arrayfields' => $arrayfields, 'param' => $param, 'sortfield' => $sortfield, 'sortorder' => $sortorder, 'totalarray' => &$totalarray);
$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters, $object);
print $hookmanager->resPrint;
if (empty($conf->global->MAIN_CHECKBOX_LEFT_COLUMN)) {
	print getTitleFieldOfList(($mode != 'kanban' ? $selectedfields : ''), 0, $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ') . "\n";
}
$totalarray['nbfield']++;
print '</tr>' . "\n";


// Loop on record
$i = 0;
$savnbfield = $totalarray['nbfield'];
$totalarray = array();
$totalarray['nbfield'] = 0;
$imaxinloop = ($limit ? min($num, $limit) : $num);
while ($i < $imaxinloop) {
	$obj = $db->fetch_object($resql);
	if (empty($obj)) {
		break;
	}

	$object->setVarsFromFetchObj($obj);

	$j = 0;
	print '<tr data-rowid="' . $object->id . '" class="oddeven">';
	if (!empty($conf->global->MAIN_CHECKBOX_LEFT_COLUMN)) {
		print '<td class="nowrap center">';
		if ($massactionbutton || $massaction) {
			$selected = in_array($object->id, $arrayofselected) ? 1 : 0;
			print '<input id="cb' . $object->id . '" class="flat checkforselect" type="checkbox" name="toselect[]" value="' . $object->id . '"' . ($selected ? ' checked="checked"' : '') . '>';
		}
		print '</td>';
	}
	foreach ($object->fields as $key => $val) {
		$cssforfield = (empty($val['csslist']) ? (empty($val['css']) ? '' : $val['css']) : $val['csslist']);
		if (in_array($val['type'], array('date', 'datetime', 'timestamp'))) {
			$cssforfield .= ($cssforfield ? ' ' : '') . 'center';
		} elseif ($key == 'status') {
			$cssforfield .= ($cssforfield ? ' ' : '') . 'center';
		} elseif ($key == 'ref') {
			$cssforfield .= ($cssforfield ? ' ' : '') . 'nowrap';
		}

		if (!empty($arrayfields['t.' . $key]['checked'])) {
			print '<td' . ($cssforfield ? ' class="' . $cssforfield . '"' : '');
			if (preg_match('/tdoverflow/', $cssforfield)) {
				print ' title="' . dol_escape_htmltag($object->$key) . '"';
			}
			print '>';
			if ($key == 'status') {
				print $object->getLibStatut(5);
			} elseif ($key == 'ref') {
				print $object->getNomUrl(1);
			} elseif ($key == 'rowid') {
				print $object->showOutputField($val, $key, $object->id, '');
			} else {
				print $object->showOutputField($val, $key, $object->$key, '');
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
	}
	$parameters = array('arrayfields' => $arrayfields, 'object' => $object, 'obj' => $obj, 'i' => $i, 'totalarray' => &$totalarray);
	$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters, $object);
	print $hookmanager->resPrint;
	if (empty($conf->global->MAIN_CHECKBOX_LEFT_COLUMN)) {
		print '<td class="nowrap center">';
		if ($massactionbutton || $massaction) {
			$selected = in_array($object->id, $arrayofselected) ? 1 : 0;
			print '<input id="cb' . $object->id . '" class="flat checkforselect" type="checkbox" name="toselect[]" value="' . $object->id . '"' . ($selected ? ' checked="checked"' : '') . '>';
		}
		print '</td>';
	}
	if (!$i) {
		$totalarray['nbfield']++;
	}
	print '</tr>' . "\n";

	$i++;
}

// If no record found
if ($num == 0) {
	$colspan = 1;
	foreach ($arrayfields as $key => $val) {
		if (!empty($val['checked'])) {
			$colspan++;
		}
	}
	print '<tr><td colspan="' . $colspan . '"><span class="opacitymedium">' . $langs->trans("NoRecordFound") . '</span></td></tr>';
}

$db->free($resql);

$parameters = array('arrayfields' => $arrayfields, 'sql' => $sql);
$reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters, $object, $action);
print $hookmanager->resPrint;

print '</table>' . "\n";
print '</div>' . "\n";

print '</form>' . "\n";

// End of page
llxFooter();
$db->close();
