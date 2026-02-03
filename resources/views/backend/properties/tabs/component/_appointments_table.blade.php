@if ($events->isEmpty())
    <p class="text-muted">No appointments found for this property.</p>
@else
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Title</th>
                <th>Start</th>
                <th>End</th>
                <th>Diary Of</th>
                <th>Booked By</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($events as $event)
                <tr data-id="{{ $event->id }}" data-start="{{ $event->start_datetime }}">
                    <td>{{ $event->title }}</td>
                    <td>{{ formatDateTime($event->start_datetime) }}</td>
                    <td>{{ formatDateTime($event->end_datetime) }}</td>
                    <td>{{ optional($event->diaryOwner)->name ?? '—' }}</td>
                    <td>{{ optional($event->onBehalfOf)->name ?? '—' }}</td>
                    <td>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
                                data-bs-toggle="dropdown">
                                {{ ucfirst($event->status) }}
                            </button>
                            <ul class="dropdown-menu">
                                @foreach (['confirmed', 'pending', 'cancelled', 'schedule', 'completed'] as $status)
                                    @if (ucfirst($status) !== $event->status)
                                        <li>
                                            <a class="dropdown-item change-status-btn" href="#"
                                                data-id="{{ $event->id }}" data-status="{{ $status }}">
                                                Mark as {{ ucfirst($status) }}
                                            </a>
                                        </li>
                                    @endif
                                @endforeach
                            </ul>
                        </div>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-info btn-edit" data-id="{{ $event->id }}">Edit</button>
                        {{-- <button class="btn btn-sm btn-danger btn-delete" data-url="{{ route('backend.events.deleteInstance', $event->id) }}" data-id="{{ $event->id }}">Delete</button> --}}
                        <div class="dropdown">
                            <button class="btn btn-danger btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                Delete
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item delete-option" href="#" data-label="single" data-type="single">Delete This</a></li>
                                <li><a class="dropdown-item delete-option" href="#" data-label="series" data-type="series">Delete Series</a></li>
                                <li><a class="dropdown-item delete-option" href="#" data-label="this and future" data-type="future">Delete Future</a></li>
                            </ul>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Laravel pagination --}}
    <div class="mt-3">
        {!! $events->appends(request()->query())->links() !!}
    </div>
@endif
