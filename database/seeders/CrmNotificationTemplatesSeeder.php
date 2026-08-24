<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

class CrmNotificationTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('crm_notifications.events', []) as $eventKey => $definition) {
            $legacy = EmailTemplate::query()->whereNull('account_id')
                ->whereIn('identifier', config('crm_notifications.legacy_aliases', [])[$eventKey] ?? [])
                ->where('status', true)->first();
            $template = EmailTemplate::query()->firstOrCreate(
                ['account_id' => null, 'identifier' => $eventKey],
                [
                    'receiver' => 'all',
                    'email_type' => ucwords(str_replace(['.', '_'], ' ', $eventKey)),
                    'subject' => $legacy?->subject ?: $definition['subject'],
                    'default_text' => $legacy?->default_text ?: $this->layout($definition['message']),
                    'status' => true,
                ]
            );
            if ($template->receiver === 'Recipient') {
                $template->update(['receiver' => 'all']);
            }
        }
    }

    private function layout(string $message): string
    {
        return '<p>'.$message.'</p><p><a href="[[action_url]]">Open in ResiSquare</a></p>';
    }
}
