@props([
    'class'=>'',
    'placeholder'=>'',
    'value'=>'',
    'onClick'=>'onclick()',
])

<div class="rs_search {{$class}}">
    <input type="text" value="{{ $value }}" placeholder="{{ $placeholder }}"/>
    <i class="bi bi-search pointer" onClick="{{$onClick}}"></i>
</div>