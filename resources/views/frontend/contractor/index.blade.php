@extends('backend.layout.app')

@section('content')
<style>
    #detail-pane, #list-pane { transition: all 0.5s ease; }
    .hidden-pane { opacity:0; visibility:hidden; width:0; padding:0; margin:0; overflow:hidden; }
    .spinner-overlay { display:flex; align-items:center; justify-content:center; height:100%; }
    .fade-in { animation: fadeIn 0.3s ease-in-out; }
    @keyframes fadeIn { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }
    .repair-row > td:hover { color: #d83434; }
    thead > tr > th { background-color: #e9eef5 !important; color: #000 !important; }
    .repair-row.selected > td { background-color: #6c6c6c; color: #fff; }
    .repair-row > td { transition: background-color 0.3s ease, color 0.3s ease; }
</style>

<div class="row" id="master-detail-wrapper">

    {{-- ── Left: List + Filters ── --}}
    <div class="col-md-5" id="list-pane">
        <button id="toggle-detail-pane" class="btn btn-outline-secondary float-end toggle-btn mt-2">
            <i class="fas fa-chevron-left"></i> Hide Detail
        </button>

        {{-- Filter --}}
        <div class="row mb-3">
            <div class="col-12">
                <div class="d-flex gap-2 flex-wrap">
                    <form method="GET" action="{{ route('contractor.repairs.index') }}" id="filter-form">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control"
                                   placeholder="Search by Property Name or ID"
                                   value="{{ request('search') }}">
                            <button class="btn btn-primary" type="submit">Search</button>
                        </div>
                    </form>
                    <form method="GET" action="{{ route('contractor.repairs.index') }}">
                        <select name="status" class="form-select" onchange="this.form.submit()">
                            <option value="">-- Filter by Status --</option>
                            @foreach(['Pending','Reported','Under Process','Work Completed','Invoice Received','Invoice Paid','Closed'] as $s)
                                <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ $s }}</option>
                            @endforeach
                        </select>
                    </form>
                    <a href="{{ route('contractor.repairs.index') }}" class="btn btn-secondary">Reset Filters</a>
                </div>
            </div>
        </div>

        {{-- Cards list --}}
        @include('frontend.contractor.list.cards', [
            'repairIssues'     => $repairIssues,
            'selectedRepairId' => isset($firstRepairIssue) ? $firstRepairIssue->id : null,
        ])
    </div>

    {{-- ── Right: Detail ── --}}
    <div class="col-md-7" id="detail-pane">
        @if(isset($firstRepairIssue))
            @include('frontend.contractor.detail.show', ['repairIssue' => $firstRepairIssue])
        @else
            <div class="alert alert-info">Select a repair item to view details.</div>
        @endif
    </div>

</div>
@endsection

@section('page.scripts')
<script>
    let lastLoadedUrl = null;
    let isExpanded = true;

    // Expand / Collapse All
    $(document).on('click', '#toggleAll', function () {
        if (isExpanded) {
            $('.accordion-collapse').collapse('hide');
            $(this).text('Expand All');
        } else {
            $('.accordion-collapse').collapse('show');
            $(this).text('Collapse All');
        }
        isExpanded = !isExpanded;
    });

    // Toggle detail pane
    const $toggleBtn = $('#toggle-detail-pane');

    function showDetailPane() {
        const $dp = $('#detail-pane'), $lp = $('#list-pane');
        if ($dp.hasClass('hidden-pane')) {
            $dp.removeClass('hidden-pane col-md-0').addClass('col-md-7');
            $lp.removeClass('col-md-12').addClass('col-md-5');
            $toggleBtn.find('i').removeClass('fa-chevron-right').addClass('fa-chevron-left');
            $toggleBtn.contents().last().replaceWith(' Hide Detail');
        }
    }

    $toggleBtn.click(function () {
        const $dp = $('#detail-pane'), $lp = $('#list-pane');
        const isHidden = $dp.hasClass('hidden-pane');
        if (isHidden) {
            $dp.removeClass('hidden-pane col-md-0').addClass('col-md-7');
            $lp.removeClass('col-md-12').addClass('col-md-5');
            $(this).find('i').removeClass('fa-chevron-right').addClass('fa-chevron-left');
            $(this).contents().last().replaceWith(' Hide Detail');
        } else {
            $dp.addClass('hidden-pane col-md-0').removeClass('col-md-7');
            $lp.removeClass('col-md-5').addClass('col-md-12');
            $(this).find('i').removeClass('fa-chevron-left').addClass('fa-chevron-right');
            $(this).contents().last().replaceWith(' Show Detail');
        }
    });

    // AJAX search
    $('#filter-form').on('submit', function (e) {
        e.preventDefault();
        $.get('{{ route("contractor.repairs.index") }}', $(this).serialize(), function (res) {
            $('#card-list').html(res);
            lastLoadedUrl = null;
        });
    });

    // Load detail via AJAX
    window.loadRepairDetailByUrl = function (el) {
        const url = $(el).data('url');
        showDetailPane();
        if (url === lastLoadedUrl) return;
        lastLoadedUrl = url;

        const $dp = $('#detail-pane');
        $dp.html('<div class="spinner-overlay"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');
        $('.repair-row').removeClass('selected');
        $(el).closest('.repair-row').addClass('selected');

        $.get(url, function (response) {
            $dp.html('<div class="fade-in">' + response + '</div>');
        }).fail(function () {
            $dp.html('<div class="alert alert-danger fade-in">Failed to load detail.</div>');
        });
    };
</script>
@endsection
