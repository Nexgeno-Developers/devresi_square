@extends('backend.layout.app')

@section('content')

<section class="smtp-settings mt-3 ">
    <div class="row">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0 h6">SMTP Settings</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('env_key_update.update') }}" method="POST">
                        @csrf

                        <!-- Mail Driver -->
                        <div class="mb-3">
                            <input type="hidden" name="types[]" value="MAIL_DRIVER">
                            <label for="MAIL_DRIVER" class="form-label fw-bold">Mail Driver</label>
                            <select id="MAIL_DRIVER" class="form-select" name="MAIL_DRIVER" onchange="checkMailDriver()">
                                <option value="sendmail" @if(env('MAIL_DRIVER')=="sendmail") selected @endif>Sendmail</option>
                                <option value="smtp" @if(env('MAIL_DRIVER')=="smtp") selected @endif>SMTP</option>
                                <option value="mailgun" @if(env('MAIL_DRIVER')=="mailgun") selected @endif>Mailgun</option>
                            </select>
                        </div>

                        <!-- Accordion -->
                        <div class="accordion" id="mailAccordion">
                            
                            <!-- SMTP Section -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingSmtp">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSmtp" aria-expanded="false" aria-controls="collapseSmtp">
                                        SMTP Configuration
                                    </button>
                                </h2>
                                <div id="collapseSmtp" class="accordion-collapse collapse" aria-labelledby="headingSmtp" data-bs-parent="#mailAccordion">
                                    <div class="accordion-body">
                                        
                                        <div class="mb-3">
                                            <input type="hidden" name="types[]" value="MAIL_HOST">
                                            <label class="form-label">Mail Host</label>
                                            <input type="text" class="form-control" name="MAIL_HOST" value="{{ env('MAIL_HOST') }}" placeholder="smtp.mailtrap.io">
                                        </div>

                                        <div class="mb-3">
                                            <input type="hidden" name="types[]" value="MAIL_PORT">
                                            <label class="form-label">Mail Port</label>
                                            <input type="text" class="form-control" name="MAIL_PORT" value="{{ env('MAIL_PORT') }}" placeholder="587">
                                        </div>

                                        <div class="mb-3">
                                            <input type="hidden" name="types[]" value="MAIL_USERNAME">
                                            <label class="form-label">Username</label>
                                            <input type="text" class="form-control" name="MAIL_USERNAME" value="{{ env('MAIL_USERNAME') }}" placeholder="Your email username">
                                        </div>

                                        <div class="mb-3">
                                            <input type="hidden" name="types[]" value="MAIL_PASSWORD">
                                            <label class="form-label">Password</label>
                                            <input type="password" class="form-control" name="MAIL_PASSWORD" value="{{ env('MAIL_PASSWORD') }}" placeholder="Your email password">
                                        </div>

                                        <div class="mb-3">
                                            <input type="hidden" name="types[]" value="MAIL_ENCRYPTION">
                                            <label class="form-label">Encryption</label>
                                            <input type="text" class="form-control" name="MAIL_ENCRYPTION" value="{{ env('MAIL_ENCRYPTION') }}" placeholder="tls/ssl">
                                        </div>

                                        <div class="mb-3">
                                            <input type="hidden" name="types[]" value="MAIL_FROM_ADDRESS">
                                            <label class="form-label">From Address</label>
                                            <input type="email" class="form-control" name="MAIL_FROM_ADDRESS" value="{{ env('MAIL_FROM_ADDRESS') }}" placeholder="noreply@example.com">
                                        </div>

                                        <div class="mb-3">
                                            <input type="hidden" name="types[]" value="MAIL_FROM_NAME">
                                            <label class="form-label">From Name</label>
                                            <input type="text" class="form-control" name="MAIL_FROM_NAME" value="{{ env('MAIL_FROM_NAME') }}" placeholder="Your App Name">
                                        </div>

                                    </div>
                                </div>
                            </div>

                            <!-- Mailgun Section -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingMailgun">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseMailgun" aria-expanded="false" aria-controls="collapseMailgun">
                                        Mailgun Configuration
                                    </button>
                                </h2>
                                <div id="collapseMailgun" class="accordion-collapse collapse" aria-labelledby="headingMailgun" data-bs-parent="#mailAccordion">
                                    <div class="accordion-body">
                                        
                                        <div class="mb-3">
                                            <input type="hidden" name="types[]" value="MAILGUN_DOMAIN">
                                            <label class="form-label">Mailgun Domain</label>
                                            <input type="text" class="form-control" name="MAILGUN_DOMAIN" value="{{ env('MAILGUN_DOMAIN') }}" placeholder="example.com">
                                        </div>

                                        <div class="mb-3">
                                            <input type="hidden" name="types[]" value="MAILGUN_SECRET">
                                            <label class="form-label">Mailgun Secret</label>
                                            <input type="text" class="form-control" name="MAILGUN_SECRET" value="{{ env('MAILGUN_SECRET') }}" placeholder="Your Mailgun secret key">
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Save Button -->
                        <div class="text-end mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i> Save Configuration
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Right Column (unchanged test + instructions) --}}
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 h6">Test SMTP configuration</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('test.smtp') }}" method="post">
                        @csrf
                        <div class="row">
                            <div class="col">
                                <input type="email" class="form-control" name="email" value="{{ auth()->user()->email }}" placeholder="Enter your email address">
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-primary">Send test email</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0 h6">Instruction</h5>
                </div>
                <div class="card-body">
                    <p class="text-danger">Please be careful when you are configuring SMTP. Incorrect configuration will cause errors during order placement, new registration, or sending newsletters.</p>
                    <h6 class="text-muted">For Non-SSL</h6>
                    <ul class="list-group">
                        <li class="list-group-item text-dark">Select sendmail for Mail Driver if you face any issue after configuring smtp as Mail Driver</li>
                        <li class="list-group-item text-dark">Set Mail Host according to your server Mail Client Manual Settings</li>
                        <li class="list-group-item text-dark">Set Mail port as 587</li>
                        <li class="list-group-item text-dark">Set Mail Encryption as ssl if you face issue with tls</li>
                    </ul>
                    <br>
                    <h6 class="text-muted">For SSL</h6>
                    <ul class="list-group">
                        <li class="list-group-item text-dark">Select sendmail for Mail Driver if you face any issue after configuring smtp as Mail Driver</li>
                        <li class="list-group-item text-dark">Set Mail Host according to your server Mail Client Manual Settings</li>
                        <li class="list-group-item text-dark">Set Mail port as 465</li>
                        <li class="list-group-item text-dark">Set Mail Encryption as ssl</li>
                    </ul>
                </div>
            </div>

        </div>
    </div>
</section>
@endsection

@section('script')
<script type="text/javascript">
    $(document).ready(function(){
        checkMailDriver();
    });

    function checkMailDriver(){
        let driver = $('select[name=MAIL_DRIVER]').val();
        
        if(driver === 'mailgun'){
            $('#collapseMailgun').collapse('show');
            $('#collapseSmtp').collapse('hide');
        } else if(driver === 'smtp'){
            $('#collapseSmtp').collapse('show');
            $('#collapseMailgun').collapse('hide');
        } else {
            $('#collapseSmtp').collapse('hide');
            $('#collapseMailgun').collapse('hide');
        }
    }
</script>
@endsection
