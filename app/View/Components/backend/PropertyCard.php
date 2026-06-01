<?php

namespace App\View\Components\Backend;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class PropertyCard extends Component
{

    public $class, $propertyName, $bed, $bath, $floor, $living, $price, $lettingPrice, $type, $available, $cardStyle, $propertyId, $weeklyLettingPrice;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($class = null, $propertyName, $bed, $bath, $floor, $living, $price, $lettingPrice = null, $type, $available, $cardStyle = 'horizontal', $propertyId)
    {
        $this->class = $class;
        $this->propertyName = $propertyName;
        $this->bed = $bed;
        $this->bath = $bath;
        $this->floor = $floor;
        $this->living = $living;
        $this->price = $price;
        $this->lettingPrice = $lettingPrice;
        $this->type = $type;
        $this->available = $available;
        $this->cardStyle = $cardStyle;  // Vertical | Horizontal
        $this->propertyId = $propertyId;

        // Weekly rent calculation
        if ($lettingPrice) {
            $this->weeklyLettingPrice = round(($lettingPrice * 12) / 52, 2);
        } else {
            $this->weeklyLettingPrice = null;
        }
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('backend.components.property-h-card');
    }
}
