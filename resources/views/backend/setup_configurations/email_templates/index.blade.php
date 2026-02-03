@extends('backend.layout.app')

@section('content')

    <div class="text-left mt-2 mb-3">
        <div class="align-items-center">
            <h1 class="h3">Email Templates</h1>
        </div>
    </div>

    <div class="card">
        <div class="card-header row gutters-5 align-items-center">
            <div class="col text-left">
                <h5 class="mb-md-0 h6">{{ ucfirst($emailReceiver).' '.'Email Templates' }}</h5>
            </div>
            <div class="col-md-4">
                <form class="" id="sort_email_templates" action="" method="GET">
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control form-control-sm h-100" name="email_template_sort_search" @isset($email_template_sort_search) value="{{ $email_template_sort_search }}" @endisset placeholder="Type & Enter">
                        <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button>
                    </div>
                </form>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Email Type</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($emailTemplates as $key => $emailTemplate)
                            <tr>
                                <td>{{ ($key+1) + ($emailTemplates->currentPage() - 1)*$emailTemplates->perPage() }}</td>
                                <td>{{ $emailTemplate->email_type }}</td>
                                <td>
                                    {{ $emailTemplate->subject }}</td>
                                <td>
                                    <label class="aiz-switch aiz-switch-success mb-0">
                                        <input onchange="update_status(this)" 
                                            value="{{ $emailTemplate->id }}"
                                            type="checkbox" 
                                            @if($emailTemplate->status == 1) checked @endif
                                            @if($emailTemplate->is_status_changeable == 0) disabled @endif>
                                        <span class="slider round"></span>
                                    </label>
                                </td>
                                <td class="text-right">
                                     <a class="btn btn-sm btn-outline-primary" href="{{ route('email-templates.edit', $emailTemplate->id) }}" title="Edit">
                                        <i class="fa fa-edit"></i> Edit
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                                    No email templates found
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="aiz-pagination">
                    {{ $emailTemplates->appends(request()->input())->links() }}
                </div>
            </div>

        </div>
    </div>
@endsection


@section('script')
    <script type="text/javascript">
        function sort_email_templates(value) {
            $('input[name="email_user_type"]').val(value);
            $('#sort_email_templates').submit();
        }

        function update_status(el) {
            var status = el.checked ? 1 : 0;
            $.post('{{ route('email-template.update-status') }}', {
                _token: '{{ csrf_token() }}',
                id: el.value,
                status: status
            }, function(data) {
                if (data == 1) {
                    AIZ.plugins.notify('success',
                        'Email Template status updated successfully');
                } else {
                    AIZ.plugins.notify('danger', 'Something went wrong');
                }
            });
        }

        $(document).on("change", ".check-all", function() {
            $('.check-one:checkbox').prop('checked', this.checked);
        });
    </script>
@endsection
