<!-- Use asset() to generate the correct URL for JS files -->
<script src="{{ asset('asset/js/jquery.min.js') }}"></script>
{{-- <script src="{{ asset('asset/js/bootstrap.bundle.min.js') }}"></script> --}}
<script src="{{ asset('asset/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('asset/js/toastr.min.js') }}"></script>
<script src="{{ asset('asset/js/jquery.validate.min.js') }}"></script>
<script src="{{ asset('asset/backend/js/Init.js') }}"></script>
<script src="{{ asset('asset/backend/js/vendors.js') }}"></script>
<!-- Tagify JS -->
<script src="https://cdn.jsdelivr.net/npm/@yaireo/tagify/dist/tagify.min.js"></script>
<script src="{{ asset('asset/backend/js/style.js') }}"></script>
<script src="{{ asset('asset/js/dataTables.bootstrap5.min.js') }}"></script>
<script>
    var AIZ = AIZ || {};
    AIZ.local = {
        nothing_selected: 'Nothing selected',
        nothing_found: 'Nothing found',
        choose_file: 'Choose File',
        file_selected: 'File selected',
        files_selected: 'Files selected',
        add_more_files: 'Add more files',
        adding_more_files: 'Adding more files',
        drop_files_here_paste_or: 'Drop files here, paste or',
        browse: 'Browse',
        upload_complete: 'Upload complete',
        upload_paused: 'Upload paused',
        resume_upload: 'Resume upload',
        pause_upload: 'Pause upload',
        retry_upload: 'Retry upload',
        cancel_upload: 'Cancel upload',
        uploading: 'Uploading',
        processing: 'Processing',
        complete: 'Complete',
        file: 'File',
        files: 'Files',
    }
</script>
<script src="{{ asset('asset/backend/js/aiz-core.js') }}"></script>
