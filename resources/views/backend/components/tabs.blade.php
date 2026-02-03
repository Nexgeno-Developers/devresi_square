<div class="pv_tabs {{ $class }}">
    <ul class="nav">
        @foreach ($tabs as $key => $tab)
            <li class="{{ $key === 0 ? 'active' : '' }}">
                <a href="#{{ Str::slug($tab['name']) }}" data-tab-name="{{ strtolower($tab['name']) }}" class="tab-link {{ $key === 0 ? 'active' : '' }}">
                    {{ $tab['name'] }}
                </a>
            </li>
        @endforeach
    </ul>
</div>
