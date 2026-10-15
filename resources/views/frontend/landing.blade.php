@php
    $calendlyUrl = config('services.calendly.url');
    $navItems = ['Properties', 'Tenancies', 'Rent', 'Repairs', 'Documents', 'Calendar'];
    $chapters = [
        [
            'id' => 'portfolio',
            'index' => '01',
            'title' => 'Portfolio',
            'active' => 'Properties',
            'body' => 'Every home sits in one register. Occupancy, address, and status are current without a second spreadsheet.',
        ],
        [
            'id' => 'tenancy',
            'index' => '02',
            'title' => 'Tenancies',
            'active' => 'Tenancies',
            'body' => 'The agreement, the term, and the renewal stay attached to the property they belong to.',
        ],
        [
            'id' => 'rent',
            'index' => '03',
            'title' => 'Rent',
            'active' => 'Rent',
            'body' => 'Invoices and payments live on the tenancy. The platform is a flat fee, not a share of the rent.',
        ],
        [
            'id' => 'repair',
            'index' => '04',
            'title' => 'Repairs',
            'active' => 'Repairs',
            'body' => 'A tenant raises an issue. You appoint a contractor, confirm the visit, then track the work and close the repair from the same property record.',
        ],
        [
            'id' => 'access',
            'index' => '05',
            'title' => 'Access',
            'active' => 'Documents',
            'body' => 'Landlords, tenants, contractors, and staff each see the scope of their role. Access is assigned, not assumed.',
        ],
    ];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ResiSquare — property operations</title>
    <meta name="description" content="Manage your properties, tenancies, rent and repairs from one clean dashboard. Built for landlords and independent agents.">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="{{ asset('asset/css/landing.css') }}?v={{ filemtime(public_path('asset/css/landing.css')) }}" rel="stylesheet">
</head>
<body class="landing">
    <a class="skip" href="#content">Skip to content</a>

    <header class="nav">
        <div class="wrap nav-inner">
            <a class="brand" href="{{ route('home') }}">
                <img src="{{ site_header_logo_url() }}" alt="ResiSquare">
            </a>
            <nav class="nav-links" aria-label="Primary">
                <a href="#product">Product</a>
                <a href="{{ route('pricing') }}">Pricing</a>
                <a href="{{ route('login') }}">Login</a>
            </nav>
            <div class="nav-actions">
                @if($calendlyUrl)
                    <a class="btn btn-ghost" href="{{ $calendlyUrl }}" target="_blank" rel="noopener">Book a demo</a>
                @else
                    <button type="button" class="btn btn-ghost" data-demo-open>Book a demo</button>
                @endif
                <a class="btn btn-solid" href="{{ route('register') }}">Start trial</a>
            </div>
        </div>
    </header>

    <main id="content">
        <section class="hero" aria-labelledby="hero-title">
            <div class="wrap hero-grid">
                <div class="hero-copy">
                    <p class="eyebrow">Property operations</p>
                    <h1 id="hero-title">One Workspace for Your Entire Portfolio.</h1>
                    <p class="lede">Manage your properties, tenancies, rent and repairs from one clean dashboard. Built for landlords and independent agents who run property as a business.</p>
                    <div class="hero-actions">
                        <a class="btn btn-solid" href="{{ route('register') }}">Start trial</a>
                        @if($calendlyUrl)
                            <a class="btn btn-line" href="{{ $calendlyUrl }}" target="_blank" rel="noopener">Book a demo</a>
                        @else
                            <button type="button" class="btn btn-line" data-demo-open>Book a demo</button>
                        @endif
                    </div>
                    <p class="fine">Try ResiSquare free for 7 days. <a href="{{ route('pricing') }}">View pricing</a></p>
                </div>
                <div class="hero-stage">
                    <div class="hero-app">
                        <div class="window-bar">
                            <span>ResiSquare</span>
                            <span>Portfolio</span>
                        </div>
                        <div class="app">
                            <aside class="side" aria-hidden="true">
                                <p class="side-label">Portfolio</p>
                                @foreach ($navItems as $item)
                                    <span class="{{ $item === 'Properties' ? 'is-active' : '' }}">{{ $item }}</span>
                                @endforeach
                            </aside>
                            <div class="app-main">
                                <div class="sheet-head">
                                    <h3>Properties</h3>
                                    <span>24 active</span>
                                </div>
                                <div class="table" role="presentation">
                                    <div class="tr th"><span>Property</span><span>City</span><span>Status</span><span>Rent</span></div>
                                    <div class="tr"><span>14 Elm Row</span><span>Manchester</span><span class="chip">Occupied</span><span>£1,250</span></div>
                                    <div class="tr"><span>8 Chapel Street</span><span>Leeds</span><span class="chip chip-alert">Notice</span><span>£980</span></div>
                                    <div class="tr"><span>22 Quay House</span><span>Bristol</span><span class="chip chip-quiet">Vacant</span><span>—</span></div>
                                    <div class="tr"><span>Unit 4, Harbour Court</span><span>Liverpool</span><span class="chip">Occupied</span><span>£1,450</span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <p class="visually-hidden">ResiSquare portfolio, showing occupied and vacant homes with rent in one workspace.</p>
                </div>
            </div>
        </section>

        <section class="spec" aria-label="What the workspace covers">
            <div class="wrap">
                <ol>
                    <li><span>01</span> Properties</li>
                    <li><span>02</span> Tenancies</li>
                    <li><span>03</span> Rent</li>
                    <li><span>04</span> Repairs</li>
                    <li><span>05</span> Access</li>
                </ol>
            </div>
        </section>

        <section class="cost" aria-labelledby="cost-title">
            <div class="wrap">
                <header class="section-head">
                    <p class="eyebrow">The operating cost</p>
                    <h2 id="cost-title">Too Many Systems Cost You Time and Margin</h2>
                </header>
                <ol class="cost-list">
                    <li>
                        <span>01</span>
                        <h3>Records</h3>
                        <p>Tenancy files, repair notes, and payment history sit in different places. Nothing is current at the same time.</p>
                    </li>
                    <li>
                        <span>02</span>
                        <h3>Margin</h3>
                        <p>Tools that take a percentage of rent scale against you. ResiSquare is a platform fee, not a cut of income.</p>
                    </li>
                    <li>
                        <span>03</span>
                        <h3>Time</h3>
                        <p>Chasing updates is not the work. The record should already hold the status, the document, and the next action.</p>
                    </li>
                </ol>
            </div>
        </section>

        <section class="product-stage" id="product" aria-labelledby="product-title">
            <div class="wrap stage">
                <div class="stage-copy">
                    <p class="eyebrow">The workspace</p>
                    <h2 id="product-title" class="visually-hidden">ResiSquare workspace</h2>
                    <div class="rail" aria-hidden="true"><span class="rail-fill"></span></div>
                    <div class="steps">
                        @foreach ($chapters as $chapter)
                            <article class="step">
                                <p class="step-index">{{ $chapter['index'] }}</p>
                                <h3>{{ $chapter['title'] }}</h3>
                                <p>{{ $chapter['body'] }}</p>
                            </article>
                        @endforeach
                    </div>
                </div>

                <div class="stage-visual">
                    <div class="window">
                        <div class="window-bar">
                            <span>ResiSquare</span>
                            <span class="window-meta">Workspace</span>
                        </div>
                        <div class="stage-screens">
                            @foreach ($chapters as $chapter)
                                <article class="screen" data-screen="{{ $chapter['id'] }}">
                                    <header class="screen-caption">
                                        <p class="step-index">{{ $chapter['index'] }}</p>
                                        <h3>{{ $chapter['title'] }}</h3>
                                        <p>{{ $chapter['body'] }}</p>
                                    </header>
                                    <div class="app">
                                        <aside class="side" aria-hidden="true">
                                            <p class="side-label">Portfolio</p>
                                            @foreach ($navItems as $item)
                                                <span class="{{ $item === $chapter['active'] ? 'is-active' : '' }}">{{ $item }}</span>
                                            @endforeach
                                        </aside>
                                        <div class="app-main">
                                            @if ($chapter['id'] === 'portfolio')
                                                <div class="sheet-head">
                                                    <h3>Properties</h3>
                                                    <span>24 active</span>
                                                </div>
                                                <div class="table" role="presentation">
                                                    <div class="tr th"><span>Property</span><span>City</span><span>Status</span><span>Rent</span></div>
                                                    <div class="tr"><span>14 Elm Row</span><span>Manchester</span><span class="chip">Occupied</span><span>£1,250</span></div>
                                                    <div class="tr"><span>8 Chapel Street</span><span>Leeds</span><span class="chip chip-alert">Notice</span><span>£980</span></div>
                                                    <div class="tr"><span>22 Quay House</span><span>Bristol</span><span class="chip chip-quiet">Vacant</span><span>—</span></div>
                                                    <div class="tr"><span>Unit 4, Harbour Court</span><span>Liverpool</span><span class="chip">Occupied</span><span>£1,450</span></div>
                                                </div>
                                            @elseif ($chapter['id'] === 'tenancy')
                                                <div class="sheet-head">
                                                    <h3>14 Elm Row</h3>
                                                    <span class="chip">Active</span>
                                                </div>
                                                <p class="sheet-sub">Residential tenancy</p>
                                                <dl class="facts">
                                                    <div><dt>Tenant</dt><dd>A. Rahman</dd></div>
                                                    <div><dt>Start</dt><dd>1 Sep 2024</dd></div>
                                                    <div><dt>Status</dt><dd>Current</dd></div>
                                                    <div><dt>Rent</dt><dd>£1,250 monthly</dd></div>
                                                    <div><dt>Deposit</dt><dd>Held</dd></div>
                                                    <div><dt>Record</dt><dd>On this property</dd></div>
                                                </dl>
                                            @elseif ($chapter['id'] === 'rent')
                                                <div class="sheet-head">
                                                    <h3>Invoice RS-10482</h3>
                                                    <span class="chip">Paid</span>
                                                </div>
                                                <p class="amount">£1,250.00</p>
                                                <p class="sheet-sub">October 2026 · 14 Elm Row</p>
                                                <dl class="facts facts-lines">
                                                    <div><dt>Rent</dt><dd>£1,250.00</dd></div>
                                                    <div><dt>Received</dt><dd>2 Oct 2026</dd></div>
                                                    <div><dt>Method</dt><dd>Bank transfer</dd></div>
                                                    <div><dt>Balance</dt><dd>£0.00</dd></div>
                                                </dl>
                                            @elseif ($chapter['id'] === 'repair')
                                                <div class="sheet-head">
                                                    <h3>Boiler pressure loss</h3>
                                                    <span class="chip chip-alert">Visit booked</span>
                                                </div>
                                                <p class="sheet-sub">14 Elm Row · Raised by tenant</p>
                                                <ol class="thread">
                                                    <li><span>9 Oct</span><strong>Raised</strong><em>Tenant report logged on the property.</em></li>
                                                    <li><span>9 Oct</span><strong>Assigned</strong><em>Northline Heating, approved contractor.</em></li>
                                                    <li><span>16 Oct</span><strong>Visit</strong><em>Thursday, 09:30. Confirmed.</em></li>
                                                    <li><span>—</span><strong>Close</strong><em>Work is marked complete on the property record.</em></li>
                                                </ol>
                                            @else
                                                <div class="sheet-head">
                                                    <h3>Access</h3>
                                                    <span>4 roles</span>
                                                </div>
                                                <div class="table roles" role="presentation">
                                                    <div class="tr th"><span>Role</span><span>Sees</span></div>
                                                    <div class="tr"><span>Landlord</span><span>Portfolio, rent, repairs</span></div>
                                                    <div class="tr"><span>Tenant</span><span>Own home, payments, issues</span></div>
                                                    <div class="tr"><span>Contractor</span><span>Assigned jobs only</span></div>
                                                    <div class="tr"><span>Staff</span><span>Set by permission</span></div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="day" aria-labelledby="day-title">
            <div class="wrap">
                <header class="section-head">
                    <p class="eyebrow">A working day</p>
                    <h2 id="day-title">Your portfolio updates as the day happens.</h2>
                </header>
                <div class="day-track">
                <span class="day-progress" aria-hidden="true"></span>
                <ol class="day-list">
                    <li>
                        <time datetime="07:40">07:40</time>
                        <h3>Rent received</h3>
                        <p>October invoice settled for 14 Elm Row. £1,250 is recorded against the tenancy.</p>
                    </li>
                    <li>
                        <time datetime="13:10">13:10</time>
                        <h3>Repair assigned</h3>
                        <p>Boiler pressure loss is passed to an approved contractor. The visit is on the property record.</p>
                    </li>
                    <li>
                        <time datetime="18:00">18:00</time>
                        <h3>Portfolio reconciled</h3>
                        <p>Occupancy, arrears, and open jobs are in one view before the day ends.</p>
                    </li>
                </ol>
                </div>
            </div>
        </section>

        <section class="close" aria-labelledby="close-title">
            <div class="wrap close-grid">
                <div>
                    <p class="eyebrow">Begin</p>
                    <h2 id="close-title">Bring Your Entire Portfolio Together</h2>
                    <p class="lede">Start a trial on the published plans, or book a walkthrough of the workspace.</p>
                    <div class="hero-actions">
                        <a class="btn btn-solid" href="{{ route('register') }}">Start trial</a>
                        @if($calendlyUrl)
                            <a class="btn btn-line" href="{{ $calendlyUrl }}" target="_blank" rel="noopener">Book a demo</a>
                        @else
                            <button type="button" class="btn btn-line" data-demo-open>Book a demo</button>
                        @endif
                    </div>
                </div>
                <div class="close-model" aria-hidden="true">
                    <div class="elevation elevation-still">
                        @for ($i = 0; $i < 15; $i++)
                            <span class="pane {{ $i === 7 ? 'is-lit' : '' }}"></span>
                        @endfor
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="foot">
        <div class="wrap foot-inner">
            <p>&copy; {{ date('Y') }} ResiSquare</p>
            <nav aria-label="Footer">
                <a href="{{ route('pricing') }}">Pricing</a>
                <a href="{{ route('login') }}">Login</a>
                <a href="{{ route('register') }}">Register</a>
            </nav>
        </div>
    </footer>

    <dialog class="demo" id="demo-dialog" aria-labelledby="demo-title">
        <form class="demo-form" method="POST" action="{{ route('form.submit', 'book_demo') }}">
            @csrf
            <div class="demo-head">
                <h2 id="demo-title">Book a demo</h2>
                <button type="button" class="demo-close" data-demo-close aria-label="Close">Close</button>
            </div>
            <p class="demo-note">Tell us when to walk through the workspace. We will confirm by email.</p>
            <div class="fields">
                <label>First name<input name="first_name" autocomplete="given-name" required maxlength="255"></label>
                <label>Last name<input name="last_name" autocomplete="family-name" maxlength="255"></label>
                <label>Email<input type="email" name="email" autocomplete="email" required maxlength="255"></label>
                <label>Phone<input type="tel" name="phone" autocomplete="tel" required maxlength="20"></label>
                <label class="span-2">Type
                    <select name="user_role" required>
                        <option value="" selected disabled>Select</option>
                        <option value="landlord">Landlord</option>
                        <option value="owner">Owner</option>
                        <option value="freelancing_agent">Estate agent</option>
                        <option value="contractor">Contractor</option>
                    </select>
                </label>
                <label>Date<input type="date" name="demo_date" required></label>
                <label>Time<input type="time" name="demo_time" required></label>
                <label class="span-2">How did you hear about us?<input name="hear_about" maxlength="255"></label>
            </div>
            <label class="check"><input type="checkbox" name="subscribe" value="1"> Send product updates by email.</label>
            <p class="form-error" role="alert" hidden></p>
            <p class="form-success" role="status" hidden>Thank you. We will confirm the time shortly.</p>
            <button class="btn btn-solid btn-block" type="submit">Request demo</button>
        </form>
    </dialog>

    <script src="https://cdn.jsdelivr.net/npm/gsap@3.13.0/dist/gsap.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.13.0/dist/ScrollTrigger.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/lenis@1.1.20/dist/lenis.min.js" defer></script>
    <script src="{{ asset('asset/js/landing.js') }}?v={{ filemtime(public_path('asset/js/landing.js')) }}" defer></script>
</body>
</html>
