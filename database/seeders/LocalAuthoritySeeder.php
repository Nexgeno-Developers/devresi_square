<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\LocalAuthorityGroup;
use App\Models\LocalAuthority;

class LocalAuthoritySeeder extends Seeder
{
    public function run(): void
    {
        // 1. Define each group and its members
        $all = [
            // England
            'London Boroughs' => [
                'Barking and Dagenham','Barnet','Bexley','Brent','Bromley',
                'Camden','Croydon','Ealing','Enfield','Greenwich','Hackney',
                'Hammersmith and Fulham','Haringey','Harrow','Havering',
                'Hillingdon','Hounslow','Islington','Kensington and Chelsea',
                'Kingston upon Thames','Lambeth','Lewisham','Merton','Newham',
                'Redbridge','Richmond upon Thames','Southwark','Sutton',
                'Tower Hamlets','Waltham Forest','Wandsworth','Westminster',
                'City of London',
            ],
            'Metropolitan Borough Councils' => [
                'Barnsley','Birmingham','Bolton','Bradford','Bury','Calderdale',
                'Coventry','Doncaster','Dudley','Gateshead','Kirklees','Knowsley',
                'Leeds','Liverpool','Manchester','Newcastle upon Tyne',
                'North Tyneside','Oldham','Rochdale','Rotherham','Salford',
                'Sandwell','Sefton','Sheffield','Solihull','South Tyneside',
                'St Helens','Stockport','Sunderland','Tameside','Trafford',
                'Wakefield','Walsall','Wigan','Wolverhampton','Wirral',
            ],
            'Unitary Authorities' => [
                'Bath and North East Somerset','Bedford Borough','Blackburn with Darwen',
                'Blackpool','Bournemouth, Christchurch and Poole','Bracknell Forest',
                'Brighton and Hove','Bristol','Buckinghamshire','Central Bedfordshire',
                'Cheshire East','Cheshire West and Chester','Cornwall','County Durham',
                'Darlington','Derby','Dorset','East Riding of Yorkshire','Halton',
                'Hartlepool','Herefordshire','Isle of Wight','Isles of Scilly',
                'Kingston upon Hull','Leicester','Luton','Medway','Middlesbrough',
                'Milton Keynes','North East Lincolnshire','North Lincolnshire',
                'North Northamptonshire','North Somerset','Nottingham','Peterborough',
                'Plymouth','Portsmouth','Reading','Redcar and Cleveland','Rutland',
                'Shropshire','Slough','South Gloucestershire','Southampton',
                'Southend-on-Sea','Stockton-on-Tees','Stoke-on-Trent','Swindon',
                'Telford and Wrekin','Thurrock','Torbay','Warrington','West Berkshire',
                'West Northamptonshire','Wiltshire','Windsor and Maidenhead',
                'Wokingham','York',
            ],
            'County Councils' => [
                'Bedfordshire','Cambridgeshire','Derbyshire','Devon','Dorset',
                'Durham','East Sussex','Essex','Gloucestershire','Hampshire',
                'Hertfordshire','Kent','Lancashire','Leicestershire','Lincolnshire',
                'Norfolk','Northamptonshire','Nottinghamshire','Oxfordshire',
                'Somerset','Staffordshire','Suffolk','Surrey','Warwickshire',
                'West Sussex',
            ],
            // District Councils (England) – skipped here; add if/when needed
            // Scotland
            'Scotland' => [
                'Aberdeen City','Aberdeenshire','Angus','Argyll and Bute',
                'Clackmannanshire','Dumfries and Galloway','Dundee City',
                'East Ayrshire','East Dunbartonshire','East Lothian',
                'East Renfrewshire','Edinburgh City','Falkirk','Fife',
                'Glasgow City','Highland','Inverclyde','Midlothian','Moray',
                'North Ayrshire','North Lanarkshire','Orkney Islands',
                'Perth and Kinross','Renfrewshire','Scottish Borders',
                'Shetland Islands','South Ayrshire','South Lanarkshire',
                'Stirling','West Dunbartonshire','West Lothian','Western Isles (Eilean Siar)',
            ],
            // Wales
            'Wales' => [
                'Blaenau Gwent','Bridgend','Caerphilly','Cardiff','Carmarthenshire',
                'Ceredigion','Conwy','Denbighshire','Flintshire','Gwynedd',
                'Isle of Anglesey','Merthyr Tydfil','Monmouthshire','Neath Port Talbot',
                'Newport','Pembrokeshire','Powys','Rhondda Cynon Taf','Swansea',
                'Torfaen','Vale of Glamorgan','Wrexham',
            ],
            // Northern Ireland
            'Northern Ireland' => [
                'Antrim and Newtownabbey','Ards and North Down',
                'Armagh City, Banbridge, and Craigavon','Belfast City',
                'Causeway Coast and Glens','Derry City and Strabane',
                'Fermanagh and Omagh','Lisburn and Castlereagh City',
                'Mid and East Antrim','Mid Ulster','Newry, Mourne, and Down',
            ],
        ];

        // 2. Seed each group + its members
        foreach ($all as $groupName => $members) {
            $group = LocalAuthorityGroup::create([
                'name'           => $groupName,
                'display_prefix' => $groupName, 
                // we’ll echo “{$prefix} of {$name}” so prefix = groupName
            ]);

            foreach ($members as $authorityName) {
                LocalAuthority::create([
                    'local_authority_group_id' => $group->id,
                    'name'                     => $authorityName,
                ]);
            }
        }
    }
}
