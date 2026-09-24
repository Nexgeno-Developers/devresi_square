<?php

$homes = [
        'wharf' => [
            'id' => 'wharf',
            'name' => 'Flat 12',
            'line_1' => '1 Baltimore Wharf',
            'city' => 'London',
            'postcode' => 'E14 9RU',
            'thumb' => 'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?auto=format&fit=crop&w=240&q=60',
            'hero' => 'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?auto=format&fit=crop&w=1200&q=70',
            'photos' => [
                'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?auto=format&fit=crop&w=400&q=60',
                'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?auto=format&fit=crop&w=400&q=60',
                'https://images.unsplash.com/photo-1554995207-c18c203602cb?auto=format&fit=crop&w=400&q=60',
            ],
            'type' => 'Flat',
            'beds' => 2,
            'baths' => 2,
            'reception' => 1,
            'floor' => '8',
            'area' => '74 sqm',
            'tenure' => 'Leasehold',
            'council_tax' => 'E',
            'local_authority' => 'Tower Hamlets',
            'parking' => 'Yes',
            'pets' => 'No',
            'gas' => true,
            'occupancy' => 'Let',
            'display_rent' => 1250,
            'unpaid' => 1100,
            'description' => 'Eighth-floor two-bed overlooking the dock. Gas hob, allocated parking, no pets.',
            'tenancies' => [
                [
                    'household' => 'Tina Tenant',
                    'household_meta' => 'Main · tenant@resisquare.test',
                    'type' => 'Assured shorthold',
                    'status' => 'Active',
                    'rent' => 1250,
                    'frequency' => 'Monthly',
                    'deposit' => 1442.31,
                    'deposit_held' => 'Landlord',
                    'protected' => false,
                    'move_in' => '1 Jan 2026',
                    'move_out' => null,
                    'portal' => 'Can sign in',
                    'primary' => true,
                ],
                [
                    'household' => 'Sabir Sayyed',
                    'household_meta' => 'Invite QA Guest, Lucid Mail Test',
                    'type' => 'Assured shorthold',
                    'status' => 'Active',
                    'rent' => 233,
                    'frequency' => 'Monthly',
                    'deposit' => null,
                    'deposit_held' => null,
                    'protected' => false,
                    'move_in' => '1 Jan 2026',
                    'move_out' => null,
                    'portal' => 'No login',
                    'primary' => false,
                ],
            ],
            'repairs' => [
                ['title' => 'Entrance lock stiff', 'status' => 'In progress', 'who' => 'Lara', 'when' => '2 Sep 2026', 'logged' => '2026-09-02'],
                ['title' => 'Bathroom extract fan noisy', 'status' => 'Reported', 'who' => 'Tina', 'when' => '9 Sep 2026', 'logged' => '2026-09-09'],
                ['title' => 'Kitchen tap dripping', 'status' => 'Reported', 'who' => 'Tina', 'when' => '14 Sep 2026', 'logged' => '2026-09-14'],
            ],
            'certs' => [
                ['name' => 'Gas Safe', 'date' => 'No record', 'tone' => 'bad', 'action' => 'Upload'],
                ['name' => 'EICR', 'date' => 'No record', 'tone' => 'bad', 'action' => 'Upload'],
                ['name' => 'EPC', 'date' => 'B · valid to 2031', 'tone' => 'idle', 'action' => null],
            ],
            'owners' => [
                ['name' => 'Lara Landlord', 'email' => 'Landlord.owner@Resisquare.test', 'main' => true],
            ],
            'purchased' => '12 Mar 2021',
            'documents' => [
                ['name' => 'Assured shorthold agreement', 'type' => 'Tenancy', 'visibility' => 'Private', 'date' => '1 Jan 2026'],
                ['name' => 'How to rent guide', 'type' => 'Guide', 'visibility' => 'Shared with Tina', 'date' => '1 Jan 2026'],
                ['name' => 'Check-in inventory', 'type' => 'Inventory', 'visibility' => 'Shared with Tina', 'date' => '1 Jan 2026'],
            ],
        ],
        'street' => [
            'id' => 'street',
            'name' => '1 Staging Landlord Street',
            'line_1' => '1 Staging Landlord Street',
            'city' => 'Stafford',
            'postcode' => 'ST1 1AA',
            'thumb' => 'https://images.unsplash.com/photo-1568605114967-8130f3a36994?auto=format&fit=crop&w=240&q=60',
            'hero' => 'https://images.unsplash.com/photo-1568605114967-8130f3a36994?auto=format&fit=crop&w=1200&q=70',
            'photos' => [],
            'type' => 'House',
            'beds' => 2,
            'baths' => 1,
            'reception' => 1,
            'floor' => null,
            'area' => '86 sqm',
            'tenure' => 'Freehold',
            'council_tax' => 'C',
            'local_authority' => 'Stafford',
            'parking' => 'Yes',
            'pets' => 'Yes',
            'gas' => true,
            'occupancy' => 'Vacant',
            'display_rent' => 1250,
            'unpaid' => 0,
            'description' => '',
            'tenancies' => [],
            'repairs' => [],
            'certs' => [
                ['name' => 'Gas Safe', 'date' => 'Needed before the next let', 'tone' => 'warn', 'action' => 'Upload'],
                ['name' => 'EICR', 'date' => 'Needed before the next let', 'tone' => 'warn', 'action' => 'Upload'],
                ['name' => 'EPC', 'date' => 'Not found', 'tone' => 'idle', 'action' => 'Upload'],
            ],
            'owners' => [
                ['name' => 'Lara Landlord', 'email' => 'Landlord.owner@Resisquare.test', 'main' => true],
            ],
            'purchased' => '4 Jun 2019',
            'documents' => [],
        ],
    ];

    $today = \Carbon\Carbon::parse('2026-09-16');
    $dot = fn ($tone) => 'pws-dot pws-dot-'.($tone === 'bad' ? 'bad' : ($tone === 'warn' ? 'warn' : 'idle'));
    $repairTone = function (array $repair) use ($today) {
        $logged = \Carbon\Carbon::parse($repair['logged'] ?? $repair['when']);
        $hours = $logged->diffInHours($today);
        $days = $logged->diffInDays($today);
        if ($repair['status'] === 'Reported' && $hours <= 48) {
            return 'idle';
        }
        if ($repair['status'] === 'Reported' && $days >= 14) {
            return 'bad';
        }
        if ($repair['status'] === 'In progress' && $days >= 21) {
            return 'bad';
        }
        return 'warn';
    };
    $initials = function (string $name) {
        $parts = preg_split('/\s+/', trim($name));
        return strtoupper(substr($parts[0], 0, 1).(isset($parts[1]) ? substr(end($parts), 0, 1) : ''));
    };
    $homeState = function (array $home) use ($repairTone) {
        $missingCerts = collect($home['certs'])->whereIn('tone', ['bad', 'warn']);
        $letRent = collect($home['tenancies'])->sum('rent');
        $letCount = count($home['tenancies']);
        $primary = collect($home['tenancies'])->firstWhere('primary', true);
        $epc = collect($home['certs'])->firstWhere('name', 'EPC');
        $epcLetter = ($epc && preg_match('/\b([A-G])\b/', $epc['date'] ?? '', $m)) ? $m[1] : null;
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
        return [
            'repairCount' => count($home['repairs']),
            'missingCerts' => $missingCerts,
            'letRent' => $letRent,
            'letCount' => $letCount,
            'annualised' => $letRent ? $letRent * 12 : null,
            'epcLetter' => $epcLetter,
            'queue' => $queue,
        ];
    };
