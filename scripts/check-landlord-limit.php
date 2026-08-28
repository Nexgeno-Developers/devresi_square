<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = App\Models\User::whereRaw('LOWER(email) = ?', ['landlord.owner@resisquare.test'])->first();

if (! $user) {
    echo "USER NOT FOUND\n";
    exit(1);
}

$account = app(App\Services\Saas\CurrentAccountService::class)->current($user);
$limits = app(App\Services\Saas\AccountLimitService::class);

echo "user_id={$user->id}\n";
echo 'account_id=' . ($account?->id ?? 'none') . "\n";
echo 'used=' . ($account ? $limits->getUsedProperties($account) : 'n/a') . "\n";
echo 'limit=' . ($account ? $limits->getPropertyLimit($account) : 'n/a') . "\n";
echo 'canAdd=' . ($account && $limits->canAddProperty($account) ? 'yes' : 'no') . "\n";
echo 'subscription=' . ($account ? ($limits->getCurrentSubscription($account)?->status ?? 'none') : 'n/a') . "\n";
echo 'plan=' . ($account ? ($limits->getPlan($account)?->name ?? 'none') : 'n/a') . "\n";
