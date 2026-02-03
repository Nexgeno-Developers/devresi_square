<?php

namespace App\View\Components\backend;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class MobileButton extends Component
{
    public $class, $name, $link, $iconName, $onClick;
    public function __construct($class=null, $name=null, $link = null, $iconName=null, $onClick = null)
    {
        $this->class = $class;
        $this->name = $name;
        $this->link = $link;  // hyperlink
        $this->iconName = $iconName;  // bootstrap icon name
        $this->onClick = $onClick;  // jquery click function
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('backend.components.common.mobile-button');
    }
}
