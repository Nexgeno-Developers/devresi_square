@php require resource_path('views/backend/ui-lab/partials/homes-data.php'); @endphp

<p class="lab-note">Landlord Properties on a phone: list to a stacked control centre. Same homes, queue and overlays as desktop — not a squashed sidebar.</p>

<x-ui-lab.landlord-phone tab="Homes">
    <div class="llphone-list" data-llphone-view="list" hidden>
        <div class="llphone-list-head">
            <span>{{ count($homes) }} homes</span>
            <button type="button" class="lab-btn lab-btn-secondary pws-add" data-lab-open="lab-add-property">Add</button>
        </div>
        <label class="pws-search">
            <span class="visually-hidden">Search properties</span>
            <input type="search" placeholder="Address or postcode" data-llphone-search>
        </label>
        <div class="chips llphone-chips">
            <button type="button" class="chip is-on" data-llphone-filter="all">All</button>
            <button type="button" class="chip" data-llphone-filter="Let">Lets</button>
            <button type="button" class="chip" data-llphone-filter="Vacant">Vacant</button>
            <button type="button" class="chip" data-llphone-filter="repairs">Repairs</button>
            <button type="button" class="chip" data-llphone-filter="certs">Certs</button>
        </div>
        <div class="llphone-rows">
            @foreach($homes as $home)
                @php
                    $work = [];
                    if ($home['occupancy'] === 'Vacant') {
                        $work[] = 'Asking £'.number_format($home['display_rent']).'/mo';
                    }
                    if ($home['unpaid']) {
                        $work[] = '£'.number_format($home['unpaid']).' unpaid';
                    }
                    if (count($home['repairs'])) {
                        $work[] = count($home['repairs']).' repairs';
                    }
                    $due = collect($home['certs'])->contains(fn ($c) => in_array($c['tone'], ['bad', 'warn'], true));
                @endphp
                <button type="button"
                    class="pws-row"
                    data-llphone-home="{{ $home['id'] }}"
                    data-occupancy="{{ $home['occupancy'] }}"
                    data-repairs="{{ count($home['repairs']) }}"
                    data-certs-due="{{ $due ? '1' : '0' }}"
                    data-search="{{ strtolower($home['name'].' '.$home['line_1'].' '.$home['postcode']) }}">
                    <img src="{{ $home['thumb'] }}" alt="">
                    <span class="pws-row-main">
                        <strong>{{ $home['name'] }}</strong>
                        <span class="pws-row-meta">
                            <i class="{{ $dot('idle') }}"></i>
                            {{ $home['occupancy'] }} · {{ $home['postcode'] }} · {{ $home['beds'] }} bed
                        </span>
                        @if($work)
                            <span class="pws-row-work">
                                @foreach($work as $i => $bit)
                                    @if($i) · @endif
                                    @if(str_contains($bit, 'unpaid'))
                                        <span class="pws-row-bad">{{ $bit }}</span>
                                    @else
                                        {{ $bit }}
                                    @endif
                                @endforeach
                            </span>
                        @endif
                    </span>
                </button>
            @endforeach
        </div>
    </div>

    @foreach($homes as $home)
        @php $state = $homeState($home); @endphp
        <section class="llphone-home{{ $home['id'] === 'wharf' ? ' is-on' : '' }}" data-llphone-view="{{ $home['id'] }}" @unless($home['id'] === 'wharf') hidden @endunless>
            <div class="llphone-sticky">
                <header class="llphone-bar">
                    <button type="button" class="llphone-back" data-llphone-back>Homes</button>
                    <div class="llphone-bar-copy">
                        <strong>{{ $home['line_1'] }}</strong>
                        <em>{{ $home['name'] }} · {{ $home['postcode'] }}</em>
                    </div>
                    @if($home['occupancy'] === 'Vacant')
                        <button type="button" class="lab-btn lab-btn-primary pws-add" data-lab-open="lab-add-tenancy">Add</button>
                    @else
                        <button type="button" class="lab-btn lab-btn-ghost pws-add">Edit</button>
                    @endif
                </header>
                <div class="llphone-seg" role="tablist">
                    <button type="button" class="is-on" role="tab" data-llphone-tab="overview">Overview</button>
                    <button type="button" role="tab" data-llphone-tab="tenancy">Tenancy</button>
                    <button type="button" role="tab" data-llphone-tab="certificates">Certs</button>
                    <button type="button" role="tab" data-llphone-tab="owners">Owners</button>
                    <button type="button" role="tab" data-llphone-tab="documents">Docs</button>
                </div>
            </div>

            <div class="llphone-panels">
                <div class="llphone-stack is-on" data-llphone-panel="overview">
                    <article class="pws-hero">
                        <div class="pws-hero-photo">
                            <img src="{{ $home['hero'] }}" alt="">
                            <span class="pws-hero-chip">{{ $home['name'] }}</span>
                            <span class="pws-occ-pill llphone-occ">{{ $home['occupancy'] }}</span>
                        </div>
                        <div class="pws-hero-body">
                            <div class="pws-hero-stats">
                                <div>
                                    <span>{{ $home['occupancy'] === 'Let' ? 'Rent' : 'Asking' }}</span>
                                    <strong>£{{ number_format($state['letRent'] ?: $home['display_rent']) }}</strong>
                                </div>
                                <div>
                                    <span>Beds / baths</span>
                                    <strong>{{ $home['beds'] }} / {{ $home['baths'] }}</strong>
                                </div>
                                <div>
                                    <span>EPC</span>
                                    <strong>{{ $state['epcLetter'] ?: '—' }}</strong>
                                </div>
                            </div>
                        </div>
                    </article>

                    <div class="pws-kpis">
                        <article class="pws-kpi">
                            <span>Annualised</span>
                            <strong>{{ $state['annualised'] ? '£'.number_format($state['annualised']) : '—' }}</strong>
                            <em>{{ $state['letCount'] ? $state['letCount'].' let'.($state['letCount'] === 1 ? '' : 's') : 'Vacant' }}</em>
                        </article>
                        <article class="pws-kpi{{ $home['unpaid'] ? ' is-warn' : '' }}">
                            <span>Owed</span>
                            <strong>£{{ number_format($home['unpaid']) }}</strong>
                            <em>{{ $home['unpaid'] ? 'Arrears' : 'Clear' }}</em>
                        </article>
                        <article class="pws-kpi{{ $state['missingCerts']->count() ? ($state['missingCerts']->where('tone', 'bad')->count() ? ' is-bad' : ' is-warn') : '' }}">
                            <span>Certs</span>
                            <strong>{{ $state['missingCerts']->count() ?: '0' }}</strong>
                            <em>
                                @if($state['missingCerts']->where('tone', 'bad')->count())
                                    Missing
                                @elseif($state['missingCerts']->count())
                                    Before let
                                @else
                                    On file
                                @endif
                            </em>
                        </article>
                    </div>

                    <article class="pws-block">
                        <div class="pws-block-head">
                            <div>
                                <p class="pws-kicker">Action required</p>
                                <h2>Property queue</h2>
                            </div>
                        </div>
                        @if($state['queue'])
                            <ul class="pws-queue">
                                @foreach($state['queue'] as $item)
                                    <li>
                                        <i class="{{ $dot($item['tone']) }}"></i>
                                        <div>
                                            <strong>{{ $item['title'] }}</strong>
                                            <em>{{ $item['detail'] }}</em>
                                        </div>
                                        <button type="button" class="lab-btn lab-btn-secondary pws-queue-btn" data-lab-open="{{ $item['open'] }}">{{ $item['action'] }}</button>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="pws-empty">Nothing waiting on this property.</p>
                        @endif
                    </article>

                    <article class="pws-block">
                        <div class="pws-block-head">
                            <h2>Residents</h2>
                            @if($state['letCount'])
                                <button type="button" class="pws-text" data-llphone-tab="tenancy">All lets</button>
                            @endif
                        </div>
                        @if($home['tenancies'])
                            <ul class="pws-residents">
                                @foreach($home['tenancies'] as $row)
                                    <li>
                                        <i>{{ $initials($row['household']) }}</i>
                                        <div>
                                            <strong>{{ $row['household'] }}</strong>
                                            <em>{{ $row['type'] }} · {{ strtolower($row['frequency']) }} £{{ number_format($row['rent']) }}</em>
                                        </div>
                                        @if($row['primary'] && $home['unpaid'])
                                            <b class="is-bad">£{{ number_format($home['unpaid']) }} unpaid</b>
                                        @elseif($row['portal'] === 'No login')
                                            <b class="is-idle">No login</b>
                                        @else
                                            <b class="is-idle">{{ $row['status'] }}</b>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="pws-empty">No active tenancy. This home is vacant.</p>
                            <button type="button" class="lab-btn lab-btn-primary" data-lab-open="lab-add-tenancy">Add tenancy</button>
                        @endif
                    </article>

                    <article class="pws-block">
                        <div class="pws-block-head">
                            <h2>Open repairs</h2>
                            <a class="pws-text" href="{{ route('backend.ui_lab', ['screen' => 'repairs']) }}">View all</a>
                        </div>
                        @if($state['repairCount'])
                            <ul class="pws-fix">
                                @foreach($home['repairs'] as $repair)
                                    @php $tone = $repairTone($repair); @endphp
                                    <li>
                                        <button type="button" class="pws-fix-btn" data-lab-open="lab-repair-case">
                                            <i class="{{ $dot($tone) }}"></i>
                                            <span>
                                                <strong>{{ $repair['title'] }}</strong>
                                                <em>{{ $repair['status'] }} · {{ $repair['when'] }}</em>
                                            </span>
                                            <b aria-hidden="true">›</b>
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="pws-empty">None open on this property.</p>
                        @endif
                    </article>
                </div>

                <div class="llphone-stack" data-llphone-panel="tenancy" hidden>
                    <div class="pws-block-head pws-panel-head">
                        <p class="pws-lead">Lets on this home.</p>
                        <button type="button" class="lab-btn {{ $home['occupancy'] === 'Vacant' ? 'lab-btn-primary' : 'lab-btn-secondary' }}" data-lab-open="lab-add-tenancy">Add tenancy</button>
                    </div>
                    @if($home['tenancies'])
                        @foreach($home['tenancies'] as $row)
                            <article class="pws-block llphone-let">
                                <div class="llphone-let-head">
                                    <div>
                                        <strong>{{ $row['household'] }}</strong>
                                        <em>{{ $row['household_meta'] }}</em>
                                    </div>
                                    <span class="pws-row-status"><i class="{{ $dot('idle') }}"></i>{{ $row['status'] }}</span>
                                </div>
                                <dl class="pws-fields">
                                    <dt>Type</dt><dd>{{ $row['type'] }}</dd>
                                    <dt>Rent</dt><dd>£{{ number_format($row['rent']) }} {{ strtolower($row['frequency']) }}</dd>
                                    <dt>Deposit</dt>
                                    <dd>
                                        @if($row['deposit'])
                                            £{{ number_format($row['deposit'], 2) }} · {{ $row['deposit_held'] }} holding · {{ $row['protected'] ? 'Protected' : 'Not protected' }}
                                        @else
                                            Not recorded
                                        @endif
                                    </dd>
                                    <dt>Term</dt><dd>{{ $row['move_in'] }}{{ $row['move_out'] ? ' – '.$row['move_out'] : ' · periodic' }}</dd>
                                    <dt>Portal</dt><dd>{{ $row['portal'] }}</dd>
                                </dl>
                                <div class="pws-actions">
                                    <button type="button" class="lab-btn lab-btn-secondary" data-lab-open="lab-tenancy">Open</button>
                                    <button type="button" class="lab-btn lab-btn-secondary" data-lab-open="lab-invoice-view">Ledger</button>
                                </div>
                            </article>
                        @endforeach
                    @else
                        <div class="pws-blank">
                            <strong>No tenancy on this property</strong>
                            <p>Vacant until you add one.</p>
                            <button type="button" class="lab-btn lab-btn-primary" data-lab-open="lab-add-tenancy">Add tenancy</button>
                        </div>
                    @endif
                </div>

                <div class="llphone-stack" data-llphone-panel="certificates" hidden>
                    <p class="pws-lead">Energy rating can come from the UK register without a PDF. Gas and electrical still need a file on record.</p>
                    <article class="pws-block">
                        <ul class="llphone-certs">
                            @foreach($home['certs'] as $cert)
                                <li>
                                    <div>
                                        <strong>{{ $cert['name'] }}</strong>
                                        <span class="pws-row-status"><i class="{{ $dot($cert['tone']) }}"></i>{{ $cert['date'] }}</span>
                                    </div>
                                    @if($cert['action'])
                                        <button type="button" class="lab-btn lab-btn-secondary" data-lab-open="lab-upload">{{ $cert['action'] }}</button>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </article>
                </div>

                <div class="llphone-stack" data-llphone-panel="owners" hidden>
                    <article class="pws-block">
                        <div class="pws-block-head">
                            <h2>Owner group</h2>
                            <span class="pws-row-status"><i class="{{ $dot('idle') }}"></i>Active</span>
                        </div>
                        <p class="pws-empty">Purchased {{ $home['purchased'] }}</p>
                        <ul class="pws-people">
                            @foreach($home['owners'] as $owner)
                                <li>
                                    <i>{{ $initials($owner['name']) }}</i>
                                    <div>
                                        <strong>{{ $owner['name'] }}</strong>
                                        <span>{{ $owner['email'] }}{{ $owner['main'] ? ' · Main owner' : '' }}</span>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                        <button type="button" class="lab-btn lab-btn-secondary" data-lab-open="lab-owners-edit">Edit group</button>
                    </article>
                </div>

                <div class="llphone-stack" data-llphone-panel="documents" hidden>
                    <div class="pws-block-head pws-panel-head">
                        <p class="pws-lead">Tenancy agreements and inventories. Safety certificates stay on Certs.</p>
                        <button type="button" class="lab-btn lab-btn-secondary" data-lab-open="lab-upload">Upload</button>
                    </div>
                    @if($home['documents'])
                        <ul class="pws-docs">
                            @foreach($home['documents'] as $doc)
                                <li>
                                    <span class="doc-ico">PDF</span>
                                    <div>
                                        <strong>{{ $doc['name'] }}</strong>
                                        <span>{{ $doc['type'] }} · {{ $doc['date'] }}</span>
                                    </div>
                                    <span class="pws-row-status"><i class="{{ $dot('idle') }}"></i>{{ $doc['visibility'] }}</span>
                                    @if($doc['visibility'] === 'Private')
                                        <button type="button" class="lab-btn lab-btn-ghost" data-lab-open="lab-share">Share</button>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="pws-blank">
                            <strong>No documents on this property</strong>
                            <p>Tenancy agreements and inventories belong here once a let starts.</p>
                            <button type="button" class="lab-btn lab-btn-primary" data-lab-open="lab-upload">Upload</button>
                        </div>
                    @endif
                </div>
            </div>
        </section>
    @endforeach
</x-ui-lab.landlord-phone>
