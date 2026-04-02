@csrf
@if(isset($item))
    @method('PUT')
@endif

<div class="row">
    @foreach($fields as $field)
        @php
            $name = $field['name'];
            $type = $field['type'] ?? 'text';
            $required = $field['required'] ?? false;
            $value = old($name, data_get($item ?? null, $name, data_get($defaults ?? [], $name)));
            $inlineOptions = $field['options'] ?? null;
            $dynamicOptions = $selectOptions[$name] ?? [];
            $options = $inlineOptions ?: $dynamicOptions;
        @endphp

        @if($type === 'checkbox')
            <div class="col-12 mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="{{ $name }}" name="{{ $name }}" value="1" {{ $value ? 'checked' : '' }}>
                    <label class="form-check-label" for="{{ $name }}">{{ $field['label'] }}</label>
                </div>
            </div>
        @else
            <div class="col-md-6 mb-3">
                <label for="{{ $name }}" class="form-label">
                    {{ $field['label'] }}
                    @if($required)
                        <span class="text-danger">*</span>
                    @endif
                </label>

                @if($type === 'select')
                    <select class="form-select" id="{{ $name }}" name="{{ $name }}" {{ $required ? 'required' : '' }}>
                        <option value="">Select</option>
                        @foreach($options as $optionValue => $optionLabel)
                            <option value="{{ $optionValue }}" {{ (string)$value === (string)$optionValue ? 'selected' : '' }}>
                                {{ $optionLabel }}
                            </option>
                        @endforeach
                    </select>
                @elseif($type === 'textarea')
                    <textarea class="form-control" id="{{ $name }}" name="{{ $name }}" rows="3" {{ $required ? 'required' : '' }}>{{ $value }}</textarea>
                @else
                    <input
                        class="form-control"
                        type="{{ $type }}"
                        id="{{ $name }}"
                        name="{{ $name }}"
                        value="{{ $value }}"
                        {{ $required ? 'required' : '' }}
                        @if(isset($field['step'])) step="{{ $field['step'] }}" @endif
                        @if(isset($field['min'])) min="{{ $field['min'] }}" @endif
                        @if(isset($field['max'])) max="{{ $field['max'] }}" @endif
                    >
                @endif
            </div>
        @endif
    @endforeach
</div>

<div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary">Save</button>
    <a href="{{ route($routeName . '.index') }}" class="btn btn-secondary">Cancel</a>
</div>
