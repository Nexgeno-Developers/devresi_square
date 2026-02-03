<?php

namespace App\View\Components\Backend;
use Illuminate\View\Component;

class PropertiesTabs extends Component
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
     *
     * @return \Illuminate\View\View|\Closure|string
     */
    public function render()
    {
        return view('backend.components.tabs');
    }
}
