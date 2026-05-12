<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EmailTemplate;

class FixPasswordResetTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $body = '<p>Hello [[user_name]],</p>'
            . '<p>We received a request to reset your password for your <strong>[[crm_name]]</strong> account.</p>'
            . '<p>Click the button below to set your password:</p>'
            . '<p><a href="[[reset_link]]" style="background:#dc3545;color:#fff;padding:10px 20px;text-decoration:none;border-radius:4px;">Set Your Password</a></p>'
            . '<p>Or copy and paste this link into your browser:</p>'
            . '<p>[[reset_link]]</p>'
            . '<p>If you did not request this, you can safely ignore this email.</p>'
            . '<p>Regards,<br>The [[crm_name]] Team</p>';

        EmailTemplate::where('identifier', 'password_reset')->update([
            'subject'      => 'Set Your Password — [[crm_name]]',
            'default_text' => $body,
        ]);

        $this->command->info('Password reset template fixed.');
    }
}
