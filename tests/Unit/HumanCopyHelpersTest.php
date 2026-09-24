<?php

namespace Tests\Unit;

use App\Models\Property;
use App\Models\RentInvoice;
use App\Models\User;
use Carbon\Carbon;
use Tests\TestCase;

class HumanCopyHelpersTest extends TestCase
{
    public function test_money_date_month_and_person_helpers(): void
    {
        $this->assertSame('£1,100.00', rs_money(1100));
        $this->assertSame('£12.50', rs_money(1250, true));
        $this->assertSame('1 Jan 2026', rs_date(Carbon::parse('2026-01-01')));
        $this->assertSame('January 2026', rs_month(Carbon::parse('2026-01-15')));

        $user = new User(['name' => 'Tina Tenant', 'email' => 'tina@example.com']);
        $this->assertSame('Tina Tenant', rs_person($user));
        $this->assertSame('Someone', rs_person(null));
    }

    public function test_property_and_document_titles_ban_system_ids(): void
    {
        $property = new Property([
            'prop_ref_no' => 'RESISQP0000001',
            'line_1' => 'Flat 12',
            'postcode' => 'E14 9RU',
            'prop_name' => '',
        ]);
        $this->assertStringNotContainsString('RESISQ', rs_property_title($property));
        $this->assertStringContainsString('Flat 12', rs_property_title($property));

        $this->assertSame('Gas certificate', rs_document_title('Untitled', 'Gas certificate'));
        $this->assertSame('How to Rent', rs_document_title('How to Rent', 'Guide'));
    }

    public function test_rent_title_prefers_month_over_invoice_no(): void
    {
        $invoice = new RentInvoice([
            'invoice_no' => 'RENT-0003',
            'period_start' => Carbon::parse('2026-09-01'),
            'period_end' => Carbon::parse('2026-09-30'),
        ]);
        $this->assertSame('September 2026 rent', rs_rent_title($invoice));
        $this->assertStringNotContainsString('RENT-', rs_rent_title($invoice));
    }
}
