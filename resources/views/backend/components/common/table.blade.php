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
                        {{-- {{dd($rows)}} --}}
                        @foreach ($row as $key => $value)
                            @if ($key !== 'id' && $key !== 'updated_at')
                                @if ($key === 'created_at' && $value)
                                    <td>{{ \Carbon\Carbon::parse($value)->format('d-m-Y') }}</td>
                                @else
                                    <td>{{ $value }}</td>
                                @endif
                            @endif
                        @endforeach
                        @if($actionBtn == True )
                            <td>
                                @php 
                                    $options = [ 'edit' => 'Edit', 'delete' => 'Delete' ]; 
                                    $selectedOptions = 'edit'; 
                                @endphp
                                <x-backend.forms.dropdown
                                class='right_icon'
                                :options="$options"
                                :selected="$selectedOptions"
                                isIcon="{{true}}"
                            />
                            </td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mobile_only">
        <div class="rs_mobile_table">
            @foreach($rows as $row)
                <div class="data-row">
                    @foreach($row as $key => $value)
                        <div class="tr_row">
                            {{-- <div>{{ $headers[$index] }}</div> --}}
                            @if ($key !== 'id')
                                <div class="text_right fw_normal">{{ $value }}</div>
                            @endif
                        </div>
                    @endforeach
                    <div class="flex">
                        @foreach($options as $value => $label)
                        <a class="btn" href="#" onclick="selectOption('{{ $value }}', '{{ $label }}')">{{ $label }}</a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
