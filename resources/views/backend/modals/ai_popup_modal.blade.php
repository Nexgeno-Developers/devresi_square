<!-- Generate Content Modal -->
<div class="modal fade" id="aiGenerateModal" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title h6">Generate Product Description</h5>
                <button type="button" class="close" data-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="min-height: 300px;">
                <div class="mb-3">
                    <label for="aiPrompt" class="form-label">Enter your base description:</label>
                    <textarea id="aiPrompt" class="form-control" rows="4"></textarea>
                </div>
                <div id="aiResponseArea" class="border rounded p-3 mb-3" style="min-height:100px; display:none;">
                    <pre id="aiResponseText" style="white-space: pre-wrap;"></pre>
                </div>
                <div id="aiLoading" class="text-center my-3" style="display:none;">
                    <div class="spinner-border"></div>
                </div>
            </div>

            <div class="modal-footer">
                <button id="aiGenerate" type="button" class="btn btn-primary">Generate</button>
                {{-- <button id="aiRetry" type="button" class="btn btn-secondary" style="display:none;">Retry</button> --}}
                <button id="aiInsert" type="button" class="btn btn-success" style="display:none;">Insert into Description</button>
                <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>