{{-- resources/views/components/form.blade.php --}}

@props([
    'action' => '',
    'formId' => 'crmForm',
    'submitText' => 'Submit',
    'successMessage' => 'Thank you! Your form has been submitted.',
])
<form
    id="{{ $formId }}"
    action="{{ $action }}"
    method="POST"
    onsubmit="return submitCrmFormAjax(this);"
    class="crm-form needs-validation"
>
    @csrf

    <div class="row g-3">
        <div class="col-md-6">
            <div class="form-floating">
                <input type="text" name="first_name" class="form-control" id="first_name" required placeholder="First Name" required>
                <label for="first_name">First Name</label>
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-floating">
                <input type="text" name="last_name" class="form-control" id="last_name" placeholder="Last Name">
                <label for="last_name">Last Name</label>
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-floating">
                <input type="email" name="email" class="form-control" id="email" required placeholder="Email" required>
                <label for="email">Email</label>
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-floating">
                <input type="text" name="phone" class="form-control" id="phone" required placeholder="Phone">
                <label for="phone">Phone</label>
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-floating">
                <input type="date" name="demo_date" class="form-control" id="demo_date" required placeholder="Demo Date">
                <label for="demo_date">Demo Date</label>
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-floating">
                <input type="time" name="demo_time" class="form-control" id="demo_time" required placeholder="Demo Time">
                <label for="demo_time">Demo Time</label>
            </div>
        </div>

        <div class="col-12">
            <div class="form-floating">
                <input type="text" name="hear_about" class="form-control" id="hear_about" placeholder="How did you hear about us?">
                <label for="hear_about">How did you hear about us?</label>
            </div>
        </div>

        <div class="col-12">
            <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" name="subscribe" id="subscribe">
                <label class="form-check-label" for="subscribe">Yes! I'd like to receive news and updates by email.</label>
            </div>
        </div>

        <div class="col-12">
            <button type="submit" class="btn btn-primary w-100">{{ $submitText }}</button>
        </div>

        <div class="col-12">
            <div class="form-success-message mt-2 text-success d-none">
                {{ $successMessage ?? 'Thank you! We will user you soon.' }}
            </div>
        </div>
    </div>
</form>

@once
    <script>
        function submitCrmFormAjax(form) {
            const $form = $(form);
            const formData = new FormData(form);

            $.ajax({
                url: $form.attr('action'),
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    const $successMsg = $form.find('.form-success-message');
                    // Remove the d-none class and fade in the message
                    $successMsg.removeClass('d-none').fadeIn();
                    // Show success message for 3 seconds
                    setTimeout(function () {
                        // Fade out the message
                        $successMsg.fadeOut(function () {
                            // Optionally re-add d-none after fade out
                            $successMsg.addClass('d-none');
                        });
                        // Close the modal if the form is inside one
                        const $modal = $form.closest('.modal');
                        if ($modal.length) {
                            $modal.modal('hide');
                        }
                        // Reset the form
                        $form[0].reset();
                    }, 3000); // 3 seconds
                },
                error: function (xhr) {
                    const errors = xhr.responseJSON.errors;
                    if (errors) {
                        alert('Please fix the errors and try again.');
                        console.log(errors);
                    }
                }
            });

            return false;
        }
    </script>
@endonce
