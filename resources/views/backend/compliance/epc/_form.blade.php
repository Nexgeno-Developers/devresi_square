<form id='epc'>
    @csrf

    <!-- Rating Field -->
    <div class="mb-3">
        <label for="rating" class="form-label">Rating</label>
        <select class="form-select" id="rating" name="rating" required>
            <option value="" {{ !isset($complianceRecord->rating) ? 'selected' : 'disabled' }}>Select Rating</option>
            @foreach(['A', 'B', 'C', 'D', 'E', 'F', 'G'] as $rating)
                <option value="{{ $rating }}" {{ isset($complianceDetails['rating']) && $complianceDetails['rating']->value == $rating ? 'selected' : '' }}>{{ $rating }}</option>
            @endforeach
        </select>
    </div>

    @include('backend.compliance._common_field')

</form>
