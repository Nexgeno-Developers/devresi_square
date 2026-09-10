<script>
    function openComplianceModal(complianceTypeId, complianceRecordId = null) {
        var propertyEl = document.getElementById('hidden-property-id');
        var propertyId = propertyEl ? (propertyEl.getAttribute('data-property-id') || '') : '';

        let url = complianceRecordId
            ? '{{ route('admin.compliance.type.form', [':complianceTypeId', ':complianceRecordId']) }}'
                .replace(':complianceTypeId', complianceTypeId)
                .replace(':complianceRecordId', complianceRecordId)
            : '{{ route('admin.compliance.type.form', ':complianceTypeId') }}'
                .replace(':complianceTypeId', complianceTypeId);

        $.ajax({
            url: url,
            type: 'GET',
            success: function (response) {
                $('#complianceModalLabel').html(response.heading);
                $('#complianceModalBody').html(response.content);
                var formId = $('#complianceModalBody form').attr('id');
                $("input[name='property_id']").val(propertyId);
                $("input[name='compliance_type_id']").val(complianceTypeId);
                if (window.AIZ && AIZ.uploader && typeof AIZ.uploader.previewGenerate === 'function') {
                    AIZ.uploader.previewGenerate();
                }
                $('#submitComplianceForm').attr('form', formId);
                $('#complianceModal').modal('show');
            },
            error: function () {
                if (window.AIZ && AIZ.plugins) {
                    AIZ.plugins.notify('danger', 'Could not open the certificate form.');
                }
            }
        });
    }

    $(document).on('click', '#submitComplianceForm', function (e) {
        e.preventDefault();
        let formId = $(this).attr('form');
        let formEl = document.getElementById(formId);
        if (!formEl) return;
        let formData = new FormData(formEl);

        $.ajax({
            url: formData.get('record_id')
                ? '{{ route('admin.compliance.update') }}'
                : '{{ route('admin.compliance.store') }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                if (response.success) {
                    AIZ.plugins.notify('success', response.message);
                    $('#complianceModal').modal('hide');
                    location.reload();
                } else {
                    AIZ.plugins.notify('danger', 'Failed to save the certificate.');
                }
            },
            error: function (error) {
                let errorMessage = error.responseJSON?.message || 'Could not save the certificate.';
                AIZ.plugins.notify('danger', errorMessage);
            }
        });
    });

    let certificateRecordIdToDelete = null;

    function confirmCertificateDelete(complianceRecordId) {
        certificateRecordIdToDelete = complianceRecordId;
        $('#certificateDeleteModal').modal('show');
    }

    $(document).on('click', '#confirmCertificateDeleteBtn', function () {
        if (!certificateRecordIdToDelete) return;
        $.ajax({
            url: '{{ route('admin.compliance.delete', ':complianceRecordId') }}'.replace(
                ':complianceRecordId',
                certificateRecordIdToDelete
            ),
            type: 'DELETE',
            data: { _token: '{{ csrf_token() }}' },
            success: function (response) {
                if (response.success) {
                    AIZ.plugins.notify('success', response.message);
                    $('#certificateDeleteModal').modal('hide');
                    location.reload();
                } else {
                    AIZ.plugins.notify('danger', 'Failed to delete the certificate.');
                }
            },
            error: function (error) {
                let errorMessage = error.responseJSON?.message || 'Could not delete the certificate.';
                AIZ.plugins.notify('danger', errorMessage);
            }
        });
    });
</script>
