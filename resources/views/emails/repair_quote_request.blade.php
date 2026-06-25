<p>Hello {{ $contractor->name }},</p>

<p>You have been invited to submit a quote for repair issue <strong>{{ $repairIssue->reference_number }}</strong>.</p>

<p>
    <strong>Property:</strong>
    {{ getPropertyDetails($repairIssue->property_id, ['prop_name', 'line_1', 'city', 'postcode']) }}
</p>

<p><strong>Issue:</strong> {{ getRepairCategoryDetails($repairIssue->repair_category_id) }}</p>

<p>Please review the attached scope of work and submit your quote using the secure link below:</p>

<p><a href="{{ $quoteUrl }}">{{ $quoteUrl }}</a></p>

<p>Thank you.</p>
