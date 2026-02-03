@props(['name' => 'property_id', 'selected' => null, 'disabled' => false])

@php
    $selectedId = $selected;
    $isDisabled = filter_var($disabled, FILTER_VALIDATE_BOOLEAN);
@endphp

<select name="{{ $name }}" id="{{ $name }}" class="form-control property-select {{ $name }}" style="width:100%" @if($isDisabled) disabled @endif>
    @if($selectedId)
        @php
            $property = \App\Models\Property::find($selectedId);
        @endphp
        @if($property)
            <option value="{{ $property->id }}" selected>{{ $property->display_label }}</option>
        @endif
    @endif
</select>
{{-- If disabled, we still need to submit the value (disabled inputs are not POSTed) --}}
@if($isDisabled)
    <input type="hidden" name="{{ $name }}" value="{{ $selectedId }}">
@endif

@push('scripts')
<script>
$(function() {
    // If disabled, we still initialize select2 so the user can see the selection,
    // but the tag will not be editable. If you prefer to skip select2 init when disabled, adjust here.
    var $el = $('#{{ $name }}');

    $el.select2({
        placeholder: 'Select Property',
        allowClear: true,
        ajax: {
            url: '{{ route("backend.properties.search-ajax") }}',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term // search term
                };
            },
            processResults: function (data) {
                return {
                    results: data.map(function(item) {
                        return {
                            id: item.id,
                            text: item.display_label
                        };
                    })
                };
            },
            cache: true
        }
    });

    // if element was disabled by blade attribute, disable select2 control as well
    @if($isDisabled)
        $el.prop('disabled', true);
        // select2 keeps a visual; make sure it doesn't allow removing selection
        $el.on('select2:opening select2:closing', function (e) {
            e.preventDefault();
        });
    @endif
    
});
</script>
@endpush
