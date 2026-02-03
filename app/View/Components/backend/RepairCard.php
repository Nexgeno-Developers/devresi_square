<?php

namespace App\View\Components\Backend;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class RepairCard extends Component
{
    public $repair;
    
    /**
     * Create a new component instance.
     *
     * @return void
     */

    public function __construct($repair)
    {
        $this->repair = $repair;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.backend.repair-card');
    }
}
