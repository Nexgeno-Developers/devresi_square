<?php

namespace App\View\Components\Backend;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class SearchComp extends Component
{
    public $class, $value, $placeholder, $onClick ;
    public function __construct( $class, $value = null, $placeholder="null", $onClick=null)
    {
        $this->class = $class ;
        $this->value =  $value ;
        $this->placeholder =  $placeholder ;
        $this->onClick =$onClick ;

    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('backend.components.common.search');
    }
}
