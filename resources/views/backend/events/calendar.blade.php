@extends('backend.layout.app')

@section('content')
<div class="container-fluid">
    <div class="row h-100">
        <div class="col-6">
            @include('backend.partials.event-list')
        </div>
        <div class="col-6">
            @include('backend.partials.calendar')
        </div>
    </div>
</div>

<style>
.event-list-panel {
    display: flex;
    flex-direction: column;
    height: 100%;
}
.event-list-header {
    padding: 1rem;
    background: #fff;
    border-bottom: 1px solid #dee2e6;
}
.event-filters {
    background: #fff;
}
.event-list-container {
    flex: 1;
    overflow-y: auto;
    padding: 0.75rem;
}
.event-list-item {
    cursor: pointer;
    transition: box-shadow 0.15s ease;
}
.event-list-item:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}
.event-list-pagination {
    background: #fff;
    border-top: 1px solid #dee2e6;
}
#calendar {
    height: 100%;
    background: #fff;
    border-radius: 0.375rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    padding: 0.75rem;
    overflow: hidden;
}
</style>
@include('backend.partials._calendar_modals')

@endsection

@section('page.scripts')
<script>
(function() {
    const listUrl = '{{ route("backend.events.list") }}';
    let currentFilters = {};

    function loadEventList() {
        const params = new URLSearchParams();
        
        document.querySelectorAll('.calendar-filter').forEach(function(el) {
            if (el.value && el.value !== '' && el.value !== 'all') {
                params.set(el.name, el.value);
            }
        });
        
        const searchEl = document.getElementById('eventSearch');
        if (searchEl && searchEl.value.trim()) {
            params.set('search', searchEl.value.trim());
        }
        
        const url = listUrl + '?' + params.toString();
        
        fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            renderEventList(data.events || []);
            renderPagination(data.pagination || {});
        })
        .catch(err => {
            console.error('Failed to load events:', err);
            document.getElementById('eventListContainer').innerHTML = 
                '<div class="alert alert-danger m-3">Failed to load events. Please try again.</div>';
        });
    }

    function renderEventList(events) {
        const container = document.getElementById('eventListContainer');
        if (!events.length) {
            container.innerHTML = '<div class="text-muted text-center py-4">No events found.</div>';
            return;
        }

        let html = '';
        events.forEach(function(evt) {
            const start = evt.start_datetime ? new Date(evt.start_datetime).toLocaleString() : '';
            const end = evt.end_datetime ? new Date(evt.end_datetime).toLocaleString() : '';
            const status = evt.status || '';
            const type = (evt.type && evt.type.name) ? evt.type.name : (evt.type_name || '');
            const subType = (evt.sub_type && evt.sub_type.name) ? evt.sub_type.name : (evt.subType && evt.subType.name ? evt.subType.name : (evt.sub_type_name || ''));
            const diaryOwner = (evt.diary_owner && evt.diary_owner.name) ? evt.diary_owner.name : (evt.diaryOwner && evt.diaryOwner.name ? evt.diaryOwner.name : '');
            const hasReminders = (evt.reminders && evt.reminders.length) ? '?? ' : '';
            
            html += `
                <div class="event-list-item card mb-2" data-event-id="${evt.id}" data-master-id="${evt.parent_id || evt.id}">
                    <div class="card-body py-2 px-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <h6 class="mb-1 fw-semibold">${hasReminders}${evt.title}</h6>
                                <small class="text-muted d-block">${start}${end ? ' — ' + end : ''}</small>
                                ${type ? `<small class="badge bg-info text-dark me-1">${type}</small>` : ''}
                                ${subType ? `<small class="badge bg-secondary me-1">${subType}</small>` : ''}
                                ${status ? `<small class="badge bg-${status === 'Confirmed' ? 'success' : status === 'Cancelled' ? 'danger' : status === 'Pending' ? 'warning' : 'primary'}">${status}</small>` : ''}
                                ${diaryOwner ? `<small class="text-muted d-block mt-1">?? ${diaryOwner}</small>` : ''}
                                ${evt.location ? `<small class="text-muted d-block">?? ${evt.location}</small>` : ''}
                            </div>
                            <button class="btn btn-sm btn-outline-primary event-goto-calendar" data-event-id="${evt.id}" data-master-id="${evt.parent_id || evt.id}" title="Show on calendar">
                                <i class="fa-solid fa-calendar-day"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
        });

        container.innerHTML = html;

        container.querySelectorAll('.event-goto-calendar').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const eventId = this.dataset.eventId;
                const masterId = this.dataset.masterId;
                if (typeof calendar !== 'undefined' && calendar.getEventById) {
                    const evt = calendar.getEventById(eventId) || calendar.getEventById(masterId);
                    if (evt) {
                        evt._show = true;
                        calendar.on('datesSet', function() {
                            setTimeout(function() {
                                const el = document.querySelector(`[data-event-id="${eventId}"]`) || 
                                           document.querySelector(`[data-event-id="${masterId}"]`);
                                if (el) {
                                    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                    el.classList.add('border', 'border-warning');
                                    setTimeout(() => el.classList.remove('border', 'border-warning'), 2000);
                                }
                            }, 300);
                        });
                        const start = evt.start;
                        if (start) {
                            calendar.changeView('dayGridMonth');
                            calendar.gotoDate(start);
                        }
                    }
                }
            });
        });
    }

    function renderPagination(pagination) {
        const container = document.getElementById('eventListPagination');
        if (!container || !pagination.last_page) {
            if (container) container.innerHTML = '';
            return;
        }

        let html = '<nav><ul class="pagination pagination-sm justify-content-center mb-0">';
        
        if (pagination.current_page > 1) {
            html += `<li class="page-item"><a class="page-link event-page-link" href="#" data-page="${pagination.current_page - 1}">Prev</a></li>`;
        } else {
            html += `<li class="page-item disabled"><span class="page-link">Prev</span></li>`;
        }
        
        for (let i = 1; i <= pagination.last_page; i++) {
            if (i === pagination.current_page) {
                html += `<li class="page-item active"><span class="page-link">${i}</span></li>`;
            } else {
                html += `<li class="page-item"><a class="page-link event-page-link" href="#" data-page="${i}">${i}</a></li>`;
            }
        }
        
        if (pagination.current_page < pagination.last_page) {
            html += `<li class="page-item"><a class="page-link event-page-link" href="#" data-page="${pagination.current_page + 1}">Next</a></li>`;
        } else {
            html += `<li class="page-item disabled"><span class="page-link">Next</span></li>`;
        }
        
        html += '</ul></nav>';
        container.innerHTML = html;

        container.querySelectorAll('.event-page-link').forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const page = this.dataset.page;
                const pageInput = document.getElementById('eventListPage');
                if (pageInput) {
                    pageInput.value = page;
                    loadEventList();
                }
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.calendar-filter').forEach(function(el) {
            el.addEventListener('change', function() {
                const pageInput = document.getElementById('eventListPage');
                if (pageInput) pageInput.value = 1;
                loadEventList();
            });
        });

        const searchEl = document.getElementById('eventSearch');
        if (searchEl) {
            let debounce;
            searchEl.addEventListener('input', function() {
                clearTimeout(debounce);
                debounce = setTimeout(function() {
                    const pageInput = document.getElementById('eventListPage');
                    if (pageInput) pageInput.value = 1;
                    loadEventList();
                }, 400);
            });
        }

        loadEventList();
    });

    window.refreshEventList = loadEventList;
})();
</script>
@endsection