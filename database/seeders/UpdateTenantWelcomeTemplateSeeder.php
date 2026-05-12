<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EmailTemplate;

class UpdateTenantWelcomeTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $body = '
<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:20px;color:#333;">

    <h2 style="color:#dc3545;">Welcome to [[crm_name]]!</h2>

    <p>Dear <strong>[[tenant_name]]</strong>,</p>

    <p>You have been added as a tenant for the following property:</p>

    <table style="width:100%;border-collapse:collapse;margin:16px 0;">
        <tr><td style="padding:8px;border:1px solid #ddd;background:#f9f9f9;font-weight:bold;width:40%;">Property</td><td style="padding:8px;border:1px solid #ddd;">[[property_name]]</td></tr>
        <tr><td style="padding:8px;border:1px solid #ddd;background:#f9f9f9;font-weight:bold;">Address</td><td style="padding:8px;border:1px solid #ddd;">[[property_address]]</td></tr>
        <tr><td style="padding:8px;border:1px solid #ddd;background:#f9f9f9;font-weight:bold;">Move-in Date</td><td style="padding:8px;border:1px solid #ddd;">[[move_in_date]]</td></tr>
        <tr><td style="padding:8px;border:1px solid #ddd;background:#f9f9f9;font-weight:bold;">Rent</td><td style="padding:8px;border:1px solid #ddd;">[[rent]]</td></tr>
    </table>

    <p>Your account has been created. Here are your login credentials:</p>

    <table style="width:100%;border-collapse:collapse;margin:16px 0;">
        <tr><td style="padding:8px;border:1px solid #ddd;background:#f9f9f9;font-weight:bold;width:40%;">Login Email</td><td style="padding:8px;border:1px solid #ddd;">[[tenant_email]]</td></tr>
        <tr><td style="padding:8px;border:1px solid #ddd;background:#f9f9f9;font-weight:bold;">Password</td><td style="padding:8px;border:1px solid #ddd;font-family:monospace;font-size:16px;letter-spacing:2px;"><strong>[[tenant_password]]</strong></td></tr>
    </table>

    <p style="margin:24px 0;">
        <a href="[[login_url]]" style="background:#dc3545;color:#fff;padding:12px 24px;text-decoration:none;border-radius:4px;display:inline-block;font-weight:bold;">Login to Your Account</a>
    </p>

    <p style="margin:16px 0;">
        <a href="[[reset_link]]" style="background:#6c757d;color:#fff;padding:10px 20px;text-decoration:none;border-radius:4px;display:inline-block;">Need to Change Your Password?</a>
    </p>

    <hr style="border:none;border-top:1px solid #eee;margin:24px 0;">

    <p style="color:#666;font-size:13px;">If you have any questions, contact us at <a href="mailto:[[admin_email]]">[[admin_email]]</a>.</p>
    <p style="color:#666;font-size:13px;">Best regards,<br><strong>[[crm_name]] Team</strong></p>

</div>
        ';

        EmailTemplate::updateOrCreate(
            ['identifier' => 'tenant_welcome'],
            [
                'receiver'     => 'tenant',
                'email_type'   => 'Tenant Welcome',
                'subject'      => 'Welcome to [[crm_name]] — Your Tenant Account for [[property_name]]',
                'default_text' => trim($body),
                'status'       => 1,
            ]
        );

        $this->command->info('Tenant welcome template updated with password + change password button.');
    }
}
