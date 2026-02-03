<?php

namespace App\View\Components\Backend;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class UsersTabs extends Component
{
        public $tabs, $class;

    /**
     * Create a new component instance.
     *
     * @param array $tabs
     * @return void
     */
    public function __construct($tabs, $class = null)
    {
        $this->tabs = $tabs;
        $this->class = $class;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.backend.users-tabs');
    }
}
