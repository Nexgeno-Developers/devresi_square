<?php

namespace App\Enums;

enum CrmNotificationEvent: string
{
    case AppointmentInvited = 'appointment.invited';
    case AppointmentReminder = 'appointment.reminder';
    case AppointmentRescheduled = 'appointment.rescheduled';
    case AppointmentCancelled = 'appointment.cancelled';

    case OfferSubmitted = 'offer.submitted';
    case OfferAccepted = 'offer.accepted';
    case OfferRejected = 'offer.rejected';
    case OfferWithdrawn = 'offer.withdrawn';

    case TenancyActivated = 'tenancy.activated';
    case TenancyUpdated = 'tenancy.updated';
    case TenancyMoveInDue = 'tenancy.move_in_due';
    case TenancyDepositDue = 'tenancy.deposit_due';
    case TenancyRightToRentDue = 'tenancy.right_to_rent_due';
    case TenancyNoticeServed = 'tenancy.notice_served';

    case ComplianceExpiring = 'compliance.expiring';
    case ComplianceExpired = 'compliance.expired';
    case ComplianceRemediationDue = 'compliance.remediation_due';
    case ComplianceRenewed = 'compliance.renewed';

    case RepairReported = 'repair.reported';
    case RepairEscalated = 'repair.escalated';
    case RepairManagerAssigned = 'repair.manager_assigned';
    case RepairQuoteRequested = 'repair.quote_requested';
    case RepairQuoteSubmitted = 'repair.quote_submitted';
    case RepairContractorAssigned = 'repair.contractor_assigned';
    case RepairVisitScheduled = 'repair.visit_scheduled';
    case RepairStatusChanged = 'repair.status_changed';

    case FinanceInvoiceIssued = 'finance.invoice_issued';
    case FinanceInvoiceDue = 'finance.invoice_due';
    case FinanceInvoiceOverdue = 'finance.invoice_overdue';
    case FinancePaymentReceived = 'finance.payment_received';
    case FinanceInvoiceVoided = 'finance.invoice_voided';
    case FinanceStatementReady = 'finance.statement_ready';

    case DeliveryFailed = 'system.delivery_failed';
}
