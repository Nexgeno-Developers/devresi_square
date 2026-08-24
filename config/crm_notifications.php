<?php

use App\Enums\CrmNotificationEvent;

$both = ['email', 'system'];

$events = [
        CrmNotificationEvent::AppointmentInvited->value => [
            'category' => 'appointments', 'priority' => 'normal', 'channels' => $both,
            'subject' => 'Appointment invitation: [[appointment_title]]',
            'message' => 'You have been invited to [[appointment_title]] on [[appointment_at]].',
        ],
        CrmNotificationEvent::AppointmentReminder->value => [
            'category' => 'appointments', 'priority' => 'normal', 'channels' => $both,
            'subject' => 'Appointment reminder: [[appointment_title]]',
            'message' => '[[appointment_title]] starts on [[appointment_at]].',
        ],
        CrmNotificationEvent::AppointmentRescheduled->value => [
            'category' => 'appointments', 'priority' => 'high', 'channels' => $both,
            'subject' => 'Appointment rescheduled: [[appointment_title]]',
            'message' => '[[appointment_title]] has been rescheduled to [[appointment_at]].',
        ],
        CrmNotificationEvent::AppointmentCancelled->value => [
            'category' => 'appointments', 'priority' => 'high', 'channels' => $both,
            'subject' => 'Appointment cancelled: [[appointment_title]]',
            'message' => '[[appointment_title]] scheduled for [[appointment_at]] has been cancelled.',
        ],
        CrmNotificationEvent::OfferSubmitted->value => [
            'category' => 'offers', 'priority' => 'normal', 'channels' => $both,
            'subject' => 'New offer for [[property_address]]',
            'message' => 'A new offer of [[offer_amount]] has been submitted for [[property_address]].',
        ],
        CrmNotificationEvent::OfferAccepted->value => [
            'category' => 'offers', 'priority' => 'high', 'channels' => $both,
            'subject' => 'Offer accepted for [[property_address]]',
            'message' => 'The offer for [[property_address]] has been accepted.',
        ],
        CrmNotificationEvent::OfferRejected->value => [
            'category' => 'offers', 'priority' => 'normal', 'channels' => $both,
            'subject' => 'Offer update for [[property_address]]',
            'message' => 'The offer for [[property_address]] was not successful.',
        ],
        CrmNotificationEvent::OfferWithdrawn->value => [
            'category' => 'offers', 'priority' => 'normal', 'channels' => $both,
            'subject' => 'Offer withdrawn for [[property_address]]',
            'message' => 'The offer for [[property_address]] has been withdrawn.',
        ],
        CrmNotificationEvent::TenancyActivated->value => [
            'category' => 'tenancies', 'priority' => 'high', 'channels' => $both, 'locked_channels' => ['email'],
            'subject' => 'Your tenancy at [[property_address]]',
            'message' => 'Your tenancy at [[property_address]] starts on [[move_in_date]].',
        ],
        CrmNotificationEvent::TenancyUpdated->value => [
            'category' => 'tenancies', 'priority' => 'normal', 'channels' => $both,
            'subject' => 'Tenancy updated: [[property_address]]',
            'message' => 'Your tenancy details for [[property_address]] have been updated.',
        ],
        CrmNotificationEvent::TenancyMoveInDue->value => [
            'category' => 'tenancies', 'priority' => 'high', 'channels' => $both,
            'subject' => 'Move-in approaching: [[property_address]]',
            'message' => 'The tenancy at [[property_address]] starts in [[days_text]].',
        ],
        CrmNotificationEvent::TenancyDepositDue->value => [
            'category' => 'compliance', 'priority' => 'high', 'channels' => $both,
            'subject' => 'Deposit protection deadline: [[property_address]]',
            'message' => 'Deposit protection and prescribed information are [[due_text]] for [[property_address]].',
        ],
        CrmNotificationEvent::TenancyRightToRentDue->value => [
            'category' => 'compliance', 'priority' => 'high', 'channels' => $both,
            'subject' => 'Right to rent follow-up due',
            'message' => 'A recorded right to rent follow-up check is [[due_text]]. Open the tenancy record for details.',
        ],
        CrmNotificationEvent::TenancyNoticeServed->value => [
            'category' => 'tenancies', 'priority' => 'high', 'channels' => $both, 'locked_channels' => ['email'],
            'subject' => 'Tenancy notice: [[notice_type]]',
            'message' => 'A [[notice_type]] notice has been recorded for [[property_address]].',
        ],
        CrmNotificationEvent::ComplianceExpiring->value => [
            'category' => 'compliance', 'priority' => 'high', 'channels' => $both,
            'subject' => '[[compliance_type]] expires soon',
            'message' => '[[compliance_type]] for [[property_address]] expires on [[due_date]].',
        ],
        CrmNotificationEvent::ComplianceExpired->value => [
            'category' => 'compliance', 'priority' => 'critical', 'channels' => $both,
            'subject' => 'Expired: [[compliance_type]]',
            'message' => '[[compliance_type]] for [[property_address]] expired on [[due_date]].',
        ],
        CrmNotificationEvent::ComplianceRemediationDue->value => [
            'category' => 'compliance', 'priority' => 'critical', 'channels' => $both,
            'subject' => 'Compliance remediation [[due_text]]',
            'message' => 'Remediation for [[compliance_type]] at [[property_address]] is [[due_text]].',
        ],
        CrmNotificationEvent::ComplianceRenewed->value => [
            'category' => 'compliance', 'priority' => 'normal', 'channels' => $both, 'locked_channels' => ['email'],
            'subject' => '[[compliance_type]] renewed',
            'message' => 'A renewed [[compliance_type]] has been recorded for [[property_address]].',
        ],
        CrmNotificationEvent::RepairReported->value => [
            'category' => 'repairs', 'priority' => 'high', 'channels' => $both,
            'subject' => 'Repair reported: [[repair_reference]]',
            'message' => 'A [[repair_priority]] priority repair has been reported at [[property_address]].',
        ],
        CrmNotificationEvent::RepairEscalated->value => [
            'category' => 'repairs', 'priority' => 'critical', 'channels' => $both,
            'subject' => 'Unacknowledged repair: [[repair_reference]]',
            'message' => 'Repair [[repair_reference]] remains unacknowledged and requires attention.',
        ],
        CrmNotificationEvent::RepairManagerAssigned->value => [
            'category' => 'repairs', 'priority' => 'high', 'channels' => $both,
            'subject' => 'Repair acknowledged: [[repair_reference]]',
            'message' => 'A property manager has been assigned to repair [[repair_reference]].',
        ],
        CrmNotificationEvent::RepairQuoteRequested->value => [
            'category' => 'repairs', 'priority' => 'normal', 'channels' => $both,
            'subject' => 'Quote requested: [[repair_reference]]',
            'message' => 'You have been invited to quote for repair [[repair_reference]].',
        ],
        CrmNotificationEvent::RepairQuoteSubmitted->value => [
            'category' => 'repairs', 'priority' => 'high', 'channels' => $both,
            'subject' => 'Quote received: [[repair_reference]]',
            'message' => 'A contractor quote has been submitted for repair [[repair_reference]].',
        ],
        CrmNotificationEvent::RepairContractorAssigned->value => [
            'category' => 'repairs', 'priority' => 'high', 'channels' => $both,
            'subject' => 'Repair work assigned: [[repair_reference]]',
            'message' => 'Repair [[repair_reference]] has been assigned to you.',
        ],
        CrmNotificationEvent::RepairVisitScheduled->value => [
            'category' => 'repairs', 'priority' => 'high', 'channels' => $both,
            'subject' => 'Repair visit scheduled: [[repair_reference]]',
            'message' => 'A repair visit for [[repair_reference]] is scheduled for [[appointment_at]].',
        ],
        CrmNotificationEvent::RepairStatusChanged->value => [
            'category' => 'repairs', 'priority' => 'normal', 'channels' => $both,
            'subject' => 'Repair update: [[repair_reference]]',
            'message' => 'Repair [[repair_reference]] changed from [[old_status]] to [[new_status]].',
        ],
        CrmNotificationEvent::FinanceInvoiceIssued->value => [
            'category' => 'finance', 'priority' => 'high', 'channels' => $both, 'locked_channels' => ['email'],
            'subject' => 'Invoice [[invoice_number]]',
            'message' => 'Invoice [[invoice_number]] for [[invoice_amount]] is due on [[due_date]].',
        ],
        CrmNotificationEvent::FinanceInvoiceDue->value => [
            'category' => 'finance', 'priority' => 'high', 'channels' => $both,
            'subject' => 'Invoice [[invoice_number]] is due soon',
            'message' => 'Invoice [[invoice_number]] for [[invoice_amount]] is due on [[due_date]].',
        ],
        CrmNotificationEvent::FinanceInvoiceOverdue->value => [
            'category' => 'finance', 'priority' => 'critical', 'channels' => $both,
            'subject' => 'Invoice [[invoice_number]] is overdue',
            'message' => 'Invoice [[invoice_number]] is overdue with [[invoice_amount]] outstanding.',
        ],
        CrmNotificationEvent::FinancePaymentReceived->value => [
            'category' => 'finance', 'priority' => 'normal', 'channels' => $both, 'locked_channels' => ['email'],
            'subject' => 'Payment received: [[payment_amount]]',
            'message' => 'We received [[payment_amount]] for [[invoice_number]].',
        ],
        CrmNotificationEvent::FinanceInvoiceVoided->value => [
            'category' => 'finance', 'priority' => 'high', 'channels' => $both,
            'subject' => 'Invoice [[invoice_number]] voided',
            'message' => 'Invoice [[invoice_number]] has been voided.',
        ],
        CrmNotificationEvent::FinanceStatementReady->value => [
            'category' => 'finance', 'priority' => 'normal', 'channels' => $both,
            'subject' => 'Your property statement is ready',
            'message' => 'A new statement for [[property_address]] is ready to view.',
        ],
        CrmNotificationEvent::DeliveryFailed->value => [
            'category' => 'system', 'priority' => 'critical', 'channels' => ['system'], 'locked_channels' => ['system'],
            'subject' => 'Notification delivery failed',
            'message' => 'A notification could not be delivered after all retry attempts.',
        ],
];

$permittedPlaceholders = [
    'appointment_title', 'appointment_at', 'property_address', 'offer_amount', 'move_in_date',
    'days_text', 'due_text', 'due_date', 'notice_type', 'compliance_type', 'repair_reference',
    'repair_priority', 'old_status', 'new_status', 'invoice_id', 'invoice_number', 'invoice_amount',
    'payment_amount', 'action_url', 'customer_name', 'customer_email', 'invoice_pdf_url',
    'recipient_name', 'recipient_email', 'brand_name', 'currency',
];

foreach ($events as &$definition) {
    $definition['placeholders'] ??= $permittedPlaceholders;
    $definition['recipient_resolver'] ??= 'subject';
    $definition['deep_link'] ??= 'context.action_url';
}
unset($definition);

return [
    'enabled' => env('CRM_NOTIFICATIONS_ENABLED', true),
    'default_timezone' => 'Europe/London',
    'max_attempts' => 3,
    'groups' => [
        'appointment' => env('CRM_NOTIFICATIONS_APPOINTMENTS_ENABLED', true),
        'offer' => env('CRM_NOTIFICATIONS_OFFERS_ENABLED', true),
        'repair' => env('CRM_NOTIFICATIONS_REPAIRS_ENABLED', true),
        'tenancy' => env('CRM_NOTIFICATIONS_TENANCIES_ENABLED', true),
        'compliance' => env('CRM_NOTIFICATIONS_COMPLIANCE_ENABLED', true),
        'finance' => env('CRM_NOTIFICATIONS_FINANCE_ENABLED', true),
        'system' => true,
    ],
    'legacy_aliases' => [
        'appointment.invited' => ['inspection_schedule_to_tenant'],
        'tenancy.activated' => ['lease_created_to_tenant', 'lease_created_to_owner'],
        'tenancy.notice_served' => ['notice_to_vacate_to_tenant'],
        'repair.reported' => ['maintenance_request_to_agent'],
        'repair.contractor_assigned' => ['maintenance_assigned_to_contractor'],
        'finance.invoice_due' => ['rent_due_reminder_to_tenant'],
        'finance.payment_received' => ['payment_received_to_owner'],
        'finance.statement_ready' => ['monthly_statement_to_owner'],
    ],
    'events' => $events,
];
