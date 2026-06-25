<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #111; font-size: 12px; line-height: 1.45; }
        h1 { font-size: 22px; margin: 0 0 12px; }
        h2 { font-size: 15px; margin: 18px 0 8px; border-bottom: 1px solid #ddd; padding-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td { border: 1px solid #ddd; padding: 7px; text-align: left; vertical-align: top; }
        th { width: 28%; background: #f5f5f5; }
        .photo { display: inline-block; width: 150px; height: 110px; object-fit: cover; margin: 0 8px 8px 0; border: 1px solid #ddd; }
    </style>
</head>
<body>
    <h1>Scope of Work</h1>
    <p><strong>Repair Reference:</strong> {{ $repairIssue->reference_number }}</p>

    <h2>Property</h2>
    <table>
        <tr>
            <th>Property</th>
            <td>{{ $repairIssue->property->prop_name ?? '-' }}</td>
        </tr>
        <tr>
            <th>Address</th>
            <td>
                {{ $repairIssue->property->line_1 ?? '' }}
                {{ $repairIssue->property->line_2 ?? '' }},
                {{ $repairIssue->property->city ?? '' }}
                {{ $repairIssue->property->postcode ?? '' }}
            </td>
        </tr>
        <tr>
            <th>Type</th>
            <td>{{ $repairIssue->property->specific_property_type ?? '-' }}</td>
        </tr>
    </table>

    <h2>Issue</h2>
    <table>
        <tr>
            <th>Issue In</th>
            <td>{{ getRepairCategoryDetails($repairIssue->repair_category_id) }}</td>
        </tr>
        <tr>
            <th>Navigation</th>
            <td>{!! getFormattedRepairNavigation($repairIssue->repair_navigation) !!}</td>
        </tr>
        <tr>
            <th>Description</th>
            <td>{{ $repairIssue->description }}</td>
        </tr>
        <tr>
            <th>Priority</th>
            <td>{{ ucfirst($repairIssue->priority) }}</td>
        </tr>
        <tr>
            <th>Status</th>
            <td>{{ $repairIssue->status }}</td>
        </tr>
        <tr>
            <th>Access Details</th>
            <td>{{ $repairIssue->access_details ?: '-' }}</td>
        </tr>
    </table>

    <h2>Issue Photos</h2>
    @php $hasPhotos = false; @endphp
    @foreach($repairIssue->repairPhotos as $photo)
        @foreach(explode(',', (string) $photo->photos) as $photoId)
            @php
                $photoId = trim($photoId);
                $upload = $photoId ? \App\Models\Upload::find($photoId) : null;
                $path = $upload && ! $upload->external_link ? storage_path('app/public/' . $upload->file_name) : null;
                $mime = $path && is_file($path) ? mime_content_type($path) : null;
                $url = $path && $mime ? 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path)) : null;
                $hasPhotos = $hasPhotos || (bool) $url;
            @endphp
            @if($url)
                <img src="{{ $url }}" class="photo" alt="Repair photo">
            @endif
        @endforeach
    @endforeach
    @if(! $hasPhotos)
        <p>No issue photos attached.</p>
    @endif
</body>
</html>
