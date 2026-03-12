<?php
/**
 * WhatsApp Recurrence Designer - Edit/Create mode template
 *
 * Expected variables:
 * @var bool       $isWhatsappType  Whether the current event type is AC_SEND_WHATSAPP_NOTIFY
 * @var array      $existingDates   Array of date strings (Y-m-d\TH:i) for editable (PENDING/DISABLED) schedules
 * @var array      $sentDates       Array of ReminderSchedule objects with SENT/ERROR status
 * @var int        $dateCount       Count of editable dates
 * @var Translate  $langs           Dolibarr language object
 */

// Protection to avoid direct call of template
if (!defined('NOTOKENRENEWAL')) {
	// Check we're included not called directly
	if (!isset($langs) || !is_object($langs)) {
		print "Error: template can't be called as URL";
		exit;
	}
}

// Close the current table from card.php form to render our designer outside it
print '</table>';
print "\n" . '<!-- WhatsApp Recurrence Designer (edit mode) -->' . "\n";
print '<div id="wa-recurrence-form" class="wa-recurrence-designer" style="' . ($isWhatsappType ? '' : 'display:none') . '">';
print '<br><hr><br>';
print '<div class="titre inline-block">';
print '<i class="fab fa-whatsapp" style="color:#25D366"></i> ';
print $langs->trans('WhatsappScheduleTitle');
print ' <span class="badge badge-info" id="wa_dates_count">' . $dateCount . '</span>';
print '</div>';

// --- Recurrence Generator ---
print '<div class="wa-recurrence-form fichecenter">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td colspan="7">' . $langs->trans('WhatsappScheduleDesigner') . '</td>';
print '</tr>';
print '<tr class="oddeven">';

// Base date
print '<td class="nowraponall">' . $langs->trans('WhatsappScheduleBaseDate') . '</td>';
print '<td>';
print '<input type="date" id="wa_gen_base_date" class="flat minwidth100">';
print '</td>';

// Base time
print '<td class="nowraponall">' . $langs->trans('WhatsappScheduleBaseTime') . '</td>';
print '<td>';
print '<input type="time" id="wa_gen_base_time" class="flat" value="09:00">';
print '</td>';

// Frequency + Unit
print '<td class="nowraponall">' . $langs->trans('WhatsappScheduleRepeatEvery') . '</td>';
print '<td class="nowraponall">';
print '<input type="number" id="wa_gen_frequency" class="flat" min="1" max="99" value="1" style="width:50px"> ';
print '<select id="wa_gen_unit" class="flat">';
print '<option value="week">' . $langs->trans('WhatsappScheduleWeeks') . '</option>';
print '<option value="month">' . $langs->trans('WhatsappScheduleMonths') . '</option>';
print '<option value="year" selected>' . $langs->trans('WhatsappScheduleYears') . '</option>';
print '</select>';
print '</td>';

// Occurrences + Generate button
print '<td class="nowraponall">';
print '&times; <input type="number" id="wa_gen_occurrences" class="flat" min="1" max="100" value="3" style="width:50px"> ';
print '<button type="button" id="wa_btn_generate" class="button small">';
print '<i class="fas fa-sync-alt"></i> ' . $langs->trans('WhatsappScheduleGenerate');
print '</button>';
print '</td>';

print '</tr>';
print '</table>';
print '</div>';

// --- Editable Dates List ---
print '<div class="fichecenter" style="margin-top:8px">';
print '<table class="noborder centpercent" id="wa_dates_table">';
print '<thead><tr class="liste_titre">';
print '<td style="width:40px">#</td>';
print '<td>' . $langs->trans('WhatsappScheduleDatesList') . '</td>';
print '<td class="center" style="width:100px">' . $langs->trans('Status') . '</td>';
print '<td style="width:40px"></td>';
print '</tr></thead>';

// Show sent/error schedules as read-only (non-deletable)
if (!empty($sentDates)) {
	foreach ($sentDates as $sentIdx => $sentSched) {
		$sentStatusClass = ReminderSchedule::getStatusBadgeClass($sentSched->status);
		$sentStatusLabel = ReminderSchedule::getStatusLabel($sentSched->status);
		print '<tr class="oddeven" style="opacity:0.7">';
		print '<td>' . ($sentIdx + 1) . '</td>';
		print '<td>';
		print '<i class="fas fa-lock" style="color:#999;margin-right:5px" title="' . dol_escape_htmltag($langs->trans('WhatsappScheduleSentLocked')) . '"></i>';
		print dol_print_date($sentSched->reminder_date, 'dayhour');
		if ($sentSched->date_sent) {
			print ' <span class="opacitymedium small">(' . $langs->trans('WhatsappScheduleDateSent') . ': ' . dol_print_date($sentSched->date_sent, 'dayhour') . ')</span>';
		}
		print '</td>';
		print '<td class="center"><span class="' . $sentStatusClass . '">' . $sentStatusLabel . '</span></td>';
		print '<td></td>';
		print '</tr>';
	}
}

print '<tbody id="wa_dates_list">';

// Pre-populate with existing editable dates
if (!empty($existingDates)) {
	$nowTs = dol_now();
	foreach ($existingDates as $idx => $dateVal) {
		$dateTs = strtotime($dateVal);
		$isOverdue = ($dateTs && $dateTs < $nowTs);
		if ($isOverdue) {
			$badgeHtml = '<span class="badge badge-status8"><i class="fas fa-clock" style="margin-right:3px"></i>' . $langs->trans('WhatsappScheduleOverdue') . '</span>';
		} else {
			$badgeHtml = '<span class="badge badge-status1">' . $langs->trans('WhatsappSchedulePending') . '</span>';
		}
		print '<tr class="oddeven wa-date-row' . ($isOverdue ? ' wa-schedule-overdue' : '') . '">';
		print '<td class="wa-date-num">' . ($idx + 1) . '</td>';
		print '<td><input type="datetime-local" name="wa_schedule_dates[]" class="flat minwidth200" value="' . dol_escape_htmltag($dateVal) . '"></td>';
		print '<td class="center wa-date-status">' . $badgeHtml . '</td>';
		print '<td class="center"><a class="wa-remove-date reposition" title="' . dol_escape_htmltag($langs->trans('Delete')) . '"><i class="fas fa-times-circle" style="color:#c44;cursor:pointer"></i></a></td>';
		print '</tr>';
	}
}

print '</tbody>';
print '</table>';

// Add manual date button
print '<div style="padding:5px 0">';
print '<button type="button" id="wa_btn_add_date" class="button small">';
print '<i class="fas fa-plus"></i> ' . $langs->trans('WhatsappScheduleAddSingle');
print '</button>';
print '</div>';

print '</div>';

// Info note
print '<div class="opacitymedium small" style="padding:5px 0">';
print '<i class="fas fa-info-circle"></i> ' . $langs->trans('WhatsappScheduleCreateNote');
print '</div>';

print '</div>'; // End wa-recurrence-form

// Reopen table for any remaining fields from other hooks/extrafields
print '<table class="border tableforfield centpercent">';

// JavaScript
print '
<script>
$(function() {
	var $designer = $("#wa-recurrence-form");
	var $list = $("#wa_dates_list");
	var lblConfirm = ' . json_encode($langs->trans('Confirm')) . ';
	var lblCancel = ' . json_encode($langs->trans('Cancel')) . ';
	var lblClose = ' . json_encode($langs->trans('Close')) . ';
	var lblSelectDate = ' . json_encode($langs->trans('WhatsappScheduleSelectDate')) . ';
	var lblConfirmGenerate = ' . json_encode($langs->trans('WhatsappScheduleConfirmGenerate')) . ';

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

	// Show/hide based on event type selector
	function toggleWaDesigner() {
		var val = "";
		var $sel = $("#actioncode, select[name=actioncode]");
		var $hidden = $("input[type=hidden][name=actioncode]");
		if ($sel.length) val = $sel.val();
		else if ($hidden.length) val = $hidden.val();
		if (val === "AC_SEND_WHATSAPP_NOTIFY") {
			$designer.slideDown(200);
		} else {
			$designer.slideUp(200);
		}
	}

	$("#actioncode, select[name=actioncode]").on("change", toggleWaDesigner);

	// Renumber rows and update badge
	function renumberRows() {
		$list.find("tr.wa-date-row").each(function(i) {
			$(this).find(".wa-date-num").text(i + 1);
		});
		$("#wa_dates_count").text($list.find("tr.wa-date-row").length);
	}

	var lblPending = ' . json_encode($langs->trans('WhatsappSchedulePending')) . ';
	var lblOverdue = ' . json_encode($langs->trans('WhatsappScheduleOverdue')) . ';

	// Get status badge HTML based on date value
	function getStatusBadge(dateValue) {
		if (dateValue) {
			var d = new Date(dateValue);
			if (!isNaN(d.getTime()) && d < new Date()) {
				return \'<span class="badge badge-status8"><i class="fas fa-clock" style="margin-right:3px"></i>\' + lblOverdue + \'</span>\';
			}
		}
		return \'<span class="badge badge-status1">\' + lblPending + \'</span>\';
	}

	// Add a date row with optional value
	function addDateRow(value) {
		var idx = $list.find("tr.wa-date-row").length + 1;
		var isOverdue = false;
		if (value) { var d = new Date(value); isOverdue = (!isNaN(d.getTime()) && d < new Date()); }
		var html = \'<tr class="oddeven wa-date-row\' + (isOverdue ? \' wa-schedule-overdue\' : \'\') + \'">\' +
			\'<td class="wa-date-num">\' + idx + \'</td>\' +
			\'<td><input type="datetime-local" name="wa_schedule_dates[]" class="flat minwidth200" value="\' + (value || "") + \'"></td>\' +
			\'<td class="center wa-date-status">\' + getStatusBadge(value) + \'</td>\' +
			\'<td class="center"><a class="wa-remove-date reposition" title="' . dol_escape_js($langs->trans('Delete')) . '"><i class="fas fa-times-circle" style="color:#c44;cursor:pointer"></i></a></td>\' +
			\'</tr>\';
		$list.append(html);
		renumberRows();
	}

	// Update status badge when date input changes
	$list.on("change", "input[type=datetime-local]", function() {
		var $row = $(this).closest("tr");
		var val = $(this).val();
		$row.find(".wa-date-status").html(getStatusBadge(val));
		if (val) { var d = new Date(val); $row.toggleClass("wa-schedule-overdue", !isNaN(d.getTime()) && d < new Date()); }
		else { $row.removeClass("wa-schedule-overdue"); }
	});

	// Remove date row
	$list.on("click", ".wa-remove-date", function(e) {
		e.preventDefault();
		$(this).closest("tr").fadeOut(150, function() {
			$(this).remove();
			renumberRows();
		});
	});

	// Add manual date
	$("#wa_btn_add_date").on("click", function() {
		addDateRow("");
	});

	// Generate dates from recurrence params
	$("#wa_btn_generate").on("click", function() {
		var baseDate = $("#wa_gen_base_date").val();
		var baseTime = $("#wa_gen_base_time").val() || "09:00";
		var frequency = parseInt($("#wa_gen_frequency").val()) || 1;
		var unit = $("#wa_gen_unit").val();
		var occurrences = parseInt($("#wa_gen_occurrences").val()) || 1;

		if (!baseDate) {
			waAlert(lblSelectDate);
			return;
		}

		function doGenerate() {
			// Clear existing list
			$list.empty();

			// Generate dates
			var base = new Date(baseDate + "T" + baseTime + ":00");
			var pad = function(n) { return n < 10 ? "0" + n : n; };
			for (var i = 0; i < Math.min(occurrences, 100); i++) {
				var d = new Date(base.getTime());
				if (unit === "week") d.setDate(d.getDate() + (frequency * i * 7));
				else if (unit === "month") d.setMonth(d.getMonth() + (frequency * i));
				else if (unit === "year") d.setFullYear(d.getFullYear() + (frequency * i));
				var val = d.getFullYear() + "-" + pad(d.getMonth()+1) + "-" + pad(d.getDate()) + "T" + pad(d.getHours()) + ":" + pad(d.getMinutes());
				addDateRow(val);
			}
		}

		// Confirm if list already has items
		if ($list.find("tr.wa-date-row").length > 0) {
			waConfirm(lblConfirmGenerate, doGenerate);
		} else {
			doGenerate();
		}
	});

	renumberRows();
});
</script>';
