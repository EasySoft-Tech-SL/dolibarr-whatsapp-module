<?php
/* Copyright (C) 2025 Alberto SuperAdmin <aluquerivasdev@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

if (!defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', '1');
if (!defined('NOREQUIREMENU'))  define('NOREQUIREMENU', '1');
if (!defined('NOREQUIREHTML'))  define('NOREQUIREHTML', '1');
if (!defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX', '1');
if (!defined('NOREQUIRESOC'))   define('NOREQUIRESOC', '1');
if (!defined('NOCSRFCHECK'))    define('NOCSRFCHECK', '1');

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
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
dol_include_once('/whatsapp/class/reminderschedule.class.php');

$langs->load("whatsapp@whatsapp");

// Security check
if (!$user->rights->whatsapp->read) {
	http_response_code(403);
	echo json_encode(array('status' => 'error', 'message' => $langs->trans('AccessDenied')));
	exit;
}

header('Content-Type: application/json; charset=utf-8');

$action = GETPOST('action', 'alpha');
$fk_actioncomm = GETPOST('fk_actioncomm', 'int');

$response = array('status' => 'error', 'message' => '');

switch ($action) {
	case 'generate':
		$baseDate = GETPOST('base_date', 'alpha');
		$baseTime = GETPOST('base_time', 'alpha');
		$frequency = GETPOST('frequency', 'int');
		$unit = GETPOST('unit', 'alpha');
		$occurrences = GETPOST('occurrences', 'int');

		if (empty($fk_actioncomm) || empty($baseDate) || empty($frequency) || empty($unit) || empty($occurrences)) {
			$response['message'] = $langs->trans('MissingParameters');
			break;
		}

		// Validate unit
		if (!in_array($unit, array('week', 'month', 'year'))) {
			$response['message'] = $langs->trans('InvalidParameter');
			break;
		}

		// Limit occurrences to a reasonable number
		if ($occurrences < 1 || $occurrences > 100) {
			$response['message'] = $langs->trans('WhatsappScheduleMaxOccurrences');
			break;
		}

		// Build base timestamp
		$timeStr = $baseTime ? $baseTime : '09:00';
		$dateParts = explode('-', $baseDate);
		$timeParts = explode(':', $timeStr);
		if (count($dateParts) != 3 || count($timeParts) < 2) {
			$response['message'] = $langs->trans('InvalidParameter');
			break;
		}
		$baseTimestamp = dol_mktime((int)$timeParts[0], (int)$timeParts[1], 0, (int)$dateParts[1], (int)$dateParts[2], (int)$dateParts[0]);
		if (!$baseTimestamp) {
			$response['message'] = $langs->trans('InvalidParameter');
			break;
		}

		// First, delete existing pending schedules for this actioncomm
		$scheduleObj = new ReminderSchedule($db);
		$scheduleObj->deletePendingByActionComm($fk_actioncomm);

		// Generate new schedules
		$result = $scheduleObj->generateSchedules($fk_actioncomm, $baseTimestamp, $frequency, $unit, $occurrences, $user);
		if ($result > 0) {
			$response['status'] = 'success';
			$response['message'] = $langs->trans('WhatsappScheduleGenerated', $result);
			$response['count'] = $result;
			// Return the generated list
			$response['schedules'] = getScheduleListHtml($db, $fk_actioncomm, $langs);
		} else {
			$response['message'] = $scheduleObj->error;
		}
		break;

	case 'toggle':
		$scheduleId = GETPOST('schedule_id', 'int');
		if (empty($scheduleId)) {
			$response['message'] = $langs->trans('MissingParameters');
			break;
		}

		$scheduleObj = new ReminderSchedule($db);
		$res = $scheduleObj->fetch($scheduleId);
		if ($res > 0) {
			// Only toggle between disabled and pending
			if ($scheduleObj->status == ReminderSchedule::STATUS_DISABLED) {
				$newStatus = ReminderSchedule::STATUS_PENDING;
			} elseif ($scheduleObj->status == ReminderSchedule::STATUS_PENDING) {
				$newStatus = ReminderSchedule::STATUS_DISABLED;
			} else {
				$response['message'] = $langs->trans('WhatsappScheduleCannotToggle');
				break;
			}
			$result = $scheduleObj->updateStatus($newStatus);
			if ($result > 0) {
				$response['status'] = 'success';
				$response['new_status'] = $newStatus;
				$response['schedules'] = getScheduleListHtml($db, $scheduleObj->fk_actioncomm, $langs);
			} else {
				$response['message'] = $scheduleObj->error;
			}
		} else {
			$response['message'] = $langs->trans('WhatsappScheduleNotFound');
		}
		break;

	case 'delete_schedule':
		$scheduleId = GETPOST('schedule_id', 'int');
		if (empty($scheduleId)) {
			$response['message'] = $langs->trans('MissingParameters');
			break;
		}

		$scheduleObj = new ReminderSchedule($db);
		$res = $scheduleObj->fetch($scheduleId);
		if ($res > 0) {
			// Only allow deleting pending or disabled entries
			if ($scheduleObj->status == ReminderSchedule::STATUS_PENDING || $scheduleObj->status == ReminderSchedule::STATUS_DISABLED) {
				$result = $scheduleObj->delete();
				if ($result > 0) {
					$response['status'] = 'success';
					$response['schedules'] = getScheduleListHtml($db, $scheduleObj->fk_actioncomm, $langs);
				} else {
					$response['message'] = $scheduleObj->error;
				}
			} else {
				$response['message'] = $langs->trans('WhatsappScheduleCannotDelete');
			}
		} else {
			$response['message'] = $langs->trans('WhatsappScheduleNotFound');
		}
		break;

	case 'add_single':
		$singleDate = GETPOST('single_date', 'alpha');
		$singleTime = GETPOST('single_time', 'alpha');

		if (empty($fk_actioncomm) || empty($singleDate)) {
			$response['message'] = $langs->trans('MissingParameters');
			break;
		}

		$timeStr = $singleTime ? $singleTime : '09:00';
		$dateParts = explode('-', $singleDate);
		$timeParts = explode(':', $timeStr);
		if (count($dateParts) != 3 || count($timeParts) < 2) {
			$response['message'] = $langs->trans('InvalidParameter');
			break;
		}
		$singleTimestamp = dol_mktime((int)$timeParts[0], (int)$timeParts[1], 0, (int)$dateParts[1], (int)$dateParts[2], (int)$dateParts[0]);
		if (!$singleTimestamp) {
			$response['message'] = $langs->trans('InvalidParameter');
			break;
		}

		$scheduleObj = new ReminderSchedule($db);
		$scheduleObj->fk_actioncomm = $fk_actioncomm;
		$scheduleObj->reminder_date = $singleTimestamp;
		$scheduleObj->status = ReminderSchedule::STATUS_PENDING;

		$result = $scheduleObj->create($user);
		if ($result > 0) {
			$response['status'] = 'success';
			$response['message'] = $langs->trans('WhatsappScheduleDateAdded');
			$response['schedules'] = getScheduleListHtml($db, $fk_actioncomm, $langs);
		} else {
			$response['message'] = $scheduleObj->error;
		}
		break;

	case 'get_list':
		if (empty($fk_actioncomm)) {
			$response['message'] = $langs->trans('MissingParameters');
			break;
		}
		$response['status'] = 'success';
		$response['schedules'] = getScheduleListHtml($db, $fk_actioncomm, $langs);
		break;

	default:
		$response['message'] = 'Unknown action';
		break;
}

echo json_encode($response);
exit;


/**
 * Generate HTML for the schedule list table
 *
 * @param DoliDB    $db             Database handler
 * @param int       $fk_actioncomm  ActionComm ID
 * @param Translate $langs          Translations
 * @return string HTML
 */
function getScheduleListHtml($db, $fk_actioncomm, $langs)
{
	$scheduleObj = new ReminderSchedule($db);
	$schedules = $scheduleObj->fetchAllByActionComm($fk_actioncomm);

	if (!is_array($schedules) || count($schedules) == 0) {
		return '<div class="opacitymedium">' . $langs->trans('WhatsappNoSchedules') . '</div>';
	}

	$html = '<table class="noborder centpercent wa-schedule-table">';
	$html .= '<thead><tr class="liste_titre">';
	$html .= '<th>' . $langs->trans('Date') . '</th>';
	$html .= '<th>' . $langs->trans('Time') . '</th>';
	$html .= '<th class="center">' . $langs->trans('Status') . '</th>';
	$html .= '<th class="center">' . $langs->trans('WhatsappScheduleDateSent') . '</th>';
	$html .= '<th class="center">' . $langs->trans('Actions') . '</th>';
	$html .= '</tr></thead>';
	$html .= '<tbody>';

	$now = dol_now();
	foreach ($schedules as $sched) {
		$statusClass = ReminderSchedule::getStatusBadgeClass($sched->status);
		$statusLabel = ReminderSchedule::getStatusLabel($sched->status);
		$isOverdue = ($sched->status == ReminderSchedule::STATUS_PENDING && $sched->reminder_date < $now);
		$canModify = ($sched->status == ReminderSchedule::STATUS_PENDING || $sched->status == ReminderSchedule::STATUS_DISABLED);

		$html .= '<tr class="oddeven' . ($isOverdue ? ' wa-schedule-overdue' : '') . '">';
		$html .= '<td>' . dol_print_date($sched->reminder_date, 'day') . '</td>';
		$html .= '<td>' . dol_print_date($sched->reminder_date, 'hour') . '</td>';
		$html .= '<td class="center">';
		if ($isOverdue) {
			$html .= '<span class="badge badge-status8"><i class="fas fa-clock" style="margin-right:3px"></i>' . $langs->trans('WhatsappScheduleOverdue') . '</span>';
		} else {
			$html .= '<span class="' . $statusClass . '">' . $statusLabel . '</span>';
		}
		$html .= '</td>';
		$html .= '<td class="center">' . ($sched->date_sent ? dol_print_date($sched->date_sent, 'dayhour') : '-') . '</td>';
		$html .= '<td class="center nowraponall">';

		if ($canModify) {
			$toggleIcon = ($sched->status == ReminderSchedule::STATUS_PENDING) ? 'fa-pause' : 'fa-play';
			$toggleTitle = ($sched->status == ReminderSchedule::STATUS_PENDING) ? $langs->trans('Disable') : $langs->trans('Enable');
			$html .= '<a class="wa-schedule-toggle reposition" href="javascript:;" data-id="' . $sched->id . '" title="' . $toggleTitle . '">';
			$html .= '<i class="fas ' . $toggleIcon . ' paddingright"></i>';
			$html .= '</a> ';
			$html .= '<a class="wa-schedule-delete reposition" href="javascript:;" data-id="' . $sched->id . '" title="' . $langs->trans('Delete') . '">';
			$html .= '<i class="fas fa-trash-alt pictodelete"></i>';
			$html .= '</a>';
		} elseif ($sched->status == ReminderSchedule::STATUS_ERROR) {
			$html .= '<span class="small" title="' . dol_escape_htmltag($sched->error_msg) . '"><i class="fas fa-exclamation-triangle warning"></i></span>';
		} else {
			$html .= '<i class="fas fa-check-circle" style="color:#28a745"></i>';
		}

		$html .= '</td>';
		$html .= '</tr>';
	}

	$html .= '</tbody></table>';
	return $html;
}
