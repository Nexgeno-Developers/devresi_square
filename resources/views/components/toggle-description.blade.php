@props(['text', 'limit' => 100])

@php
    $shortText = Str::limit(strip_tags($text), $limit);
    $isLongText = strlen(strip_tags($text)) > $limit; // Check if text exceeds the limit
    $id = 'desc_' . uniqid();
@endphp

<div class="description-toggle">
    <span class="short-text" id="{{ $id }}_short">{{ html_entity_decode($shortText) }}</span>
    <span class="full-text d-none" id="{{ $id }}_full">{{ html_entity_decode($text) }}</span>
    
    @if ($isLongText)
    <button data-target="{{ $id }}" type="button" class="toggle-link btn btn-sm btn-outline-danger" style="display: inline-block; margin-left: 5px;">
        Show More
    </button>
    @endif
</div>
