@extends('backend.layout.app')

@section('content')
<div class="row">
    <div class="col-lg-10 mt-3 mx-auto">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 h6">Staff Information</h5>
            </div>

            <form action="{{ route('staffs.update', $staff->id) }}" method="POST">
                @method('PATCH')
                @csrf
                <div class="card-body">
                    {{-- Title dropdown --}}
                    <div class="form-group row">
                        <label class="col-sm-3 col-from-label" for="title">Title</label>
                        <div class="col-sm-9">
                            <select id="title" name="title" class="form-control">
                                <option value="">Select Title</option>
                                <option value="Mr" {{ $staff->user->title == 'Mr' ? 'selected' : '' }}>Mr</option>
                                <option value="Mrs" {{ $staff->user->title == 'Mrs' ? 'selected' : '' }}>Mrs</option>
                                <option value="Miss" {{ $staff->user->title == 'Miss' ? 'selected' : '' }}>Miss</option>
                                {{-- <option value="Dr" {{ $staff->user->title == 'Dr' ? 'selected' : '' }}>Dr</option> --}}
                            </select>
                        </div>
                    </div>

                    {{-- first name --}}
                    <div class="form-group row">
                        <label class="col-sm-3 col-from-label" for="first_name">First Name</label>
                        <div class="col-sm-9">
                            <input type="text" placeholder="First Name" id="first_name" name="first_name" value="{{ $staff->user->first_name }}" class="form-control" required>
                        </div>
                    </div>
                    {{-- middle name --}}
                    <div class="form-group row">
                        <label class="col-sm-3 col-from-label" for="middle_name">Middle Name</label>
                        <div class="col-sm-9">
                            <input type="text" placeholder="Middle Name" id="middle_name" name="middle_name" value="{{ $staff->user->middle_name }}" class="form-control">
                        </div>
                    </div>
                    {{-- last name --}}
                    <div class="form-group row">
                        <label class="col-sm-3 col-from-label" for="last_name">Last Name</label>
                        <div class="col-sm-9">
                            <input type="text" placeholder="Last Name" id="last_name" name="last_name" value="{{ $staff->user->last_name }}" class="form-control" required>
                        </div>
                    </div>

                    {{-- <div class="form-group row">
                        <label class="col-sm-3 col-from-label" for="name">Name</label>
                        <div class="col-sm-9">
                            <input type="text" placeholder="Name" id="name" name="name" value="{{ $staff->user->name }}" class="form-control" required>
                        </div>
                    </div> --}}

                    <div class="form-group row">
                        <label class="col-sm-3 col-from-label" for="email">Email</label>
                        <div class="col-sm-9">
                            <div class="input-group">
                                <input type="text" placeholder="Email" id="email" name="email" value="{{ $staff->user->email }}" class="form-control" required>
                                <button type="button" class="btn btn-danger" id="add-email-btn" title="Add another email">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Extra Emails --}}
                    <div id="extra-emails-list">
                        @foreach($staff->emails as $contact)
                            <div class="form-group row contact-row">
                                <div class="col-sm-9 offset-sm-3">
                                    <div class="input-group">
                                        <input type="text" name="extra_emails[]" class="form-control" placeholder="Enter email address" value="{{ $contact->value }}">
                                        <button type="button" class="btn btn-outline-danger remove-contact-btn" title="Remove">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    

                    {{-- Primary Phone --}}
                    <div class="form-group row">
                        <label class="col-sm-3 col-from-label" for="phone">Phone</label>
                        <div class="col-sm-9">
                            <div class="input-group">
                                <input type="text" placeholder="Phone Number" id="phone" name="phone" value="{{ $staff->user->phone }}" class="form-control">
                                <button type="button" class="btn btn-danger" id="add-phone-btn" title="Add another phone">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Extra Phones --}}
                    <div id="extra-phones-list">
                        @foreach($staff->phones as $contact)
                            <div class="form-group row contact-row">
                                <div class="col-sm-9 offset-sm-3">
                                    <div class="input-group">
                                        <input type="text" name="extra_phones[]" class="form-control" placeholder="Enter phone number" value="{{ $contact->value }}">
                                        <button type="button" class="btn btn-outline-danger remove-contact-btn" title="Remove">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="form-group row">
                        <label class="col-sm-3 col-from-label" for="password">Password</label>
                        <div class="col-sm-9">
                            <input type="password" placeholder="Password" id="password" name="password" class="form-control">
                        </div>
                    </div>

                    {{-- Designation --}}
                    <div class="form-group row">
                        <label class="col-sm-3 col-from-label" for="designation_id">Designation</label>
                        <div class="col-sm-9">
                            <select id="designation_id" name="designation_id" class="form-control select2" required>
                                <option value="">Select Designation</option>
                                @foreach($designations as $designation)
                                    <option value="{{ $designation->id }}" {{ $staff->user->designation_id == $designation->id ? 'selected' : '' }}>
                                        {{ $designation->title }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                </div>
                <div class="card-footer text-right">
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="fas fa-save me-1"></i> Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@include('backend.partials.assets.select2')

@section('page.scripts')
<script>
    function addContactRow(listId, inputName, placeholder) {
        const row = $(`
            <div class="form-group row contact-row">
                <div class="col-sm-9 offset-sm-3">
                    <div class="input-group">
                        <input type="text" name="${inputName}[]" class="form-control" placeholder="${placeholder}">
                        <button type="button" class="btn btn-outline-danger remove-contact-btn" title="Remove">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `);
        $(`#${listId}`).append(row);
    }

    $(document).ready(function () {
        initSelect2('.select2');

        $('#add-email-btn').on('click', function () {
            addContactRow('extra-emails-list', 'extra_emails', 'Enter email address');
        });

        $('#add-phone-btn').on('click', function () {
            addContactRow('extra-phones-list', 'extra_phones', 'Enter phone number');
        });

        $(document).on('click', '.remove-contact-btn', function () {
            $(this).closest('.input-group').remove();
        });
    });
</script>
@endsection
