@props(['id', 'title', 'type' => 'dialog'])

<div class="lab-overlay" id="lab-{{ $id }}" hidden data-lab-overlay>
    <div class="lab-{{ $type }}" role="dialog" aria-modal="true" aria-labelledby="lab-{{ $id }}-title">
        <header class="lab-overlay-head">
            <h2 id="lab-{{ $id }}-title">{{ $title }}</h2>
            <button type="button" class="lab-overlay-x" data-lab-close aria-label="Close">×</button>
        </header>
        <div class="lab-overlay-body">{{ $slot }}</div>
    </div>
</div>
