@props(['noteableType', 'noteableId', 'noteTypes', 'initialNotes' => null])
<div class="notes-component" data-noteable-type="{{ $noteableType }}" data-noteable-id="{{ $noteableId }}">

    {{-- ADD NEW --}}
    <div class="mb-3">
        <button type="button" class="btn btn-outline-primary notes-add">Add New Note</button>
    </div>
    {{-- FILTER FORM --}}
    <form class="notes-filter-form row g-2 mb-3">
        <div class="col-md-3">
            <select name="note_type_id" class="form-select">
                <option value="">All Types</option>
                @foreach($noteTypes as $type)
                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <input type="text" name="search" class="form-control" placeholder="Search content…">
        </div>
        <div class="col-md-2">
            <input type="date" name="from_date" class="form-control">
        </div>
        <div class="col-md-2">
            <input type="date" name="to_date" class="form-control">
        </div>
        <div class="col-md-2 d-flex gap-1">
            <button type="submit" class="btn btn-primary">Filter</button>
            <button type="button" class="btn btn-secondary notes-reset">Reset</button>
        </div>
    </form>
    {{-- LIST --}}
    {{-- <div class="notes-list"></div> --}}
    {{-- Render initial list server-side: --}}
    <div class="notes-list">
        @include('components.backend.notes._notes_list', [
        'notes'     => $initialNotes,
        'noteTypes' => $noteTypes
        ])
    </div>

    {{-- MODAL for Add/Edit/View --}}
    <div class="modal fade" id="notesModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="notesModalLabel">Notes</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Form will be appended here dynamically -->
                </div>
            </div>
        </div>
    </div>

</div>