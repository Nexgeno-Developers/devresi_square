
<!-- Recurrence Rule Builder Modal -->
<div class="modal fade" id="rruleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Repeat</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Frequency -->
                <div class="mb-3">
                    <label class="form-label">Frequency</label>
                    <select id="freqSelect" class="form-select">
                        <option value="DAILY">Daily</option>
                        <option value="WEEKLY">Weekly</option>
                        <option value="MONTHLY">Monthly</option>
                        <option value="YEARLY">Yearly</option>
                    </select>
                </div>

                <!-- Interval -->
                <div class="mb-3">
                    <label class="form-label">Repeat Every</label>
                    <div class="input-group">
                        <input type="number" id="intervalInput" class="form-control" min="1" value="1">
                        <span class="input-group-text" id="intervalLabel">day(s)</span>
                    </div>
                </div>

                <!-- By Day (for WEEKLY) -->
                <div class="mb-3 d-none" id="byDayContainer">
                    <label class="form-label">On Days of Week</label>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" value="MO" id="chkMO">
                        <label class="form-check-label" for="chkMO">Mon</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" value="TU" id="chkTU">
                        <label class="form-check-label" for="chkTU">Tue</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" value="WE" id="chkWE">
                        <label class="form-check-label" for="chkWE">Wed</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" value="TH" id="chkTH">
                        <label class="form-check-label" for="chkTH">Thu</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" value="FR" id="chkFR">
                        <label class="form-check-label" for="chkFR">Fri</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" value="SA" id="chkSA">
                        <label class="form-check-label" for="chkSA">Sat</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" value="SU" id="chkSU">
                        <label class="form-check-label" for="chkSU">Sun</label>
                    </div>
                </div>

                <!-- By Month Day vs. By Ordinal Day of Month (example for “2nd Tuesday”) -->
                <div class="mb-3 d-none" id="byOrdinalContainer">
                    <label class="form-label">Monthly On</label>
                    <div class="row">
                        <div class="col-4">
                            <select id="bySetPos" class="form-select">
                                <option value="1">First</option>
                                <option value="2">Second</option>
                                <option value="3">Third</option>
                                <option value="4">Fourth</option>
                                <option value="-1">Last</option>
                            </select>
                        </div>
                        <div class="col-8">
                            <select id="byDayOrdinal" class="form-select">
                                <option value="MO">Monday</option>
                                <option value="TU">Tuesday</option>
                                <option value="WE">Wednesday</option>
                                <option value="TH">Thursday</option>
                                <option value="FR">Friday</option>
                                <option value="SA">Saturday</option>
                                <option value="SU">Sunday</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- End Conditions -->
                <div class="mb-3">
                    <label class="form-label">End</label>
                    <select id="endTypeSelect" class="form-select">
                        <option value="NEVER">Never</option>
                        <option value="AFTER">After N Occurrences</option>
                        <option value="BYDATE">By Date</option>
                    </select>
                </div>

                <div class="mb-3 d-none" id="endAfterContainer">
                    <label class="form-label">Occurrences</label>
                    <input type="number" id="endAfterCount" class="form-control" min="1" value="1">
                </div>

                <div class="mb-3 d-none" id="endByDateContainer">
                    <label class="form-label">End Date</label>
                    <input type="date" id="endByDateInput" class="form-control">
                </div>

                <!-- Exclusion Dates (e.g. public holidays) -->
                <div class="mb-3">
                    <label class="form-label">Exclude Specific Dates</label>
                    <div id="exdateList" class="mb-2">
                        <!-- We'll dynamically add date inputs here -->
                    </div>
                    <button type="button" id="addExdateBtn" class="btn btn-sm btn-outline-secondary">
                        + Add Exclusion Date
                    </button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveRRuleBtn">
                    Save repeat
                </button>
            </div>
        </div>
    </div>
</div>
<style>
    #rruleModal .modal-content,
    #eventModal .modal-content,
    #seriesChoiceModal .modal-content {
        border: 0;
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 24px 60px rgba(15, 23, 58, 0.18);
    }
    #rruleModal .modal-header,
    #seriesChoiceModal .modal-header,
    #eventModal .visit-modal-head {
        flex: 0 0 auto;
        border-bottom: 1px solid #e8edf3;
        padding: 1.1rem 1.25rem;
    }
    #rruleModal .modal-title,
    #seriesChoiceModal .modal-title,
    #eventModal .visit-modal-head h5 {
        font-weight: 700;
        letter-spacing: -0.02em;
        color: #0f173a;
    }
    #rruleModal .modal-body {
        background: #fff;
        max-height: min(440px, calc(100vh - 12rem));
        overflow-y: auto;
    }
    #rruleModal .modal-footer,
    #eventModal .modal-footer {
        border-top: 1px solid #e8edf3;
        background: #fbfcfe;
    }
    #rruleModal #saveRRuleBtn,
    #eventModal .visit-primary {
        background: #ff5c1d;
        border-color: #ff5c1d;
        border-radius: 999px;
        font-weight: 700;
        padding: 0.45rem 1rem;
    }
    #rruleModal #saveRRuleBtn:hover,
    #eventModal .visit-primary:hover {
        background: #e84e12;
        border-color: #e84e12;
    }
    #eventModal .visit-kicker {
        margin: 0 0 0.15rem;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #ff5c1d;
    }
    #eventModal .visit-lead {
        margin: 0.25rem 0 0;
        color: #5b6573;
        font-size: 0.92rem;
    }
    #eventModal .visit-stage {
        display: inline-flex;
        align-items: center;
        margin-top: 0.55rem;
        padding: 0.15rem 0.6rem;
        border-radius: 999px;
        background: #fff4e8;
        color: #9a3412;
        font-size: 0.75rem;
        font-weight: 700;
    }
    #eventModal .visit-block {
        padding-bottom: 0.25rem;
    }
    #eventModal .visit-block h6 {
        margin: 0 0 0.65rem;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #5b6573;
    }
    #eventModal .visit-repeat {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 0.75rem 0.9rem;
        border: 1px solid #e8edf3;
        border-radius: 12px;
        background: #f8f9fc;
    }
    #eventModal .visit-repeat strong {
        display: block;
        color: #0f173a;
        font-size: 0.92rem;
    }
    #eventModal .visit-repeat span {
        color: #5b6573;
        font-size: 0.8rem;
    }
    #eventModal #editRRuleBtn,
    #eventModal .visit-cancel {
        border-radius: 999px;
        font-weight: 700;
    }
    #eventModal .visit-delete {
        margin-right: auto;
        border: 0;
        background: transparent;
        color: #b42318;
        font-weight: 700;
        padding-left: 0;
    }
    #eventModal .visit-delete:hover {
        color: #8f1d14;
    }
    #eventModal[data-mode="create"] .is-edit-only {
        display: none !important;
    }
    #eventModal[data-mode="edit"] .is-create-only {
        display: none !important;
    }
    #eventModal .reply-row {
        display: flex;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 0.55rem 0;
        border-bottom: 1px solid #eef1f4;
    }
    #eventModal #eventRsvp {
        margin-top: 0.75rem;
        border: 1px solid #e8edf3;
        border-radius: 12px;
        padding: 0.85rem;
        background: #fff;
    }

    /* The form wraps header, body and footer, so Bootstrap's scrollable
       dialog never constrains the body. Pin the footer and scroll the fields. */
    #eventModal .modal-dialog {
        height: auto;
        max-height: calc(100vh - 2rem);
        margin: 1rem auto;
    }
    #eventModal .modal-content {
        max-height: calc(100vh - 2rem);
        overflow: hidden;
    }
    #eventModal #eventForm {
        display: flex;
        flex-direction: column;
        max-height: calc(100vh - 2rem);
        min-height: 0;
        overflow: hidden;
    }
    #eventModal .modal-header,
    #eventModal .modal-footer {
        flex: 0 0 auto;
    }
    #eventModal .modal-body {
        overflow-y: auto;
        flex: 1 1 auto;
        min-height: 0;
    }
    #eventModal .reminder-row .input-group {
        flex-wrap: nowrap;
    }
    #eventModal .reminder-row .form-control {
        max-width: 6.5rem;
        flex: 0 0 6.5rem;
    }
</style>

<!-- Choice Modal -->
<div class="modal fade" id="seriesChoiceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">This is a recurring series</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>What would you like to do?</p>
                <ul class="list-group">
                    <li class="list-group-item action-item" data-action="edit-one">Edit only this occurrence</li>
                    <li class="list-group-item action-item" data-action="edit-future">Edit this & future occurrences
                    </li>
                    <li class="list-group-item action-item" data-action="edit-all">Edit entire series</li>

                    <li class="list-group-item action-item" data-action="cancel-one">Cancel only this occurrence</li>
                    <li class="list-group-item action-item" data-action="cancel-future">Cancel this & future occurrences
                    </li>
                    <li class="list-group-item action-item" data-action="cancel-all">Cancel entire series</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="eventModal" data-mode="create" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <form id="eventForm" novalidate>
                @csrf
                <div class="visit-modal-head d-flex justify-content-between align-items-start gap-3">
                    <div>
                        <p class="visit-kicker" id="eventKicker">New visit</p>
                        <h5 class="modal-title mb-0" id="eventModalTitle">Add to the calendar</h5>
                        <p class="visit-lead is-create-only" id="eventCreateLead">Set the time, then choose the homes this visit is for.</p>
                        <p class="visit-lead is-edit-only" id="eventEditLead">This occurrence</p>
                        <span class="visit-stage is-edit-only" id="eventStage">Pending</span>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12 visit-block">
                            <h6>Visit</h6>
                            <label class="form-label" for="eventTitle">Title</label>
                            <input id="eventTitle" type="text" name="title" class="form-control" placeholder="Inspection, viewing, check-in" required>
                            <div class="text-danger" data-error-for="title"></div>
                            <div class="form-check mt-3">
                                <input class="form-check-input" type="checkbox" name="visible_to_tenant" id="visible-to-tenant" value="1">
                                <label class="form-check-label" for="visible-to-tenant">Invite the tenants at the selected properties</label>
                            </div>
                        </div>

                        <div class="col-12 visit-block">
                            <h6>When</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Starts</label>
                                    <input type="datetime-local" name="start_datetime" class="form-control" required>
                                    <div class="text-danger" data-error-for="start_datetime"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Ends</label>
                                    <input type="datetime-local" name="end_datetime" class="form-control" required>
                                    <div class="text-danger" data-error-for="end_datetime"></div>
                                </div>
                                <div class="col-12">
                                    <div class="visit-repeat">
                                        <div>
                                            <strong id="rruleSummary">Does not repeat</strong>
                                            <span class="is-create-only">Leave this as a single visit, or set a repeat.</span>
                                            <span class="is-edit-only">Change the repeat for the scope shown above.</span>
                                        </div>
                                        <button class="btn btn-sm btn-outline-dark" type="button" id="editRRuleBtn">Set repeat</button>
                                    </div>
                                    <textarea name="rrule" id="rruleInput" class="d-none"></textarea>
                                    <textarea name="exdates" id="exdatesInput" class="d-none"></textarea>
                                </div>
                            </div>
                        </div>

                        <div id="remindersContainer" class="col-12 visit-block">
                            <h6>Reminders</h6>
                            <p class="form-text mt-0 mb-2">Minutes before the start. Invited people get an email and an in-app notice.</p>
                            <div id="reminderList"></div>
                            <button type="button" id="addReminderBtn" class="btn btn-sm btn-outline-dark">Add reminder</button>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Properties</label>
                            <select id="property-select" class="form-control select-entity" data-entity="property"
                                data-mode="multi" data-max="3" data-url="{{ route('admin.properties.ajax') }}">
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Repairs</label>
                            <select id="repair-select" class="form-control select-entity" data-entity="repair" data-mode="multi" data-max="5" data-url="{{ route('admin.property_repairs.ajax') }}"></select>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="eventNotes">Notes</label>
                            <textarea id="eventNotes" name="description" class="form-control" rows="3" placeholder="Anything the invited people should know"></textarea>
                        </div>

                        <div class="col-12 is-edit-only d-none" id="eventResponses">
                            <h6 class="visit-block">Replies</h6>
                            <div id="eventResponseList"></div>
                            <div id="eventRsvp" class="d-none">
                                <p class="mb-2">Accept this visit, or decline it with a note.</p>
                                <textarea id="rsvpNote" class="form-control mb-2" rows="2" placeholder="Required if you decline"></textarea>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-sm btn-success" id="rsvpAccept">Accept</button>
                                    <button type="button" class="btn btn-sm btn-outline-danger" id="rsvpReject">Decline</button>
                                </div>
                                <div class="text-danger small mt-2" id="rsvpError"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <input type="hidden" name="event_id" value="">
                    <input type="hidden" name="instance_id" value="">
                    <input type="hidden" name="master_id" value="">
                    <input type="hidden" name="form_action" value="">
                    <input type="hidden" name="choice_action" value="">
                    <input type="hidden" name="original_start" value="">
                    <input type="hidden" name="original_end" value="">
                    <button type="button" class="visit-delete js-delete-btn d-none" id="eventDeleteBtn">Delete</button>
                    <button type="button" class="btn btn-outline-secondary visit-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary visit-primary" id="eventSubmitBtn">Create visit</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // The calendar page binds its own submit (it also refreshes the calendar).
    // Every other page that shows this modal, including a property, saves here.
    $(function () {
        if (document.getElementById('calendar')) return;

        $('#eventForm').on('submit', function (event) {
            event.preventDefault();
            const form = this;
            if (!form.checkValidity()) {
                const invalid = form.querySelector(':invalid');
                if (invalid) {
                    invalid.scrollIntoView({ block: 'center', behavior: 'smooth' });
                    invalid.reportValidity();
                }
                return;
            }

            const $form = $(form);
            const $save = $form.find('button[type="submit"]').prop('disabled', true);
            $.ajax({
                url: '{{ route('backend.events.store') }}',
                method: 'POST',
                data: $form.serialize(),
                success: function () {
                    const modal = document.getElementById('eventModal');
                    if (window.bootstrap?.Modal) {
                        bootstrap.Modal.getOrCreateInstance(modal).hide();
                    }
                    if (typeof fetchAppointments === 'function') {
                        fetchAppointments($('#appointments-filter-form').serialize());
                    }
                },
                error: function (xhr) {
                    const errors = xhr.responseJSON?.errors || {};
                    const first = Object.values(errors)[0];
                    const message = (Array.isArray(first) ? first[0] : null)
                        || xhr.responseJSON?.message
                        || 'Could not save this event.';
                    $('#eventFormAlert').remove();
                    const safe = String(message).replace(/[&<>"']/g, function (char) {
                        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[char];
                    });
                    $form.find('.modal-body').prepend(
                        '<div id="eventFormAlert" class="alert alert-danger">' + safe + '</div>'
                    );
                    const body = $form.find('.modal-body').get(0);
                    if (body) body.scrollTop = 0;
                },
                complete: function () {
                    $save.prop('disabled', false);
                }
            });
        });
    });
</script>
@endpush

<template id="reminderTpl">
    <div class="reminder-row mb-2">
        <div class="input-group">
            <input type="number" name="reminders[][minutes_before]" class="form-control" min="0" value="30" aria-label="Minutes before the start">
            <span class="input-group-text">minutes before the start</span>
            <button type="button" class="btn btn-outline-danger removeReminderBtn" aria-label="Remove reminder">&times;</button>
        </div>
        <input type="hidden" name="reminders[][channel]" value="email">
        <div class="form-text">Email and in-app notification</div>
    </div>
</template>
