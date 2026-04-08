@props([
    'class'=>'',
    'placeholder'=>'',
    'value'=>'',
    'onClick'=>'',
    'id'=>'searchInput',
])

<div class="rs_search {{$class}}">
    <input type="text" id="{{ $id }}" value="{{ $value }}" placeholder="{{ $placeholder }}"/>
    <i class="bi bi-search pointer"></i>
</div>