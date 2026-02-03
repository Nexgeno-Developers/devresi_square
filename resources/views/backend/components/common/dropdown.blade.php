@php
    $dropdownId = 'dropdownMenuButton' . uniqid();
@endphp

<div class="dropdown">
    @if($isIcon == false)
        <div class="rs_dropdown dropdown_click {{ $class }}" id="{{ $dropdownId }}" aria-expanded="false" onclick="toggleDropdown('{{ $dropdownId }}')">
            Select Option
        </div>
    @else
        <div class="rs_icon_dropdown dropdown_click {{ $class }}" id="{{ $dropdownId }}"  aria-expanded="false" onclick="toggleDropdown('{{ $dropdownId }}')"></div>
    @endif


    <div class="dropdown-menu" id="dropdownMenu_{{ $dropdownId }}" aria-labelledby="{{ $dropdownId }}" style="display: none;">
        @foreach($options as $value => $label)
            <a class="dropdown-item" href="#" onclick="selectOption('{{ $value }}', '{{ $label }}', '{{ $dropdownId }}')">{{ $label }}</a>
        @endforeach
    </div>
</div>
<input type="hidden" id="selectedOption_{{ $dropdownId }}" name="selectedOption" value="{{ $selected }}">

<script>
    function toggleDropdown(dropdownId) {
        var dropdownMenu = document.getElementById('dropdownMenu_' + dropdownId);
        dropdownMenu.style.display = dropdownMenu.style.display === 'none' ? 'block' : 'none';
    }

    function selectOption(value, label, dropdownId) {
        document.getElementById(dropdownId).innerText = label;
        document.getElementById('selectedOption_' + dropdownId).value = value;
        document.getElementById('dropdownMenu_' + dropdownId).style.display = 'none';
    }

    // Close the dropdown if the user clicks outside of it
    window.onclick = function(event) {
        if (!event.target.matches('.dropdown_click')) {
            var dropdowns = document.getElementsByClassName("dropdown-menu");
            for (var i = 0; i < dropdowns.length; i++) {
                var openDropdown = dropdowns[i];
                if (openDropdown.style.display === 'block') {
                    openDropdown.style.display = 'none';
                }
            }
        }
    }
</script>




