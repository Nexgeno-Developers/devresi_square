@extends('backend.layout.app')

@section('content')
    <div class="container-fluid lw-page lw-repairs">
        <div class="lw-hero d-flex justify-content-between align-items-center gap-3 flex-wrap">
            <div>
                <h4>Repairs</h4>
                <p>Track every issue from report to completion.</p>
            </div>
            <a href="{{ route('admin.property_repairs.create') }}" class="btn lw-btn-primary btn-sm">Add Repair</a>
        </div>

        @include('backend.repair.list.filter')

        @include('backend.repair.list.cards', [
            'repairIssues' => $repairIssues,
            'selectedRepairId' => null,
        ])
    </div>

    <div class="modal fade" id="repairDetailModal" tabindex="-1" aria-labelledby="repairDetailTitle" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="repairDetailTitle">Repair</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="repairDetailBody"></div>
            </div>
        </div>
    </div>

    @include('backend.components.modal')
@endsection

@include('backend.partials.assets.select2')

@section('page.scripts')
    <script>
        let repairSectionsExpanded = false;

        $(document).on('click', '#toggleAll', function () {
            if (repairSectionsExpanded) {
                $('#repairDetailBody .accordion-collapse').collapse('hide');
                $(this).text('Expand all');
            } else {
                $('#repairDetailBody .accordion-collapse').collapse('show');
                $(this).text('Collapse all');
            }
            repairSectionsExpanded = !repairSectionsExpanded;
        });

        window.loadRepairDetailByUrl = function (event, el) {
            if (event && event.target.closest('a, button, form, .dropdown')) {
                return;
            }

            const url = el.getAttribute('data-url');
            const title = el.getAttribute('data-title') || 'Repair';
            const modalEl = document.getElementById('repairDetailModal');
            const body = document.getElementById('repairDetailBody');
            const heading = document.getElementById('repairDetailTitle');
            if (!url || !modalEl || !body) {
                return;
            }

            document.querySelectorAll('.lw-repair-card').forEach(function (card) {
                card.classList.remove('is-open');
            });
            el.classList.add('is-open');
            heading.textContent = title;
            body.innerHTML = '<div class="lw-loading"><div class="lw-loading-spinner" role="status"></div><div class="lw-loading-label">Loading repair</div></div>';
            repairSectionsExpanded = false;
            bootstrap.Modal.getOrCreateInstance(modalEl).show();

            $.get(url).done(function (response) {
                body.innerHTML = response;
            }).fail(function () {
                body.innerHTML = '<p class="mb-0">This repair could not be opened. Try again.</p>';
            });
        };
    </script>
    @stack('extra.scripts')
@endsection
