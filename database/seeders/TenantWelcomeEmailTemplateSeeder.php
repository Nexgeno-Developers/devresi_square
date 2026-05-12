<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EmailTemplate;

class TenantWelcomeEmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        EmailTemplate::firstOrCreate(
            ['identifier' => 'tenant_welcome'],
            [
                'receiver'     => 'tenant',
                'email_type'   => 'Tenant Welcome',
                'subject'      => 'Welcome to [[property_name]] — Your Tenant Account Details',
                'default_text' => '
<p>Dear [[tenant_name]],</p>

<p>Welcome! You have been added as a tenant for the following property:</p>

<p>
    <strong>Property:</strong> [[property_name]]<br>
    <strong>Address:</strong> [[property_address]]<br>
    <strong>Move-in Date:</strong> [[move_in_date]]<br>
    <strong>Rent:</strong> [[rent]]<br>
</p>

<p>Your account has been created on <strong>[[crm_name]]</strong>. You can log in using the details below:</p>

<p>
    <strong>Email:</strong> [[tenant_email]]<br>
    <strong>Password:</strong> [[tenant_password]]<br>
</p>

<p>Please log in and change your password as soon as possible:</p>
<p><a href="[[login_url]]">[[login_url]]</a></p>

<p>If you have any questions, please contact us at [[admin_email]].</p>

<p>Best regards,<br>[[crm_name]] Team</p>
                ',
                'status' => 1,
            ]
        );

        $this->command->info('Tenant welcome email template created.');
    }
}
