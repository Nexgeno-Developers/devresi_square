@if(!isset($editMode) || !$editMode)
    <!-- Display View Mode -->
    <div class="my-3 px-2">    
        @if ($repairIssue->final_contractor_id && $repairIssue->finalContractor)
            <p><strong>Name:</strong> {{ $repairIssue->finalContractor->name }}</p>
            <p><strong>Email:</strong> {{ $repairIssue->finalContractor->email }}</p>
            <p><strong>Phone:</strong> {{ $repairIssue->finalContractor->phone }}</p>
        @else
            <p>No final contractor selected.</p>
        @endif
    </div>
    

@else
    <form id="propertyAccessiblityForm">
        @csrf
        <input type="hidden" name="repair_id" value="{{ $repairIssue->id }}">
        <input type="hidden" name="form_type" value="property_accessibility">

        <div class="form-group">
            <label for="access_arrangement">Access Arrangement Description</label>
            <textarea name="access_arrangement" id="access_arrangement" rows="6" placeholder="Write Description" class="form-control">{{ isset($property) && $property->access_arrangement ? $property->access_arrangement : '' }}</textarea>
        </div>

        <div class="form-group">
            <label for="key_highlights">Key Highlights Description</label>
            <textarea name="key_highlights" id="key_highlights" rows="6" placeholder="Write Description" class="form-control">{{ isset($property) && $property->key_highlights ? $property->key_highlights : '' }}</textarea>
        </div>

        <!-- Nearest Station -->
        <div class="form-group">
            <div class="rs_sub_title">Nearest Station</div>
            <input id="station_name" type="text" class="tagify-input" placeholder="Station Name"
                data-source="stations"
                data-values="{{ $property->nearest_station }}"
                data-id-value="{{ json_encode($allstations) }}"
                data-options="{{ json_encode(['maxTags' => 5, 'dropdownEnabled' => 1, 'maxItems' => 10, 'searchKeys' => ['name'], 'closeOnSelect' => false])  }}">
            <input type="hidden" name="nearest_station" value="{{ $property->nearest_station }}" class="hidden-input" required>
        </div>

        <!-- Nearest School -->
        <div class="form-group">
            <div class="rs_sub_title">Nearest School</div>
            <input id="school_name" type="text" class="tagify-input" placeholder="School Name"
                data-source="schools"
                data-values="{{ $property->nearest_school }}"
                data-id-value="{{ json_encode($allschools) }}"
                data-options="{{ json_encode(['maxTags' => 5, 'dropdownEnabled' => 1, 'maxItems' => 10, 'searchKeys' => ['name'], 'closeOnSelect' => false])  }}">
            <input type="hidden" name="nearest_school" value="{{ $property->nearest_school }}" class="hidden-input" required>
        </div>

        <div class="form-group">
            <div class="rs_sub_title">Nearest Religious Places (Distance in KM)</div>
            @php
                // If nearest_religious_places is not null, decode the JSON and use it; otherwise, use empty values
                $religiousPlaces =
                    isset($property) && $property->nearest_religious_places
                        ? json_decode($property->nearest_religious_places)
                        : ['masjid' => '', 'church' => '', 'mandir' => ''];
            @endphp
            <input type="number" pattern="[0-9]" class="form-control mt-2" inputmode="numeric"
                name="nearest_religious_places[masjid]" value="{{ $religiousPlaces->masjid ?? '' }}"
                placeholder="Masjid" required>
            <input type="number" pattern="[0-9]" class="form-control mt-2" inputmode="numeric"
                name="nearest_religious_places[church]" value="{{ $religiousPlaces->church ?? '' }}"
                placeholder="Church" required>
            <input type="number" pattern="[0-9]" class="form-control mt-2" inputmode="numeric"
                name="nearest_religious_places[mandir]" value="{{ $religiousPlaces->mandir ?? '' }}"
                placeholder="Mandir" required>
        </div>

        <div class="form-group">
            <label for="useful_information">Useful Information</label>
            <input type="text" name="useful_information" id="useful_information" class="form-control"
                value="{{ isset($property) && $property->useful_information ? $property->useful_information : '' }}"
                required>
        </div>
        <button type="submit" class="btn btn_secondary mt-3 float-end">Save Changes</button>
    </form>


@endif