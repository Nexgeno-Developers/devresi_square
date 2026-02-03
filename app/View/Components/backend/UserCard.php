<?php

namespace App\View\Components\Backend;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class UserCard extends Component
{
    public $class, $userName, $email, $phone, $cardStyle, $userId;

    /**
     * Create a new component instance.
     */
    public function __construct($userName, $email, $phone, $userId, $class = null, $cardStyle = 'horizontal')
    {
        $this->class = $class;
        $this->userName = $userName;
        $this->email = $email;
        $this->phone = $phone;
        $this->cardStyle = $cardStyle;  // Vertical | Horizontal
        $this->userId = $userId;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.backend.user-card');
    }
}
