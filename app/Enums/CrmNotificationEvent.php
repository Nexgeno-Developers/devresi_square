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
    case TenancyDetailsCorrectionRequested = 'tenancy.details_correction_requested';
    case TenancyDetailsCorrectionApproved = 'tenancy.details_correction_approved';
    case TenancyDetailsCorrectionRejected = 'tenancy.details_correction_rejected';

    case ComplianceExpiring = 'compliance.expiring';
    case ComplianceExpired = 'compliance.expired';
    case ComplianceRemediationDue = 'compliance.remediation_due';
    case ComplianceRenewed = 'compliance.renewed';
    case DocumentShared = 'document.shared';

    case RepairReported = 'repair.reported';
    case RepairEscalated = 'repair.escalated';
    case RepairManagerAssigned = 'repair.manager_assigned';
    case RepairQuoteRequested = 'repair.quote_requested';
    case RepairQuoteSubmitted = 'repair.quote_submitted';
    case RepairContractorAssigned = 'repair.contractor_assigned';
    case RepairJobInvited = 'repair.job_invited';
    case RepairJobOfferSent = 'repair.job_offer_sent';
    case RepairJobOfferWithdrawn = 'repair.job_offer_withdrawn';
    case RepairJobInviteDeclined = 'repair.job_invite_declined';
    case RepairJobAccepted = 'repair.job_accepted';
    case RepairJobNotSelected = 'repair.job_not_selected';
    case RepairJobCancelled = 'repair.job_cancelled';
    case RepairContractorComplianceBlocked = 'repair.contractor_compliance_blocked';
    case RepairJobRequestSent = 'repair.job_request_sent';
    case RepairJobOfferDigest = 'repair.job_offer_digest';
    case RepairJobOfferReminder = 'repair.job_offer_reminder';
    case RepairJobInvitesExpired = 'repair.job_invites_expired';
    case RepairJobDetailsChanged = 'repair.job_details_changed';
    case RepairJobReconfirm = 'repair.job_reconfirm';
    case RepairJobProgress = 'repair.job_progress';
    case RepairJobCompleted = 'repair.job_completed';
    case RepairJobDisputed = 'repair.job_disputed';
    case RepairJobMessage = 'repair.job_message';
    case RepairJobVariation = 'repair.job_variation';
    case RepairJobRescheduled = 'repair.job_rescheduled';
    case RepairJobInvoiced = 'repair.job_invoiced';
    case RepairJobRated = 'repair.job_rated';
    case RepairVisitScheduled = 'repair.visit_scheduled';
    case RepairStatusChanged = 'repair.status_changed';

    case FinanceInvoiceIssued = 'finance.invoice_issued';
    case FinanceInvoiceDue = 'finance.invoice_due';
    case FinanceInvoiceOverdue = 'finance.invoice_overdue';
    case FinancePaymentReceived = 'finance.payment_received';
    case FinancePaymentFailed = 'finance.payment_failed';
    case FinanceInvoiceVoided = 'finance.invoice_voided';
    case FinanceStatementReady = 'finance.statement_ready';

    case DeliveryFailed = 'system.delivery_failed';
}
