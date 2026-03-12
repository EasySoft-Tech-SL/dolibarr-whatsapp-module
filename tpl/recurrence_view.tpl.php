<?php
/**
 * WhatsApp Recurrence Designer - View mode template (AJAX-based)
 *
 * Expected variables:
 * @var ActionComm       $object         The current event object
 * @var array            $schedules      Array of ReminderSchedule objects
 * @var int              $scheduleCount  Count of schedules
 * @var string           $baseDateValue  Base date value from extrafield
 * @var string           $ajaxUrl        URL to the AJAX endpoint
 * @var Translate        $langs          Dolibarr language object
 */

// Protection to avoid direct call of template
if (!defined('NOTOKENRENEWAL')) {
	if (!isset($langs) || !is_object($langs)) {
		print "Error: template can't be called as URL";
		exit;
	}
}

// Close the extrafields table, render designer, reopen table
print '</table>';
print "\n" . '<!-- WhatsApp Recurrence Designer (view mode) -->' . "\n";
print '<div class="wa-recurrence-designer">';
print '<div class="titre inline-block">';
print '<i class="fab fa-whatsapp" style="color:#25D366"></i> ';
print $langs->trans('WhatsappScheduleTitle');
print ' <span class="badge">' . $scheduleCount . '</span>';
print '</div>';

// Designer form for generating/adding
print '<div class="wa-recurrence-form fichecenter">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td colspan="6">' . $langs->trans('WhatsappScheduleDesigner') . '</td>';
print '</tr>';
print '<tr class="oddeven">';

// Base date
print '<td class="fieldrequired nowraponall">' . $langs->trans('WhatsappScheduleBaseDate') . '</td>';
print '<td>';
print '<input type="date" id="wa_base_date" class="flat minwidth100" value="' . ($baseDateValue ? dol_print_date($baseDateValue, '%Y-%m-%d') : '') . '">';
print '</td>';

// Base time
print '<td class="nowraponall">' . $langs->trans('WhatsappScheduleBaseTime') . '</td>';
print '<td>';
print '<input type="time" id="wa_base_time" class="flat" value="' . ($baseDateValue ? dol_print_date($baseDateValue, '%H:%M') : '09:00') . '">';
print '</td>';

print '<td colspan="2"></td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td class="fieldrequired nowraponall">' . $langs->trans('WhatsappScheduleRepeatEvery') . '</td>';
print '<td>';
print '<input type="number" id="wa_frequency" class="flat" min="1" max="99" value="1" style="width:60px">';
print '</td>';

print '<td class="fieldrequired">' . $langs->trans('WhatsappScheduleUnit') . '</td>';
print '<td>';
print '<select id="wa_unit" class="flat minwidth100">';
print '<option value="week">' . $langs->trans('WhatsappScheduleWeeks') . '</option>';
print '<option value="month">' . $langs->trans('WhatsappScheduleMonths') . '</option>';
print '<option value="year" selected>' . $langs->trans('WhatsappScheduleYears') . '</option>';
print '</select>';
print '</td>';

print '<td class="fieldrequired nowraponall">' . $langs->trans('WhatsappScheduleOccurrences') . '</td>';
print '<td>';
print '<input type="number" id="wa_occurrences" class="flat" min="1" max="100" value="3" style="width:60px">';
print '</td>';
print '</tr>';

// Preview row
print '<tr class="oddeven">';
print '<td>' . $langs->trans('WhatsappSchedulePreview') . '</td>';
print '<td colspan="4">';
print '<div id="wa_preview_dates" class="small opacitymedium" style="max-height:120px;overflow-y:auto;"></div>';
print '</td>';
print '<td class="right">';
print '<button type="button" id="wa_btn_generate" class="butAction small">' . $langs->trans('WhatsappScheduleGenerate') . '</button>';
print '</td>';
print '</tr>';

// Add single date
print '<tr class="oddeven">';
print '<td>' . $langs->trans('WhatsappScheduleAddSingle') . '</td>';
print '<td>';
print '<input type="date" id="wa_single_date" class="flat minwidth100">';
print '</td>';
print '<td></td>';
print '<td>';
print '<input type="time" id="wa_single_time" class="flat" value="09:00">';
print '</td>';
print '<td colspan="2" class="right">';
print '<button type="button" id="wa_btn_add_single" class="butAction small">' . $langs->trans('Add') . '</button>';
print '</td>';
print '</tr>';

print '</table>';
print '</div>';

// Schedule list
print '<div id="wa_schedule_list" class="wa-schedule-list">';
if ($scheduleCount > 0) {
	$html = '<table class="noborder centpercent wa-schedule-table">';
	$html .= '<thead><tr class="liste_titre">';
	$html .= '<th>' . $langs->trans('Date') . '</th>';
	$html .= '<th>' . $langs->trans('Time') . '</th>';
	$html .= '<th class="center">' . $langs->trans('Status') . '</th>';
	$html .= '<th class="center">' . $langs->trans('WhatsappScheduleDateSent') . '</th>';
	$html .= '<th class="center">' . $langs->trans('Actions') . '</th>';
	$html .= '</tr></thead><tbody>';
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
			$html .= '<a class="wa-schedule-toggle reposition" href="javascript:;" data-id="' . $sched->id . '" title="' . $toggleTitle . '"><i class="fas ' . $toggleIcon . ' paddingright"></i></a> ';
			$html .= '<a class="wa-schedule-delete reposition" href="javascript:;" data-id="' . $sched->id . '" title="' . $langs->trans('Delete') . '"><i class="fas fa-trash-alt pictodelete"></i></a>';
		} elseif ($sched->status == ReminderSchedule::STATUS_ERROR) {
			$html .= '<span class="small" title="' . dol_escape_htmltag($sched->error_msg) . '"><i class="fas fa-exclamation-triangle warning"></i></span>';
		} else {
			$html .= '<i class="fas fa-check-circle" style="color:#28a745"></i>';
		}
		$html .= '</td></tr>';
	}
	$html .= '</tbody></table>';
	print $html;
} else {
	print '<div class="opacitymedium">' . $langs->trans('WhatsappNoSchedules') . '</div>';
}
print '</div>';

print '</div>'; // End wa-recurrence-designer

// Reopen table for remaining extrafields
print '<table class="border tableforfield centpercent">';

// JavaScript for AJAX designer
print '
<script>
$(function() {
	var ajaxUrl = ' . json_encode($ajaxUrl) . ';
	var fkActioncomm = ' . ((int) $object->id) . ';
	var token = ' . json_encode(newToken()) . ';
	var lblConfirm = ' . json_encode($langs->trans('Confirm')) . ';
	var lblCancel = ' . json_encode($langs->trans('Cancel')) . ';
	var lblClose = ' . json_encode($langs->trans('Close')) . ';
	var lblSelectDate = ' . json_encode($langs->trans('WhatsappScheduleSelectDate')) . ';
	var lblConfirmGenerate = ' . json_encode($langs->trans('WhatsappScheduleConfirmGenerate')) . ';
	var lblConfirmDelete = ' . json_encode($langs->trans('WhatsappScheduleConfirmDelete')) . ';
	var lblGenerate = ' . json_encode($langs->trans('WhatsappScheduleGenerate')) . ';
	var lblConnectionError = ' . json_encode($langs->trans('WhatsappConnectionError')) . ';

	// jQuery UI confirmation dialog
	function waConfirm(message, onConfirm) {
		$("<div>").html(message).dialog({
			title: lblConfirm,
			resizable: false,
			modal: true,
			dialogClass: "wa-dialog",
			buttons: [
				{ text: lblConfirm, "class": "button", click: function() { $(this).dialog("close"); onConfirm(); } },
				{ text: lblCancel, "class": "button", click: function() { $(this).dialog("close"); } }
			],
			close: function() { $(this).dialog("destroy").remove(); }
		});
	}

	// jQuery UI alert dialog
	function waAlert(message) {
		$("<div>").html(message).dialog({
			title: "Info",
			resizable: false,
			modal: true,
			dialogClass: "wa-dialog",
			buttons: [{ text: lblClose, "class": "button", click: function() { $(this).dialog("close"); } }],
			close: function() { $(this).dialog("destroy").remove(); }
		});
	}

	// Preview dates
	function updatePreview() {
		var baseDate = $("#wa_base_date").val();
		var baseTime = $("#wa_base_time").val() || "09:00";
		var frequency = parseInt($("#wa_frequency").val()) || 1;
		var unit = $("#wa_unit").val();
		var occurrences = parseInt($("#wa_occurrences").val()) || 1;

		if (!baseDate) {
			$("#wa_preview_dates").html("<em>' . dol_escape_js($langs->trans('WhatsappScheduleSelectDate')) . '</em>");
			return;
		}

		var dates = [];
		var base = new Date(baseDate + "T" + baseTime + ":00");
		for (var i = 0; i < Math.min(occurrences, 100); i++) {
			var d = new Date(base.getTime());
			if (unit === "week") d.setDate(d.getDate() + (frequency * i * 7));
			else if (unit === "month") d.setMonth(d.getMonth() + (frequency * i));
			else if (unit === "year") d.setFullYear(d.getFullYear() + (frequency * i));
			var pad = function(n) { return n < 10 ? "0" + n : n; };
			dates.push(pad(d.getDate()) + "/" + pad(d.getMonth()+1) + "/" + d.getFullYear() + " " + pad(d.getHours()) + ":" + pad(d.getMinutes()));
		}
		var html = "<ol style=\"margin:0;padding-left:20px\">";
		for (var j = 0; j < dates.length; j++) html += "<li>" + dates[j] + "</li>";
		html += "</ol>";
		$("#wa_preview_dates").html(html);
	}

	$("#wa_base_date, #wa_base_time, #wa_frequency, #wa_unit, #wa_occurrences").on("change input", updatePreview);
	updatePreview();

	// Generate schedules via AJAX
	$("#wa_btn_generate").on("click", function() {
		var baseDate = $("#wa_base_date").val();
		if (!baseDate) { waAlert(lblSelectDate); return; }
		var btn = $(this);
		waConfirm(lblConfirmGenerate, function() {
			btn.prop("disabled", true).text("...");
			$.post(ajaxUrl, {
				action: "generate", fk_actioncomm: fkActioncomm,
				base_date: baseDate, base_time: $("#wa_base_time").val(),
				frequency: $("#wa_frequency").val(), unit: $("#wa_unit").val(),
				occurrences: $("#wa_occurrences").val(), token: token
			}, function(data) {
				btn.prop("disabled", false).text(lblGenerate);
				if (data.status === "success") { $("#wa_schedule_list").html(data.schedules); bindScheduleActions(); }
				else waAlert(data.message || "Error");
			}, "json").fail(function() { btn.prop("disabled", false).text(lblGenerate); waAlert(lblConnectionError); });
		});
	});

	// Add single date via AJAX
	$("#wa_btn_add_single").on("click", function() {
		var singleDate = $("#wa_single_date").val();
		if (!singleDate) { waAlert(lblSelectDate); return; }
		var btn = $(this);
		btn.prop("disabled", true);
		$.post(ajaxUrl, {
			action: "add_single", fk_actioncomm: fkActioncomm,
			single_date: singleDate, single_time: $("#wa_single_time").val(), token: token
		}, function(data) {
			btn.prop("disabled", false);
			if (data.status === "success") { $("#wa_schedule_list").html(data.schedules); $("#wa_single_date").val(""); bindScheduleActions(); }
			else waAlert(data.message || "Error");
		}, "json").fail(function() { btn.prop("disabled", false); waAlert(lblConnectionError); });
	});

	// Bind toggle/delete
	function bindScheduleActions() {
		$(".wa-schedule-toggle").off("click").on("click", function() {
			var id = $(this).data("id");
			$.post(ajaxUrl, { action: "toggle", schedule_id: id, token: token }, function(data) {
				if (data.status === "success") { $("#wa_schedule_list").html(data.schedules); bindScheduleActions(); }
				else waAlert(data.message || "Error");
			}, "json");
		});
		$(".wa-schedule-delete").off("click").on("click", function() {
			var el = this;
			var id = $(el).data("id");
			waConfirm(lblConfirmDelete, function() {
				$.post(ajaxUrl, { action: "delete_schedule", schedule_id: id, token: token }, function(data) {
					if (data.status === "success") { $("#wa_schedule_list").html(data.schedules); bindScheduleActions(); }
					else waAlert(data.message || "Error");
				}, "json");
			});
		});
	}
	bindScheduleActions();
});
</script>';
