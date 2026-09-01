@extends('backend.layout.app')

{{-- Design experiment — property workspace prototype.
     ZERO database queries. ZERO auth checks. Pure dummy data.
     This file is the only changed/added file. --}}
@php
    // ── hardcoded demo data ────────────────────────────────────────────────
    $demoPhotos = [
        'https://images.unsplash.com/photo-1564013799919-ab6000274e2c?w=400&h=300&fit=crop',
        'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=400&h=300&fit=crop',
        'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=400&h=300&fit=crop',
    ];
    $demoFloorPlan = 'https://images.unsplash.com/photo-1558618666-fcd25c85f82e?w=400&h=300&fit=crop';
    $demoView360 = '';
    $demoYoutube = 'https://youtube.com/watch?v=demo';
    $demoInstagram = '';

    $property = (object)[
        'id' => 1,
        'prop_ref_no' => 'RSQ-2024-0042',
        'prop_name' => '42 Maple Crescent',
        'line_1' => '42 Maple Crescent',
        'line_2' => 'Kensington',
        'city' => 'London',
        'postcode' => 'SW7 2NR',
        'property_type' => 'both',
        'transaction_type' => 'residential',
        'specific_property_type' => 'flat',
        'sales_current_status' => 'for sale',
        'letting_current_status' => 'let agreed',
        'price' => 425000,
        'letting_price' => 2200,
        'ground_rent' => 350,
        'service_charge' => 1200,
        'estate_charge' => 0,
        'miscellaneous_charge' => 0,
        'annual_council_tax' => 1440,
        'council_tax_band' => 'E',
        'bedroom' => 2,
        'bathroom' => 1,
        'reception' => 1,
        'floor' => 'ground',
        'square_feet' => 680,
        'square_meter' => 63,
        'parking' => 1,
        'parking_location' => 'Underground bay 12',
        'balcony' => 1,
        'garden' => 0,
        'aspects' => 'south-west',
        'service' => 'Fully Managed',
        'pets_allow' => 0,
        'collecting_rent' => 1,
        'epc_required' => 1,
        'epc_rating' => 'B',
        'is_gas' => 1,
        'market_on' => ['Rightmove','Zoopla'],
        'imp_notes' => 'Tenant has requested carpet replacement before renewal. EPC renewal due March 2026. Check smoke alarm batteries during next visit.',
        'photos' => implode(',', [1,2,3]),
        'floor_plan' => '4',
        'view_360' => '',
        'youtube_url' => $demoYoutube,
        'instagram_url' => $demoInstagram,
        'access_arrangement' => 'Key safe at front door — code 4421. Letting agent holds spare key.',
        'key_highlights' => 'Newly refurbished kitchen (2024), double-glazed throughout, close to Gloucester Road tube (3 min walk).',
        'nearest_station' => '1,2',
        'nearest_school' => '3',
        'nearest_places' => json_encode(['Tesco Express'=>'0.3','Gloucester Road Tube'=>'0.4','Hyde Park'=>'0.8']),
        'useful_information' => 'Tenancy starts 1st of each month. Rent is paid by standing order. Contact for emergencies: 07700 900123.',
        'frunishing_type' => 'Furnished',
        'local_authority' => 'Kensington and Chelsea',
        'availability_from' => '2025-01-15',
        'tenure' => 'Leasehold',
        'length_of_lease' => 125,
        'account_id' => 1,
        'company_id' => 1,
    ];
    $pid = $property->id;

    $photoUrls = $demoPhotos;
    $fpUrls = [$demoFloorPlan];
    $allMedia = array_merge($photoUrls, $fpUrls, array_filter([$demoView360]));

    $tenancies = [
        (object)[
            'id' => 10, 'status' => 'Active',
            'move_in' => '2024-06-01', 'move_out' => '2026-05-31',
            'rent' => 2200, 'deposit' => 2640,
            'term_months' => 24, 'term_days' => null,
            'tenancySubStatus' => (object)['name' => 'Periodic'],
            'tenantMembers' => new \Illuminate\Support\Collection([
                (object)['user' => (object)['name' => 'Sarah Mitchell', 'phone' => '07700 900001', 'email' => 'sarah.m@email.com'], 'is_main_person' => 1],
                (object)['user' => (object)['name' => 'James Mitchell', 'phone' => '07700 900002', 'email' => 'james.m@email.com'], 'is_main_person' => 0],
            ]),
        ],
        (object)[
            'id' => 7, 'status' => 'Archived',
            'move_in' => '2022-01-01', 'move_out' => '2024-05-31',
            'rent' => 1950, 'deposit' => 1950,
            'term_months' => 29, 'term_days' => null,
            'tenancySubStatus' => (object)['name' => 'Fixed term ended'],
            'tenantMembers' => new \Illuminate\Support\Collection([
                (object)['user' => (object)['name' => 'Alex Turner', 'phone' => '07700 900003', 'email' => 'alex.t@email.com'], 'is_main_person' => 1],
            ]),
        ],
    ];
    $activeTenancy = $tenancies[0];
    $rentMonthly = (float) ($activeTenancy->rent ?: $property->letting_price);
    $nextRentDue = '01 Sep';

    $openRepairs = 2;
    $repairs = [
        (object)['id'=>201,'reference_number'=>'RPR-2024-008','description'=>'Leaking kitchen tap — mains water pipe','status'=>'Under Process','priority'=>'high','updated_at'=>now()->subDays(2)],
        (object)['id'=>199,'reference_number'=>'RPR-2024-006','description'=>'Hallway light fixture intermittent','status'=>'Pending','priority'=>'low','updated_at'=>now()->subDays(5)],
        (object)['id'=>195,'reference_number'=>'RPR-2024-003','description'=>'Window lock broken — bedroom 2','status'=>'Reported','priority'=>'medium','updated_at'=>now()->subDays(10)],
    ];

    $complianceTypes = [
        (object)['id'=>1,'name'=>'Electrical Installation Condition Report','alias'=>'EICR'],
        (object)['id'=>2,'name'=>'Energy Performance Certificate','alias'=>'EPC'],
        (object)['id'=>3,'name'=>'Gas Safety Certificate','alias'=>'Gas Safe'],
        (object)['id'=>4,'name'=>'Legionella Risk Assessment','alias'=>'Legionella'],
    ];
    $rawCompliance = new \Illuminate\Support\Collection([
        (object)['id'=>1,'compliance_type_id'=>1,'issued_date'=>'2024-01-15','expiry_date'=>now()->addMonths(6),'status'=>'Valid','reference_number'=>'EICR-88421','complianceType'=>$complianceTypes[0]],
        (object)['id'=>2,'compliance_type_id'=>2,'issued_date'=>'2024-03-01','expiry_date'=>now()->addMonths(18),'status'=>'Valid','reference_number'=>'EPC-8823-B','complianceType'=>$complianceTypes[1]],
        (object)['id'=>3,'compliance_type_id'=>3,'issued_date'=>'2024-06-01','expiry_date'=>now()->subDays(5),'status'=>'Expired','reference_number'=>'GSC-11044','complianceType'=>$complianceTypes[2]],
    ]);
    $complianceGroups = $rawCompliance->groupBy('compliance_type_id')->map(fn($items)=>$items->sortByDesc('expiry_date')->first());
    $complianceCount = $rawCompliance->count();
    $expiringCount = $rawCompliance->where('expiry_date','<=',now()->addMonths(2))->where('expiry_date','>=',now())->count();
    $expiredCount = $rawCompliance->where('expiry_date','<',now())->count();
    $missingTypes = $complianceTypes->filter(fn($t)=>!isset($complianceGroups[$t->id]));
    $complianceHealth = match(true){$expiredCount>0=>'danger',$expiringCount>0=>'warning',$complianceCount>0=>'success',default=>'muted'};
    $latestExpiry = $rawCompliance->max('expiry_date');
    $daysToExpiry = $latestExpiry ? (int) ceil($latestExpiry->diffInDays(now(), false)) : null;

    $documents = new \Illuminate\Support\Collection([
        (object)['id'=>1,'documentType'=>(object)['name'=>'Tenancy Agreement'],'created_at'=>now()->subMonths(18),'upload_ids'=>[10]],
        (object)['id'=>2,'documentType'=>(object)['name'=>'Gas Safety Certificate'],'created_at'=>now()->subMonths(6),'upload_ids'=>[11]],
        (object)['id'=>3,'documentType'=>(object)['name'=>'EPC Certificate'],'created_at'=>now()->subMonths(5),'upload_ids'=>[12]],
    ]);
    $documentCount = 8;

    $notes = new \Illuminate\Support\Collection([
        (object)['id'=>1,'noteType'=>(object)['name'=>'General'],'content'=>'Tenant called to confirm boiler service appointment for next Tuesday.','updated_at'=>now()->subDays(1)],
        (object)['id'=>2,'noteType'=>(object)['name'=>'Important'],'content'=>'Carpet replacement scheduled for first week of September. Confirm with tenant before proceeding.','updated_at'=>now()->subDays(3)],
        (object)['id'=>3,'noteType'=>(object)['name'=>'Maintenance'],'content'=>'Annual boiler service completed 2024-06-15 by WarmRite. Certificate uploaded.','updated_at'=>now()->subMonths(2)],
    ]);
    $noteCount = 5;

    $events = new \Illuminate\Support\Collection([
        (object)['id'=>1,'title'=>'Boiler service — annual check','start_datetime'=>now()->addDays(5)->setTime(10,0),'end_datetime'=>now()->addDays(5)->setTime(11,0),'status'=>'Confirmed','location'=>'On-site'],
        (object)['id'=>2,'title'=>'Mid-tenancy inspection','start_datetime'=>now()->addDays(12)->setTime(14,0),'end_datetime'=>now()->addDays(12)->setTime(15,0),'status'=>'Pending','location'=>'On-site'],
        (object)['id'=>3,'title'=>'Tenancy renewal discussion','start_datetime'=>now()->addDays(20)->setTime(11,0),'end_datetime'=>now()->addDays(20)->setTime(11,30),'status'=>'Pending','location'=>'Video call'],
    ]);

    $offers = new \Illuminate\Support\Collection([
        (object)['id'=>1,'status'=>'Accepted','price'=>425000,'deposit'=>21250,'term'=>'24 months','move_in_date'=>'2024-06-01','tenant_details'=>json_encode(['1'=>['name'=>'Sarah Mitchell']])],
    ]);

    $owners = new \Illuminate\Support\Collection([
        (object)['id'=>1,'status'=>'active','purchased_date'=>'2022-01-01','ownerGroupUsers'=>new \Illuminate\Support\Collection([
            (object)['user'=>(object)['name'=>'Robert & Helen Chambers'],'is_main'=>1],
        ])],
    ]);

    $responsibilities = new \Illuminate\Support\Collection([
        (object)['responsibility_type'=>'property_manager','user'=>(object)['name'=>'Lisa Park']],
        (object)['responsibility_type'=>'lettings_consultant','user'=>(object)['name'=>'Dan Hughes']],
    ]);

    $stmt = [
        'summary' => ['invoiced' => 28640.00, 'paid' => 26400.00, 'balance_due' => 2240.00],
        'from' => '2025-01-01',
        'to' => '2025-08-28',
        'warnings' => [],
        'lines' => [
            ['date'=>'2025-08-01','memo'=>'Rent — Sarah Mitchell','account_code'=>'4000','account_name'=>'Rent Income','source_type'=>'Tenancy','source_id'=>'10','debit'=>0,'credit'=>2200,'delta'=>2200,'running'=>26400],
            ['date'=>'2025-08-01','memo'=>'Service charge','account_code'=>'4001','account_name'=>'Service Charge','source_type'=>'','source_id'=>'','debit'=>0,'credit'=>100,'delta'=>100,'running'=>26500],
            ['date'=>'2025-07-01','memo'=>'Rent — Sarah Mitchell','account_code'=>'4000','account_name'=>'Rent Income','source_type'=>'Tenancy','source_id'=>'10','debit'=>0,'credit'=>2200,'delta'=>2200,'running'=>26400],
            ['date'=>'2025-07-01','memo'=>'Repair invoice #RPR-2024-008','account_code'=>'5100','account_name'=>'Repairs','source_type'=>'Repair','source_id'=>'201','debit'=>340,'credit'=>0,'delta'=>-340,'running'=>-340],
        ],
    ];

    $alerts = [
        ['lvl'=>'danger','text'=>'1 compliance item(s) expired','href'=>'#'],
        ['lvl'=>'warning','text'=>'1 compliance item(s) expiring within 60 days','href'=>'#'],
        ['lvl'=>'warning','text'=>'2 open repair(s)','href'=>'#'],
        ['lvl'=>'danger','text'=>'Balance due: £2,240.00','href'=>'#'],
    ];

    $completion = 67;
    $missingSetup = ['EPC rating','First tenancy','Assigned team'];

    $timelineItems = [
        ['ts'=>now()->subDays(2),'icon'=>'bi-wrench','color'=>'warning','title'=>'Repair: Leaking kitchen tap — Under Process','sub'=>'2 days ago'],
        ['ts'=>now()->subDays(5),'icon'=>'bi-wrench','color'=>'info','title'=>'Repair: Hallway light — Pending','sub'=>'5 days ago'],
        ['ts'=>now()->subDays(5),'icon'=>'bi-shield-check','color'=>'danger','title'=>'EICR — Expired','sub'=>'Expired 5 days ago'],
        ['ts'=>now()->subDays(1),'icon'=>'bi-journal-text','color'=>'info','title'=>'Note: General','sub'=>'1 day ago'],
        ['ts'=>now()->subDays(3),'icon'=>'bi-journal-text','color'=>'info','title'=>'Note: Important — Carpet replacement','sub'=>'3 days ago'],
        ['ts'=>now()->subDays(18),'icon'=>'bi-house-door','color'=>'primary','title'=>'Tenancy started — Sarah Mitchell','sub'=>'01 Jun 2024 → 31 May 2026'],
        ['ts'=>now()->subMonths(2),'icon'=>'bi-file-earmark','color'=>'secondary','title'=>'Document: Gas Safety Certificate','sub'=>'Added 2 months ago'],
        ['ts'=>now()->addDays(5),'icon'=>'bi-calendar-event','color'=>'info','title'=>'Boiler service — annual check','sub'=>'05 Sep 10:00 · On-site'],
    ];

    $stations = ['Gloucester Road','South Kensington'];
    $schools = ['St Mary\'s Kensington'];
    $places = ['Tesco Express'=>'0.3','Gloucester Road Tube'=>'0.4','Hyde Park'=>'0.8'];
@endphp

@push('styles')
<style>
#wrapper.main_content{background-color:#F6F7FB !important;padding-left:0 !important}

.tx-app{width:100%;max-width:1280px;margin:0 auto;padding:0 24px 40px}
@media(max-width:767px){.tx-app{padding:0 14px 32px}}

.tx-topbar{display:flex;align-items:center;gap:8px;padding:14px 0;border-bottom:1px solid #E5E7EB;margin-bottom:20px}
.tx-breadcrumb{display:flex;align-items:center;gap:6px;font-size:13px;color:#6B7280}
.tx-breadcrumb a{color:#6B7280;text-decoration:none;display:inline-flex;align-items:center;gap:4px}
.tx-breadcrumb a:hover{color:#FF5C1D}
.tx-breadcrumb .sep{color:#D1D5DB}
.tx-breadcrumb .current{color:#1E293B;font-weight:500}

.tx-switcher{position:relative}
.tx-switch-btn{display:inline-flex;align-items:center;gap:7px;padding:6px 12px;border-radius:8px;border:1px solid #E5E7EB;background:#fff;font-size:13px;color:#1E293B;cursor:pointer;font-weight:500;white-space:nowrap}
.tx-switch-btn .bx{font-size:16px;color:#FF5C1D}
.tx-switch-pop{position:absolute;top:calc(100% + 6px);left:0;right:0;min-width:320px;background:#fff;border:1px solid #E5E7EB;border-radius:12px;box-shadow:0 10px 25px rgba(0,0,0,.08);z-index:200;overflow:hidden;display:none}
.tx-switch-pop.is-open{display:block}
.tx-switch-pop input{width:100%;padding:9px 12px;border:none;border-bottom:1px solid #F3F4F6;font-size:13px;outline:none}
.tx-switch-list{max-height:220px;overflow-y:auto}
.tx-switch-opt{padding:9px 12px;font-size:13px;color:#1E293B;cursor:pointer;display:flex;flex-direction:column;gap:1px;text-decoration:none;border-bottom:1px solid #F9FAFB}
.tx-switch-opt:last-child{border-bottom:none}
.tx-switch-opt:hover,.tx-switch-opt.is-active{background:#FFF7ED}
.tx-switch-opt .ref{color:#9CA3AF;font-size:12px}

.tx-spacer{flex:1}
.tx-cmd-btn{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:8px;border:1px solid #E5E7EB;background:#fff;font-size:13px;color:#4B5563;cursor:pointer}
.tx-cmd-btn:hover{border-color:#FF5C1D;color:#FF5C1D}
.tx-cmd-btn .kbd{display:inline-flex;align-items:center;padding:1px 5px;border-radius:4px;background:#F3F4F6;border:1px solid #E5E7EB;font-size:11px;color:#9CA3AF;font-family:inherit}

.tx-hero{display:flex;gap:24px;padding:22px 0 18px;border-bottom:1px solid #E5E7EB;margin-bottom:24px;align-items:flex-start}
.tx-hero-thumbs{display:flex;gap:6px;flex-shrink:0}
.tx-thumb{width:80px;height:60px;border-radius:8px;object-fit:cover;cursor:pointer;border:1px solid #E5E7EB;transition:transform .15s}
.tx-thumb:hover{transform:scale(1.04)}
.tx-thumb-ph{width:80px;height:60px;border-radius:8px;background:#F3F4F6;border:1px solid #E5E7EB;display:flex;align-items:center;justify-content:center;color:#D1D5DB;font-size:22px}
.tx-hero-info{flex:1;min-width:0}
.tx-hero-row1{display:flex;align-items:flex-start;gap:10px;flex-wrap:wrap}
.tx-hero-name{font-size:22px;font-weight:700;color:#0F172A;line-height:1.25;margin:0}
.tx-pill{display:inline-flex;align-items:center;padding:2px 9px;border-radius:999px;font-size:12px;font-weight:500;flex-shrink:0}
.tx-pill.success{background:#ECFDF5;color:#065F46}
.tx-pill.warning{background:#FFFBEB;color:#92400E}
.tx-pill.danger{background:#FEF2F2;color:#991B1B}
.tx-pill.info{background:#EFF6FF;color:#1E40AF}
.tx-pill.muted{background:#F3F4F6;color:#6B7280}
.tx-pill.secondary{background:#F3F4F6;color:#4B5563}
.tx-pill.primary-pill{background:#FFF7ED;color:#C2410C}
.tx-addr{font-size:14px;color:#6B7280;margin-top:4px;display:flex;align-items:flex-start;gap:6px;flex-wrap:wrap}
.tx-addr a{color:#6B7280;text-decoration:none}
.tx-addr a:hover{color:#FF5C1D}
.tx-hero-strip{display:flex;gap:8px;margin-top:14px;flex-wrap:wrap}

.tx-metric{flex:1;min-width:130px;background:#fff;border:1px solid #E5E7EB;border-radius:10px;padding:12px 14px}
.tx-metric-lbl{font-size:11px;text-transform:uppercase;letter-spacing:.4px;color:#9CA3AF;margin-bottom:3px}
.tx-metric-val{font-size:17px;font-weight:700;color:#0F172A}
.tx-metric-val.text-success{color:#059669}
.tx-metric-val.text-danger{color:#DC2626}
.tx-metric-val.text-warning{color:#D97706}
.tx-metric-sub{font-size:11px;color:#9CA3AF;margin-top:1px}

.tx-tabs{display:flex;gap:2px;border-bottom:1px solid #E5E7EB;margin-bottom:24px;overflow-x:auto;scrollbar-width:none;-ms-overflow-style:none}
.tx-tabs::-webkit-scrollbar{display:none}
.tx-tab{padding:9px 16px;font-size:13.5px;font-weight:500;color:#6B7280;cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-1px;white-space:nowrap;background:none;border-top:none;border-left:none;border-right:none;transition:color .15s,border-color .15s}
.tx-tab:hover{color:#1E293B}
.tx-tab.is-active{color:#FF5C1D;border-bottom-color:#FF5C1D;font-weight:600}
.tx-panel{display:none}
.tx-panel.is-active{display:block}

.tx-card{background:#fff;border:1px solid #E5E7EB;border-radius:12px;margin-bottom:16px;overflow:hidden}
.tx-card-head{padding:14px 18px;border-bottom:1px solid #F3F4F6;display:flex;align-items:center;justify-content:space-between;gap:8px}
.tx-card-title{font-size:14px;font-weight:600;color:#0F172A;display:flex;align-items:center;gap:7px}
.tx-card-title .ic{font-size:16px;color:#FF5C1D}
.tx-card-body{padding:16px 18px}
.tx-card-foot{padding:10px 18px;border-top:1px solid #F3F4F6;background:#FAFBFC}
.tx-card-foot a{font-size:13px;color:#FF5C1D;text-decoration:none;font-weight:500}
.tx-card-foot a:hover{text-decoration:underline}

.tx-grid{display:grid;gap:10px}
.tx-grid.cols-2{grid-template-columns:repeat(2,1fr)}
.tx-grid.cols-3{grid-template-columns:repeat(3,1fr)}
.tx-grid.cols-4{grid-template-columns:repeat(4,1fr)}
@media(max-width:767px){.tx-grid.cols-2,.tx-grid.cols-3,.tx-grid.cols-4{grid-template-columns:1fr}}
.tx-row{display:flex;justify-content:space-between;align-items:flex-start;gap:8px;padding:6px 0;border-bottom:1px solid #F9FAFB}
.tx-row:last-child{border-bottom:none}
.tx-key{font-size:13px;color:#6B7280;flex-shrink:0}
.tx-val{font-size:13px;color:#0F172A;text-align:right;font-weight:500;word-break:break-word}

.tx-dl-row{display:grid;grid-template-columns:140px 1fr;gap:8px;padding:7px 0;border-bottom:1px solid #F9FAFB;align-items:start}
.tx-dl-row:last-child{border-bottom:none}
.tx-dl-key{font-size:13px;color:#6B7280}
.tx-dl-val{font-size:13px;color:#0F172A;font-weight:500;text-align:right;word-break:break-word}

.tx-badge{display:inline-flex;align-items:center;padding:2px 8px;border-radius:999px;font-size:12px;font-weight:500}

.tx-section-hd{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;gap:8px}
.tx-section-title{font-size:16px;font-weight:700;color:#0F172A;display:flex;align-items:center;gap:7px}
.tx-section-title .ic{color:#FF5C1D;font-size:18px}

.tx-list{list-style:none;margin:0;padding:0}
.tx-list-item{padding:10px 0;border-bottom:1px solid #F3F4F6;display:flex;gap:10px;align-items:flex-start}
.tx-list-item:last-child{border-bottom:none}
.tx-list-ic{width:30px;height:30px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0;margin-top:1px}
.tx-list-ic.primary{background:#FFF7ED;color:#C2410C}
.tx-list-ic.success{background:#ECFDF5;color:#065F46}
.tx-list-ic.warning{background:#FFFBEB;color:#92400E}
.tx-list-ic.danger{background:#FEF2F2;color:#991B1B}
.tx-list-ic.info{background:#EFF6FF;color:#1E40AF}
.tx-list-ic.muted{background:#F3F4F6;color:#6B7280}
.tx-list-ic.secondary{background:#F9FAFB;color:#4B5563}
.tx-list-body{flex:1;min-width:0}
.tx-list-title{font-size:13px;font-weight:500;color:#0F172A;margin-bottom:1px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.tx-list-sub{font-size:12px;color:#9CA3AF;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}

.tx-empty{text-align:center;padding:28px 16px;color:#9CA3AF;font-size:13px}

.tx-empty-box{display:flex;flex-direction:column;align-items:center;gap:8px;padding:28px 16px;text-align:center}
.tx-empty-box .ic{font-size:28px;color:#D1D5DB}
.tx-empty-box p{font-size:13px;color:#9CA3AF;margin:0}
.tx-empty-box a{font-size:13px;color:#FF5C1D;text-decoration:none;font-weight:500}

.tx-table{width:100%;border-collapse:collapse}
.tx-table th{text-align:left;padding:8px 10px;font-size:11px;text-transform:uppercase;letter-spacing:.4px;color:#9CA3AF;border-bottom:1px solid #E5E7EB;font-weight:600;background:#FAFBFC}
.tx-table td{padding:9px 10px;font-size:13px;color:#1E293B;border-bottom:1px solid #F3F4F6;vertical-align:middle}
.tx-table tr:last-child td{border-bottom:none}
.tx-table tr:hover td{background:#FCFCFD}

.tx-photo-grid{display:flex;flex-wrap:wrap;gap:6px}
.tx-photo{width:72px;height:54px;border-radius:6px;object-fit:cover;cursor:pointer;border:1px solid #E5E7EB;transition:transform .15s}
.tx-photo:hover{transform:scale(1.05)}

.tx-timeline{position:relative;padding-left:22px}
.tx-timeline::before{content:'';position:absolute;left:7px;top:6px;bottom:6px;width:1.5px;background:#E5E7EB}
.tx-tl-item{position:relative;padding:0 0 14px 14px}
.tx-tl-item:last-child{padding-bottom:0}
.tx-tl-dot{position:absolute;left:-18px;top:3px;width:12px;height:12px;border-radius:50%;border:2px solid #fff;box-shadow:0 0 0 1.5px currentColor}
.tx-tl-title{font-size:13px;font-weight:500;color:#0F172A;margin-bottom:1px}
.tx-tl-sub{font-size:12px;color:#9CA3AF}

.tx-overlay{position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1500;display:none;align-items:center;justify-content:center;padding:24px}
.tx-overlay.is-open{display:flex}
.tx-overlay-box{background:#fff;border-radius:14px;box-shadow:0 20px 60px rgba(0,0,0,.18);width:100%;max-width:560px;max-height:90vh;overflow-y:auto;padding:0}
.tx-overlay-box.wide{max-width:720px}
.tx-overlay-hd{padding:16px 20px;border-bottom:1px solid #F3F4F6;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;background:#fff;z-index:1;border-radius:14px 14px 0 0}
.tx-overlay-hd h3{font-size:15px;font-weight:600;margin:0}
.tx-overlay-close{background:none;border:none;font-size:20px;color:#9CA3AF;cursor:pointer;padding:0;width:28px;height:28px;display:flex;align-items:center;justify-content:center;border-radius:6px}
.tx-overlay-close:hover{background:#F3F4F6;color:#1E293B}
.tx-overlay-body{padding:18px 20px}
.tx-cmd-overlay{position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:1600;display:none;align-items:flex-start;justify-content:center;padding-top:min(20vh,160px)}
.tx-cmd-overlay.is-open{display:flex}
.tx-cmd-box{background:#fff;border-radius:14px;box-shadow:0 20px 60px rgba(0,0,0,.2);width:100%;max-width:560px;overflow:hidden}
.tx-cmd-input{width:100%;padding:16px 20px;font-size:15px;border:none;border-bottom:1px solid #F3F4F6;outline:none}
.tx-cmd-list{max-height:340px;overflow-y:auto;padding:6px}
.tx-cmd-item{padding:10px 12px;border-radius:8px;cursor:pointer;display:flex;align-items:center;gap:10px;font-size:14px;color:#1E293B}
.tx-cmd-item:hover,.tx-cmd-item.is-active{background:#FFF7ED}
.tx-cmd-item .ci-ic{width:30px;height:30px;border-radius:7px;background:#FFF7ED;color:#C2410C;display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0}
.tx-cmd-item .ci-lbl{flex:1}
.tx-cmd-item .ci-hint{font-size:12px;color:#9CA3AF}

.tx-lightbox{position:fixed;inset:0;background:rgba(0,0,0,.88);z-index:2000;display:none;align-items:center;justify-content:center;padding:24px;flex-direction:column;gap:12px}
.tx-lightbox.is-open{display:flex}
.tx-lightbox img{max-width:90vw;max-height:80vh;object-fit:contain;border-radius:4px}
.tx-lightbox-close{color:#fff;font-size:28px;cursor:pointer;opacity:.8;background:none;border:none;line-height:1}
.tx-lightbox-close:hover{opacity:1}

.tx-alert{display:flex;gap:10px;align-items:flex-start;padding:10px 12px;border-radius:8px;margin-bottom:6px;font-size:13px}
.tx-alert.danger{background:#FEF2F2;color:#991B1B}
.tx-alert.warning{background:#FFFBEB;color:#92400E}
.tx-alert.info{background:#EFF6FF;color:#1E40AF}
.tx-alert.secondary{background:#F3F4F6;color:#4B5563}
.tx-alert .al-ic{font-size:15px;flex-shrink:0;margin-top:1px}
.tx-alert-more{font-size:12px;color:#6B7280;padding:4px 12px;cursor:pointer;text-decoration:underline}
.tx-alert-more:hover{color:#FF5C1D}

.tx-progress{height:4px;background:#E5E7EB;border-radius:2px;overflow:hidden;margin-top:6px}
.tx-progress-bar{height:100%;background:#FF5C1D;border-radius:2px;transition:width .4s ease}

.tx-badge-num{background:#FF5C1D;color:#fff;font-size:11px;font-weight:700;padding:1px 7px;border-radius:999px;display:inline-flex;align-items:center;justify-content:center;min-width:20px}

.tx-detail-list{margin-bottom:18px}
.tx-detail-list-hd{display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;cursor:pointer;user-select:none}
.tx-detail-list-hd h4{font-size:13px;font-weight:600;color:#374151;margin:0;display:flex;align-items:center;gap:6px}
.tx-detail-list-hd h4 .ic{color:#FF5C1D;font-size:14px}
.tx-detail-edit{font-size:12px;color:#FF5C1D;text-decoration:none;font-weight:500;display:inline-flex;align-items:center;gap:3px}
.tx-detail-edit:hover{text-decoration:underline}
.tx-detail-body{overflow:hidden;transition:max-height .25s ease}
.tx-detail-body.is-closed{max-height:0 !important}

.tx-actions-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:8px}
.tx-act-btn{display:flex;align-items:center;gap:8px;padding:9px 12px;border-radius:8px;border:1px solid #E5E7EB;background:#fff;font-size:13px;color:#1E293B;text-decoration:none;transition:border-color .15s,background .15s;cursor:pointer;font-family:inherit;width:100%;text-align:left}
.tx-act-btn:hover{border-color:#FF5C1D;background:#FFF7ED;color:#C2410C}
.tx-act-btn .ic{font-size:15px;color:#FF5C1D;flex-shrink:0;width:16px;text-align:center}
.tx-act-btn.danger:hover{border-color:#DC2626;background:#FEF2F2;color:#991B1B}
.tx-act-btn.danger .ic{color:#DC2626}
.tx-act-btn .tx-badge{margin-left:auto;flex-shrink:0}

.tx-check{display:flex;align-items:center;gap:8px;padding:5px 0;font-size:13px;color:#374151}
.tx-check .ci{font-size:14px}
.tx-check.done{color:#059669}
.tx-check.open{color:#D97706}

.tx-people-avatars{display:flex;flex-wrap:wrap;gap:6px}
.tx-avatar{display:flex;align-items:center;gap:6px;padding:4px 9px 4px 5px;border-radius:20px;background:#F3F4F6;font-size:12px;color:#374151}
.tx-avatar-ic{width:22px;height:22px;border-radius:50%;background:#FF5C1D;color:#fff;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;flex-shrink:0}

.tx-drawer-overlay{position:fixed;inset:0;background:rgba(0,0,0,.35);z-index:1400;display:none;align-items:center;justify-content:center;padding:20px}
.tx-drawer-overlay.is-open{display:flex}
.tx-drawer{background:#fff;border-radius:14px;box-shadow:0 20px 60px rgba(0,0,0,.18);width:100%;max-width:520px;max-height:90vh;overflow-y:auto;padding:20px}
.tx-drawer h3{font-size:16px;font-weight:700;color:#0F172A;margin:0 0 14px;display:flex;align-items:center;gap:8px}
.tx-drawer h3 .ic{color:#FF5C1D}

.tx-overlay-scroll{max-height:calc(90vh - 60px);overflow-y:auto}
.tx-compact-table td,.tx-compact-table th{padding:7px 9px;font-size:12px}

.tx-truncate{white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:220px;display:inline-block}

.tx-demo-bar{display:flex;align-items:center;gap:8px;padding:8px 14px;background:#FFF7ED;border:1px solid #FED7AA;border-radius:8px;margin-bottom:18px;font-size:13px;color:#C2410C}
.tx-demo-bar .ic{font-size:16px;color:#FF5C1D}
</style>
@endpush

@section('content')
<div class="tx-app" id="txApp">

{{-- Demo mode banner --}}
<div class="tx-demo-bar">
    <i class="bi bi-eye ic"></i>
    <span><strong>Demo mode</strong> — this page uses sample data. No real property or account data is loaded.</span>
</div>

{{-- Context bar --}}
<div class="tx-topbar">
    <nav class="tx-breadcrumb" aria-label="breadcrumb">
        <a href="#">Properties</a>
        <span class="sep">›</span>
        <span class="current">Workspace <span style="font-size:11px;color:#9CA3AF;font-weight:400">(demo)</span></span>
    </nav>
    <div class="tx-spacer"></div>
    <div class="tx-switcher" id="txSwitcher">
        <button class="tx-switch-btn" id="txSwitchBtn" aria-haspopup="listbox" aria-expanded="false">
            <i class="bi bi-building bx"></i>
            <span class="tx-truncate">{{ $property->prop_ref_no }} — {{ $property->prop_name }}</span>
            <i class="bi bi-chevron-down" style="font-size:11px;color:#9CA3AF;margin-left:2px"></i>
        </button>
        <div class="tx-switch-pop" id="txSwitchPop" role="listbox">
            <input type="text" id="txSwitchSearch" placeholder="Filter properties…" autocomplete="off">
            <div class="tx-switch-list" id="txSwitchList">
                <a class="tx-switch-opt is-active" href="#" role="option">
                    <span>RSQ-2024-0042 — 42 Maple Crescent</span>
                    <span class="ref">London, SW7 2NR</span>
                </a>
                <a class="tx-switch-opt" href="#" role="option">
                    <span>RSQ-2024-0031 — 17 Oakwood Gardens</span>
                    <span class="ref">Clapham, SW4 9LP</span>
                </a>
                <a class="tx-switch-opt" href="#" role="option">
                    <span>RSQ-2024-0015 — Flat 3, 9 Victoria Road</span>
                    <span class="ref">Kensington, SW8 3AA</span>
                </a>
                <a class="tx-switch-opt" href="#" role="option">
                    <span>RSQ-2024-0008 — 55 High Street</span>
                    <span class="ref">Richmond, TW9 2SR</span>
                </a>
                <a class="tx-switch-opt" href="#" role="option">
                    <span>RSQ-2023-0122 — 8 Riverside Walk</span>
                    <span class="ref">Chelsea, SW10 0XL</span>
                </a>
            </div>
        </div>
    </div>
    <button class="tx-cmd-btn" id="txCmdBtn" title="Actions (Ctrl+K)">
        <i class="bi bi-list-ul"></i> Actions <span class="kbd">⌘K</span>
    </button>
</div>

{{-- Hero header --}}
<div class="tx-hero">
    <div class="tx-hero-thumbs">
        @foreach(array_slice($photoUrls,0,3) as $url)
        <img src="{{ $url }}" alt="" class="tx-thumb" loading="lazy" onclick="openTxLightbox(this.src)" onerror="this.style.display='none'">
        @endforeach
        @if(empty($photoUrls))
        <div class="tx-thumb-ph"><i class="bi bi-building"></i></div>
        @endif
        @if(!empty($demoFloorPlan))
        <div class="tx-thumb-ph" style="font-size:12px;color:#9CA3AF;align-items:center;display:flex;flex-direction:column;gap:2px" title="Floor plan available">
            <i class="bi bi-diagram-3"></i><span style="font-size:10px">FP</span>
        </div>
        @endif
    </div>
    <div class="tx-hero-info">
        <div class="tx-hero-row1">
            <h1 class="tx-hero-name">{{ $property->prop_name }}</h1>
            <span class="tx-pill muted">{{ ucfirst($property->property_type) }}</span>
            <span class="tx-pill secondary">{{ ucfirst($property->transaction_type) }}</span>
            <span class="tx-pill success">For sale</span>
            <span class="tx-pill warning">Let agreed</span>
        </div>
        <div class="tx-addr">
            {{ $property->line_1 }}{{ $property->line_2 ? ', '.$property->line_2 : '' }}, {{ $property->city }}, {{ $property->postcode }}
            <button onclick="copyText(this,'{{ addslashes($property->line_1.', '.$property->line_2.', '.$property->city.', '.$property->postcode) }}')" style="background:none;border:none;color:#9CA3AF;cursor:pointer;padding:0;font-size:13px" title="Copy address"><i class="bi bi-clipboard"></i></button>
        </div>
        <div class="tx-hero-strip">
            <div class="tx-metric">
                <div class="tx-metric-lbl">Rent / mo</div>
                <div class="tx-metric-val">£{{ number_format($rentMonthly, 0) }}</div>
                @if($nextRentDue)<div class="tx-metric-sub">Next due {{ $nextRentDue }}</div>@endif
            </div>
            <div class="tx-metric">
                <div class="tx-metric-lbl">Occupancy</div>
                <div class="tx-metric-val text-success">Occupied</div>
                <div class="tx-metric-sub">42% of term elapsed</div>
            </div>
            <div class="tx-metric">
                <div class="tx-metric-lbl">Compliance</div>
                <div class="tx-metric-val text-danger">1 exp.</div>
                <div class="tx-metric-sub">-5 days to nearest expiry</div>
            </div>
            <div class="tx-metric">
                <div class="tx-metric-lbl">Open items</div>
                <div class="tx-metric-val text-warning">3</div>
                <div class="tx-metric-sub">2 repairs · 1 comp.</div>
            </div>
        </div>
    </div>
</div>

{{-- Segment tabs --}}
<div class="tx-tabs" role="tablist" aria-label="Property sections">
@php
    $tabDefs = [
        ['key'=>'overview','label'=>'Overview','active'=>true],
        ['key'=>'tenancy','label'=>'Tenancy & People','active'=>false],
        ['key'=>'money','label'=>'Money','active'=>false],
        ['key'=>'compliance','label'=>'Compliance & Safety','active'=>false],
        ['key'=>'maintenance','label'=>'Maintenance','active'=>false],
        ['key'=>'files','label'=>'Files & Media','active'=>false],
    ];
@endphp
@foreach($tabDefs as $tab)
@php
    $tabClass = 'tx-tab' . ($tab['active'] ? ' is-active' : '');
    $tabAria = $tab['active'] ? 'true' : 'false';
@endphp
    <button class="{{ $tabClass }}" role="tab" aria-selected="{{ $tabAria }}" data-panel="tx-panel-{{ $tab['key'] }}">{{ $tab['label'] }}</button>
@endforeach
</div>

{{-- ===== OVERVIEW ===== --}}
<div class="tx-panel is-active" id="tx-panel-overview" role="tabpanel">
<div class="row" style="display:flex;gap:24px">
<div class="col" style="flex:1;min-width:0">

{{-- Alerts --}}
<div class="tx-card" style="margin-bottom:16px">
    <div class="tx-card-head" style="padding:10px 14px;cursor:pointer" id="txAlertsToggle">
        <span class="tx-card-title" style="font-size:13px"><i class="bi bi-exclamation-triangle ic" style="color:#D97706"></i> Needs attention <span class="tx-badge-num" style="background:#D97706">{{ count($alerts) }}</span></span>
        <i class="bi bi-chevron-down" style="font-size:13px;color:#9CA3AF;transition:transform .2s" id="txAlertsArrow"></i>
    </div>
    <div class="tx-card-body" id="txAlertsBody" style="padding:6px 12px">
        @php
            $alertIcons = [
                'danger'  => 'x-circle',
                'warning' => 'exclamation-triangle',
                'info'    => 'info-circle',
                'secondary' => 'bell',
            ];
        @endphp
        @foreach($alerts as $i=>$a)
        @php
            $alertExtraClass = $i > 2 ? ' tx-alert-more-item' : '';
            $alertStyleAttr = $i > 2 ? ' style="display:none"' : '';
            $alertIcon = $alertIcons[$a['lvl']] ?? 'bell';
        @endphp
        <a href="{{ $a['href'] }}" class="tx-alert {{ $a['lvl'] }}{{ $alertExtraClass }}"{!! $alertStyleAttr !!}>
            <i class="bi bi-{{ $alertIcon }} al-ic"></i>
            <span style="flex:1">{{ $a['text'] }}</span>
        </a>
        @endforeach
        @if(count($alerts) > 3)
        <button class="tx-alert-more" id="txAlertsMore" style="margin:4px 4px 0">Show {{ count($alerts)-3 }} more</button>
        @endif
    </div>
</div>

{{-- Current tenancy card --}}
<div class="tx-card">
    <div class="tx-card-head">
        <span class="tx-card-title"><i class="bi bi-house-door ic"></i> Current tenancy</span>
        <a href="#" class="tx-card-foot" style="border:none;padding:0;font-size:13px"><i class="bi bi-plus-circle me-1"></i>Add tenancy</a>
    </div>
    @if($activeTenancy)
    @php
        $tenantName = $activeTenancy->tenantMembers->first()->user->name;
        $tenantPhone = $activeTenancy->tenantMembers->first()->user->phone;
        $tenantEmail = $activeTenancy->tenantMembers->first()->user->email;
        $ssName = $activeTenancy->tenancySubStatus->name;
        $startTs = strtotime($activeTenancy->move_in);
        $endTs = strtotime($activeTenancy->move_out);
        $nowTs = time();
        $termProgress = $endTs > $startTs ? (int) round((($nowTs - $startTs) / ($endTs - $startTs)) * 100) : 0;
        $termProgress = max(0, min(100, $termProgress));
    @endphp
    <div class="tx-card-body">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;flex-wrap:wrap">
            <div style="width:34px;height:34px;border-radius:50%;background:#FFF7ED;color:#C2410C;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;flex-shrink:0">
                {{ strtoupper(mb_substr($tenantName,0,1)) }}
            </div>
            <div>
                <div style="font-weight:600;font-size:14px;color:#0F172A">{{ $tenantName }}</div>
                <div class="tx-badge muted" style="font-size:11px">{{ $ssName }}</div>
            </div>
            <div class="tx-spacer"></div>
            <span class="tx-pill success">Active</span>
        </div>
        <div class="tx-grid cols-3" style="margin-bottom:10px">
            <div><div class="tx-key">Rent / mo</div><div class="tx-val">£{{ number_format($activeTenancy->rent, 2) }}</div></div>
            <div><div class="tx-key">Deposit</div><div class="tx-val">£{{ number_format($activeTenancy->deposit, 2) }}</div></div>
            <div><div class="tx-key">Move in</div><div class="tx-val">{{ date('d M Y', strtotime($activeTenancy->move_in)) }}</div></div>
            <div><div class="tx-key">Move out</div><div class="tx-val">{{ date('d M Y', strtotime($activeTenancy->move_out)) }}</div></div>
            <div><div class="tx-key">Term</div><div class="tx-val">{{ $activeTenancy->term_months.' mo' }}</div></div>
            <div><div class="tx-key">Phone</div><div class="tx-val"><a href="tel:{{ $tenantPhone }}" style="color:#FF5C1D;text-decoration:none">{{ $tenantPhone }}</a></div></div>
            <div><div class="tx-key">Email</div><div class="tx-val" style="word-break:break-all"><a href="mailto:{{ $tenantEmail }}" style="color:#FF5C1D;text-decoration:none;font-size:12px">{{ $tenantEmail }}</a></div></div>
        </div>
        <div style="font-size:11px;color:#9CA3AF;margin-bottom:3px">Tenancy term progress</div>
        <div class="tx-progress"><div class="tx-progress-bar" style="width:{{ $termProgress }}%"></div></div>
        <div style="font-size:11px;color:#9CA3AF;margin-top:3px">{{ $termProgress }}% elapsed · 348 days remaining</div>
        <div style="display:flex;gap:8px;margin-top:12px;flex-wrap:wrap">
            <a href="#" class="tx-act-btn" style="width:auto"><i class="bi bi-journal-arrow-down ic"></i> Rent ledger</a>
            <a href="#" class="tx-act-btn" style="width:auto"><i class="bi bi-pencil ic"></i> Edit</a>
            <a href="#" class="tx-act-btn" style="width:auto"><i class="bi bi-eye ic"></i> View</a>
        </div>
    </div>
    @endif
</div>

{{-- Recent activity --}}
<div class="tx-card">
    <div class="tx-card-head">
        <span class="tx-card-title"><i class="bi bi-activity ic"></i> Recent activity</span>
    </div>
    <div class="tx-card-body">
        @if(!empty($timelineItems))
        <div class="tx-timeline">
            @foreach($timelineItems as $item)
            <div class="tx-tl-item">
                <div class="tx-tl-dot" style="color:@if($item['color']==='primary')#FF5C1D@elseif($item['color']==='success')#059669@elseif($item['color']==='warning')#D97706@elseif($item['color']==='danger')#DC2626@elseif($item['color']==='info')#2563EB@else #6B7280@endif;background:@if($item['color']==='primary')#FFF7ED@elseif($item['color']==='success')#ECFDF5@elseif($item['color']==='warning')#FFFBEB@elseif($item['color']==='danger')#FEF2F2@elseif($item['color']==='info')#EFF6FF@else #F3F4F6@endif"></div>
                <div class="tx-tl-title">{{ $item['title'] }}</div>
                <div class="tx-tl-sub">{{ $item['sub'] }}</div>
            </div>
            @endforeach
        </div>
        @else
        <div class="tx-empty">No recent activity.</div>
        @endif
    </div>
</div>

{{-- Property details accordion --}}
<div class="tx-card">
    <div class="tx-card-head" style="cursor:pointer" onclick="this.nextElementSibling.classList.toggle('is-closed')">
        <span class="tx-card-title"><i class="bi bi-layers ic"></i> Property details</span>
        <i class="bi bi-chevron-down" style="font-size:13px;color:#9CA3AF;transition:transform .2s"></i>
    </div>
    <div class="tx-card-body tx-detail-body" id="txDetailBody">

        {{-- Key facts --}}
        <div class="tx-detail-list">
            <div class="tx-detail-list-hd" onclick="toggleDetail(this)">
                <h4><i class="bi bi-info-circle ic"></i> Key facts</h4>
                <a href="#" class="tx-detail-edit"><i class="bi bi-pencil"></i> Edit</a>
            </div>
            <div class="tx-detail-body" style="max-height:600px">
                <div class="tx-dl-row"><span class="tx-dl-key">Property type</span><span class="tx-dl-val text-capitalize">{{ $property->property_type }}</span></div>
                <div class="tx-dl-row"><span class="tx-dl-key">Category</span><span class="tx-dl-val text-capitalize">{{ $property->transaction_type }}</span></div>
                <div class="tx-dl-row"><span class="tx-dl-key">Specific type</span><span class="tx-dl-val text-capitalize">{{ $property->specific_property_type }}</span></div>
                <div class="tx-dl-row"><span class="tx-dl-key">Bedrooms</span><span class="tx-dl-val">{{ $property->bedroom }}</span></div>
                <div class="tx-dl-row"><span class="tx-dl-key">Bathrooms</span><span class="tx-dl-val">{{ $property->bathroom }}</span></div>
                <div class="tx-dl-row"><span class="tx-dl-key">Reception</span><span class="tx-dl-val">{{ $property->reception }}</span></div>
                <div class="tx-dl-row"><span class="tx-dl-key">Floor</span><span class="tx-dl-val text-capitalize">{{ $property->floor }}</span></div>
                <div class="tx-dl-row"><span class="tx-dl-key">Balcony</span><span class="tx-dl-val">Yes</span></div>
                <div class="tx-dl-row"><span class="tx-dl-key">Garden</span><span class="tx-dl-val">No</span></div>
                <div class="tx-dl-row"><span class="tx-dl-key">Parking</span><span class="tx-dl-val">Yes · {{ $property->parking_location }}</span></div>
                <div class="tx-dl-row"><span class="tx-dl-key">Aspect</span><span class="tx-dl-val text-capitalize">{{ $property->aspects }}</span></div>
                <div class="tx-dl-row"><span class="tx-dl-key">Area</span><span class="tx-dl-val">{{ number_format($property->square_feet,2) }} sqft ({{ number_format($property->square_meter,2) }} sqm)</span></div>
            </div>
        </div>

        {{-- Pricing --}}
        <div class="tx-detail-list" style="margin-top:14px">
            <div class="tx-detail-list-hd" onclick="toggleDetail(this)">
                <h4><i class="bi bi-currency-pound ic"></i> Pricing & charges</h4>
                <a href="#" class="tx-detail-edit"><i class="bi bi-pencil"></i> Edit</a>
            </div>
            <div class="tx-detail-body" style="max-height:600px">
                <div class="tx-dl-row"><span class="tx-dl-key">Sales price</span><span class="tx-dl-val">£{{ number_format($property->price,2) }}</span></div>
                <div class="tx-dl-row"><span class="tx-dl-key">Letting price</span><span class="tx-dl-val">£{{ number_format($property->letting_price,2) }}/mo</span></div>
                <div class="tx-dl-row"><span class="tx-dl-key">Ground rent</span><span class="tx-dl-val">£{{ number_format($property->ground_rent,2) }}/yr</span></div>
                <div class="tx-dl-row"><span class="tx-dl-key">Service charge</span><span class="tx-dl-val">£{{ number_format($property->service_charge,2) }}/yr</span></div>
                <div class="tx-dl-row"><span class="tx-dl-key">Estate charge</span><span class="tx-dl-val">—</span></div>
                <div class="tx-dl-row"><span class="tx-dl-key">Misc. charge</span><span class="tx-dl-val">—</span></div>
                <div class="tx-dl-row"><span class="tx-dl-key">Council tax</span><span class="tx-dl-val">£{{ number_format($property->annual_council_tax,2) }}/yr ({{ $property->council_tax_band }})</span></div>
            </div>
        </div>

        {{-- Features & services --}}
        <div class="tx-detail-list" style="margin-top:14px">
            <div class="tx-detail-list-hd" onclick="toggleDetail(this)">
                <h4><i class="bi bi-gear ic"></i> Features & services</h4>
                <a href="#" class="tx-detail-edit"><i class="bi bi-pencil"></i> Edit</a>
            </div>
            <div class="tx-detail-body" style="max-height:600px">
                <div class="tx-grid cols-3">
                    <div class="tx-row" style="border:none;padding:2px 0"><span class="tx-key">Service</span><span class="tx-val">{{ $property->service }}</span></div>
                    <div class="tx-row" style="border:none;padding:2px 0"><span class="tx-key">Pets allowed</span><span class="tx-val">N/A</span></div>
                    <div class="tx-row" style="border:none;padding:2px 0"><span class="tx-key">Collect rent</span><span class="tx-val">Yes</span></div>
                    <div class="tx-row" style="border:none;padding:2px 0"><span class="tx-key">Furniture</span><span class="tx-val">{{ $property->frunishing_type }}</span></div>
                </div>
            </div>
        </div>

        {{-- Location & access --}}
        <div class="tx-detail-list" style="margin-top:14px">
            <div class="tx-detail-list-hd" onclick="toggleDetail(this)">
                <h4><i class="bi bi-geo-alt ic"></i> Location & access</h4>
                <a href="#" class="tx-detail-edit"><i class="bi bi-pencil"></i> Edit</a>
            </div>
            <div class="tx-detail-body" style="max-height:600px">
                <div class="tx-dl-row"><span class="tx-dl-key">Access</span><span class="tx-dl-val">{{ $property->access_arrangement }}</span></div>
                <div class="tx-dl-row"><span class="tx-dl-key">Highlights</span><span class="tx-dl-val">{{ $property->key_highlights }}</span></div>
                <div class="tx-dl-row"><span class="tx-dl-key">Nearest station</span><span class="tx-dl-val">{{ implode(', ', $stations) }}</span></div>
                <div class="tx-dl-row"><span class="tx-dl-key">Nearest school</span><span class="tx-dl-val">{{ implode(', ', $schools) }}</span></div>
                <div style="margin-top:6px">
                    <div style="font-size:12px;color:#6B7280;margin-bottom:4px">Nearest places</div>
                    <div class="tx-grid cols-2">
                        @foreach($places as $name=>$dist)
                        <div class="tx-row" style="border:none;padding:2px 0"><span class="tx-key" style="font-size:12px">{{ ucfirst($name) }}</span><span class="tx-val" style="font-size:12px">{{ $dist }} km</span></div>
                        @endforeach
                    </div>
                </div>
                <div class="tx-dl-row" style="margin-top:4px"><span class="tx-dl-key">Useful info</span><span class="tx-dl-val">{{ $property->useful_information }}</span></div>
            </div>
        </div>
    </div>
</div>

</div>
<div class="col" style="width:310px;flex-shrink:0">

{{-- Quick actions --}}
<div class="tx-card">
    <div class="tx-card-head" style="padding:10px 14px">
        <span class="tx-card-title" style="font-size:13px"><i class="bi bi-lightning ic"></i> Quick actions</span>
    </div>
    <div class="tx-card-body" style="padding:8px 10px">
        <div style="display:flex;flex-direction:column;gap:4px">
            <a href="#" class="tx-act-btn"><i class="bi bi-plus-circle ic"></i> New tenancy</a>
            <a href="#" class="tx-act-btn"><i class="bi bi-wrench ic"></i> Raise repair</a>
            <a href="#" class="tx-act-btn"><i class="bi bi-shield-check ic"></i> Add certificate</a>
            <a href="#" class="tx-act-btn"><i class="bi bi-cloud-upload ic"></i> Upload document</a>
            <a href="#" class="tx-act-btn"><i class="bi bi-journal-plus ic"></i> Add note</a>
            <a href="#" class="tx-act-btn"><i class="bi bi-calendar-plus ic"></i> Book appointment</a>
            <a href="#" class="tx-act-btn"><i class="bi bi-image ic"></i> Update media</a>
        </div>
    </div>
</div>

{{-- Setup checklist --}}
<div class="tx-card">
    <div class="tx-card-head" style="padding:10px 14px">
        <span class="tx-card-title" style="font-size:13px"><i class="bi bi-check2-circle ic"></i> Setup checklist</span>
        <span class="tx-badge-num" style="background:#FF5C1D">{{ $completion }}%</span>
    </div>
    <div class="tx-card-body">
        <div class="tx-progress"><div class="tx-progress-bar" style="width:{{ $completion }}%"></div></div>
        <div style="margin-top:10px;display:flex;flex-direction:column;gap:2px">
            @foreach($missingSetup as $item)
            <div class="tx-check open"><i class="bi bi-circle ci"></i> Add {{ $item }}</div>
            @endforeach
            @foreach(['Address','Photos','Floor plan','Pricing','Gas/EPC','First tenancy','Documents','Assigned team'] as $item)
                @if(!in_array($item, $missingSetup))
                <div class="tx-check done"><i class="bi bi-check-circle-fill ci"></i> {{ $item }}</div>
                @endif
            @endforeach
        </div>
    </div>
</div>

{{-- Important note --}}
<div class="tx-card" style="border-color:#FDE68A">
    <div class="tx-card-head" style="padding:10px 14px;background:#FFFBEB">
        <span class="tx-card-title" style="font-size:13px;color:#92400E"><i class="bi bi-exclamation-triangle ic" style="color:#D97706"></i> Important note</span>
        <a href="#" class="tx-detail-edit" style="color:#D97706"><i class="bi bi-pencil"></i></a>
    </div>
    <div class="tx-card-body" style="font-size:13px;color:#92400E">{{ $property->imp_notes }}</div>
</div>

{{-- People --}}
<div class="tx-card">
    <div class="tx-card-head" style="padding:10px 14px">
        <span class="tx-card-title" style="font-size:13px"><i class="bi bi-people ic"></i> People</span>
    </div>
    <div class="tx-card-body">
        @foreach($owners as $og)
            <div style="font-size:12px;color:#6B7280;margin-bottom:6px">
                <i class="bi bi-person-badge" style="color:#FF5C1D"></i>
                {{ ucfirst($og->status) }} owner group
                <span style="color:#374151;font-weight:500">— {{ $og->ownerGroupUsers->first()->user->name }}</span>
                <a href="#" style="font-size:11px;color:#FF5C1D;text-decoration:none;margin-left:4px">View</a>
            </div>
        @endforeach
        <div style="margin-top:6px;display:flex;flex-wrap:wrap;gap:5px">
            @foreach($responsibilities as $r)
            <div class="tx-avatar">
                <div class="tx-avatar-ic">{{ strtoupper(mb_substr($r->user->name,0,1)) }}</div>
                {{ $r->user->name }}
                <span style="font-size:11px;color:#9CA3AF">{{ str_replace('_',' ',ucfirst($r->responsibility_type)) }}</span>
            </div>
            @endforeach
        </div>
    </div>
</div>

</div>
</div>
</div>

{{-- ===== TENANCY & PEOPLE ===== --}}
<div class="tx-panel" id="tx-panel-tenancy" role="tabpanel">
<div class="tx-section-hd">
    <span class="tx-section-title"><i class="bi bi-people ic"></i> Tenancy & People</span>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <a href="#" class="tx-act-btn" style="width:auto"><i class="bi bi-plus-circle ic"></i> New tenancy</a>
        <a href="#" class="tx-act-btn" style="width:auto"><i class="bi bi-person-plus ic"></i> Add owners</a>
    </div>
</div>

{{-- Active tenancy --}}
<div class="tx-card">
    <div class="tx-card-head">
        <span class="tx-card-title"><i class="bi bi-person-check ic"></i> Active tenancy</span>
        <span class="tx-pill success">Active</span>
    </div>
    <div class="tx-card-body">
        <div style="display:flex;gap:10px;align-items:flex-start;margin-bottom:14px;flex-wrap:wrap">
            <div style="width:38px;height:38px;border-radius:50%;background:#FFF7ED;color:#C2410C;display:flex;align-items:center;justify-content:center;font-size:15px;font-weight:700;flex-shrink:0">
                {{ strtoupper(mb_substr($activeTenancy->tenantMembers->first()->user->name,0,1)) }}
            </div>
            <div style="flex:1;min-width:0">
                <div style="font-weight:600;color:#0F172A;font-size:14px">{{ $activeTenancy->tenantMembers->first()->user->name }}</div>
                <div style="font-size:12px;color:#9CA3AF;margin-top:1px">
                    {{ $activeTenancy->tenantMembers->first()->user->email }} · {{ $activeTenancy->tenantMembers->first()->user->phone }}
                </div>
                <div class="tx-badge muted" style="font-size:11px;margin-top:3px">{{ $activeTenancy->tenancySubStatus->name }}</div>
            </div>
            <div style="margin-left:auto;text-align:right">
                <div style="font-size:20px;font-weight:700;color:#0F172A">£{{ number_format($activeTenancy->rent, 2) }}<span style="font-size:12px;color:#9CA3AF;font-weight:400">/mo</span></div>
                <div style="font-size:12px;color:#9CA3AF">Deposit £{{ number_format($activeTenancy->deposit, 2) }}</div>
            </div>
        </div>
        @if($activeTenancy->tenantMembers->count() > 1)
        <div style="border-top:1px solid #F3F4F6;padding-top:10px;margin-top:4px">
            <div style="font-size:12px;color:#6B7280;margin-bottom:6px">Tenancy members</div>
            <div class="tx-people-avatars">
                @foreach($activeTenancy->tenantMembers as $m)
                <div class="tx-avatar">
                    <div class="tx-avatar-ic">{{ strtoupper(mb_substr($m->user->name,0,1)) }}</div>
                    {{ $m->user->name }}
                    @if($m->is_main_person)<i class="bi bi-star-fill" style="font-size:10px;color:#FF5C1D;margin-left:2px" title="Main person"></i>@endif
                </div>
                @endforeach
            </div>
        </div>
        @endif
        <div style="display:flex;gap:8px;margin-top:12px;flex-wrap:wrap">
            <a href="#" class="tx-act-btn" style="width:auto"><i class="bi bi-journal-arrow-down ic"></i> Rent ledger</a>
            <a href="#" class="tx-act-btn" style="width:auto"><i class="bi bi-pencil ic"></i> Edit</a>
            <a href="#" class="tx-act-btn" style="width:auto"><i class="bi bi-eye ic"></i> View</a>
        </div>
    </div>
</div>

{{-- History --}}
<div class="tx-card">
    <div class="tx-card-head">
        <span class="tx-card-title"><i class="bi bi-clock-history ic"></i> History</span>
        <a href="#" class="tx-card-foot" style="border:none">View all tenancies</a>
    </div>
    <div class="tx-card-body" style="padding:0">
        <div style="overflow-x:auto">
        <table class="tx-table tx-compact-table">
            <thead><tr><th>Status</th><th>Sub status</th><th>Tenant</th><th>Rent</th><th>Move in</th><th>Move out</th><th>Actions</th></tr></thead>
            <tbody>
                @foreach($tenancies as $t)
                @php $tName = $t->tenantMembers->first()->user->name; @endphp
                <tr>
                    <td><span class="tx-pill {{ strtolower($t->status)==='active'?'success':'muted' }}">{{ $t->status }}</span></td>
                    <td>{{ $t->tenancySubStatus->name }}</td>
                    <td>{{ $tName }}</td>
                    <td>£{{ number_format($t->rent, 2) }}</td>
                    <td>{{ date('d M Y', strtotime($t->move_in)) }}</td>
                    <td>{{ date('d M Y', strtotime($t->move_out)) }}</td>
                    <td style="white-space:nowrap">
                        <a href="#" style="font-size:12px;color:#FF5C1D;text-decoration:none;margin-right:6px">View</a>
                        <a href="#" style="font-size:12px;color:#6B7280;text-decoration:none">Edit</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    </div>
</div>

{{-- Offers --}}
<div class="tx-card">
    <div class="tx-card-head">
        <span class="tx-card-title"><i class="bi bi-file-earmark-text ic"></i> Offers</span>
        <a href="#" class="tx-card-foot" style="border:none"><i class="bi bi-plus me-1"></i>New offer</a>
    </div>
    <div class="tx-card-body" style="padding:0">
        <table class="tx-table tx-compact-table">
            <thead><tr><th>Status</th><th>Price</th><th>Deposit</th><th>Term</th><th>Move in</th></tr></thead>
            <tbody>
                @foreach($offers as $o)
                <tr>
                    <td><span class="tx-pill success">{{ ucfirst($o->status) }}</span></td>
                    <td>£{{ number_format($o->price, 2) }}</td>
                    <td>£{{ number_format($o->deposit, 2) }}</td>
                    <td>{{ $o->term }}</td>
                    <td>{{ date('d M Y', strtotime($o->move_in_date)) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- Owners --}}
<div class="tx-card">
    <div class="tx-card-head">
        <span class="tx-card-title"><i class="bi bi-person-badge ic"></i> Owners</span>
        <a href="#" class="tx-card-foot" style="border:none"><i class="bi bi-plus me-1"></i>Add group</a>
    </div>
    <div class="tx-card-body">
        @foreach($owners as $og)
        <div style="display:flex;align-items:center;gap:8px;padding:6px 0;border-bottom:1px solid #F3F4F6">
            <div style="width:28px;height:28px;border-radius:50%;background:#ECFDF5;color:#065F46;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700"><i class="bi bi-people"></i></div>
            <div style="flex:1;min-width:0">
                <div style="font-size:13px;font-weight:500;color:#0F172A">{{ ucfirst($og->status) }} owner group</div>
                <div style="font-size:12px;color:#9CA3AF">{{ $og->ownerGroupUsers->count() }} member(s)</div>
            </div>
            <a href="#" style="font-size:12px;color:#FF5C1D;text-decoration:none">View</a>
        </div>
        @endforeach
    </div>
</div>
</div>

{{-- ===== MONEY ===== --}}
<div class="tx-panel" id="tx-panel-money" role="tabpanel">
<div class="tx-section-hd">
    <span class="tx-section-title"><i class="bi bi-currency-pound ic"></i> Money</span>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <a href="#" class="tx-act-btn" style="width:auto"><i class="bi bi-journal-text ic"></i> Full statement</a>
        <a href="#" class="tx-act-btn" style="width:auto"><i class="bi bi-receipt ic"></i> Raise invoice</a>
        <a href="#" class="tx-act-btn" style="width:auto"><i class="bi bi-cash-stack ic"></i> Record payment</a>
    </div>
</div>

<div class="row" style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:16px">
    <div class="tx-metric" style="flex:1;min-width:140px">
        <div class="tx-metric-lbl">Invoiced (YTD)</div>
        <div class="tx-metric-val">£{{ number_format($stmt['summary']['invoiced'], 2) }}</div>
    </div>
    <div class="tx-metric" style="flex:1;min-width:140px">
        <div class="tx-metric-lbl">Paid (YTD)</div>
        <div class="tx-metric-val text-success">£{{ number_format($stmt['summary']['paid'], 2) }}</div>
    </div>
    <div class="tx-metric" style="flex:1;min-width:140px">
        <div class="tx-metric-lbl">Balance due</div>
        <div class="tx-metric-val text-danger">£{{ number_format($stmt['summary']['balance_due'], 2) }}</div>
    </div>
    <div class="tx-metric" style="flex:1;min-width:140px">
        <div class="tx-metric-lbl">Period</div>
        <div class="tx-metric-val" style="font-size:14px">{{ \Carbon\Carbon::parse($stmt['from'])->format('M Y') }} – {{ \Carbon\Carbon::parse($stmt['to'])->format('M Y') }}</div>
    </div>
</div>

<div class="tx-card">
    <div class="tx-card-head">
        <span class="tx-card-title"><i class="bi bi-list-ul ic"></i> Recent transactions</span>
        <a href="#" class="tx-card-foot" style="border:none">View full statement</a>
    </div>
    <div class="tx-card-body" style="padding:0">
        <div style="overflow-x:auto">
        <table class="tx-table tx-compact-table">
            <thead><tr><th>Date</th><th>Details</th><th class="text-end">Debit</th><th class="text-end">Credit</th><th class="text-end">Delta</th></tr></thead>
            <tbody>
                @foreach($stmt['lines'] as $line)
                <tr>
                    <td style="white-space:nowrap">{{ $line['date'] }}</td>
                    <td>
                        <div style="font-size:13px;color:#0F172A">{{ $line['memo'] }}</div>
                        <div style="font-size:11px;color:#9CA3AF">{{ $line['account_code'] }} {{ $line['account_name'] }}@if($line['source_type']) ({{ $line['source_type'] }} {{ $line['source_id'] }})@endif</div>
                    </td>
                    <td class="text-end">£{{ number_format($line['debit'], 2) }}</td>
                    <td class="text-end">£{{ number_format($line['credit'], 2) }}</td>
                    <td class="text-end">{{ number_format($line['delta'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    </div>
</div>
</div>

{{-- ===== COMPLIANCE & SAFETY ===== --}}
<div class="tx-panel" id="tx-panel-compliance" role="tabpanel">
<div class="tx-section-hd">
    <span class="tx-section-title"><i class="bi bi-shield-check ic"></i> Compliance & Safety</span>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <a href="#" class="tx-act-btn" style="width:auto;font-size:12px;padding:7px 10px"><i class="bi bi-plus-circle ic" style="font-size:13px"></i> Add Legionella</a>
    </div>
</div>

<div class="tx-card" style="margin-bottom:16px">
    <div class="tx-card-body" style="display:flex;gap:16px;flex-wrap:wrap;padding:12px 16px">
        <div><span style="font-size:12px;color:#6B7280">Records</span><div style="font-size:18px;font-weight:700;color:#0F172A">{{ $complianceCount }}</div></div>
        <div><span style="font-size:12px;color:#6B7280">Expired</span><div style="font-size:18px;font-weight:700;color:#DC2626">{{ $expiredCount }}</div></div>
        <div><span style="font-size:12px;color:#6B7280">Expiring soon</span><div style="font-size:18px;font-weight:700;color:#D97706">{{ $expiringCount }}</div></div>
        <div><span style="font-size:12px;color:#6B7280">Missing</span><div style="font-size:18px;font-weight:700;color:#9CA3AF">{{ $missingTypes->count() }}</div></div>
    </div>
</div>

@foreach($complianceTypes as $ct)
    @php $latest = $complianceGroups[$ct->id] ?? null; $isMissing = !$latest; @endphp
    <div class="tx-card" style="margin-bottom:12px;@if($isMissing)border-style:dashed;opacity:.8@endif">
        <div class="tx-card-head">
            <span class="tx-card-title" style="@if($isMissing)color:#9CA3AF@endif">
                <i class="bi bi-@if($isMissing)plus-circle-circle@else shield-check@endif ic" style="@if($isMissing)color:#D1D5DB@endif"></i>
                {{ $ct->alias ?: $ct->name }}
            </span>
            @if($isMissing)
            <a href="#" class="tx-card-foot" style="border:none;padding:0;font-size:13px"><i class="bi bi-plus-circle me-1"></i>Add</a>
            @else
            @php $diff = $latest->expiry_date->diffInDays(now(), false); $statusLbl = $diff<0?'Expired':($diff<60?'Expiring soon':'Valid'); $statusCls = $diff<0?'danger':($diff<60?'warning':'success'); @endphp
            <span class="tx-pill {{ $statusCls }}">{{ $statusLbl }}</span>
            @endif
        </div>
        @if(!$isMissing)
        <div class="tx-card-body" style="padding:12px 18px">
            <div class="tx-grid cols-2">
                <div class="tx-dl-row"><span class="tx-dl-key">Reference</span><span class="tx-dl-val">{{ $latest->reference_number }}</span></div>
                <div class="tx-dl-row"><span class="tx-dl-key">Issued</span><span class="tx-dl-val">{{ date('d M Y', strtotime($latest->issued_date)) }}</span></div>
                <div class="tx-dl-row"><span class="tx-dl-key">Expires</span><span class="tx-dl-val {{ $diff<0?'text-danger':($diff<60?'text-warning':'') }}">{{ date('d M Y', strtotime($latest->expiry_date)) }}</span></div>
                <div class="tx-dl-row"><span class="tx-dl-key">Status</span><span class="tx-dl-val">{{ ucfirst($latest->status) }}</span></div>
            </div>
            <div style="margin-top:10px;display:flex;gap:8px">
                <a href="#" class="tx-act-btn" style="width:auto"><i class="bi bi-pencil ic"></i> Edit</a>
                <a href="#" class="tx-act-btn" style="width:auto"><i class="bi bi-file-earmark-pdf ic"></i> View document</a>
            </div>
        </div>
        @else
        <div class="tx-card-body tx-empty-box" style="padding:16px">
            <p style="font-size:13px;color:#9CA3AF">No {{ $ct->name }} record yet.</p>
        </div>
        @endif
    </div>
@endforeach
</div>

{{-- ===== MAINTENANCE ===== --}}
<div class="tx-panel" id="tx-panel-maintenance" role="tabpanel">
<div class="tx-section-hd">
    <span class="tx-section-title"><i class="bi bi-wrench ic"></i> Maintenance</span>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <a href="#" class="tx-act-btn" style="width:auto"><i class="bi bi-plus-circle ic"></i> Raise repair</a>
        <a href="#" class="tx-act-btn" style="width:auto"><i class="bi bi-calendar-plus ic"></i> Book appointment</a>
    </div>
</div>

<div class="row" style="display:flex;gap:16px;flex-wrap:wrap">
<div style="flex:1;min-width:280px">
{{-- Repairs --}}
<div class="tx-card">
    <div class="tx-card-head">
        <span class="tx-card-title"><i class="bi bi-wrench ic"></i> Repairs</span>
        <span class="tx-badge-num">{{ $openRepairs }}</span>
        <a href="#" class="tx-card-foot" style="border:none">View all</a>
    </div>
    <div class="tx-card-body" style="padding:0">
        <div style="overflow-x:auto">
        <table class="tx-table tx-compact-table">
            <thead><tr><th>Ref</th><th>Description</th><th>Status</th><th>Priority</th><th>Updated</th></tr></thead>
            <tbody>
                @foreach($repairs as $r)
                <tr>
                    <td style="white-space:nowrap;font-size:12px;color:#6B7280">{{ $r->reference_number }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($r->description, 40) }}</td>
                    <td>
                        <span class="tx-badge {{ match(strtolower($r->status)){ 'pending'=>'warning','reported'=>'info','under process'=>'primary-pill','closed'=>'muted','invoice paid'=>'success', default=>'secondary'} }}">
                            {{ ucfirst($r->status) }}
                        </span>
                    </td>
                    <td>{{ ucfirst($r->priority) }}</td>
                    <td style="white-space:nowrap;font-size:12px;color:#6B7280">{{ $r->updated_at->diffForHumans() }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    </div>
</div>
</div>
<div style="width:310px;flex-shrink:0">
{{-- Appointments --}}
<div class="tx-card">
    <div class="tx-card-head">
        <span class="tx-card-title"><i class="bi bi-calendar-event ic"></i> Upcoming</span>
        <a href="#" class="tx-card-foot" style="border:none">Calendar</a>
    </div>
    <div class="tx-card-body">
        <div style="display:flex;flex-direction:column;gap:8px">
            @foreach($events as $e)
            <div style="display:flex;gap:8px;align-items:flex-start;padding:7px 0;border-bottom:1px solid #F3F4F6">
                <div style="width:38px;height:38px;border-radius:8px;background:#EFF6FF;color:#1E40AF;display:flex;flex-direction:column;align-items:center;justify-content:center;font-size:10px;font-weight:700;flex-shrink:0;line-height:1.2">
                    <span>{{ $e->start_datetime->format('d') }}</span>
                    <span>{{ $e->start_datetime->format('M') }}</span>
                </div>
                <div style="flex:1;min-width:0">
                    <div style="font-size:13px;font-weight:500;color:#0F172A;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $e->title }}</div>
                    <div style="font-size:12px;color:#9CA3AF">{{ $e->start_datetime->format('H:i') }} – {{ $e->end_datetime->format('H:i') }}{{ $e->location ? ' · '.$e->location : '' }}</div>
                </div>
                <span class="tx-pill {{ match(strtolower($e->status)){ 'confirmed'=>'success','pending'=>'warning','cancelled'=>'danger', default=>'muted'} }}" style="font-size:11px">{{ $e->status }}</span>
            </div>
            @endforeach
        </div>
    </div>
</div>
</div>
</div>
</div>

{{-- ===== FILES & MEDIA ===== --}}
<div class="tx-panel" id="tx-panel-files" role="tabpanel">
<div class="tx-section-hd">
    <span class="tx-section-title"><i class="bi bi-folder2-open ic"></i> Files & Media</span>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <a href="#" class="tx-act-btn" style="width:auto"><i class="bi bi-image ic"></i> Manage media</a>
        <a href="#" class="tx-act-btn" style="width:auto"><i class="bi bi-cloud-upload ic"></i> Documents</a>
        <a href="#" class="tx-act-btn" style="width:auto"><i class="bi bi-journal-text ic"></i> Notes</a>
    </div>
</div>

<div class="row" style="display:flex;gap:16px;flex-wrap:wrap">
{{-- Media --}}
<div class="tx-card" style="flex:1;min-width:260px">
    <div class="tx-card-head">
        <span class="tx-card-title"><i class="bi bi-image ic"></i> Media</span>
        <span style="font-size:12px;color:#9CA3AF">{{ count($photoUrls) }} photo(s) · {{ count($fpUrls) }} floor plan(s)</span>
    </div>
    <div class="tx-card-body">
        @if(!empty($allMedia))
        <div class="tx-photo-grid">
            @foreach($allMedia as $url)
            <img src="{{ $url }}" alt="" class="tx-photo" loading="lazy" onclick="openTxLightbox('{{ $url }}')" onerror="this.style.display='none'">
            @endforeach
        </div>
        @if(!empty($demoView360))
        <div style="margin-top:10px">
            <a href="{{ $demoView360 }}" target="_blank" rel="noopener" class="tx-act-btn" style="width:auto"><i class="bi bi-badge-3d ic"></i> View 360° tour</a>
        </div>
        @endif
        @if($property->youtube_url || $property->instagram_url)
        <div style="margin-top:8px;display:flex;gap:8px;flex-wrap:wrap">
            @if($property->youtube_url)<a href="{{ $property->youtube_url }}" target="_blank" rel="noopener" class="tx-act-btn" style="width:auto"><i class="bi bi-youtube ic"></i> YouTube</a>@endif
            @if($property->instagram_url)<a href="{{ $property->instagram_url }}" target="_blank" rel="noopener" class="tx-act-btn" style="width:auto"><i class="bi bi-instagram ic"></i> Instagram</a>@endif
        </div>
        @endif
        @else
        <div class="tx-empty-box"><i class="bi bi-image" style="font-size:24px;color:#D1D5DB"></i><p>No media yet.</p><a href="#">Upload media</a></div>
        @endif
    </div>
</div>

{{-- Documents --}}
<div class="tx-card" style="flex:1;min-width:260px">
    <div class="tx-card-head">
        <span class="tx-card-title"><i class="bi bi-file-earmark ic"></i> Documents</span>
        <span class="tx-badge-num">{{ $documentCount }}</span>
    </div>
    <div class="tx-card-body" style="padding:0">
        <table class="tx-table tx-compact-table">
            <thead><tr><th>Name</th><th>Type</th><th>Added</th></tr></thead>
            <tbody>
                @foreach($documents as $d)
                <tr>
                    <td>
                        @php $firstId = is_array($d->upload_ids) ? ($d->upload_ids[0] ?? null) : null; @endphp
                        @if($firstId)
                        <a href="{{ uploaded_asset($firstId) }}" target="_blank" rel="noopener" style="font-size:13px;color:#FF5C1D;text-decoration:none;font-weight:500">{{ $d->documentType->name }}</a>
                        @else
                        <span style="font-size:13px;color:#0F172A">{{ $d->documentType->name }}</span>
                        @endif
                    </td>
                    <td style="font-size:12px;color:#6B7280">{{ $d->documentType->name }}</td>
                    <td style="font-size:12px;color:#6B7280;white-space:nowrap">{{ $d->created_at->diffForHumans() }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @if($documentCount > 5)<div class="tx-card-foot"><a href="#">View all {{ $documentCount }} documents</a></div>@endif
    </div>
</div>

{{-- Notes --}}
<div class="tx-card" style="flex:1;min-width:260px">
    <div class="tx-card-head">
        <span class="tx-card-title"><i class="bi bi-journal-text ic"></i> Notes</span>
        <span class="tx-badge-num">{{ $noteCount }}</span>
    </div>
    <div class="tx-card-body" style="padding:0">
        <table class="tx-table tx-compact-table">
            <thead><tr><th>Type</th><th>Note</th><th>Updated</th></tr></thead>
            <tbody>
                @foreach($notes as $n)
                <tr>
                    <td style="font-size:12px;color:#6B7280;white-space:nowrap">{{ $n->noteType->name }}</td>
                    <td><div style="font-size:13px;color:#0F172A;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:180px">{{ \Illuminate\Support\Str::limit(strip_tags($n->content), 60) }}</div></td>
                    <td style="font-size:12px;color:#6B7280;white-space:nowrap">{{ $n->updated_at->diffForHumans() }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @if($noteCount > 5)<div class="tx-card-foot"><a href="#">View all {{ $noteCount }} notes</a></div>@endif
    </div>
</div>
</div>
</div>

{{-- Actions overlay --}}
<div class="tx-drawer-overlay" id="txActionsOverlay" onclick="if(event.target===this)closeTxActions()">
<div class="tx-drawer" role="dialog" aria-label="All actions">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
        <h3><i class="bi bi-list-ul ic"></i> All actions</h3>
        <button class="tx-overlay-close" onclick="closeTxActions()" aria-label="Close"><i class="bi bi-x-lg"></i></button>
    </div>
    <input type="text" id="txActionFilter" placeholder="Filter actions…" style="width:100%;padding:9px 12px;border:1px solid #E5E7EB;border-radius:8px;font-size:13px;outline:none;margin-bottom:10px" autocomplete="off">
    <div id="txActionsList" style="display:flex;flex-direction:column;gap:3px;max-height:60vh;overflow-y:auto">
        @php
            $groups = [
                'Property' => [
                    ['label'=>'Edit property','icon'=>'bi-pencil','href'=>'#'],
                    ['label'=>'Open in classic view','icon'=>'bi-window-stack','href'=>'#'],
                    ['label'=>'Download brochure','icon'=>'bi-file-earmark-pdf','href'=>'#'],
                    ['label'=>'Add property','icon'=>'bi-plus-circle','href'=>'#'],
                    ['label'=>'All properties','icon'=>'bi-building','href'=>'#'],
                    ['label'=>'Deleted properties','icon'=>'bi-trash','href'=>'#'],
                    ['label'=>'Delete property','icon'=>'bi-trash','href'=>'#','danger'=>true,'onclick'=>'alert("Demo — delete disabled")'],
                ],
                'Tenancy & People' => [
                    ['label'=>'New tenancy','icon'=>'bi-plus-circle','href'=>'#'],
                    ['label'=>'Rent ledger','icon'=>'bi-journal-arrow-down','href'=>'#'],
                    ['label'=>'All tenancies','icon'=>'bi-houses','href'=>'#'],
                    ['label'=>'New offer','icon'=>'bi-file-earmark-text','href'=>'#'],
                    ['label'=>'All offers','icon'=>'bi-collection','href'=>'#'],
                    ['label'=>'Add owners','icon'=>'bi-person-plus','href'=>'#'],
                    ['label'=>'All owner groups','icon'=>'bi-people','href'=>'#'],
                ],
                'Money' => [
                    ['label'=>'Statement','icon'=>'bi-journal-text','href'=>'#'],
                    ['label'=>'Raise invoice','icon'=>'bi-receipt','href'=>'#'],
                    ['label'=>'Record payment','icon'=>'bi-cash-stack','href'=>'#'],
                    ['label'=>'All invoices','icon'=>'bi-receipt','href'=>'#'],
                ],
                'Maintenance' => [
                    ['label'=>'Raise repair','icon'=>'bi-wrench','href'=>'#'],
                    ['label'=>'All repairs','icon'=>'bi-tools','href'=>'#'],
                    ['label'=>'Book appointment','icon'=>'bi-calendar-plus','href'=>'#'],
                ],
                'Files' => [
                    ['label'=>'Manage media','icon'=>'bi-image','href'=>'#'],
                    ['label'=>'Documents','icon'=>'bi-cloud-upload','href'=>'#'],
                    ['label'=>'Notes','icon'=>'bi-journal-text','href'=>'#'],
                ],
            ];
        @endphp
        @foreach($groups as $gName=>$items)
        <div style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#9CA3AF;padding:6px 8px 2px;font-weight:600">{{ $gName }}</div>
        @foreach($items as $a)
        @php
            $actionOnclick = !empty($a['onclick']) ? ' onclick="'.$a['onclick'].'"' : '';
        @endphp
        <a href="{{ $a['href'] }}"{!! $actionOnclick !!} class="tx-cmd-item" data-action-label="{{ $a['label'] }}">
            <div class="ci-ic"><i class="bi {{ $a['icon'] }}"></i></div>
            <span class="ci-lbl">{{ $a['label'] }}</span>
            @if(!empty($a['danger']))<span class="tx-pill danger" style="font-size:10px;padding:1px 6px">Danger</span>@endif
        </a>
        @endforeach
        @endforeach
    </div>
</div>
</div>

{{-- Command palette --}}
<div class="tx-cmd-overlay" id="txCmdOverlay">
<div class="tx-cmd-box">
    <input type="text" class="tx-cmd-input" id="txCmdInput" placeholder="Type an action or search…" autocomplete="off">
    <div class="tx-cmd-list" id="txCmdList"></div>
    <div id="txCmdEmpty" style="display:none;padding:16px;text-align:center;color:#9CA3AF;font-size:13px">No actions found.</div>
</div>
</div>

{{-- Lightbox --}}
<div class="tx-lightbox" id="txLightbox" onclick="if(event.target===this)closeTxLightbox()">
<button class="tx-lightbox-close" onclick="closeTxLightbox()" aria-label="Close"><i class="bi bi-x-lg"></i></button>
<img src="" alt="" id="txLightboxImg">
<div style="color:#fff;font-size:13px;opacity:.8" id="txLightboxCaption"></div>
</div>

@endsection

@push('styles')
<style>
#wrapper.main_content{background-color:#F6F7FB !important;padding-left:0 !important}
</style>
@endpush

@section('page.scripts')
<script>
(function(){
'use strict';

function qs(sel, el){return (el||document).querySelector(sel)}
function qsa(sel, el){return Array.from((el||document).querySelectorAll(sel))}

function copyText(btn, text){
    navigator.clipboard.writeText(text).then(function(){
        var old=btn.innerHTML; btn.innerHTML='<i class="bi bi-check"></i>'; btn.style.color='#059669';
        setTimeout(function(){btn.innerHTML=old;btn.style.color='';},1500);
    }).catch(function(){});
}

function openTxLightbox(src){
    qs('#txLightboxImg').src=src;
    qs('#txLightbox').classList.add('is-open');
    document.body.style.overflow='hidden';
}
function closeTxLightbox(){
    qs('#txLightbox').classList.remove('is-open');
    document.body.style.overflow='';
    setTimeout(function(){if(!qs('#txLightbox').classList.contains('is-open'))qs('#txLightboxImg').src='';},200);
}

document.addEventListener('keydown',function(e){
    if(e.key==='Escape'){
        closeTxLightbox();
        closeTxActions();
        closeCmd();
    }
    if((e.metaKey||e.ctrlKey)&&e.key==='k'){e.preventDefault();openCmd();}
});

window.toggleDetail=function(hd){
    var body=hd.nextElementSibling;
    if(!body)return;
    var isClosed=body.classList.toggle('is-closed');
    var arrow=qs('i.bi-chevron-down',hd);
    if(arrow)arrow.style.transform=isClosed?'rotate(-90deg)':'';
};

(function(){
    var switcher=qs('#txSwitcher');
    if(!switcher)return;
    var btn=qs('#txSwitchBtn');
    var pop=qs('#txSwitchPop');
    var search=qs('#txSwitchSearch');
    var list=qs('#txSwitchList');
    btn.addEventListener('click',function(e){e.stopPropagation();pop.classList.toggle('is-open');btn.setAttribute('aria-expanded',pop.classList.contains('is-open'));if(pop.classList.contains('is-open')&&search)search.focus();});
    document.addEventListener('click',function(){pop.classList.remove('is-open');btn.setAttribute('aria-expanded','false');});
    pop.addEventListener('click',function(e){e.stopPropagation();});
    if(search){search.addEventListener('input',function(){
        var q=this.value.toLowerCase();
        qsa('.tx-switch-opt',list).forEach(function(el){
            var t=(el.textContent||'').toLowerCase(); el.style.display=t.includes(q)?'':'none';
        });
    });}
})();

(function(){
    var tabs=qsa('.tx-tab');
    var panels=qsa('.tx-panel');
    tabs.forEach(function(tab){
        tab.addEventListener('click',function(){
            var target=this.dataset.panel;
            tabs.forEach(function(t){t.classList.remove('is-active');t.setAttribute('aria-selected','false');});
            panels.forEach(function(p){p.classList.remove('is-active');});
            this.classList.add('is-active');this.setAttribute('aria-selected','true');
            var panel=qs('#'+target);if(panel)panel.classList.add('is-active');
            if(window.location.hash!=='#'+target){history.replaceState(null,'','#'+target);}
        });
    });
    var hash=location.hash.replace('#','');
    if(hash){var t=qs('.tx-tab[data-panel="tx-panel-'+hash+'"]');if(t)t.click();}
})();

(function(){
    var btn=qs('#txCmdBtn');
    var overlay=qs('#txCmdOverlay');
    var input=qs('#txCmdInput');
    var list=qs('#txCmdList');
    var empty=qs('#txCmdEmpty');
    if(!btn||!overlay)return;
    var items=[];
    qsa('.tx-cmd-item',document).forEach(function(el){
        items.push({label:el.dataset.actionLabel||el.textContent.trim(),href:el.getAttribute('href')||'#',onclick:el.getAttribute('onclick')||null});
    });
    function render(filter){
        var q=(filter||'').toLowerCase();
        var matches=items.filter(function(it){return it.label.toLowerCase().indexOf(q)!==-1;});
        list.innerHTML='';
        if(matches.length===0){empty.style.display='';}else{empty.style.display='none';}
        matches.forEach(function(it){
            var div=document.createElement('div');
            div.className='tx-cmd-item';
            div.innerHTML='<div class="ci-ic"><i class="bi bi-lightning"></i></div><span class="ci-lbl">'+it.label.replace(/</g,'&lt;')+'</span>';
            div.addEventListener('click',function(){if(it.onclick){eval(it.onclick.replace('event.preventDefault();','').replace('event.preventDefault()',''));}else if(it.href&&it.href!=='#'){window.location.href=it.href;}closeCmd();});
            list.appendChild(div);
        });
    }
    function openCmd(){overlay.classList.add('is-open');input.value='';render('');setTimeout(function(){input.focus();},50);}
    function closeCmd(){overlay.classList.remove('is-open');}
    btn.addEventListener('click',openCmd);
    overlay.addEventListener('click',function(e){if(e.target===overlay)closeCmd();});
    input.addEventListener('input',function(){render(this.value);});
    input.addEventListener('keydown',function(e){
        var opts=qsa('.tx-cmd-item',list);
        var idx=Array.prototype.indexOf.call(qsa('.tx-cmd-item',list),qs('.tx-cmd-item.is-active',list));
        if(e.key==='ArrowDown'){e.preventDefault();idx=(idx+1)%opts.length;opts.forEach(function(o){o.classList.remove('is-active');});if(opts[idx])opts[idx].classList.add('is-active');}
        else if(e.key==='ArrowUp'){e.preventDefault();idx=(idx-1+opts.length)%opts.length;opts.forEach(function(o){o.classList.remove('is-active');});if(opts[idx])opts[idx].classList.add('is-active');}
        else if(e.key==='Enter'){var active=qs('.tx-cmd-item.is-active',list)||opts[0];if(active)active.click();}
    });
    window.openCmd=openCmd;window.closeCmd=closeCmd;
})();

window.openTxActions=function(){qs('#txActionsOverlay').classList.add('is-open');document.body.style.overflow='hidden';};
window.closeTxActions=function(){qs('#txActionsOverlay').classList.remove('is-open');document.body.style.overflow='';};

(function(){
    var filter=qs('#txActionFilter');
    var list=qs('#txActionsList');
    if(!filter||!list)return;
    filter.addEventListener('input',function(){
        var q=this.value.toLowerCase();
        qsa('[data-action-label]',list).forEach(function(el){
            var lbl=(el.dataset.actionLabel||'').toLowerCase();el.style.display=lbl.indexOf(q)!==-1?'':'none';
        });
    });
})();

})();
</script>
@endsection
