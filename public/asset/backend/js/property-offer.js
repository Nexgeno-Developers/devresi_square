
    let currentStep = 1; // Active step
    let tenantForms = [{ id: 1 }]; // Initial tenant
    let nextTenantId = 2;  // Counter to generate new tenant IDs
    const tenantFormsContainer = document.getElementById('tenant-forms');
    const backButton = document.getElementById('backButton');
    const nextButton = document.getElementById('nextButton');
    const addTenantButton = document.getElementById('addTenantButton');
    const submitButton = document.getElementById('submitButton');
    const offerStep = document.getElementById('offer-step');


    // Function to retrieve query parameter from URL
    function getUrlParameter(name) {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get(name); // Return the value of the parameter
    }

    // When the modal is shown
    $('#addOfferModal').on('shown.bs.modal', function() {
        // Get the property_id from the URL
        var propertyId = getUrlParameter('property_id');

        // If propertyId exists, set it in the hidden input field
        if (propertyId) {
            $("input[name='property_id']").val(propertyId);
        }
    });

    // Function to save tenant form data
    function saveTenantFormData() {
        tenantForms.forEach((tenant, index) => {
            const tenantForm = document.querySelector(`.tenant-form[data-step='${index + 1}']`);
            if (tenantForm) {
                tenant.name = document.getElementById(`tenantName_${tenant.id}`).value || '';
                tenant.phone = document.getElementById(`tenantPhone_${tenant.id}`).value || '';
                tenant.email = document.getElementById(`tenantEmail_${tenant.id}`).value || '';
                tenant.employmentStatus = document.getElementById(`employmentStatus_${tenant.id}`).value || '';
                tenant.businessName = document.getElementById(`businessName_${tenant.id}`).value || '';
                tenant.guarantee = document.getElementById(`guarantee_${tenant.id}`).value || '';
                tenant.previouslyRented = document.getElementById(`previouslyRented_${tenant.id}`).value || '';
                tenant.poorCredit = document.getElementById(`poorCredit_${tenant.id}`).value || '';
                tenant.mainPerson = document.getElementById(`mainPerson_${tenant.id}`).checked;
            }
        });
        // Update hidden field for main person
        const mainPersonId = tenantForms.find(tenant => tenant.mainPerson)?.id || null;
        document.getElementById('mainPersonId').value = mainPersonId;

    }

    // Function to render tenant forms dynamically
    function renderTenantForms() {
        saveTenantFormData(); // Save current form data before rendering
        tenantFormsContainer.innerHTML = '';

        tenantForms.forEach((tenant, index) => {
            const tenantForm = document.createElement('div');
            tenantForm.className = `tenant-form step ${currentStep === index + 1 ? '' : 'hidden'}`;
            tenantForm.setAttribute('data-step', index + 1);
            tenantForm.innerHTML = `
                <h6 class="mb-3">Tenant ${index + 1} Detail</h6>
                <div class="row g-3 tenant-section">
                    <div class="form-group d-flex align-items-center gap-2">
                        <input type="checkbox" class="form-check-input" id="mainPerson_${tenant.id}"
                            name="mainPerson_${tenant.id}" ${tenant.mainPerson ? 'checked' : ''}
                            onclick="setMainPerson(${index})"
                            ${tenant.mainPerson || tenant.isFilled ? '' : 'disabled'}>
                        <label class="form-check-label" for="mainPerson_${tenant.id}">Check if main person</label>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="tenantName_${tenant.id}" class="form-label">Tenant Name</label>
                            <input type="text" class="form-control" id="tenantName_${tenant.id}"
                                name="tenantName_${tenant.id}" placeholder="Tenant Name" value="${tenant.name || ''}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="tenantPhone_${tenant.id}" class="form-label">Phone</label>
                            <input type="text" class="form-control" id="tenantPhone_${tenant.id}"
                                name="tenantPhone_${tenant.id}" placeholder="1234567890" value="${tenant.phone || ''}" pattern="\\d+" inputmode="numeric" title="Please enter only numbers" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="tenantEmail_${tenant.id}" class="form-label">Email</label>
                            <input type="email" class="form-control" id="tenantEmail_${tenant.id}"
                                name="tenantEmail_${tenant.id}" placeholder="tenant.email@gmail.com" value="${tenant.email || ''}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="employmentStatus_${tenant.id}" class="form-label">Employment Status</label>
                            <select class="form-select" id="employmentStatus_${tenant.id}"
                                name="employmentStatus_${tenant.id}" required>
                                <option disabled ${!tenant.employmentStatus ? 'selected' : ''}>Choose...</option>
                                <option ${tenant.employmentStatus === 'Self Employed' ? 'selected' : ''}>Self Employed</option>
                                <option ${tenant.employmentStatus === 'Employed' ? 'selected' : ''}>Employed</option>
                                <option ${tenant.employmentStatus === 'Unemployed' ? 'selected' : ''}>Unemployed</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label id="businessName_label_${tenant.id}" for="businessName_${tenant.id}" class="form-label">
                            ${
                                tenant.employmentStatus === 'Employed'
                                    ? 'Enter Company Name'
                                    : 'Business Name'
                            }</label>
                            <input type="text" class="form-control" id="businessName_${tenant.id}"
                                name="businessName_${tenant.id}" placeholder="Rainbow Ltd." value="${tenant.businessName || ''}" ${
                                tenant.employmentStatus === 'Unemployed'
                                    ? ''
                                    : 'required'
                            }>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="guarantee_${tenant.id}" class="form-label">Guarantee Required</label>
                            <select class="form-select" id="guarantee_${tenant.id}"
                                name="guarantee_${tenant.id}" required>
                                    <option value="0" ${tenant.guarantee === '0' ? 'selected' : ''}>No</option>
                                    <option value="1" ${tenant.guarantee === '1' ? 'selected' : ''}>Yes</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="previouslyRented_${tenant.id}" class="form-label">Previously Rented</label>
                            <select class="form-select" id="previouslyRented_${tenant.id}"
                                name="previouslyRented_${tenant.id}" required>
                                    <option value="0" ${tenant.previouslyRented === '0' ? 'selected' : ''}>No</option>
                                    <option value="1" ${tenant.previouslyRented === '1' ? 'selected' : ''}>Yes</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="poorCredit_${tenant.id}" class="form-label">Poor Credit History</label>
                            <select class="form-select" id="poorCredit_${tenant.id}"
                                name="poorCredit_${tenant.id}" required>
                                    <option value="0" ${tenant.poorCredit === '0' ? 'selected' : ''}>No</option>
                                    <option value="1" ${tenant.poorCredit === '1' ? 'selected' : ''}>Yes</option>
                            </select>
                        </div>
                    </div>
                </div>
                ${index > 0 ? `<button type="button" class="btn btn-danger btn-sm mt-3" onclick="deleteTenant(${index})">Delete</button>` : ''}
            `;
            tenantFormsContainer.appendChild(tenantForm);
                    // Add event listeners for all required fields
            const requiredFields = tenantForm.querySelectorAll('input[required], select[required]');
            requiredFields.forEach(field => {
                field.addEventListener(field.type === "radio" || field.type === "checkbox" ? 'change' : 'input', () => {
                    checkAndEnableMainPersonCheckbox(tenant.id); // Check if all fields are filled
                });
            });

            // — NEW: when Employment Status changes, update label/text + required‐ness of Business Name —
            const empSelect = tenantForm.querySelector(`#employmentStatus_${tenant.id}`);
            const businessLabel = tenantForm.querySelector(`#businessName_label_${tenant.id}`);
            const businessInput = tenantForm.querySelector(`#businessName_${tenant.id}`);

            empSelect.addEventListener('change', function() {
                if (this.value === 'Unemployed') {
                    businessLabel.textContent = 'Business Name';
                    businessInput.removeAttribute('required');
                } else if (this.value === 'Employed') {
                    businessLabel.textContent = 'Enter Company Name';
                    businessInput.setAttribute('required', 'required');
                    businessInput.classList.remove('is-invalid'); // Remove any previous validation error
                } else {
                    // e.g. 'Self Employed' or any other
                    businessLabel.textContent = 'Business Name';
                    businessInput.setAttribute('required', 'required');
                }
                // Also update tenantForms array so saveTenantFormData() picks up the new value:
                saveTenantFormData();
            });

        });
    }

    // Function to check if all required fields are filled and enable the checkbox
    function checkAndEnableMainPersonCheckbox(tenantId) {
        const tenantForm = document.querySelector(`.tenant-form[data-step='${tenantId}']`);
        if (!tenantForm) return; // Ensure the form exists

        const requiredFields = tenantForm.querySelectorAll('input[required], select[required]');
        const allFieldsFilled = Array.from(requiredFields).every(field => {
            return field.value.trim() !== ''; // Check if input/select has a value
        });

        const mainPersonCheckbox = document.getElementById(`mainPerson_${tenantId}`);
        if (allFieldsFilled) {
            mainPersonCheckbox.disabled = false; // Enable the checkbox if all fields are filled
        } else {
            mainPersonCheckbox.disabled = true; // Disable the checkbox if fields are missing
        }

        // Also track if the tenant's form is completely filled
        const tenant = tenantForms.find(t => t.id === tenantId);
        tenant.isFilled = allFieldsFilled;
    }

    // Function to ensure only one "main person" is selected
    function setMainPerson(selectedIndex) {
        tenantForms.forEach((tenant, index) => {
            // Uncheck all main person checkboxes and reset their mainPerson status
            const checkbox = document.getElementById(`mainPerson_${tenant.id}`);
            checkbox.checked = false;  // Uncheck the checkbox
            tenant.mainPerson = false; // Set mainPerson status to false
        });

        // Set the selected tenant as the main person
        tenantForms[selectedIndex].mainPerson = true;

        // Update the checkbox of the selected tenant and set it as checked
        const mainPersonCheckbox = document.getElementById(`mainPerson_${tenantForms[selectedIndex].id}`);
        mainPersonCheckbox.checked = true;

        // Update hidden input field with the ID of the main person
        document.getElementById('mainPersonId').value = tenantForms[selectedIndex].id;

        // Re-render the tenant forms to reflect the changes
        renderTenantForms();
    }

    // Navigation Logic
    function updateStep() {
        const totalSteps = tenantForms.length + 1;
        backButton.classList.toggle('hidden', currentStep === 1);
        nextButton.classList.toggle('hidden', currentStep === totalSteps);
        addTenantButton.classList.toggle('hidden', currentStep !== tenantForms.length);
        submitButton.classList.toggle('hidden', currentStep !== totalSteps);
        offerStep.classList.toggle('hidden', currentStep !== totalSteps);

        document.querySelectorAll('.tenant-form').forEach((form, index) => {
            form.classList.toggle('hidden', currentStep !== index + 1);
        });
    }

    // Validate the current tenant form
    function validateTenantForm() {
        const currentForm = document.querySelector(`.tenant-form[data-step='${currentStep}']`);
        const inputs = currentForm.querySelectorAll('input, select');
        for (const input of inputs) {
            if (!input.checkValidity()) {
                input.focus();
                return false;
            }
        }
        return true;
    }

    // Add Tenant
    function addTenant() {
        if (!validateTenantForm()) {
            initValidate('.tenantOfferForm');
            // alert("Please fill out all required fields for the current tenant.");
            return;
        }
        saveTenantFormData();
        // tenantForms.push({ id: Date.now() });

        // Add a new tenant with a proper ID
        tenantForms.push({ id: nextTenantId, mainPerson: false });
        nextTenantId++;  // Increment the tenant ID counter
        currentStep = tenantForms.length;
        renderTenantForms();
        updateStep();
    }

    // Delete Tenant
    function deleteTenant(index) {
        tenantForms.splice(index, 1);
        if (currentStep > tenantForms.length) currentStep--;
        renderTenantForms();
        updateStep();
    }

    // Event Listeners
    nextButton.addEventListener('click', () => {
        if (validateTenantForm()) {
            saveTenantFormData();
            if (currentStep <= tenantForms.length) currentStep++;
            updateStep();
        } else {
            initValidate('.tenantOfferForm');
            // alert("Please fill out all required fields for the current tenant.");
        }
    });

    backButton.addEventListener('click', () => {
        saveTenantFormData();
        if (currentStep > 1) currentStep--;
        updateStep();
    });

    submitButton.addEventListener('click', (e) => {
        e.preventDefault();

        console.log('Submit button clicked');

        // Ensure that at least one tenant is selected as the "main person"
        const mainPersonSelected = tenantForms.some(tenant => tenant.mainPerson);
        console.log('Main person selected:', mainPersonSelected);

        if (!mainPersonSelected) {
            alert("Please select at least one tenant as the main person.");
            return; // Prevent form submission
        }

        // Validate the form before proceeding
        if (initValidate('.tenantOfferForm')) {
            console.log('Form validation passed');
            // Get the form's action and method
            const formAction = document.getElementById('tenantOfferForm').getAttribute('action');
            const formMethod = document.getElementById('tenantOfferForm').getAttribute('method');
            console.log('Form action:', formAction);
            console.log('Form method:', formMethod);

            // Serialize the form data
            const formData = new FormData(document.getElementById('tenantOfferForm'));
            console.log('Form data:', formData);

            // Send the AJAX request
            $.ajax({
                url: formAction, // Use the action (URL) from the form
                method: formMethod, // Use the method (POST) from the form
                data: formData, // Send the serialized form data
                processData: false, // Don't process the data
                contentType: false, // Don't set content type (important for FormData)
                success: function (response) {
                    // console.log('Form submitted successfully');
                    AIZ.plugins.notify('success', response.message);
                    // alert('Form Submitted Successfully!');
                    console.log('Response:', response);
                    // Optionally clear the form and close the modal here
                    clearForm();
                    $('#addOfferModal').modal('hide');  // Close modal (if using Bootstrap modal)
                    location.reload(); // Reload the page to reflect changes
                },
                error: function (xhr, status, error) {
                    let errorMessage = error.responseJSON?.message || 'Error Generating Invoice';
                    AIZ.plugins.notify('danger', errorMessage);
                    // Handle error (e.g., display an error message)
                    // alert('An error occurred. Please try again later.');
                    console.error('AJAX Error:', error);
                }
            });

        } else {
            console.log('Form validation failed');
        }
    });

    // Event listener for modal close (Cancel or Close button)
    document.getElementById('addOfferModal').addEventListener('hidden.bs.modal', function () {
        console.log("Modal hidden event triggered");
        clearForm();
    });


    // Function to clear the form
    function clearForm() {
        console.log('Clear form initiated');

        // Reset the main person ID hidden field
        document.getElementById('mainPersonId').value = '';
        console.log('Main person ID reset');

        // Reset the form elements (all inputs, selects, etc.)
        const form = document.getElementById('tenantOfferForm');
        if (form) {
            form.reset();  // This will reset the form inputs
            console.log('Form reset');
        } else {
            console.error('Form not found!');
        }

        // Reset tenant data and remove any main person selection
        tenantForms = [{ id: 1 }];  // Reset to initial tenant
        nextTenantId = 2;  // Reset tenant ID counter

        // Ensure renderTenantForms is called properly
        if (typeof renderTenantForms === 'function') {
            console.log('Rendering tenant forms');
            renderTenantForms();  // Re-render tenant forms
        } else {
            console.error('renderTenantForms function not found!');
        }

        // Reset all offer fields, including checkboxes and radio buttons
        const offerFields = form.querySelectorAll('input, select'); // Get all inputs and selects (includes checkbox, radio, etc.)
        offerFields.forEach(field => {
            // Reset input fields (text, number, etc.)
            if (field.type === 'text' || field.type === 'number' || field.type === 'date') {
                field.value = '';
            }
            // Reset radio buttons
            if (field.type === 'radio') {
                field.checked = false;  // Uncheck radio buttons
            }
            // Reset checkboxes
            if (field.type === 'checkbox') {
                field.checked = false;  // Uncheck checkboxes
            }
            // Reset select elements
            if (field.tagName === 'SELECT') {
                field.selectedIndex = 1;  // Reset the selected index
            }
        });
        console.log('Offer fields cleared');

        // Hide the submit button and other steps if needed
        submitButton.classList.add('hidden');
        offerStep.classList.add('hidden');
        console.log('Submit button and offer step hidden');

        // Reset current step and show the first step again
        currentStep = 1;
        if (typeof updateStep === 'function') {
            console.log('Updating step');
            updateStep(); // Update step (show the first step)
        } else {
            console.error('updateStep function not found!');
        }
    }


    // Initialize
    renderTenantForms();
    updateStep();

    $(document).ready(function () {
        // Set as Main Person
        $(document).on('click', '.make-main-btn', function () {
            const id = $(this).data('id');
            const member = $(this).data('member');
            const userId = $(this).data('userid');

            // Send AJAX request to update the main person
            $.ajax({
                url: `/admin/offers/${id}/set-main-person`, // Adjusted for the admin route prefix
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'), // CSRF token
                    member: member,
                    userId: userId,
                },
                success: function (response) {
                    if (response.status) {
                        AIZ.plugins.notify('success', response.message);
                        // alert('Main person updated successfully!');
                        location.reload(); // Reload the page to reflect changes
                    } else {
                        AIZ.plugins.notify('danger', 'Failed to update the main person.');
                        // alert('Failed to update the main person.');
                    }
                },
                error: function (error) {
                    console.error(error);
                    let errorMessage = error.responseJSON?.message || 'An error occurred while saving the compliance record.';
                    AIZ.plugins.notify('danger', errorMessage);
                }
                // error: function (xhr) {
                    // console.error(xhr.responseText);
                    // alert('An error occurred. Please try again.');
                // },
            });
        });

        // Accept or Reject Offer
        $(document).on('click', '.status-btn', function () {
            const id = $(this).data('id');
            const status = $(this).data('status');
            const confirmationMessage =
                status === 'Accepted'
                    ? 'Are you sure you want to accept this offer?'
                    : 'Are you sure you want to reject this offer?';

            // Show confirmation dialog
            if (confirm(confirmationMessage)) {
                // Send AJAX request to update the status
                $.ajax({
                    url: `/admin/offers/${id}/update-status`, // Adjusted for the admin route prefix
                    type: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'), // CSRF token
                        status: status,
                    },
                    success: function (response) {
                        if (response.status) {
                            AIZ.plugins.notify('success', response.message);
                            location.reload();
                            // alert(`Offer status updated to ${status}.`);
                            // $(`#status-${id}`).text(status); // Update the status badge dynamically
                            // if (status === 'Accepted') {
                            //     $(`.status-btn[data-id="${id}"]`).hide(); // Hide buttons after accepting
                            // }
                        } else {
                            alert('Failed to update offer status.');
                        }
                    },
                    error: function (xhr) {
                        console.error(xhr.responseText);
                        alert('An error occurred. Please try again.');
                    },
                });
            }
        });
    });
