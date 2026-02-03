<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>INVOICE</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta charset="UTF-8">
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
	</style>
</head>
<body>
	<div style="padding:1em;">

		@php
			$logo = get_setting('header_logo');
		@endphp

		<div>
			<table style="padding:0px 0px 10px 0px;">
				<tr>
					<td width="18%">
						@if($logo != null)
							<img src="{{ uploaded_asset($logo) }}" height="70" style="display:inline-block;">
						@else
							<img src="{{ static_asset('assets/img/logo.png') }}" height="70" style="display:inline-block;">
						@endif
					</td>
					
					<td class="text-right" style="line-height:22px;">
					  <p style="font-size:18px; color:#c38127; padding-bottom:20px; line-height:40px;"><b>Atfleurs</b></p>
					  <p style="padding-top:30px;"><b>GSTIN:</b> {{ get_setting('user_gst') }}</p>
					  <p><b>CIN No:</b> {{ get_setting('user_cin') }}</p>
					  <p><b>PAN:</b> {{ get_setting('user_pan') }}</p>
					  <p><b>Email:</b> {{ get_setting('user_email') }}</p>
					</td>
				</tr>
			</table>
			
			<div class="hr"></div>
			
		
		</div>
		
		
		<div>
	        <table class="text-right small strong">
	        	
		        <tbody>
			        <tr>
			            <td>
			               
			            </td>
			            <td>
					        <table class="text-center small" style="line-height:23px; font-size:12px;">
						        <tbody>
							        <tr >
							            
							            <td class="text-center" width="20%"><b>Company Address:</b> Ground Floor, Title Waves, Road No.24, Opp Duruelo Convent School, Bandra West, Mumbai - 400050</td>
							        </tr>
							       
						        </tbody>
						    </table>
			            </td>
			        </tr>
		        </tbody>
		    </table>
	    </div>
	    
	<div class="hr"></div>
	
		<div>
	        <table class="text-right small strong" style="padding-top:10px;">
	        	
		        <tbody>
			        <tr>
			            <td>
			               
			            </td>
			            <td>
					        <table class="text-center small">
						        <tbody>
							        <tr >
							            
							            <td class="text-center" width="20%" style="font-size:20px; color:#c38127;">Tax Invoice</td>
							        </tr>
							       
						        </tbody>
						    </table>
			            </td>
			        </tr>
		        </tbody>
		    </table>
	    </div>
	    
	    
		<div>
            <table style="padding:0px 0px 20px 0px;">
				@php
					$shipping_address = json_decode($order->shipping_address);
				@endphp
				
				<tr>
				     <td width="30%" class="strong small gry-color" style="line-height:20px;">
				     <p><b>Customer Name</b> <br> {{ $shipping_address->name }}</p><br>
				     
				     @php
				        $user = \App\Models\User::find($order->user_id);
				     @endphp
				    
				    @if($user->gst_number)
				     <p><b>Customer GST</b> <br> {{ $user->gst_number }}</p><br>
				    @endif
				    </td>
				    
				    
				    <td width="40%" class="strong small gry-color" style="line-height:20px;">
				     <p> <b>Billing Details:</b>  </p>
				     <p>  </p>
				     <p> {{ $shipping_address->address }}, {{ $shipping_address->city }},  @if(isset(json_decode($order->shipping_address)->state)) {{ json_decode($order->shipping_address)->state }} - @endif {{ $shipping_address->postal_code }}, {{ $shipping_address->country }} </p>
				     <p> Email: {{ $shipping_address->email }} </p>
				     <p> Phone: {{ $shipping_address->phone }}  </p>
				     <p><span class="gry-color small">State Code:</span> <span class=" strong">27</span></p>
				    </td>
				    
				    <td width="30%" class="text-right" style="line-height:20px;">
					  <p><span class="gry-color small"><b>Invoice No:</b></span> <span class="strong">{{ $order->id }}</span></p>
					  <p><span class="gry-color small"><b>Order ID:</b></span> <span class="strong">{{ $order->code }}</span></p>
					  <p><span class="gry-color small"><b>Order Date:</b></span> <span class=" strong">{{ date('d-m-Y', $order->date) }}</span></p>
					 
					</td>
					
					
				   
				</tr>
			</table>
		</div>
		
		<div class="hr"></div>
		
            <table style="padding:10px 0px">
			
				<tr>
				    <td class="strong small gry-color">Country of Supply: {{json_decode($order->shipping_address)->country}}</td>
				    <td class="strong small gry-color text-right">Place of Supply: {{json_decode($order->shipping_address)->state}}</td>
				</tr>
				
			</table>
			<div class="hr"></div>

	    <div style="padding-top:20px;">
			<table class="hed padding text-left small border-bottom">
				<thead>
	                <tr style="background: #f6f6f6;">
	                    <th width="20%" class="text-left" style="padding-top:12px; padding-bottom:12px;"><b>Product Name</b></th>
	                    <th width="10%" class="text-left" style="padding-top:12px; padding-bottom:12px;"><b>HSN / SAC</b></th>
	                    <th width="8%" class="text-left" style="padding-top:12px; padding-bottom:12px;"><b>GST(%)</b></th>
						<!--<th width="15%" class="text-left" style="padding-top:12px; padding-bottom:12px;"><b>Delivery Type</b></th>-->
	                    <th width="7%" class="text-left" style="padding-top:12px; padding-bottom:12px;"><b>Qty</b></th>
	                    <th width="10%" class="text-left" style="padding-top:12px; padding-bottom:12px;"><b>Unit Price</b></th>
	                    @if( strtolower(json_decode($order->shipping_address)->state) == strtolower("Maharashtra") )
	                    <th width="9%" class="text-left" style="padding-top:12px; padding-bottom:12px;"><b>CGST(₹)</b></th>
	                    <th width="8%" class="text-left" style="padding-top:12px; padding-bottom:12px;"><b>SGST(₹)</b></th>
	                    @else
	                    <th width="8%" class="text-left" style="padding-top:12px; padding-bottom:12px;"><b>IGST(₹)</b></th>
	                    @endif
	                    <th width="8%" class="text-left" style="padding-top:12px; padding-bottom:12px;"><b>GST(₹)</b></th>
	                    <th width="11%" class="text-right" style="padding-top:12px; padding-bottom:12px;"><b>Total</b></th>
	                   
	                </tr>
				</thead>
				<tbody class="strong">
				    @php
                        $total_bill_amount = 0;
                        $total_gst_amount  = 0;
                        $total_net_amount  = 0;
                        $total_cgst        = 0;
                        $total_sgst        = 0;
                        $total_igst        = 0;
				    @endphp
				    
	                @foreach ($order->orderDetails as $key => $orderDetail)
	                
                        @php
                            $HSN = $orderDetail->product->hsn_code ? $orderDetail->product->hsn_code : '-';
                            $gstPercent        = $orderDetail->product->gst ? $orderDetail->product->gst : 0;
                            $billAmount        = $orderDetail->price+$orderDetail->tax;
                            $gstAmount         = $billAmount - ($billAmount * (100 / ( 100 + $gstPercent )));
                            $netAmount         = $billAmount - $gstAmount;
                            $CGST              = $gstAmount / 2;
                            $SGST              = $gstAmount / 2;
                            $IGST              = $gstAmount;
                            $state             = json_decode($order->shipping_address)->state;
                            
                            $total_bill_amount += $billAmount;
                            $total_gst_amount  += $gstAmount;
                            $total_net_amount  += $netAmount;
                            $total_cgst        += $CGST;
                            $total_sgst        += $SGST;
                            $total_igst        += $IGST;                            
                        @endphp		                
	                
	                
		                @if ($orderDetail->product != null)
							<tr class="">
								<td style="line-height:20px;">
                                    {{ $orderDetail->product->name }} 
                                    @if($orderDetail->variation != null) ({{ $orderDetail->variation }}) @endif
                                    <br>
                                    <small>
                                        @php
                                            $product_stock = json_decode($orderDetail->product->stocks->first(), true);
                                        @endphp
                                        SKU: {{ $product_stock['sku'] }}
                                    </small>
                                </td>
                                <td>{{$HSN}}</td>
                                <td>{{$gstPercent}}%</td>
								<!--<td>
									@if ($order->shipping_type != null && $order->shipping_type == 'home_delivery')
										Home Delivery
									@elseif ($order->shipping_type == 'pickup_point')
										@if ($order->pickup_point != null)
											{{ $order->pickup_point->getTranslation('name') }} (Pickip Point)
										@else
                                            Pickup Point
										@endif
									@elseif ($order->shipping_type == 'carrier')
										@if ($order->carrier != null)
											{{ $order->carrier->name }} (Carrier)
											<br>
											{{ "Transit Time - ".$order->carrier->transit_time }}
										@else
											Carrier
										@endif
									@endif
								</td>-->
								<td class="">{{ $orderDetail->quantity }}</td>
								<td class="currency">{{ single_price($netAmount/$orderDetail->quantity) }}</td>
								@if( strtolower(json_decode($order->shipping_address)->state) == strtolower("Maharashtra") )
								<td>{{single_price($CGST)}}</td>
								<td>{{single_price($SGST)}}</td>
								@else
								<td>{{single_price($IGST)}}</td>
								@endif
								<td>{{single_price($gstAmount)}}</td>
			                    <td class="text-right currency">{{ single_price($orderDetail->price+$orderDetail->tax) }}</td>
							</tr>
							
							
		                @endif
		                
					@endforeach
					<tr style="background: #f6f6f6;">
									<th class="text-right" colspan="4" style="padding-top:12px; padding-bottom:12px; padding-left:10px"><b>Total</b></th>
									<th style="padding-top:12px; padding-bottom:12px; padding-left:10px"><b>{{single_price($total_net_amount)}}</b></th>
									@if( strtolower(json_decode($order->shipping_address)->state) == strtolower("Maharashtra") )
									<th style="padding-top:12px; padding-bottom:12px; padding-left:10px"><b>{{single_price($total_cgst)}}</b></th>
									<th style="padding-top:12px; padding-bottom:12px; padding-left:10px"><b>{{single_price($total_sgst)}}</b></th>
									@else
									<th style="padding-top:12px; padding-bottom:12px; padding-left:10px"><b>{{single_price($total_igst)}}</b></th>
									@endif
									<th style="padding-top:12px; padding-bottom:12px; padding-left:10px"><b>{{single_price($total_gst_amount)}}</b></th>
									<th style="padding-top:12px; padding-bottom:12px; padding-left:10px"><b>{{single_price($total_bill_amount)}}</b></th>
								</tr>
	            </tbody>
			</table>
		</div>

        @php
            // Input data
            $hsn = [];
            $price = [];
            $tax = [];
            
            foreach ($order->orderDetails as $key => $orderDetail):
                $hsn[]   = $orderDetail->product->hsn_code ? $orderDetail->product->hsn_code : '-'; 
                $price[] = $orderDetail->price+$orderDetail->tax;
                $tax[]   = $orderDetail->product->gst ? $orderDetail->product->gst : 0;
            endforeach;	            
            
            // Input data
            // Combine the HSN codes with their corresponding price and tax values into a multi-dimensional associative array
            $hsn_data = [];
            for ($i = 0; $i < count($hsn); $i++) {
                $hsn_code = $hsn[$i];
                $tax_rate = $tax[$i];
                $price_tax = $price[$i]; // Calculate the price with tax
                if (isset($hsn_data[$hsn_code][$tax_rate])) {
                    $hsn_data[$hsn_code][$tax_rate] += $price_tax; // Add to the existing total for this HSN code and tax rate
                } else {
                    $hsn_data[$hsn_code][$tax_rate] = $price_tax; // Set the initial total for this HSN code and tax rate
                }
            }
            
            // Print the result
            /*foreach ($hsn_data as $hsn_code => $tax_totals) {
                foreach ($tax_totals as $tax_rate => $total_price) {
                    echo "<p>HSN $hsn_code with $tax_rate% tax has total price $total_price</p>";
                }
            }*/
        @endphp

	    <div>
	        <table class="text-right small strong" style="padding-top:20px;">
	        	
		        <tbody>
			        <tr>
			            <td width="70%">
			                <table class="hed text-left small strong">
						        <tbody>
							        <tr style="background: #f6f6f6;">
							            <th class="text-left" style="padding-top:12px; padding-bottom:12px; padding-left:10px"><b>HSN / SAC</b></th>
							            <th class="text-left" style="padding-top:12px; padding-bottom:12px; padding-left:10px"><b>GST(%)</b></th>
							             @if( strtolower(json_decode($order->shipping_address)->state) == strtolower("Maharashtra") ) 
							            <th class="text-left" style="padding-top:12px; padding-bottom:12px; padding-left:10px"><b>CGST(₹)</b></th>
							            <th class="text-left" style="padding-top:12px; padding-bottom:12px; padding-left:10px"><b>SGST(₹)</b></th>
							             @else 
							            <th class="text-left" style="padding-top:12px; padding-bottom:12px; padding-left:10px"><b>IGST(₹)</b></th>
							             @endif 
							            <th class="text-left" style="padding-top:12px; padding-bottom:12px; padding-left:10px"><b>GST(₹)</b></th>
							            <th class="text-left" style="padding-top:12px; padding-bottom:12px; padding-left:10px"><b>Amount</b></th>
							        </tr>
							        
							        @php
							        $total_tax_percent = 0;
							        $total_tax_amount = 0;
							        $total_amount = 0;
							        @endphp
							        @foreach ($hsn_data as $hsn_code => $tax_totals)
    							        @foreach($tax_totals as $tax_rate => $total_price)
    							          @php 
    							             $gstAmount          = $total_price - ($total_price * (100 / ( 100 + $tax_rate ))); 
    							             $total_tax_percent += $tax_rate;
    							             $total_tax_amount  += $gstAmount;
    							             $total_amount      += $total_price;
    							          @endphp
    							        <tr>
    							            <th class="text-left" style="padding-left:10px">{{$hsn_code}}</th>
    							            <td class="" style="padding-left:10px">{{$tax_rate}}%</td>
    							             @if( strtolower(json_decode($order->shipping_address)->state) == strtolower("Maharashtra") ) 
        							            <td class="" style="padding-left:10px">{{single_price($gstAmount/2)}}</td>
        							            <td class="" style="padding-left:10px">{{single_price($gstAmount/2)}}</td>
    							             @else 
    							                <td class="" style="padding-left:10px">{{single_price($gstAmount)}}</td>
    							             @endif 
    							            <td class="" style="padding-left:10px">{{single_price($gstAmount)}}</td>
    							            <td class="" style="padding-left:10px">{{single_price($total_price)}}</td>
    							        </tr>
    							        @endforeach
							        @endforeach
							         <tr style="background: #f1f1f1;">
							            <th class="text-right strong" style="padding-right:10px"><b>Total</b></th>
							            <td class="" style="padding-left:10px"><b>{{$total_tax_percent}}%</b></td>
							             @if( strtolower(json_decode($order->shipping_address)->state) == strtolower("Maharashtra") ) --}}
							            <td class="" style="padding-left:10px"><b>{{single_price($total_tax_amount/2)}}</b></td>
							            <td class="" style="padding-left:10px"><b>{{single_price($total_tax_amount/2)}}</b></td>
							             @else --}} 
							            <td class="" style="padding-left:10px"><b>{{single_price($total_tax_amount)}}</b></td>
							             @endif 
							            <td class="" style="padding-left:10px"><b>{{single_price($total_tax_amount)}}</b></td>
							            <td class="" style="padding-left:10px"><b>{{single_price($total_amount)}}</b></td>
							        </tr>
						        </tbody>
						    </table> 
			            </td>
			            <td width="30%">
					        <table class="text-right small" style="line-height:23px; font-size:14px;">
						        <tbody>
							        <tr>
							            <th class="text-right" width="50%"><b>Sub Total</b></th>
							            <td width="50%"><b>{{ single_price($order->orderDetails->sum('price')) }}</b></td>
							        </tr>
							        <tr>
							            <th class="text-right" width="50%"><b>Shipping Cost</b></th>
							            <td width="50%"><b>{{ single_price($order->orderDetails->sum('shipping_cost')) }}</b></td>
							        </tr>
							       
							        <tr>
							            <th class="text-right" width="50%"><b>Coupon Disc.</b></th>
							            <td width="50%"><b>{{ single_price($order->coupon_discount) }}</b></td>
							        </tr>
				                   
							        <tr>
							            <th class="text-right strong" width="50%"><b>Grand Total</b></th>
							            <td width="50%"><b>{{ single_price($order->grand_total) }}</b></td>
							        </tr>
						        </tbody>
						    </table>
			            </td>
			        </tr>
		        </tbody>
		    </table>
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
</body>
</html>