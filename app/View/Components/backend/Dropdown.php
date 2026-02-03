<?php

namespace App\View\Components\Backend;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Dropdown extends Component
{
    public $options, $selected, $class, $isIcon;

    /**
     * Create a new component instance.
     *
     * @param array $options
     * @param string|null $selected
     * @param string|null $class
     * @param boolean|null $isIcon
     * @return void
     */
    public function __construct(array $options, $selected = null,  $class=null, $isIcon=false)
    {
        $this->class = $class;
        $this->options = $options;
        $this->selected = $selected;
        $this->isIcon = $isIcon; // True | False
    }



    public function render(): View|Closure|string
    {
        return view('backend.components.common.dropdown');
    }
}
