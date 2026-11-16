@if(session('error') || $errors->any())
    {{-- alert-* for Bootstrap pages, auth-alert-* for the sign-in layout (no Bootstrap there). --}}
    <div class="alert alert-danger alert-dismissible fade show text-start auth-alert auth-alert-danger login-errors" role="alert" aria-live="assertive">
        <div class="d-flex align-items-start gap-2">
            <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
            <div>
                <strong>Login failed</strong>

                @if(session('error'))
                    <div>{{ session('error') }}</div>
                @endif

                @if($errors->any())
                    <ul class="mb-0 ps-3">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
