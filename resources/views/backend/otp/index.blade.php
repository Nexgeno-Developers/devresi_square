@extends('backend.layout.app')

@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">SMS / OTP Configuration</h1>
            <small class="text-muted">Select your active SMS provider and configure credentials.</small>
        </div>
        <div class="col-md-6 text-md-right">
            <a href="{{ route('sms-templates.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-sms me-1"></i> SMS Templates
            </a>
        </div>
    </div>
</div>

@php
    $activeProvider = $otpConfigurations->where('value', 1)->first()?->type ?? '';

    $providerLabels = [
        'fast2sms'      => 'Fast2SMS (India)',
        'twillo'        => 'Twilio',
        'nexmo'         => 'Nexmo / Vonage',
        'mimsms'        => 'MimSMS',
        'mimo'          => 'Mimo',
        'msegat'        => 'Msegat (Arabic)',
        'smsgatewayhub' => 'SMSGatewayHub (India)',
        'sparrow'       => 'Sparrow SMS (Nepal)',
        'ssl_wireless'  => 'SSL Wireless (Bangladesh)',
        'zender'        => 'Zender',
    ];

    $providerFields = [
        'fast2sms'      => [['AUTH_KEY','API Key / Auth Key'],['SENDER_ID','Sender ID'],['ROUTE','Route (q or dlt_manual)'],['LANGUAGE','Language'],['ENTITY_ID','Entity ID (DLT)']],
        'twillo'        => [['TWILIO_SID','Account SID'],['TWILIO_AUTH_TOKEN','Auth Token'],['VALID_TWILLO_NUMBER','Twilio Number'],['TWILLO_TYPE','Type (1=SMS, 2=WhatsApp)']],
        'nexmo'         => [['NEXMO_KEY','API Key'],['NEXMO_SECRET','API Secret'],['NEXMO_SENDER_ID','Sender ID']],
        'mimsms'        => [['MIM_USER_NAME','Username'],['MIM_API_KEY','API Key'],['MIM_SENDER_ID','Sender ID']],
        'mimo'          => [['MIMO_USERNAME','Username'],['MIMO_PASSWORD','Password'],['MIMO_SENDER_ID','Sender ID']],
        'msegat'        => [['MSEGAT_API_KEY','API Key'],['MSEGAT_USERNAME','Username'],['MSEGAT_USER_SENDER','Sender']],
        'smsgatewayhub' => [['SMSGHUB_API_KEY','API Key'],['SMSGHUB_SENDER','Sender ID']],
        'sparrow'       => [['SPARROW_TOKEN','Token'],['MESSGAE_FROM','Sender Name']],
        'ssl_wireless'  => [['SSL_SMS_API_TOKEN','API Token'],['SSL_SMS_SID','Sender ID'],['SSL_SMS_URL','API URL']],
        'zender'        => [['ZENDER_APIKEY','API Key'],['ZENDER_SITEURL','Site URL'],['ZENDER_SERVICE','Service (0/1=SMS, 2=WhatsApp)'],['ZENDER_DEVICE','Device ID'],['ZENDER_SIM','SIM (1 or 2)'],['ZENDER_GATEWAY','Gateway ID'],['ZENDER_WHATSAPP','WhatsApp Account ID']],
    ];
@endphp

<div class="row">
    {{-- Left: Provider selector --}}
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header"><h5 class="mb-0 h6">Active Provider</h5></div>
            <div class="card-body p-0">
                <form action="{{ route('otp.activation') }}" method="POST">
                    @csrf
                    <ul class="list-group list-group-flush">
                        @foreach($otpConfigurations as $config)
                            <li class="list-group-item d-flex align-items-center justify-content-between
                                {{ $config->value ? 'bg-success bg-opacity-10' : '' }}">
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="radio"
                                           name="otp_type" id="prov_{{ $config->type }}"
                                           value="{{ $config->type }}"
                                           {{ $config->value ? 'checked' : '' }}>
                                    <label class="form-check-label fw-semibold" for="prov_{{ $config->type }}">
                                        {{ $providerLabels[$config->type] ?? ucfirst($config->type) }}
                                    </label>
                                </div>
                                @if($config->value)
                                    <span class="badge bg-success">Active</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                    <div class="p-3">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-save me-1"></i> Set Active Provider
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Right: Credentials for active provider --}}
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 h6">
                    Credentials —
                    <span class="text-primary">{{ $providerLabels[$activeProvider] ?? ucfirst($activeProvider) }}</span>
                </h5>
            </div>
            <form action="{{ route('otp.credentials') }}" method="POST">
                @csrf
                <div class="card-body">
                    @if(isset($providerFields[$activeProvider]))
                        <div class="row g-3">
                            @foreach($providerFields[$activeProvider] as [$envKey, $label])
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small">{{ $label }}</label>
                                    <input type="text" name="{{ $envKey }}"
                                           class="form-control"
                                           value="{{ env($envKey) }}"
                                           placeholder="{{ $envKey }}">
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted">Select a provider on the left to configure credentials.</p>
                    @endif
                </div>
                @if(isset($providerFields[$activeProvider]))
                    <div class="card-footer text-right">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Save Credentials
                        </button>
                    </div>
                @endif
            </form>
        </div>
    </div>
</div>
@endsection
