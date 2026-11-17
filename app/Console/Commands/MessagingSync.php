<?php

namespace App\Console\Commands;

use App\Services\Messaging\ConversationProvisioner;
use Illuminate\Console\Command;

/** Daily safety net: open missing tenancy / job chats and archive finished ones. */
class MessagingSync extends Command
{
    protected $signature = 'messaging:sync';

    protected $description = 'Create missing tenancy and job chats, update who is in them and archive chats whose tenancy or job has ended.';

    public function handle(ConversationProvisioner $provisioner): int
    {
        $counts = $provisioner->syncAll();
        $this->info("Created {$counts['created']} chats, checked {$counts['checked']} active chats.");

        return self::SUCCESS;
    }
}
