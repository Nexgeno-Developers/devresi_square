<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Collection;
use Tests\TestCase;

class LinkedPropertiesViewTest extends TestCase
{
    public function test_linked_property_displays_property_name_address_and_details_link(): void
    {
        $user = new User(['name' => 'Owner']);
        $user->setRelation('tenancies', new Collection());

        $property = new Property([
            'prop_name' => 'Oak House',
            'prop_ref_no' => 'RES-100',
            'line_1' => '12 High Street',
            'city' => 'London',
            'postcode' => 'SW1A 1AA',
            'specific_property_type' => 'Flat',
        ]);
        $property->id = 42;

        $view = $this->view('backend.users.tabs.linked', [
            'userId' => 7,
            'user' => $user,
            'properties' => collect([$property]),
        ]);

        $view->assertSee('Oak House');
        $view->assertSee('12 High Street, London, SW1A 1AA');
        $view->assertSee('Reference: RES-100');
        $view->assertSee('View details');
        $view->assertSee('/admin/properties?property_id=42&amp;tabname=Property', false);
    }

    public function test_linked_property_uses_first_address_line_when_name_is_missing(): void
    {
        $user = new User(['name' => 'Owner']);
        $user->setRelation('tenancies', new Collection());

        $property = new Property([
            'prop_name' => null,
            'prop_ref_no' => 'RES-200',
            'line_1' => 'Flat 6',
            'line_2' => 'Discovery Dock Apartments East',
            'city' => 'London',
            'county' => 'London',
            'postcode' => 'E14 9RU',
        ]);
        $property->id = 43;
        $property->setRelation('countryRelation', null);

        $view = $this->view('backend.users.tabs.linked', [
            'userId' => 7,
            'user' => $user,
            'properties' => collect([$property]),
        ]);

        $view->assertSee('Flat 6');
        $view->assertSee('Discovery Dock Apartments East, London, E14 9RU');
        $view->assertDontSee('Unnamed property');
        $view->assertDontSee('London, London');
    }
}
