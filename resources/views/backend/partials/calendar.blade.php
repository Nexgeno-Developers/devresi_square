{{-- resources/views/partials/calendar.blade.php --}}
<div id="calendar"></div>
@include('backend.partials._calendar_modals')
{{-- Include the partial to push Select2 assets into the stacks --}}
@include('backend.partials.assets.select2')
@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.0/main.min.css" rel="stylesheet" />
@endpush

@push('scripts')
    <!-- 1) FullCalendar & Moment -->
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.0/main.min.js"></script>

    <!-- 2) rrule via Skypack -->
    <script type="module">
        import { RRule } from 'https://cdn.skypack.dev/rrule';
        window.RRule = RRule;
        // console.log('✅ rrule loaded via Skypack:', typeof RRule);
    </script>
    <script>
        function initEntitySelect($sel) {
            const mode = $sel.data("mode") || "single";
            const max = parseInt($sel.data("max"), 10) || 1;
            const ajaxUrl = $sel.data("url");

            if ($sel.hasClass("select2-hidden-accessible")) {
                $sel.select2("destroy");
            }

            if (mode === "multi") {
                $sel.attr("multiple", "multiple");
                
                // Only set name if it's not already defined
                if (!$sel.attr("name")) {
                    $sel.prop("name", `${$sel.data("entity")}_ids[]`);
                }
            } else {
                $sel.removeAttr("multiple");
                
                // Only set name if it's not already defined
                if (!$sel.attr("name")) {
                    $sel.prop("name", `${$sel.data("entity")}_id`);
                }
            }

            $sel.select2({
                dropdownParent: $('#eventModal'),
                placeholder: mode === "multi"
                    ? `Select up to ${max} ${$sel.data("entity")}s`
                    : `Select one ${$sel.data("entity")}`,
                allowClear: mode === "single",
                maximumSelectionLength: mode === "multi" ? max : 1,
                width: '100%',
                ajax: {
                    url: ajaxUrl,
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            q: params.term
                        };
                    },
                    processResults: function (data) {
                        return {
                            results: data.results
                        };
                    },
                    cache: true
                },
                minimumInputLength: 0
            });
        }

        function preselectSelect2($select, ids, items) {
            $select.empty();
            const lookup = (items || []).reduce((o, i) => {
                o[i.id] = i.text;
                return o;
            }, {});
            ids.forEach(id => {
                const label = lookup[id] || `ID #${id}`;
                const option = new Option(label, id, true, true);
                $select.append(option);
            });
            $select.trigger('change');
        }

        // Modal cleanup handler
        $(document).on('hidden.bs.modal', '.modal', function () {
            $(this).removeAttr('style');
            $('.stacked-backdrop').last().remove();
        });

        // Ensure modals stack correctly
        $(document).on('show.bs.modal', '.modal', function () {
            // Find current highest z-index of visible modals
            let maxZ = 1050; // Bootstrap default

            $('.modal.show').each(function () {
                const z = parseInt($(this).css('z-index'), 10);
                if (!isNaN(z) && z >= maxZ) {
                    maxZ = z;
                }
            });

            const modalZ = maxZ + 5;
            const backdropZ = maxZ + 4;

            // Set modal's z-index
            $(this).css('z-index', modalZ);

            // Wait for Bootstrap to insert the backdrop, then update it
            setTimeout(() => {
                // Only update the last backdrop
                $('.modal-backdrop:not(.stacked)').last().css('z-index', backdropZ).addClass('stacked');
            }, 0);
        });


        // Add a new reminder row:
        $('#addReminderBtn').on('click', function () {
            // 1. clone template
            const tpl = document.getElementById('reminderTpl').content.cloneNode(true);
            const $row = $(tpl).find('.reminder-row');

            // 2. compute next index from existing rows
            const idx = $('#reminderList .reminder-row').length;

            // 3. set correct name attributes
            $row.find('input')
                .attr('name', `reminders[${idx}][minutes_before]`)
                .val('');         // clear any default
            $row.find('select')
                .attr('name', `reminders[${idx}][channel]`)
                .val('email');    // or blank

            // 4. append to the list
            $('#reminderList').append($row);
        });

        // Remove a row:
        $('#reminderList').on('click', '.removeReminderBtn', function () {
            $(this).closest('.reminder-row').remove();
            // **optional** re-number the remaining rows so indexes stay sequential:
            $('#reminderList .reminder-row').each(function (i, el) {
                $(el).find('input')
                    .attr('name', `reminders[${i}][minutes_before]`);
                $(el).find('select')
                    .attr('name', `reminders[${i}][channel]`);
            });
        });

        // When editing an event, populate existing reminders:
        function loadExistingReminders(existingArray) {
            $('#reminderList').empty();
            existingArray.forEach((r, idx) => {
                $('#addReminderBtn').click();     // adds a new blank row
                const $last = $('#reminderList .reminder-row').last();
                $last.find('input').val(r.minutes_before);
                $last.find('select').val(r.channel);
            });
        }

        document.addEventListener('DOMContentLoaded', function () {

            if (typeof RRule === 'undefined') {
                console.error('RRule missing!');
                return;
            }

            // Shortcuts to elements:
            const $eventModal = $('#eventModal');
            const $rruleModal = $('#rruleModal');
            const $seriesChoiceModal = $('#seriesChoiceModal');
            const $freqSelect = $('#freqSelect');
            const $intervalInput = $('#intervalInput');
            const $intervalLabel = $('#intervalLabel');
            const $byDayC = $('#byDayContainer');
            const $byOrdinalC = $('#byOrdinalContainer');
            const $endType = $('#endTypeSelect');
            const $endAfterC = $('#endAfterContainer');
            const $endByDateC = $('#endByDateContainer');
            const $endAfterCnt = $('#endAfterCount');
            const $endByDate = $('#endByDateInput');
            const $exdateList = $('#exdateList');
            const $rruleInput = $('#rruleInput');
            const $exdatesInput = $('#exdatesInput');
            const $rruleSummary = $('#rruleSummary');

            // Utility: render the human‐readable summary in #rruleSummary
            function renderRRuleSummary(rruleString, exdatesArray) {
                if (!rruleString) {
                    $('#rruleSummary').text('No recurrence');
                    return;
                }
                try {
                    const rule = RRule.fromString(rruleString);
                    const human = rule.toText(); // e.g. “Every week on Monday, Wednesday until December 31, 2025”
                    $('#rruleSummary').text(human);
                }
                catch (e) {
                    $('#rruleSummary').text('Invalid recurrence rule');
                }
            }
            
            function onFrequencyChange() {
                const freq = document.getElementById('freqSelect').value;
                const label = document.getElementById('intervalLabel');
                const byDayC = document.getElementById('byDayContainer');
                const byOrdC = document.getElementById('byOrdinalContainer');

                let unit = 'day(s)';
                if (freq === 'WEEKLY') unit = 'week(s)';
                else if (freq === 'MONTHLY') unit = 'month(s)';
                else if (freq === 'YEARLY') unit = 'year(s)';
                label.textContent = unit;

                if (freq === 'WEEKLY') {
                    byDayC.classList.remove('d-none');
                } else {
                    byDayC.classList.add('d-none');
                    document.querySelectorAll('#byDayContainer input[type=checkbox]').forEach(cb => cb.checked = false);
                }

                if (freq === 'MONTHLY') {
                    byOrdC.classList.remove('d-none');
                } else {
                    byOrdC.classList.add('d-none');
                    document.getElementById('bySetPos').value = '1';
                    document.getElementById('byDayOrdinal').value = 'MO';
                }
            }

            // When the user clicks “Set Recurrence…”
            $('#editRRuleBtn').on('click', function () {
                // 1) If there is already an rrule string in the hidden <textarea>, parse it and fill the fields
                const existingRRule = $('#rruleInput').val().trim();
                const existingExdates = $('#exdatesInput').val().trim()
                    ? JSON.parse($('#exdatesInput').val())
                    : [];

                if (existingRRule) {
                    try {
                        const rule = RRule.fromString(existingRRule);
                        // Fill frequency & interval
                        $freqSelect.val(rule.options.freq === RRule.YEARLY ? 'YEARLY'
                            : rule.options.freq === RRule.MONTHLY ? 'MONTHLY'
                                : rule.options.freq === RRule.WEEKLY ? 'WEEKLY'
                                    : 'DAILY');
                        $intervalInput.val(rule.options.interval);

                        // Show/hide relevant sections
                        onFrequencyChange();

                        // For “WEEKLY” → check appropriate weekdays
                        if (rule.options.freq === RRule.WEEKLY && rule.options.byweekday) {
                            const days = rule.options.byweekday;
                            // rule.options.byweekday is an array of Weekday instances (e.g. [RRule.MO, RRule.WE])
                            $('input[type="checkbox"][id^="chk"]').prop('checked', false);
                            days.forEach(d => {
                                // day.weekday returns 0=MO,1=TU,…6=SU
                                const idMap = ['chkMO', 'chkTU', 'chkWE', 'chkTH', 'chkFR', 'chkSA', 'chkSU'];
                                const chkId = idMap[d.weekday];
                                $('#' + chkId).prop('checked', true);
                            });
                        }

                        // For “MONTHLY” → check if bymonthday (e.g. day 15) or bysetpos/byday
                        if (rule.options.freq === RRule.MONTHLY) {
                            if (rule.options.bymonthday) {
                                // You’d need another UI control to let user pick day-of-month directly
                                // (not shown above), e.g. <input type="number" id="bymonthday" min="1" max="31">
                                $('#bymonthday').val(rule.options.bymonthday[0]);
                                $('#bymonthdayContainer').show();
                                $byOrdinalC.addClass('d-none');
                            }
                            else if (rule.options.bysetpos && rule.options.byweekday) {
                                $byOrdinalC.removeClass('d-none');
                                $('#bymonthdayContainer').hide();
                                $('#bySetPos').val(rule.options.bysetpos[0]);        // e.g. 2 for “Second”
                                $('#byDayOrdinal').val(rule.options.byweekday[0].weekday); // e.g. “WE” → 2
                            }
                        }

                        // End conditions:
                        if (rule.options.count) {
                            $endType.val('AFTER');
                            $endAfterC.removeClass('d-none');
                            $endByDateC.addClass('d-none');
                            $endAfterCnt.val(rule.options.count);
                        }
                        else if (rule.options.until) {
                            $endType.val('BYDATE');
                            $endAfterC.addClass('d-none');
                            $endByDateC.removeClass('d-none');
                            // rule.options.until is a JS Date object → format to "YYYY-MM-DD"
                            const u = rule.options.until;
                            const y = u.getFullYear();
                            const m = String(u.getMonth() + 1).padStart(2, '0');
                            const d = String(u.getDate()).padStart(2, '0');
                            $endByDate.val(`${y}-${m}-${d}`);
                        }
                        else {
                            $endType.val('NEVER');
                            $endAfterC.addClass('d-none');
                            $endByDateC.addClass('d-none');
                        }
                    }
                    catch (e) {
                        console.warn('Failed to parse existing RRule:', e);
                    }
                }
                else {
                    // No existing rrule → reset UI
                    $freqSelect.val('DAILY');
                    $intervalInput.val(1);
                    onFrequencyChange();
                    $endType.val('NEVER');
                    $endAfterC.addClass('d-none');
                    $endByDateC.addClass('d-none');
                    $exdateList.empty();
                }

                // 2) Populate exdates UI
                $exdateList.empty();
                if (existingExdates.length) {
                    existingExdates.forEach(d => {
                        addExdateRow(d);
                    });
                }

                $rruleModal.modal('show');
            });

            $freqSelect.on('change', onFrequencyChange);

            // Show/hide end condition fields
            $endType.on('change', function () {
                const val = $(this).val();
                if (val === 'AFTER') {
                    $endAfterC.removeClass('d-none');
                    $endByDateC.addClass('d-none');
                } else if (val === 'BYDATE') {
                    $endAfterC.addClass('d-none');
                    $endByDateC.removeClass('d-none');
                } else {
                    $endAfterC.addClass('d-none');
                    $endByDateC.addClass('d-none');
                }
            });

            // Add a new Exclusion Date row
            $('#addExdateBtn').on('click', function () {
                addExdateRow();
            });

            function addExdateRow(initialValue = '') {
                const idx = $exdateList.children().length;
                const html = `<div class="input-group mb-2" data-idx="${idx}"><input type="date" class="form-control exdateInput" value="${initialValue}"><button class="btn btn-outline-danger removeExdateBtn" type="button">&times;</button></div>`;
                $exdateList.append(html);
            }

            // Remove a specific exdate row
            $exdateList.on('click', '.removeExdateBtn', function () {
                $(this).closest('.input-group').remove();
            });

            // 3) When user clicks “Save Recurrence”
            $('#saveRRuleBtn').on('click', function () {
                // Build options for RRule
                const freq = $freqSelect.val(); // DAILY, WEEKLY, MONTHLY, YEARLY
                const interval = parseInt($intervalInput.val()) || 1;
                const options = {
                    freq: RRule[freq],
                    interval: interval,
                };

                // If WEEKLY → collect byweekday
                if (freq === 'WEEKLY') {
                    const days = [];
                    $('input[id^="chk"]').each(function () {
                        if ($(this).prop('checked')) {
                            days.push(RRule[$(this).val()]);
                        }
                    });
                    if (days.length) {
                        options.byweekday = days;
                    }
                }

                // If MONTHLY and user picked an ordinal day
                if (freq === 'MONTHLY') {
                    const setpos = parseInt($('#bySetPos').val());     // e.g. 2
                    const bydayVal = $('#byDayOrdinal').val();         // e.g. "TU"
                    options.bysetpos = setpos;
                    options.byweekday = [RRule[bydayVal]];
                }

                // End conditions
                const endType = $endType.val();
                if (endType === 'AFTER') {
                    options.count = parseInt($endAfterCnt.val()) || 1;
                }
                else if (endType === 'BYDATE') {
                    const untilRaw = $endByDate.val(); // "YYYY-MM-DD"
                    if (untilRaw) {
                        // Convert to JS date at 23:59:59 local time
                        const ut = new Date(untilRaw + 'T23:59:59');
                        options.until = ut;
                    }
                }
                // else “NEVER” → we leave options.count & options.until undefined

                // Build the actual RRule
                let rruleString = '';
                try {
                    const rule = new RRule(options);
                    rruleString = rule.toString(); // e.g. "FREQ=WEEKLY;INTERVAL=1;BYDAY=MO,WE;COUNT=10"
                } catch (e) {
                    alert('Failed to build recurrence rule: ' + e);
                    return;
                }

                // Collect exdates from UI
                const exdates = [];
                $exdateList.find('.exdateInput').each(function () {
                    const val = $(this).val();
                    if (val) {
                        exdates.push(val); // e.g. "2025-07-04"
                    }
                });

                // Write them back to the hidden form fields
                $('#rruleInput').val(rruleString);
                $('#exdatesInput').val(JSON.stringify(exdates));

                // Also render the summary:
                renderRRuleSummary(rruleString, exdates);

                // Close the modal
                $rruleModal.modal('hide');
            });


            function updateEndMin() {
                const $start = $('input[name="start_datetime"]');
                const $end = $('input[name="end_datetime"]');
                const startVal = $start.val(); // e.g. "2025-06-05T09:00"

                if (!startVal) {
                    // If no start, clear any min restriction on End
                    $end.removeAttr('min');
                    return;
                }

                // Set the same string as min on End (so user cannot pick earlier)
                $end.attr('min', startVal);

                // If current End < Start, bump it to equal Start
                if ($end.val() && $end.val() < startVal) {
                    $end.val(startVal);
                }
                console.log('✏️[updateEndMin] Set min date for end_datetime:', startVal);
            }

            // 2) Fire when the start_datetime changes
            // Whenever the user edits Start Date & Time, re-apply the rule:
            $('input[name="start_datetime"]').on('change', function () {
                updateEndMin();
            });
            let pendingClickInfo = null;

            // 1) Wire up the list‐group items inside #seriesChoiceModal
            $('#seriesChoiceModal .action-item').on('click', function () {
                if (!pendingClickInfo) return;
                const info = pendingClickInfo;
                const action = $(this).data('action');
                $seriesChoiceModal.modal('hide');
                console.log('Event ID:', info.event.id);
                console.log('Master ID:', info.event.extendedProps.master_id);

                // Dispatch just like your old switch, but driven by `action`:
                switch (action) {
                    case 'edit-one':
                        openEventModal(info, 'single');
                        loadExistingReminders(info.event.extendedProps.reminders);
                        break;

                    case 'edit-all':
                        openEventModal(info, 'series');
                        loadExistingReminders(info.event.extendedProps.reminders);
                        break;

                    case 'edit-future':
                        openEventModal(info, 'future');
                        loadExistingReminders(info.event.extendedProps.reminders);
                        break;
                    case 'cancel-one':
                        if (confirm('Cancel only this occurrence?')) {
                            $.ajax({
                                url: '{{ route("backend.events.cancelInstance", "") }}/' + info.event.id,
                                method: 'POST',
                                data: {
                                    _token: '{{ csrf_token() }}',
                                    choice_action: 'single',
                                    occurrence_start: info.event.startStr
                                },
                                success() {
                                    calendar.refetchEvents();
                                },
                                error(xhr) {
                                    alert('Error cancelling the instance.');
                                    console.error(xhr.responseText);
                                }
                            });
                        }
                        break;

                    case 'cancel-all':
                        if (confirm('Cancel entire series?')) {
                            $.ajax({
                                url: '{{ route("backend.events.cancelInstance", "") }}/' + info.event.id,
                                method: 'POST',
                                data: {
                                    _token: '{{ csrf_token() }}',
                                    choice_action: 'series',
                                    occurrence_start: info.event.startStr
                                },
                                success() {
                                    calendar.refetchEvents();
                                },
                                error(xhr) {
                                    alert('Error cancelling the series.');
                                    console.error(xhr.responseText);
                                }
                            });
                        }
                        break;

                    case 'cancel-future':
                        if (confirm('Cancel this & future occurrences?')) {
                            $.ajax({
                                url: '{{ route("backend.events.cancelInstance", "") }}/' + info.event.id,
                                method: 'POST',
                                data: {
                                    _token: '{{ csrf_token() }}',
                                    choice_action: 'future',
                                    occurrence_start: info.event.startStr
                                },
                                success() {
                                    calendar.refetchEvents();
                                },
                                error(xhr) {
                                    alert('Error cancelling future events.');
                                    console.error(xhr.responseText);
                                }
                            });
                        }
                        break;
                }

                pendingClickInfo = null;
            });

            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    right: 'prev,next today',
                    center: 'title',
                    left: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: '{{ route("backend.events.index") }}',
                editable: true,
                selectable: true,
                select: function (info) {
                    // Prepare modal for “Create New”:
                    $('input[name="instance_id"]').val('');
                    $('input[name="master_id"]').val('');
                    $('input[name="form_action"]').val('create');
                    $('#eventForm')[0].reset();
                    $('.text-danger').remove();

                    // ❗Remove any previous Delete button
                    $('#eventModal .modal-footer .js-single-delete-btn').remove();

                    // Pre-fill start/end times
                    $("input[name='start_datetime']").val(info.startStr + 'T09:00');
                    $("input[name='end_datetime']").val(info.startStr + 'T10:00');
                    updateEndMin();

                    $('#rruleInput').val('');
                    $('#exdatesInput').val('');
                    $('#rruleSummary').text('No recurrence');

                    $('#freqSelect').val('DAILY');
                    $('#intervalInput').val(1);
                    onFrequencyChange();
                    $endType.val('NEVER');
                    $endAfterC.addClass('d-none');
                    $endByDateC.addClass('d-none');
                    $exdateList.empty();

                    // Clear Type/Sub‐Type
                    $('#type_id').val('');
                    $('#sub_type_id').html('<option value="">— Select Sub-Type —</option>');

                    // Clear previous reminders
                    $('#reminderList').empty();
                    
                    // Reset Select2s safely
                    $('.select-entity').each(function () {
                        $(this).val(null).trigger('change'); // clear value
                        $(this).empty(); // clear previous options
                        initEntitySelect($(this)); // reinitialize with placeholder etc.
                    });
                    
                    // $('.select-entity').each(function () {
                    //     initEntitySelect($(this));
                    // });

                    // initEntitySelect($('#property-select'));
                    // initEntitySelect($('#repair-select'));
                    // initEntitySelect($('#user-select'));

                    // eventModal.show();
                    $eventModal.modal('show');
                },
                eventClick: function (info) {
                    // When clicking an existing instance, load data into modal to “Edit Instance”
                    var inst = info.event.extendedProps;
                    console.log('✏️[eventClick] Instance data:', inst);

                    $('.select-entity').each(function () {
                        initEntitySelect($(this));
                    });

                    // IDs you want pre-selected
                    const propertyIds = inst.property_ids || [];
                    if (propertyIds.length) {
                        preselectSelect2($('#property-select'), inst.property_ids, inst.properties);
                    }

                    // IDs you want pre-selected
                    const repairIds = inst.repair_ids || [];
                    if (repairIds.length) {
                        preselectSelect2($('#repair-select'), inst.repair_ids, inst.repairs);
                    }

                    // If you have a diary owner (e.g. for bookings)
                    if (inst.diary_owner) {
                        preselectSelect2($('#user-select'), [inst.diary_owner], inst.users);
                    }

                    // If you have a separate “on behalf of” user
                    if (inst.on_behalf_of) {
                        preselectSelect2($('#user-select2'), [inst.on_behalf_of], inst.users);
                    }

                    // If no recurrence → treat as a single
                    if (!inst.rrule) {
                        console.log('✏️[eventClick] Editing single instance:', inst.master_id);
                        openEventModal(info, 'single'); // or whatever shows the modal
                        $('input[name="form_action"]').val('updateMaster');
                        $('#reminderList').empty();
                        loadExistingReminders(info.event.extendedProps.reminders);
                        // return;
                    } else {
                        // Recurring → show the modal
                        pendingClickInfo = info;
                        $seriesChoiceModal.modal('show');
                    }

                },
                eventDrop: function (info) {
                    // When user drags to reschedule an instance
                    var instId = info.event.id;
                    var newStart = moment(info.event.start).format('YYYY-MM-DD HH:mm:ss');
                    var newEnd = moment(info.event.end).format('YYYY-MM-DD HH:mm:ss');

                    $.ajax({
                        url: '{{ route("backend.events.updateInstance", "") }}/' + instId,
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            start_datetime: newStart,
                            end_datetime: newEnd,
                            form_action: 'updateInstance'
                        },
                        success: function () {
                            calendar.refetchEvents();
                        },
                        error: function () {
                            alert('Unable to update instance.');
                            info.revert();
                        }
                    });
                },
                eventContent: function (arg) {
                    const hasReminder = arg.event.extendedProps.remindersCount > 0;
                    let html = `<div>${arg.event.title}</div>`;
                    if (hasReminder) {
                        html += `<div class="fc-event-bell">🔔</div>`;
                    }
                    return { html };
                },
                events: '{{ route("backend.events.index") }}'
            });

            calendar.render();


            $('#type_id').on('change', function () {
                var typeId = $(this).val();
                var $sub = $('#sub_type_id');

                // Clear existing options
                $sub.html('<option value="">— Select Sub-Type —</option>');

                if (!typeId) {
                    return; // no type chosen
                }

                $.getJSON('/admin/api/event-sub-types/' + typeId, function (data) {
                    // data is an object {id: name, ...}
                    $.each(data, function (id, name) {
                        $sub.append(`<option value="${id}">${name}</option>`);
                    });
                });
            });

            // Handle form submission: could be “new master + instances” or “update instance + maybe update master”
            $('#eventForm').on('submit', function (e) {
                e.preventDefault();
                $('.text-danger').remove();

                // Read hidden IDs
                var instanceId = $('input[name="instance_id"]').val();
                var masterId = $('input[name="master_id"]').val();
                var formActionMode = $('input[name="form_action"]').val();
                var choiceMode = $('input[name="choice_action"]').val();
                var originalStart = $('input[name="original_start"]').val();
                var originalEnd = $('input[name="original_end"]').val();


                console.log('✏️[formSubmit] form_action=', formActionMode,
                    'instanceId=', instanceId,
                    'masterId=', masterId,
                    'original_start=', originalStart,
                    'original_end=', originalEnd);

                // Collect form data
                var formData = $(this).serializeArray();
                // var payload = {};
                // formData.forEach(function (f) { payload[f.name] = f.value; });
                var payload = {};
                formData.forEach(function (f) {
                    if (payload[f.name]) {
                        // Already exists → convert to array if needed
                        if (!Array.isArray(payload[f.name])) {
                            payload[f.name] = [payload[f.name]];
                        }
                        payload[f.name].push(f.value);
                    } else {
                        payload[f.name] = f.value;
                    }
                });

                // Else if masterId is present → user clicked an existing instance but may have changed recurrence or master data
                if (masterId && formActionMode === 'updateMaster') {
                    $.ajax({
                        url: '{{ route("backend.events.updateMaster", "") }}/' + masterId,
                        method: 'PUT',
                        data: payload,
                        success: function () {
                            // eventModal.hide();
                            $('#eventForm')[0].reset();
                            $eventModal.modal('hide');
                            calendar.refetchEvents();
                        },
                        error: function (xhr) {
                            if (xhr.status === 422) {
                                let errs = xhr.responseJSON.errors;
                                $.each(errs, function (key, msgs) {
                                    var $input = $('[name="' + key + '"]');
                                    if ($input.length) {
                                        $input.after('<div class="text-danger">' + msgs[0] + '</div>');
                                    }
                                });
                            } else {
                                alert('Error updating series.');
                            }
                        }
                    });
                }
                // Otherwise → new master + instances
                else {
                    $.ajax({
                        url: '{{ route("backend.events.store") }}',
                        method: 'POST',
                        data: payload,
                        success: function () {
                            // eventModal.hide();
                            $('#eventForm')[0].reset();
                            $eventModal.modal('hide');
                            calendar.refetchEvents();
                        },
                        error: function (xhr) {
                            if (xhr.status === 422) {
                                let errs = xhr.responseJSON.errors;
                                $.each(errs, function (key, msgs) {
                                    var $input = $('[name="' + key + '"]');
                                    if ($input.length) {
                                        $input.after('<div class="text-danger">' + msgs[0] + '</div>');
                                    }
                                });
                            } else {
                                alert('Error saving event.');
                            }
                        }
                    });
                }
            });

            function openEventModal(info, mode) {
                // console.log('✏️[openEventModal] mode=', mode);
                // console.log('✏️[openEventModal] info=', info);
                // info: the FullCalendar click payload  
                // mode: 'single' | 'series' | 'future'
                const inst = info.event.extendedProps;
                const isSingle = (mode === 'single');
                const isSeries = (mode === 'series');
                const isSplitFuture = (mode === 'future');
                // onsole.log(inst.status);
                // clear + reset form
                $('#eventForm')[0].reset();
                $('.text-danger').remove();

                // set hidden flags
                $('input[name="instance_id"]').val(inst.event_id ?? '');
                $('input[name="master_id"]').val(inst.master_id ?? '');
                $('input[name="form_action"]').val('updateMaster');
                $('input[name="choice_action"]').val(mode);
                // console.log('Setting status:', inst.event_status);
                // console.log('Available options:', $('select[name="status"]').html());

                // fill the common fields (title, office, etc.)
                $('input[name="title"]').val(info.event.title);
                $('input[name="office"]').val(inst.office);
                $('select[name="status"]').val((inst.event_status || '').trim());
                $('input[name="diary_owner"]').val(inst.diary_owner);
                $('input[name="on_behalf_of"]').val(inst.on_behalf_of);
                $('input[name="location"]').val(inst.location);
                $('input[name="reminder"]').val(inst.reminder);
                $('textarea[name="description"]').val(inst.description);

                // start / end: always use the clicked instance’s dates
                const start = moment(info.event.start).format('YYYY-MM-DDTHH:mm');
                const end = moment(info.event.end).format('YYYY-MM-DDTHH:mm');
                $('input[name="start_datetime"]').val(start);
                $('input[name="end_datetime"]').val(end);
                updateEndMin();

                // recurrence fields: always preload from the master
                $('#rruleInput').val(inst.rrule || '');
                $('#exdatesInput').val(inst.exdates || '[]');
                renderRRuleSummary(inst.rrule, inst.exdates ? JSON.parse(inst.exdates) : []);

                // For editing the rule itself, always show the button
                $('#editRRuleBtn').show();

                // type / sub-type
                $('#type_id').val(inst.type_id);
                $('#sub_type_id').html('<option value="">— Select Sub‑Type —</option>');
                if (inst.type_id) {
                    $.getJSON('/admin/api/event-sub-types/' + inst.type_id, data => {
                        $.each(data, (id, name) => {
                            $('#sub_type_id').append($('<option>').val(id).text(name));
                        });
                        $('#sub_type_id').val(inst.sub_type_id);
                    });
                }

                // ---------- DELETE BUTTON ----------
                const $footer = $('#eventModal .modal-footer');
                $footer.find('.js-delete-btn').remove(); // remove existing delete buttons

                const btnText = isSingle
                    ? 'Delete this occurrence'
                    : isSeries
                        ? 'Delete entire series'
                        : 'Delete this & future';

                const targetId = isSingle ? inst.event_id : inst.master_id;
                const url = '{{ route("backend.events.deleteInstance", ":id") }}'.replace(':id', targetId);
                const method = 'POST';

                const $del = $('<button>')
                    .addClass('btn btn-danger me-auto js-delete-btn')
                    .text(btnText)
                    .on('click', e => {
                        e.preventDefault();
                        if (!confirm(btnText + '?')) return;

                        const data = {
                            _token: '{{ csrf_token() }}',
                            choice_action: mode,
                            occurrence_start: info.event.startStr
                        };

                        $.ajax({
                            url: url,
                            method: method,
                            data: data,
                            success(response) {
                                if (response.success) {
                                    AIZ.plugins.notify('success', response.message || 'Event deleted successfully.');
                                } else {
                                    AIZ.plugins.notify('error', response.message || 'Could not delete event.');
                                }
                                $('#eventModal').modal('hide');
                                calendar.refetchEvents();
                            },
                            error(xhr) {
                                let errorMsg = 'Could not delete.';
                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    errorMsg = xhr.responseJSON.message;
                                }
                                AIZ.plugins.notify('error', errorMsg);
                            }
                        });
                    });
                $footer.prepend($del);

                // load reminders into the form
                loadExistingReminders(inst.reminders);

                // finally—show it!
                $eventModal.modal('show');
            }

        });
    </script>

@endpush