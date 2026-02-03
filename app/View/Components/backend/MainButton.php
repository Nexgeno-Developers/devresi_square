<?php

namespace App\View\Components\Backend;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class MainButton extends Component
{
    public $class, $name, $type, $size, $isOutline, $isLinkBtn, $link, $onClick;
    public function __construct($class=null, $name, $type = 'secondary', $size='sm', $isOutline=false, $isLinkBtn = false, $link = null, $onClick = null)
    {
        $this->class = $class;
        $this->name = $name;
        $this->type = $type;  // types: primary | secondary
        $this->size = $size;  // sizes: sm | lg 
        $this->isOutline = $isOutline;  // true | false
        $this->isLinkBtn = $isLinkBtn;  // true | false
        $this->link = $link;  // hyperlink
        $this->onClick = $onClick;  // jquery click function
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('backend.components.common.button');
    }
}
