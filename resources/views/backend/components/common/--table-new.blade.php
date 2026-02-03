<div class="dynamic_div">
    <div class="desktop_only">
        <table class="table rs_table {{ $class }}">
            <thead>
                <tr>
                    @foreach ($headers as $header)
                        @if ($header !== 'id')
                            <th>{{ $header }}</th>
                        @endif
                    @endforeach
                    @if($actionBtn == True)
                        <th></th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        @foreach ($row as $key => $value)
                            @if ($key !== 'id')
                                <td>{{ $value }}</td>
                            @endif
                        @endforeach
                        @if($actionBtn == True )
                            <td>
                                <x-backend.forms.dropdown :options="$dropdownOptions" class="" isIcon={{True}} />
                            </td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    {{-- desktop_only end  --}}
    <div class="mobile_only">
        <div class="rs_mobile_table">
            <div class="flex justify_between align_center">
                <!-- Headers -->
                <div class="rsmt_left_col">
                    @foreach ($headers as $header)
                        @if ($header !== 'id')
                            <div>{{ $header }}</div>
                        @endif
                    @endforeach
                </div>
                <!-- Values -->
                <div class="rsmt_right_col">
                    @foreach ($row as $key => $value)
                        @if ($key !== 'id')
                            <div>{{ $value}}</div>
                        @endif
                    @endforeach
                </div>
            </div>



        </div>
    </div>
    {{-- mobile_only end  --}}
</div>




{{-- <thead>
    <tr>
        @foreach($headers as $header)
                <th class="{{$header === 'id' ? 'id' : ''}}">{{ $header }}</th>
        @endforeach
        <th></th> <!-- Add an extra header for the actions column -->
    </tr>
</thead>
<tbody>
    @foreach($rows as $row)
        <tr> 
            @foreach($row as $key => $cell)
                <td class="td-{{$key+1}} {{$key == 'id' ? 'id' : ''}}">{{ $cell }}</td>
            @endforeach
            <td>
                @php 
                $countries = [ 'edit' => 'Edit', 'delete' => 'Delete' ]; 
                $selectedCountry = 'edit'; 
                @endphp
                <x-backend.forms.dropdown :options="$countries" :selected="$selectedCountry" isIcon={{true}} class="right_icon" />
            </td> <!-- Add an action cell with an icon -->
        </tr>
    @endforeach
</tbody> --}}