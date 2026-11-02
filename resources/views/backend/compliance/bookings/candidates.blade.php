@if($rows->isEmpty())
    <p class="text-muted mb-0">None in this list.</p>
@else
    <div class="table-responsive">
        <table class="table lw-table align-middle mb-0">
            <thead>
                <tr>
                    <th></th>
                    <th>Contractor</th>
                    <th>Distance</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                    <tr>
                        <td>
                            @if($row->can_take)
                                <input type="checkbox" name="contractor_ids[]" value="{{ $row->user_id }}" aria-label="Send to {{ $row->name }}">
                            @endif
                        </td>
                        <td>
                            <div>{{ $row->name }}</div>
                            @if($row->badges)
                                <div class="small text-muted">{{ implode(' · ', $row->badges) }}</div>
                            @endif
                        </td>
                        <td>{{ $row->miles !== null ? $row->miles.' miles' : '—' }}</td>
                        <td>
                            {{ $row->scope }}
                            @if($row->reason && ! $row->can_take)
                                <div class="small text-muted">{{ $row->reason }}</div>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
