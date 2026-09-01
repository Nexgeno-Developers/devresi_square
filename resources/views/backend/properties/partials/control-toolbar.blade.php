<div class="pcc-toolbar">
    <button class="pcc-back-btn" id="pccBackBtn" title="Back to list">
        <i class="bi bi-chevron-left"></i>
    </button>
    <span class="pcc-toolbar-title">Properties</span>
    <label class="pcc-toolbar-search">
        <i class="bi bi-search" aria-hidden="true"></i>
        <input type="search" name="search" id="pccListSearch"
               placeholder="Search address, ref, postcode..."
               value="{{ request('search') }}" autocomplete="off">
    </label>
    <span class="pcc-toolbar-spacer"></span>
    <button class="pcc-icon-ghost" id="pccCommandTrigger" title="Jump to a property or action (Ctrl+K)" type="button">
        <i class="bi bi-command"></i>
        <span class="pcc-kbd">Ctrl+K</span>
    </button>
    @can('create properties')
        <a href="{{ property_create_url() }}" class="pcc-btn-ink">
            <i class="bi bi-plus"></i>
            <span>Add property</span>
        </a>
    @endcan
</div>
