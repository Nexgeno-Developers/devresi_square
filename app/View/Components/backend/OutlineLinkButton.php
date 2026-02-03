<?php

namespace App\View\Components\Backend;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class OutlineLinkButton extends Component
{
    public $class, $name, $link, $onClick;
    public function __construct( $class, $name, $link, $onClick = null)
    {
        $this->class = $class;
        $this->name = $name;
        $this->link = $link;
        $this->onClick = $onClick;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('backend.components.common.outline-link-button');
    }
}
