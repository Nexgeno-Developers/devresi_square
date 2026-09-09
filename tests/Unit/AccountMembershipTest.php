<?php

namespace Tests\Unit;

use App\Support\AccountMembership;
use App\Support\AccountType;
use Tests\TestCase;

class AccountMembershipTest extends TestCase
{
    public function test_workspace_admin_types_are_owner_and_admin(): void
    {
        $this->assertTrue(AccountMembership::isWorkspaceAdmin(AccountMembership::OWNER));
        $this->assertTrue(AccountMembership::isWorkspaceAdmin(AccountMembership::ADMIN));
        $this->assertFalse(AccountMembership::isWorkspaceAdmin(AccountMembership::TENANT));
        $this->assertFalse(AccountMembership::isWorkspaceAdmin(AccountMembership::LANDLORD_CONTACT));
    }

    public function test_agency_landlord_contact_is_a_portal_type(): void
    {
        $this->assertTrue(AccountMembership::isPortalType(AccountMembership::LANDLORD_CONTACT));
        $this->assertTrue(AccountMembership::isPortalType(AccountMembership::TENANT));
        $this->assertFalse(AccountMembership::isPortalType(AccountMembership::OWNER));
        $this->assertFalse(AccountMembership::isPortalType(AccountMembership::ADMIN));
    }

    public function test_paying_landlord_is_workspace_operator_not_portal(): void
    {
        $this->assertTrue(AccountMembership::isLandlordWorkspaceOperator(
            AccountMembership::OWNER,
            AccountType::LANDLORD
        ));
        $this->assertTrue(AccountMembership::isLandlordWorkspaceOperator(
            AccountMembership::ADMIN,
            AccountType::LANDLORD
        ));
        $this->assertFalse(AccountMembership::isLandlordWorkspaceOperator(
            AccountMembership::LANDLORD_CONTACT,
            AccountType::ESTATE_AGENT_COMPANY
        ));
        $this->assertFalse(AccountMembership::isLandlordWorkspaceOperator(
            AccountMembership::TENANT,
            AccountType::LANDLORD
        ));
        $this->assertFalse(AccountMembership::isLandlordWorkspaceOperator(
            AccountMembership::OWNER,
            AccountType::ESTATE_AGENT_COMPANY
        ));
    }
}
