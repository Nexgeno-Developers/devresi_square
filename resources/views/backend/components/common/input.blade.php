
@if($isLabel == True)
<label class="mb-2" for="{{ $inputName }}">{{ $label }}</label>
@endif
<div class="rs_input {{ $class }} {{ $inputOpt }} {{ $isLabel == True ? "with_label" : ""}} {{ $isDate == True? "with_date" : "" }}">
   @if ($inputOpt == 'input_price')
      {{ getPoundSymbol() }}
   @endif

   <input type="{{ $inputType }}" name= "{{ $inputName }}" placeholder="{{ $placeHolder}}" />

   @if ($rightIcon)
   <div class="right_icon">{{ $rightIcon }}</div>
   @endif
</div>