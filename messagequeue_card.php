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
 *   	\file       messagequeue_card.php
 *		\ingroup    whatsapp
 *		\brief      Card page for a queued WhatsApp message
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

require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once __DIR__ . '/class/whatsappqueue.class.php';
require_once __DIR__ . '/lib/whatsapp_messagequeue.lib.php';

$langs->loadLangs(array("whatsapp@whatsapp", "other"));

$id     = GETPOST('id', 'int');
$ref    = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$cancel = GETPOST('cancel', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');

$object = new WhatsappQueue($db);
$hookmanager->initHooks(array('messagequeuecard'));

// Permissions
$permissiontoread = $user->rights->whatsapp->queue->read;
$permissiontoadd = $user->rights->whatsapp->queue->write;
$permissiontodelete = $user->rights->whatsapp->queue->delete;

if (empty($conf->whatsapp->enabled)) accessforbidden('Module not enabled');
if (!$permissiontoread) accessforbidden();

if ($id > 0 || !empty($ref)) {
	$result = $object->fetch($id, $ref);
	if ($result <= 0) {
		setEventMessages($langs->trans("ErrorRecordNotFound"), null, 'errors');
		header("Location: " . dol_buildpath('/whatsapp/messagequeue_list.php', 1));
		exit;
	}
}

$urllist = dol_buildpath('/whatsapp/messagequeue_list.php', 1);


/*
 * Actions
 */

if ($cancel) {
	$action = '';
}

// Delete
if ($action == 'confirm_delete' && $confirm == 'yes' && $permissiontodelete) {
	$result = $object->delete($user);
	if ($result > 0) {
		setEventMessages($langs->trans("RecordDeleted"), null, 'mesgs');
		header("Location: " . $urllist);
		exit;
	} else {
		setEventMessages($object->error, $object->errors, 'errors');
		$action = '';
	}
}

// Requeue
if ($action == 'requeue' && $permissiontoadd) {
	$result = $object->requeue($user);
	if ($result > 0) {
		setEventMessages($langs->trans("WhatsappQueueRequeued"), null, 'mesgs');
	} elseif ($result == 0) {
		setEventMessages($langs->trans("WhatsappQueueNotRequeable"), null, 'warnings');
	} else {
		setEventMessages($object->error, $object->errors, 'errors');
	}
	$object->fetch($object->id);
	$action = '';
}

// Cancel a queued message
if ($action == 'docancelqueue' && $permissiontoadd) {
	$result = $object->cancel($user);
	if ($result > 0) {
		setEventMessages($langs->trans("WhatsappQueueCancelled"), null, 'mesgs');
	} elseif ($result == 0) {
		setEventMessages($langs->trans("WhatsappQueueNotCancellable"), null, 'warnings');
	} else {
		setEventMessages($object->error, $object->errors, 'errors');
	}
	$object->fetch($object->id);
	$action = '';
}


/*
 * View
 */

$form = new Form($db);

$title = $langs->trans("WhatsappQueueMessage");
llxHeader('', $title);

$head = messagequeuePrepareHead($object);
print dol_get_fiche_head($head, 'card', $title, -1, $object->picto);

// Confirmation to delete
if ($action == 'delete') {
	print $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('Delete'), $langs->trans('WhatsappQueueConfirmDelete'), 'confirm_delete', '', 0, 1);
}

$linkback = '<a href="' . $urllist . '">' . $langs->trans("BackToList") . '</a>';

$morehtmlref = '';
dol_banner_tab($object, 'ref', $linkback, 0, 'ref', 'ref', $morehtmlref);

print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';

print '<table class="border centpercent tableforfield">' . "\n";

// Status
print '<tr><td class="titlefield">' . $langs->trans("Status") . '</td><td>' . $object->getLibStatut(5) . '</td></tr>';

// Show all view-relevant fields
foreach ($object->fields as $key => $val) {
	if (in_array($key, array('rowid', 'ref', 'status', 'entity'))) {
		continue;
	}
	$visible = (int) dol_eval(isset($val['visible']) ? $val['visible'] : 0, 1);
	if ($visible == 0 || $visible == -2) {
		continue; // hidden / technical-only
	}
	$value = $object->showOutputField($val, $key, $object->$key, '');
	if ($value === '' || $value === null) {
		continue;
	}
	print '<tr><td class="titlefield">' . $langs->trans($val['label']) . '</td><td>' . $value . '</td></tr>';
}

print '</table>';
print '</div>';

print dol_get_fiche_end();


/*
 * Action buttons
 */
print '<div class="tabsAction">' . "\n";
$parameters = array();
$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action);
if (empty($reshook)) {
	$statusint = (int) $object->status;

	// Requeue (failed / cancelled / error / sent -> back to pending)
	if ($permissiontoadd && in_array($statusint, array(WhatsappQueue::STATUS_ERROR, WhatsappQueue::STATUS_FAILED, WhatsappQueue::STATUS_CANCELLED, WhatsappQueue::STATUS_SENT))) {
		print '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=requeue&token=' . newToken() . '">' . $langs->trans("WhatsappQueueRequeue") . '</a>' . "\n";
	}

	// Cancel (pending / error)
	if ($permissiontoadd && in_array($statusint, array(WhatsappQueue::STATUS_PENDING, WhatsappQueue::STATUS_ERROR))) {
		print '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=docancelqueue&token=' . newToken() . '">' . $langs->trans("WhatsappQueueCancel") . '</a>' . "\n";
	}

	// Delete
	if ($permissiontodelete) {
		print '<a class="butActionDelete" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=delete&token=' . newToken() . '">' . $langs->trans("Delete") . '</a>' . "\n";
	}
}
print '</div>' . "\n";

llxFooter();
$db->close();
