<dl class="row">
    <dt class="col-sm-3">Type:</dt>
    <dd class="col-sm-9">{{ $document->documentType->name }}</dd>
    
{{-- <dt class="col-sm-3">Content:</dt>
    <dd class="col-sm-9">{!! $document->content !!}</dd> --}}

   @php
        // Turn CSV into array, or empty array if none
        $uploadIds = $document->upload_ids
                    ? explode(',', $document->upload_ids)
                    : [];
    @endphp

    @if(count($uploadIds))
        <dt class="col-sm-3">Attachments:</dt>
        <dd class="col-sm-9">
            <div class="d-flex flex-wrap align-items-start" style="gap:10px;">
                @foreach($uploadIds as $uid)
                    @php
                        $url     = uploaded_asset($uid);
                        $ext     = strtolower(pathinfo($url, PATHINFO_EXTENSION));
                        $isImage = in_array($ext, ['jpg','jpeg','png','gif','svg','webp']);
                    @endphp

                    <div class="text-center">
                        <a href="{{ $url }}" target="_blank" class="d-block mb-1 text-decoration-none">
                            @if($isImage)
                                <i class="fas fa-image fa-2x"></i>
                            @else
                                <i class="fas fa-file-alt fa-2x"></i>
                            @endif
                        </a>

                        {{-- This will render the modal-trigger “Preview” button --}}
                        {!! attachmentViewer(
                            $url,
                            'Preview',
                            'btn btn-outline-secondary btn-sm',
                            'lg'
                        ) !!}
                    </div>
                @endforeach
            </div>
        </dd>
    @endif

    <dt class="col-sm-3">Created:</dt>
    <dd class="col-sm-9">{{ $document->created_at->toDayDateTimeString() }}</dd>
</dl>