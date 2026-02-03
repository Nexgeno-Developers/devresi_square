<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Work Order')</title>
    <style media="all">
        @page {
			margin: 0;
			padding:0;
		}
		body{
			font-size: 12px;
            font-weight: normal;
            direction: <?php echo  $direction ?>;
            text-align: <?php echo  $text_align ?>;
			padding:0;
			margin:0; 
		}
		.gry-color *,
		.gry-color{
			color:#000;
		}
		table{
			width: 100%;
		}
		table th{
			font-weight: normal;
		}
	
		table.padding th{
			padding: .25rem .7rem;
		}
		table.padding td{
			padding: .25rem .7rem;
		}
		table.sm-padding td{
			padding: .1rem .7rem;
		}
		.border-bottom td,
		.border-bottom th{
			border-bottom:1px solid #c38127;
		}
		.text-left{
			text-align:<?php echo  $text_align ?>;
		}
		.text-right{
			text-align:<?php echo  $not_text_align ?>;
		}
		.text-muted {
            color: #595c5f !important;
        }
		.text-center{
			text-align:center;
		}
		.hr
		{
             background: #c38127;
             border:.5px solid #c38127 !important;
		}
		
		.hed th
		{
		   
		    border:1px solid #f4f4f4;
		}
		
		.hed td
		{
		    padding:10px 5px;
		    border-bottom:1px solid #f4f4f4;
		    border-left:1px solid #f4f4f4;
		}
        .table-dark th {
            background-color: #000; /* Dark black */
            color: white; /* White text */
        }

        .status-paid {
            color: green;
            font-weight: bold;
        }

        .status-pending {
            color: orange;
            font-weight: bold;
        }

        .status-overdue {
            color: red;
            font-weight: bold;
        }

        .status-cancelled, .status-cancel {
            color: gray;
            font-weight: bold;
            text-decoration: line-through;
        }

        .status-drafted, .status-draft {
            color: blue;
            font-weight: bold;
        }
        .background-dark {
            background-color: #000;
            color: white;
        }
        .background-light {
            background-color: #f4f4f4;
        }
        .background-gray {
            background-color: #e9ecef;
        }
	</style>
    <style>
        
        body { font-family: Arial, sans-serif; margin: 20px; padding: 20px; }
        .workorder-container { max-width: 800px; margin: auto; padding: 20px; border: 1px solid #ddd; }
        .header { text-align: center; margin-bottom: 20px; }
        .company-info { text-align: center; font-size: 18px; }
        .workorder-details { margin-top: 20px; }
        .workorder-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .workorder-table th, .workorder-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .footer { margin-top: 20px; text-align: center; font-size: 12px; color: gray; }
    </style>
    @stack('styles')
</head>
<body>

<div class="workorder-container">
    <!-- workorder Header -->
    <!-- Company Header -->
    <div class="header">
        @php
            $logo = get_setting('header_logo');
        @endphp
        <div style="background: #eceff4;padding: 1.5rem;">
            <table>
                <tr>
                    <td>
                        @if($logo != null)
                            {{-- <img class="img-fluid" width="250" src="{{ uploaded_asset(get_setting('header_logo')) }}" alt="Resisquare logo"> --}}
                            <img loading="lazy" src="https://laravel.resisquare.co.uk/asset/images/resisquare-logo.svg" height="40" style="display:inline-block;">
                        @else
                            <img loading="lazy" src="https://laravel.resisquare.co.uk/asset/images/resisquare-logo.svg" height="40" style="display:inline-block;">
                        @endif
                    </td>
                    <td class="text-right">
                        <h1>@yield('workorder_title', 'workorder')</h2>
                        <p># @yield('workorder_number')</p>
                        <p>@yield('workorder_status')</p>
                    </td>
                </tr>
            </table>
            <div style="margin-top:3.2rem;"></div>
            <table>
                <tr>
                    <td style="font-size: 1.2rem;" class="strong">@yield('workorder_from', get_setting('company_name') ?: 'Resisquare' )</td>
                    <td style="font-size: 1.2rem;" class="text-right strong">@yield('workorder_to', '')</td>
                </tr>
                <tr>
                    <td class="gry-color small"></td>
                    <td class="text-right small" style="padding-top: 2rem;"><span class="gry-color small">Work Order Date:</span> <span class=" strong">@yield('workorder_date')</span></td>
                </tr>
                <tr>
                    <td class="gry-color small"></td>
                    <td class="text-right small"><span class="gry-color small">Work Order Due Date:</span> <span class=" strong">@yield('workorder_due_date')</span></td>
                </tr>
            </table>
        </div>
    </div>

    <!-- workorder Content -->
    <div class="workorder-details">
        @yield('workorder_content')
    </div>

    <!-- workorder Price Breakup-->
    <div class="workorder-price-details">
        @yield('workorder_total')
    </div>
    
    @yield('additional_workorder_info')

    <!-- Footer -->
    <div class="footer">
        @yield('footer_text', 'Thank you for your business!')
    </div>

    	    
    <div style="margin-top:100px;"></div>
	    
    <div class="hr"></div>
    
    <div>
        <table class="text-right small strong">
            
            <tbody>
                <tr>
                    <td>
                       
                    </td>
                    <td>
                        <table class="text-center small" style="line-height:23px; font-size:12px;">
                            <tbody>
                                <tr>
                                    <td class="text-center" width="100%"><b>Terms -</b> The bill is system generated physical sign is not required.</td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="hr"></div>

</div>

@stack('scripts')

</body>
</html>
