<div class="pv_content_wrapper {{ $cardStyle == 'vertical'? 'vertical_card' : '' }} {{$class}}" data-user-id="{{ $userId }}">
    {{-- <div class="pv_image">
        <img src="{{ asset('/asset/images/temp-user.webp') }}" alt="user">
    </div> --}}

    <div class="pv_content">
        <div class="pvc_user_name">
            @if($userName)
                {{ $userName }}
            @else
                <em>User name not available</em>
            @endif
        </div>

        <div class="rs_row">
            <div class="rs_col">
                <div class="pv_email">
                    Email: 
                    <strong>
                        @if($email)
                            {{ $email }}
                        @else
                            Unknown
                        @endif
                    </strong>
                </div>
            </div>

            <div class="rs_col">
                <div class="pv_phone">
                    Phone:
                    <strong>
                        @if($phone)
                            {{ $phone }}
                        @else
                            Not specified
                        @endif
                    </strong>
                </div>
            </div>
        </div>
        
    </div>
</div>
