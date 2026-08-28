<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$updated = Illuminate\Support\Facades\DB::table('plans')
    ->where('name', 'Landlord Basic')
    ->update(['property_limit' => 5]);

echo "Updated {$updated} plan row(s). New limit: ";
echo Illuminate\Support\Facades\DB::table('plans')->where('name', 'Landlord Basic')->value('property_limit');
echo PHP_EOL;
