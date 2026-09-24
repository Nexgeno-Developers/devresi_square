@php require resource_path('views/backend/ui-lab/partials/homes-data.php'); @endphp

<x-ui-lab.shell active="Properties">
    <div class="pws" data-pws>
        <aside class="pws-list">
            <div class="pws-list-head">
                <span>{{ count($homes) }} homes</span>
                <button type="button" class="lab-btn lab-btn-secondary pws-add" data-lab-open="lab-add-property">Add</button>
            </div>
            <label class="pws-search">
                <span class="visually-hidden">Search properties</span>
                <input type="search" placeholder="Address or postcode" data-pws-search>
            </label>
            <div class="chips pws-chips">
                <button type="button" class="chip is-on" data-pws-filter="all">All</button>
                <button type="button" class="chip" data-pws-filter="Let">Lets</button>
                <button type="button" class="chip" data-pws-filter="Vacant">Vacant</button>
                <button type="button" class="chip" data-pws-filter="repairs">Repairs</button>
                <button type="button" class="chip" data-pws-filter="certs">Certs</button>
            </div>
            <div class="pws-rows">
                @foreach($homes as $home)
                    @php
                        $repairCount = count($home['repairs']);
                        $due = collect($home['certs'])->contains(fn ($c) => in_array($c['tone'], ['bad', 'warn'], true));
                        $work = [];
                        if ($home['occupancy'] === 'Vacant') {
                            $work[] = 'Asking £'.number_format($home['display_rent']).'/mo';
                        }
                        if ($home['unpaid']) {
                            $work[] = '£'.number_format($home['unpaid']).' unpaid';
                        }
                        if ($repairCount) {
                            $work[] = $repairCount.' repairs';
                        }
                        $rowHint = $home['occupancy'].' · '.$home['postcode'].' · '.$home['beds'].' bed'.($work ? ' · '.implode(' · ', $work) : '');
                    @endphp
                    <button type="button"
                        class="pws-row{{ $home['id'] === 'wharf' ? ' is-on' : '' }}"
                        title="{{ $rowHint }}"
                        data-pws-home="{{ $home['id'] }}"
                        data-occupancy="{{ $home['occupancy'] }}"
                        data-repairs="{{ $repairCount }}"
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
        </aside>

        @foreach($homes as $home)
            @php
                $repairCount = count($home['repairs']);
                $missingCerts = collect($home['certs'])->whereIn('tone', ['bad', 'warn']);
                $letRent = collect($home['tenancies'])->sum('rent');
                $letCount = count($home['tenancies']);
                $annualised = $letRent ? $letRent * 12 : null;
                $epc = collect($home['certs'])->firstWhere('name', 'EPC');
                $epcLetter = ($epc && preg_match('/\b([A-G])\b/', $epc['date'] ?? '', $m)) ? $m[1] : null;
                $primary = collect($home['tenancies'])->firstWhere('primary', true);
                $queue = [];
                if ($home['unpaid']) {
                    $queue[] = [
                        'tone' => 'bad',
                        'title' => '£'.number_format($home['unpaid']).' rent unpaid',
                        'detail' => ($primary['household'] ?? 'Tenant').' · September rent',
                        'action' => 'Send reminder',
                        'open' => 'lab-invoice-view',
                    ];
                }
                foreach ($missingCerts as $cert) {
                    $queue[] = [
                        'tone' => $cert['tone'],
                        'title' => $cert['name'].($cert['tone'] === 'bad' ? ' missing' : ' needed'),
                        'detail' => $cert['date'] === 'No record' ? 'No certificate on record' : $cert['date'],
                        'action' => $cert['action'] ?: 'Upload',
                        'open' => 'lab-upload',
                    ];
                }
            @endphp
            <section class="pws-detail{{ $home['id'] === 'wharf' ? ' is-on' : '' }}" data-pws-detail="{{ $home['id'] }}" @unless($home['id'] === 'wharf') hidden @endunless>
                <div class="pws-canvas">
                    <header class="pws-head">
                        <div class="pws-head-copy">
                            <h1>{{ $home['line_1'] }}</h1>
                            <p>{{ $home['name'] }} · {{ $home['city'] }} {{ $home['postcode'] }}</p>
                        </div>
                        <div class="pws-head-actions">
                            @if($home['occupancy'] === 'Vacant')
                                <button type="button" class="lab-btn lab-btn-primary" data-lab-open="lab-add-tenancy">Add tenancy</button>
                                <button type="button" class="lab-btn lab-btn-ghost">Edit</button>
                            @else
                                <button type="button" class="lab-btn lab-btn-ghost">Edit</button>
                            @endif
                        </div>
                    </header>

                    <div class="pws-tabs" role="tablist">
                        <button type="button" class="is-on" role="tab" data-pws-tab="overview">Overview</button>
                        <button type="button" role="tab" data-pws-tab="tenancy">Tenancy</button>
                        <button type="button" role="tab" data-pws-tab="certificates">Certificates</button>
                        <button type="button" role="tab" data-pws-tab="owners">Owners</button>
                        <button type="button" role="tab" data-pws-tab="documents">Documents</button>
                    </div>

                    <div class="pws-body">
                        <div class="pws-panel is-on" data-pws-panel="overview">
                            <div class="pws-dash">
                                <div class="pws-dash-main">
                                    <article class="pws-hero">
                                        <div class="pws-hero-photo">
                                            <img src="{{ $home['hero'] }}" alt="">
                                            <span class="pws-hero-chip">{{ $home['name'] }}</span>
                                        </div>
                                        <div class="pws-hero-body">
                                            <div class="pws-hero-title">
                                                <div>
                                                    <h2>{{ $home['line_1'] }}</h2>
                                                    <p>{{ $home['city'] }} {{ $home['postcode'] }} · {{ $home['name'] }}</p>
                                                </div>
                                                <span class="pws-occ-pill">{{ $home['occupancy'] }}</span>
                                            </div>
                                            <div class="pws-hero-stats">
                                                <div>
                                                    <span>{{ $home['occupancy'] === 'Let' ? 'Rent' : 'Asking' }}</span>
                                                    <strong>£{{ number_format($letRent ?: $home['display_rent']) }}</strong>
                                                </div>
                                                <div>
                                                    <span>Beds / baths</span>
                                                    <strong>{{ $home['beds'] }} / {{ $home['baths'] }}</strong>
                                                </div>
                                                <div>
                                                    <span>EPC</span>
                                                    <strong>{{ $epcLetter ?: '—' }}</strong>
                                                </div>
                                            </div>
                                        </div>
                                    </article>

                                    <article class="pws-block">
                                        <div class="pws-block-head">
                                            <h2>Residents</h2>
                                            @if($letCount)
                                                <button type="button" class="pws-text" data-pws-tab="tenancy">All lets</button>
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
                                </div>

                                <div class="pws-dash-side">
                                    <div class="pws-kpis">
                                        <article class="pws-kpi">
                                            <span>Annualised rent</span>
                                            <strong>{{ $annualised ? '£'.number_format($annualised) : '—' }}</strong>
                                            <em>{{ $letCount ? $letCount.' active tenanc'.($letCount === 1 ? 'y' : 'ies') : 'No active tenancy' }}</em>
                                        </article>
                                        <article class="pws-kpi{{ $home['unpaid'] ? ' is-warn' : '' }}">
                                            <span>Outstanding</span>
                                            <strong>£{{ number_format($home['unpaid']) }}</strong>
                                            <em>{{ $home['unpaid'] ? 'Rent arrears' : 'Nothing outstanding' }}</em>
                                        </article>
                                        <article class="pws-kpi{{ $missingCerts->count() ? ($missingCerts->where('tone', 'bad')->count() ? ' is-bad' : ' is-warn') : '' }}">
                                            <span>Compliance</span>
                                            <strong>{{ $missingCerts->count() ?: '0' }}</strong>
                                            <em>
                                                @if($missingCerts->where('tone', 'bad')->count())
                                                    Certificate{{ $missingCerts->count() === 1 ? '' : 's' }} missing
                                                @elseif($missingCerts->count())
                                                    Needed before the next let
                                                @else
                                                    Certificates on record
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
                                        @if($queue)
                                            <ul class="pws-queue">
                                                @foreach($queue as $item)
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
                                            <h2>Open repairs</h2>
                                            <a class="pws-text" href="{{ route('backend.ui_lab', ['screen' => 'repairs']) }}">View all</a>
                                        </div>
                                        @if($repairCount)
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
                            </div>
                        </div>

                        <div class="pws-panel" data-pws-panel="tenancy" hidden>
                            <div class="pws-block-head pws-panel-head">
                                <p class="pws-lead">Lets on this home.</p>
                                <button type="button" class="lab-btn {{ $home['occupancy'] === 'Vacant' ? 'lab-btn-primary' : 'lab-btn-secondary' }}" data-lab-open="lab-add-tenancy">Add tenancy</button>
                            </div>
                            @if($home['tenancies'])
                                <div class="pws-table-wrap">
                                    <table class="pws-table">
                                        <thead>
                                            <tr>
                                                <th>Household</th>
                                                <th>Status</th>
                                                <th>Type</th>
                                                <th>Rent</th>
                                                <th>Deposit</th>
                                                <th>Term</th>
                                                <th>Portal</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($home['tenancies'] as $row)
                                                <tr>
                                                    <td>
                                                        <strong>{{ $row['household'] }}</strong>
                                                        <span>{{ $row['household_meta'] }}</span>
                                                    </td>
                                                    <td><span class="pws-row-status"><i class="{{ $dot('idle') }}"></i>{{ $row['status'] }}</span></td>
                                                    <td>{{ $row['type'] }}</td>
                                                    <td>£{{ number_format($row['rent']) }}<span>{{ strtolower($row['frequency']) }}</span></td>
                                                    <td>
                                                        @if($row['deposit'])
                                                            £{{ number_format($row['deposit'], 2) }}
                                                            <span>{{ $row['deposit_held'] }} holding · {{ $row['protected'] ? 'Protected' : 'Not protected' }}</span>
                                                        @else
                                                            <span>Not recorded</span>
                                                        @endif
                                                    </td>
                                                    <td>{{ $row['move_in'] }}{{ $row['move_out'] ? ' – '.$row['move_out'] : ' · periodic' }}</td>
                                                    <td>{{ $row['portal'] }}</td>
                                                    <td class="pws-actions">
                                                        <button type="button" class="lab-btn lab-btn-secondary" data-lab-open="lab-tenancy">Open</button>
                                                        <button type="button" class="lab-btn lab-btn-secondary" data-lab-open="lab-invoice-view">Ledger</button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="pws-blank">
                                    <strong>No tenancy on this property</strong>
                                    <p>Vacant until you add one.</p>
                                    <button type="button" class="lab-btn lab-btn-primary" data-lab-open="lab-add-tenancy">Add tenancy</button>
                                </div>
                            @endif
                        </div>

                        <div class="pws-panel" data-pws-panel="certificates" hidden>
                            <p class="pws-lead">Energy rating can come from the UK register without a PDF. Gas and electrical still need a file on record.</p>
                            <div class="pws-table-wrap">
                                <table class="pws-table">
                                    <thead>
                                        <tr>
                                            <th>Certificate</th>
                                            <th>Status</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($home['certs'] as $cert)
                                            <tr>
                                                <td><strong>{{ $cert['name'] }}</strong></td>
                                                <td><span class="pws-row-status"><i class="{{ $dot($cert['tone']) }}"></i>{{ $cert['date'] }}</span></td>
                                                <td class="pws-actions">
                                                    @if($cert['action'])
                                                        <button type="button" class="lab-btn lab-btn-secondary" data-lab-open="lab-upload">{{ $cert['action'] }}</button>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="pws-panel" data-pws-panel="owners" hidden>
                            <article class="pws-block" style="max-width:36rem;">
                                <div class="pws-block-head">
                                    <h2>Owner group</h2>
                                    <span class="pws-row-status"><i class="{{ $dot('idle') }}"></i>Active</span>
                                </div>
                                <p class="pws-empty" style="margin-bottom:0.75rem;">Purchased {{ $home['purchased'] }}</p>
                                <ul class="pws-people">
                                    @foreach($home['owners'] as $owner)
                                        <li>
                                            <i>{{ strtoupper(substr($owner['name'], 0, 1).substr(strrchr($owner['name'], ' '), 1, 1)) }}</i>
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

                        <div class="pws-panel" data-pws-panel="documents" hidden>
                            <div class="pws-block-head pws-panel-head">
                                <p class="pws-lead">Tenancy agreements and inventories. Safety certificates stay on Certificates.</p>
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
                </div>
            </section>
        @endforeach
    </div>
</x-ui-lab.shell>
