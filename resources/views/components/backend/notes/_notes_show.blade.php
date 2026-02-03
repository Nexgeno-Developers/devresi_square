<dl class="row">
    <dt class="col-sm-3">Type:</dt>
    <dd class="col-sm-9">{{ $note->noteType->name }}</dd>

    <dt class="col-sm-3">Content:</dt>
    <dd class="col-sm-9">{!! $note->content !!}</dd>

    <dt class="col-sm-3">Created:</dt>
    <dd class="col-sm-9">{{ $note->created_at->toDayDateTimeString() }}</dd>
</dl>