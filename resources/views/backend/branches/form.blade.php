<div class="branch-form-card">
    <div class="branch-form-section">
        {{-- <div class="branch-section-title">
            <h2>Company</h2>
            <p>This branch will be mapped automatically from the logged-in user.</p>
        </div> --}}
        <div class="row g-3">
            <div class="col-md-6">
                <label for="company_id" class="form-label">Company</label>
                <input type="text" id="company_id" class="form-control"
                    value="{{ $company->name ?? $branch->company?->name ?? 'Mapped from logged-in user' }}" readonly>
            </div>
            <div class="col-md-6">
                <label for="name" class="form-label">Branch Name <span class="text-danger">*</span></label>
                <input type="text" name="name" id="name" class="form-control"
                    value="{{ old('name', $branch->name ?? '') }}" required>
                @error('name')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-12">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_main_head_office" id="is_main_head_office" value="1"
                        @checked(old('is_main_head_office', $branch->is_main_head_office ?? false))>
                    <label class="form-check-label" for="is_main_head_office">
                        Is Main Head Office
                    </label>
                </div>
                <div class="form-text">Marking this branch as main head office will unset the previous main head office for this company.</div>
            </div>
        </div>
    </div>

    <div class="branch-form-section">
        <div class="branch-section-title">
            <h2>Address</h2>
            <p>Use UK-style address fields for consistent branch display.</p>
        </div>
        <div class="row g-3">
            <div class="col-md-6">
                <label for="address_line_1" class="form-label">Address Line 1</label>
                <input type="text" name="address_line_1" id="address_line_1" class="form-control"
                    value="{{ old('address_line_1', $branch->address_line_1 ?? '') }}">
            </div>
            <div class="col-md-6">
                <label for="address_line_2" class="form-label">Address Line 2</label>
                <input type="text" name="address_line_2" id="address_line_2" class="form-control"
                    value="{{ old('address_line_2', $branch->address_line_2 ?? '') }}">
            </div>
            <div class="col-md-4">
                <label for="city" class="form-label">Town/City</label>
                <input type="text" name="city" id="city" class="form-control"
                    value="{{ old('city', $branch->city ?? '') }}">
            </div>
            <div class="col-md-4">
                <label for="county" class="form-label">County</label>
                <input type="text" name="county" id="county" class="form-control"
                    value="{{ old('county', $branch->county ?? '') }}">
            </div>
            <div class="col-md-4">
                <label for="postcode" class="form-label">Postcode</label>
                <input type="text" name="postcode" id="postcode" class="form-control"
                    value="{{ old('postcode', $branch->postcode ?? '') }}">
            </div>
            <div class="col-md-4">
                <label for="country" class="form-label">Country</label>
                <input type="text" name="country" id="country" class="form-control"
                    value="{{ old('country', $branch->country ?? 'UK') }}">
            </div>
        </div>
    </div>

    <div class="branch-form-section">
        <div class="branch-section-title">
            <h2>Contact</h2>
            <p>These details are used in branch lists, staff mapping, and company-facing documents.</p>
        </div>
        <div class="row g-3">
            <div class="col-md-6">
                <label for="user_email" class="form-label">Email</label>
                <input type="email" name="user_email" id="user_email" class="form-control"
                    value="{{ old('user_email', $branch->user_email ?? '') }}">
                @error('user_email')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6">
                <label for="alternate_email" class="form-label">Alternate Email</label>
                <input type="email" name="alternate_email" id="alternate_email" class="form-control"
                    value="{{ old('alternate_email', $branch->alternate_email ?? '') }}">
                @error('alternate_email')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6">
                <label for="user_phone" class="form-label">Phone</label>
                <input type="text" name="user_phone" id="user_phone" class="form-control"
                    value="{{ old('user_phone', $branch->user_phone ?? '') }}">
            </div>
            <div class="col-md-6">
                <label for="alternate_phone" class="form-label">Alternate Phone</label>
                <input type="text" name="alternate_phone" id="alternate_phone" class="form-control"
                    value="{{ old('alternate_phone', $branch->alternate_phone ?? '') }}">
            </div>
        </div>
    </div>

    <div class="branch-form-section">
        <div class="branch-section-title">
            <h2>Social Media</h2>
            <p>Optional public URLs for this branch.</p>
        </div>
        @php
            $socialMediaFields = [
                'facebook' => 'Facebook',
                'instagram' => 'Instagram',
                'linkedin' => 'LinkedIn',
                'twitter' => 'Twitter/X',
                'youtube' => 'YouTube',
                'tiktok' => 'TikTok',
                'website' => 'Website URL',
            ];
        @endphp
        <div class="row g-3">
            @foreach($socialMediaFields as $key => $label)
                <div class="col-md-6">
                    <label for="social_media_{{ $key }}" class="form-label">{{ $label }}</label>
                    <input type="url" name="social_media[{{ $key }}]" id="social_media_{{ $key }}" class="form-control"
                        value="{{ old('social_media.' . $key, $branch->social_media[$key] ?? '') }}">
                    @error('social_media.' . $key)
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
            @endforeach
        </div>
    </div>

    <div class="branch-form-actions">
        <a href="{{ route('admin.branches.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">{{ $buttonText ?? 'Save' }}</button>
    </div>
</div>
