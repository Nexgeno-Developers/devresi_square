<div class="container">
    <div class="container-fluid mt-4 quick_add_property">
        <div class="row">
            <div class="col thanks_page">
                <div class="left_content_wrapper">
                    <div class="left_title">
                        Thanks, <span class="secondary-color">Your User</span><br /> added <span
                            class="secondary-color">successfully!</span>
                    </div>
                    <div class="thanks_img">
                        <img src="{{ asset('asset/images/thanks.png') }}" alt="thanks">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    setTimeout(function() {
        window.location.href = "{{ route('admin.users.index') }}";
    }, 4000); // Redirects after 4 seconds (4000 ms)
</script>
