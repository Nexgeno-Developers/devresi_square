<table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="#f4f6f9" style="font-family: Arial, sans-serif;">
    @php $logo = get_setting('header_logo'); @endphp
    <tr>
        <td align="center" style="padding:40px 10px;">
            <!-- Container -->
            <table width="650" border="0" cellspacing="0" cellpadding="0" bgcolor="#ffffff" style="max-width:650px; border-radius:8px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.05);">
                <!-- Header -->
                <tr>
                    <td bgcolor="#ffffff" style="padding:20px 30px; background-color:#f8fafa; border-bottom:1px solid #eaeaea;">
                        <table width="100%" border="0" cellspacing="0" cellpadding="0">
                            <tr>
                                <td align="left">
                                    {{-- <img src="https://laravel.resisquare.co.uk/storage/uploads/all/SHB1anFRGQ093q3fewMx6K5r8LcgJcLyRFsh2ate.png" alt="{{ env('APP_NAME') }}" height="32" style="display:block;"> --}}
                                    <img src="{{ uploaded_asset($logo) }}" alt="{{ env('APP_NAME') }}" height="32" style="display:block;">
                                </td>
                                <td align="right" style="font-size:14px; color:#555;">
                                    <a href="{{ config('app.url') }}" target="_blank" style="color:#2a7ae2; text-decoration:none; font-weight:600;">
                                        {{ config('app.name') }}
                                    </a>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <!-- Content -->
                <tr>
                    <td style="padding:40px 30px; font-size:15px; line-height:1.6; color:#333;">
                        {!! $content !!}
                    </td>
                </tr>

                <!-- Footer -->
                <tr>
                    <td bgcolor="#f8fafa" style="padding:20px 30px; font-size:12px; color:#777; text-align:center; border-top:1px solid #eaeaea;">
                        © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                    </td>
                </tr>
            </table>
            <!-- END Container -->
        </td>
    </tr>
</table>
