<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$provider = config('address_lookup.default');
$key = config('address_lookup.providers.'.$provider.'.api_key');
$hasKey = is_string($key) && trim($key) !== '';

echo "provider={$provider}\n";
echo 'api_key_configured='.($hasKey ? 'yes' : 'no')."\n";
echo 'env_ADDRESS_LOOKUP_PROVIDER='.(env('ADDRESS_LOOKUP_PROVIDER') ?: '(not set)')."\n";
echo 'env_IDEAL_POSTCODES_API_KEY='.(env('IDEAL_POSTCODES_API_KEY') ? '(set)' : '(not set)')."\n";

try {
    $manager = app(App\Services\AddressLookup\AddressLookupManager::class);
    $results = $manager->search('SW1A 2AA');
    echo 'search_results='.count($results)."\n";
} catch (Throwable $e) {
    echo 'search_error='.$e->getMessage()."\n";
}

foreach (['ideal_postcodes', 'postcodes_io'] as $name) {
    config(['address_lookup.default' => $name]);
    try {
        $results = app(App\Services\AddressLookup\AddressLookupManager::class)->search('SW1A 2AA');
        echo "{$name}_results=".count($results)."\n";
    } catch (Throwable $e) {
        echo "{$name}_error=".$e->getMessage()."\n";
    }
}
