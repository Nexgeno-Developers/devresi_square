<?php

namespace App\Console\Commands;

use App\Models\Conversation;
use App\Models\MessageReport;
use App\Models\MessageRequest;
use App\Services\Messaging\ConversationService;
use App\Services\Messaging\MessageRequestService;
use Illuminate\Console\Command;

/** Daily: expire ignored requests, close ended relationships, purge old data. */
class MessagingMaintain extends Command
{
    protected $signature = 'messaging:maintain';

    protected $description = 'Expire message requests, make ended conversations read-only and apply messaging retention.';

    public function handle(MessageRequestService $requests, ConversationService $conversations): int
    {
        $expired = $requests->expireDue();

        $readOnly = 0;
        Conversation::query()
            ->where('status', Conversation::ACTIVE)
            ->with('participants')
            ->chunkById(200, function ($chunk) use ($conversations, &$readOnly) {
                foreach ($chunk as $conversation) {
                    if (! $conversations->refreshStatus($conversation)->isActive()) {
                        $readOnly++;
                    }
                }
            });

        $retention = config('messaging.retention');
        $purgedIntros = MessageRequest::query()
            ->whereIn('status', [MessageRequest::DECLINED, MessageRequest::EXPIRED, MessageRequest::WITHDRAWN, MessageRequest::BLOCKED])
            ->whereNotNull('intro')
            ->where('updated_at', '<', now()->subDays((int) $retention['closed_request_intro_days']))
            ->update(['intro' => null]);
        // Placeholder rows for emails that matched nobody are not needed once expired.
        $ghosts = MessageRequest::query()->where('to_type', 'email')->where('status', MessageRequest::EXPIRED)
            ->where('updated_at', '<', now()->subDays((int) $retention['closed_request_intro_days']))->delete();
        $reports = MessageReport::query()->where('status', '!=', MessageReport::OPEN)
            ->where('updated_at', '<', now()->subDays((int) $retention['report_days']))->delete();

        $this->info("Expired {$expired} requests, {$readOnly} conversations now read-only, purged {$purgedIntros} intros, {$ghosts} placeholders, {$reports} old reports.");

        return self::SUCCESS;
    }
}
