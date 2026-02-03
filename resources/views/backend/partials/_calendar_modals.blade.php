
<!-- Recurrence Rule Builder Modal -->
<div class="modal fade" id="rruleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Set Recurrence Rule</h5>
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
                    Save Recurrence
                </button>
            </div>
        </div>
    </div>
</div>
<style>
    /* 
    #eventModal .modal-content {
        max-height: calc(100vh - 200px);
        overflow-y: auto;
    } 
*/
    .modal-body {
        background: #eee;
        min-height: 600px;
        max-height: 400px;
        overflow-y: auto;
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

<!-- Modal -->
<div class="modal fade" id="eventModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            {{-- Form for creating/editing events --}}
            <form id="eventForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Create / Edit Event</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                    <div class="row g-3">
                        {{-- Master fields (updated) --}}
                        <div class="col-md-6">
                            <label class="form-label">Title *</label>
                            <input type="text" name="title" class="form-control" placeholder="Subject" required>
                            <div class="text-danger" data-error-for="title"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Type *</label>
                            <select name="type_id" id="type_id" class="form-select" required>
                                <option value="">— Select Type —</option>
                                @foreach(\App\Models\EventType::orderBy('name')->get() as $type)
                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                @endforeach
                            </select>
                            <div class="text-danger" data-error-for="type_id"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Sub-Type *</label>
                            <select name="sub_type_id" id="sub_type_id" class="form-select" required>
                                <option value="">— Select Sub-Type —</option>
                                {{-- Options will be filled via AJAX when a Type is chosen --}}
                            </select>
                            <div class="text-danger" data-error-for="sub_type_id"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Office</label>
                            <input type="text" name="office" class="form-control" placeholder="Office name">
                            <div class="text-danger" data-error-for="office"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="" selected>— Select —</option>
                                <option value="Confirmed">Confirmed</option>
                                <option value="Pending">Pending</option>
                                <option value="Cancelled">Cancelled</option>
                                <option value="Rescheduled">Rescheduled</option>
                                <option value="Scheduled">Scheduled</option>
                            </select>
                            <div class="text-danger" data-error-for="status"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">In Diary Of</label>
                            <select id="user-select" name="diary_owner" class="form-control select-entity" data-entity="diary_owner"
                                data-mode="single" data-max="1" data-url="{{ route('admin.users.ajax') }}">
                            </select>
                            <div class="text-danger" data-error-for="diary_owner"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Booked By</label>
                            <select id="user-select2" name="on_behalf_of" class="form-control select-entity" data-entity="on_behalf_of" data-mode="single" data-max="1" data-url="{{ route('admin.users.ajax') }}"></select>
                            <div class="text-danger" data-error-for="on_behalf_of"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Location</label>
                            <input type="text" name="location" class="form-control" placeholder="Meeting location">
                            <div class="text-danger" data-error-for="location"></div>
                        </div>

                        {{-- Instance fields (unchanged except error placeholders) --}}
                        <div class="col-md-6">
                            <label class="form-label">Start Date &amp; Time *</label>
                            <input type="datetime-local" name="start_datetime" class="form-control" required>
                            <div class="text-danger" data-error-for="start_datetime"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">End Date &amp; Time *</label>
                            <input type="datetime-local" name="end_datetime" class="form-control" required>
                            <div class="text-danger" data-error-for="end_datetime"></div>
                        </div>

                        <div id="remindersContainer" class="mb-3">
                            <label class="form-label">Reminders</label>
                            <div id="reminderList"></div>
                            <button type="button" id="addReminderBtn" class="btn btn-sm btn-outline-primary">
                                + Add Reminder
                            </button>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Recurrence</label>
                            <button class="btn btn-sm btn-outline-secondary" type="button" id="editRRuleBtn">
                                Set Recurrence…
                            </button>
                            <div id="rruleSummary" class="mt-2 text-muted"></div>
                            <!-- Hidden field to store the serialized RRULE string -->
                            <textarea name="rrule" id="rruleInput" class="d-none"></textarea>
                        </div>

                        <!-- We’ll also keep a hidden JSON field for exdates -->
                        <textarea name="exdates" id="exdatesInput" class="d-none">
                        </textarea>

                        <div class="mb-3">
                            <label class="form-label">Properties</label>
                            <select id="property-select" class="form-control select-entity" data-entity="property"
                                data-mode="multi" data-max="3" data-url="{{ route('admin.properties.ajax') }}">
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Repairs</label>
                            <select id="repair-select" class="form-control select-entity" data-entity="repair" data-mode="multi" data-max="5" data-url="{{ route('admin.property_repairs.ajax') }}"></select>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3"
                                placeholder="Add any notes…"></textarea>
                        </div>

                    </div>
                </div>
                <div class="modal-footer">
                    <!-- Hidden for update; fill in via JS if needed -->
                    <input type="hidden" name="event_id" value="">
                    <input type="hidden" name="instance_id" value="">
                    <input type="hidden" name="master_id" value="">
                    <input type="hidden" name="form_action" value="">
                    <input type="hidden" name="choice_action" value="">
                    <input type="hidden" name="original_start" value="">
                    <input type="hidden" name="original_end" value="">
                    {{-- Submit button --}}
                    <button type="submit" class="btn btn-primary">Save</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<template id="reminderTpl">
    <div class="input-group mb-2 reminder-row">
        <input type="number" name="reminders[][minutes_before]" class="form-control w-25" min="0" value="30">
        <select name="reminders[][channel]" class="form-select w-25">
            <option value="email">EMAIL</option>
            <option value="in_app">IN APP</option>
            <option value="sms">SMS</option>
            <option value="push">PUSH</option>
        </select>
        <span class="input-group-text">minutes before</span>
        <button type="button" class="btn btn-outline-danger removeReminderBtn">&times;</button>
    </div>
</template>