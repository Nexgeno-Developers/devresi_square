<?php

namespace App\Services\Launch;

use App\Models\EmailTemplate;
use App\Models\Plan;

/**
 * Plans and email templates for a fresh environment.
 * Does not create users.
 */
class LaunchContent
{
    public function seed(): void
    {
        Plan::query()->updateOrCreate(
            ['code' => 'landlord_basic'],
            [
                'name' => 'Landlord Basic',
                'target_account_type' => 'landlord',
                'description' => 'Starter plan for independent landlords.',
                'monthly_price_minor' => 2900,
                'annual_price_minor' => 29000,
                'currency' => 'GBP',
                'trial_days' => 7,
                'property_limit' => 3,
                'branch_limit' => 0,
                'staff_limit' => 0,
                'property_manager_limit' => 0,
                'allow_company_profile' => false,
                'allow_invoice_branding' => false,
                'allow_roles_permissions' => false,
                'allow_contact_login' => true,
                'is_active' => true,
                'sort_order' => 10,
            ]
        );

        foreach ($this->templates() as $identifier => $template) {
            $existing = EmailTemplate::query()
                ->whereNull('account_id')
                ->where('identifier', $identifier)
                ->first();

            $attributes = [
                'receiver' => $template['receiver'],
                'email_type' => $template['email_type'],
                'subject' => $template['subject'],
                'default_text' => $template['default_text'],
                'status' => 1,
            ];

            if ($existing) {
                $existing->fill($attributes);
                if ($existing->isDirty()) {
                    $existing->save();
                }
                continue;
            }

            EmailTemplate::query()->create($attributes + [
                'account_id' => null,
                'identifier' => $identifier,
            ]);
        }
    }

    /**
     * @return array<string, array{receiver: string, email_type: string, subject: string, default_text: string}>
     */
    public function templates(): array
    {
        return [
            'signup_otp' => [
                'receiver' => 'all',
                'email_type' => 'Signup OTP',
                'subject' => 'Your [[app_name]] verification code',
                'default_text' => '<p>Hi [[first_name]],</p><p>Your verification code for <strong>[[app_name]]</strong> is:</p><p style="font-size:28px;font-weight:bold;letter-spacing:6px;">[[otp]]</p><p>This code expires in 2 minutes. If you did not request it, ignore this email.</p>',
            ],
            'tenant_welcome' => [
                'receiver' => 'tenant',
                'email_type' => 'Tenant Welcome',
                'subject' => 'Welcome to [[property_name]] — Your Tenant Account Details',
                'default_text' => '<p>Dear [[tenant_name]],</p><p>Welcome! You have been added as a tenant for the following property:</p><p><strong>Property:</strong> [[property_name]]<br><strong>Address:</strong> [[property_address]]<br><strong>Move-in Date:</strong> [[move_in_date]]<br><strong>Rent:</strong> [[rent]]</p><p>Your account has been created on <strong>[[crm_name]]</strong>.</p><p><strong>Email:</strong> [[tenant_email]]</p><p><a href="[[login_url]]">Log in</a></p><p>Best regards,<br>[[crm_name]] Team</p>',
            ],
            'email_change_confirmation' => [
                'receiver' => 'all',
                'email_type' => 'Email change confirmation',
                'subject' => 'Confirm your new email',
                'default_text' => '<p>Hi [[tenant_name]],</p><p>Confirm this email address to use it for your ResiSquare login. The link works for 72 hours and only once. Until you open it, sign in with your current email.</p><p><a href="[[confirm_link]]">Confirm email</a></p><p>Best regards,<br>The ResiSquare Team</p>',
            ],
            'contractor_claim' => [
                'receiver' => 'contractor',
                'email_type' => 'Contractor claim',
                'subject' => 'Claim your ResiSquare contractor profile',
                'default_text' => '<p>Hi [[first_name]],</p><p>Use the link below to claim your contractor profile. The link works for 72 hours and only once.</p><p><a href="[[claim_link]]">Claim profile</a></p><p>Best regards,<br>The ResiSquare Team</p>',
            ],
            'repair_invited' => [
                'receiver' => 'contractor',
                'email_type' => 'Repair invited',
                'subject' => 'Repair invitation',
                'default_text' => '<p>You have been invited to quote for a repair. The job page shows the town and outward postcode until the landlord accepts you.</p>',
            ],
            'inspection_accepted' => [
                'receiver' => 'contractor',
                'email_type' => 'Inspection accepted',
                'subject' => 'Inspection accepted',
                'default_text' => '<p>The landlord accepted your inspection visit. The address is now on the job. Inspect, then send a firm price.</p>',
            ],
            'repair_awarded' => [
                'receiver' => 'contractor',
                'email_type' => 'Repair awarded',
                'subject' => 'Repair awarded',
                'default_text' => '<p>The landlord awarded you this repair. The full address and access notes are on the job.</p>',
            ],
            'repair_not_selected' => [
                'receiver' => 'contractor',
                'email_type' => 'Repair not selected',
                'subject' => 'Repair update',
                'default_text' => '<p>The landlord chose another contractor for this repair. Thank you for the quote.</p>',
            ],
            'rent_invoice_issued' => [
                'receiver' => 'tenant',
                'email_type' => 'Rent invoice issued',
                'subject' => 'Rent invoice [[invoice_number]]',
                'default_text' => '<p>Invoice [[invoice_number]] for [[invoice_amount]] is due on [[due_date]].</p><p>Property: [[property_address]]</p>',
            ],
            'rent_payment_received' => [
                'receiver' => 'all',
                'email_type' => 'Rent received',
                'subject' => 'Rent received: [[payment_amount]]',
                'default_text' => '<p>A payment of [[payment_amount]] has been received and recorded against [[invoice_number]].</p>',
            ],
            'compliance_expiry' => [
                'receiver' => 'landlord',
                'email_type' => 'Compliance expiry',
                'subject' => '[[compliance_type]] due for renewal',
                'default_text' => '<p>The compliance record for [[compliance_type]] at [[property_address]] is due for renewal on [[due_date]]. Keep the portfolio compliant by scheduling an inspection or uploading an updated certificate.</p>',
            ],
            'team_invite' => [
                'receiver' => 'all',
                'email_type' => 'Team invite',
                'subject' => 'You are invited to [[workspace_name]] on ResiSquare',
                'default_text' => '<p>Hi [[first_name]],</p><p><strong>[[inviter_name]]</strong> has invited you to join the <strong>[[workspace_name]]</strong> workspace on ResiSquare as a <strong>[[role_name]]</strong>.</p><p>Click the link below to accept your invitation and set up your password:</p><p><a href="[[invitation_url]]">Accept Invitation</a></p><p>This invitation link will expire in 7 days.</p><p>Best regards,<br>The ResiSquare Team</p>',
            ],
        ];
    }
}
