<!-- Status Confirmation Modal -->
<div class="modal fade" id="confirmStatusChangeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Status Change</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to change status to <strong id="new-status-text"></strong>?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button id="confirmStatusChangeBtn" type="button" class="btn btn-primary">Yes, Change</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteConfirmEventModal" tabindex="-1" aria-labelledby="deleteConfirmEventLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="deleteForm">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete <strong><span id="deleteTypeLabel"></span></strong> Event?
                </div>
                <div class="modal-footer">
                    <input type="hidden" name="event_id" id="deleteEventId">
                    <input type="hidden" name="occurrence_start" id="deleteOccurrenceStart">
                    <input type="hidden" name="choice_action" id="deleteChoiceAction">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Yes, Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>
