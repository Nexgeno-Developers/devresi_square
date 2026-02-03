<div class="dynamic_div">
    <div class="desktop_only">
        <table class="table rs_table {{ $class }}">
            <thead>
                <tr>
                    @foreach($headers as $header)
                        <th>{{ $header }}</th>
                    @endforeach
                    <th>Actions</th> <!-- Add an extra header for the actions column -->
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                    <tr>
                        @foreach($row as $cell)
                            <td>{{ $cell }}</td>
                        @endforeach
                        <td>
                            @php 
                            $countries = [ 'edit' => 'Edit', 'delete' => 'Delete' ]; 
                            $selectedCountry = 'edit'; 
                            @endphp
                            <x-backend.dropdown :options="$countries" :selected="$selectedCountry" isIcon={{true}} class="right_icon" />
                        </td> <!-- Add an action cell with an icon -->
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mobile_only">
        <div class="rs_mobile_table">
            @foreach($rows as $row)
                <div class="data-row">
                    @foreach($row as $index => $value)
                        <div class="tr_row">
                            <div>{{ $headers[$index] }}</div>
                            <div>{{ $value }}</div>
                        </div>
                        @endforeach
                        <div class="flex">
                            @foreach($countries as $value => $label)
                            <a class="btn" href="#" onclick="selectOption('{{ $value }}', '{{ $label }}')">{{ $label }}</a>
                            @endforeach
                        </div>
                </div>
            @endforeach
        </div>
    </div>
</table>
