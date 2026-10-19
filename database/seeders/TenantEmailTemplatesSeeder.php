<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EmailTemplate;

class TenantEmailTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        // ── Template 1: Sent when tenant user is created via Contacts ──
        $accountBody = '
<p>Hi [[tenant_name]],</p>
<p>You\'ve been invited to ResiSquare by your landlord for:</p>
<p><strong>[[property_address]]</strong></p>
<p>Your tenant account gives you one place to access the information and services connected to your home.</p>
<p>You can use ResiSquare to:</p>
<ul>
    <li>View your tenancy details</li>
    <li>See rent and payment information</li>
    <li>Access documents</li>
    <li>Report repairs</li>
    <li>Track updates linked to your property</li>
</ul>
<p>To activate your account, create your password below.</p>
<p><a href="[[reset_link]]"><strong>Set Your Password</strong></a></p>
<p>This invitation was sent to <strong>[[tenant_email]]</strong>.</p>
<p>If you were not expecting this invitation, you can safely ignore this email.</p>
<p>The ResiSquare Team</p>
<p><strong>One Workspace for Your Entire Portfolio</strong></p>';

        EmailTemplate::updateOrCreate(
            ['identifier' => 'tenant_account_created'],
            [
                'receiver'     => 'tenant',
                'email_type'   => 'Tenant invitation',
                'subject'      => "You've been invited to ResiSquare",
                'default_text' => trim($accountBody),
                'status'       => 1,
            ]
        );

        // ── Template 2: Sent when tenancy is created in Properties ──
        $tenancyBody = '
<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:20px;color:#333;">

    <h2 style="color:#dc3545;">Your Tenancy Details — [[crm_name]]</h2>

    <p>Dear <strong>[[tenant_name]]</strong>,</p>

    <p>You have been added as a tenant for the following property:</p>

    <table style="width:100%;border-collapse:collapse;margin:16px 0;">
        <tr>
            <td style="padding:8px;border:1px solid #ddd;background:#f9f9f9;font-weight:bold;width:40%;">Property</td>
            <td style="padding:8px;border:1px solid #ddd;">[[property_name]]</td>
        </tr>
        <tr>
            <td style="padding:8px;border:1px solid #ddd;background:#f9f9f9;font-weight:bold;">Address</td>
            <td style="padding:8px;border:1px solid #ddd;">[[property_address]]</td>
        </tr>
        <tr>
            <td style="padding:8px;border:1px solid #ddd;background:#f9f9f9;font-weight:bold;">Move-in Date</td>
            <td style="padding:8px;border:1px solid #ddd;">[[move_in_date]]</td>
        </tr>
        <tr>
            <td style="padding:8px;border:1px solid #ddd;background:#f9f9f9;font-weight:bold;">Rent</td>
            <td style="padding:8px;border:1px solid #ddd;">[[rent]]</td>
        </tr>
    </table>

    <p>Log in to your account to view your tenancy details:</p>

    <p style="margin:24px 0;">
        <a href="[[login_url]]" style="background:#dc3545;color:#fff;padding:12px 24px;text-decoration:none;border-radius:4px;display:inline-block;font-weight:bold;">Login to Your Account</a>
    </p>

    <hr style="border:none;border-top:1px solid #eee;margin:24px 0;">
    <p style="color:#666;font-size:13px;">If you have any questions, contact us at <a href="mailto:[[admin_email]]">[[admin_email]]</a>.</p>
    <p style="color:#666;font-size:13px;">Best regards,<br><strong>[[crm_name]] Team</strong></p>

</div>';

        EmailTemplate::updateOrCreate(
            ['identifier' => 'tenant_welcome'],
            [
                'receiver'     => 'tenant',
                'email_type'   => 'Tenant Welcome',
                'subject'      => 'Your Tenancy at [[property_name]] — [[crm_name]]',
                'default_text' => trim($tenancyBody),
                'status'       => 1,
            ]
        );

        $this->command->info('Both tenant email templates created/updated.');
    }
}
