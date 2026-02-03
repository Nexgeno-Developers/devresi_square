<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class AttachmentViewer extends Component
{
    public $fileUrl;
    public $title;
    public $buttonClass;
    public $iconClass;
    public $modalSize;
    public $modalScrollable;
    public $customWidth;
    public $customHeight;
    public $previewWidth;
    public $previewHeight;
    public $backgroundColor;
    public $borderRadius;
    public $closeButtonClass;
    public $downloadable;
    public $fileExtension;
    public $modalId;

    /**
     * Create a new component instance.
     */
    public function __construct(
        $fileUrl, 
        $title = 'View Attachment', 
        $buttonClass = 'btn btn-primary', 
        $iconClass = null,
        $modalSize = 'modal-lg', 
        $modalScrollable = false,
        $customWidth = null, 
        $customHeight = null, 
        $previewWidth = null, 
        $previewHeight = null, 
        $backgroundColor = '#ffffff',
        $borderRadius = '8px',
        $closeButtonClass = '',
        $downloadable = true
    ) {
        $this->fileUrl = $fileUrl;
        $this->title = $title;
        $this->buttonClass = $buttonClass;
        $this->iconClass = $iconClass;
        $this->modalSize = $modalSize;
        $this->modalScrollable = $modalScrollable;
        $this->customWidth = $customWidth;
        $this->customHeight = $customHeight;
        $this->previewWidth = $previewWidth;
        $this->previewHeight = $previewHeight;
        $this->backgroundColor = $backgroundColor;
        $this->borderRadius = $borderRadius;
        $this->closeButtonClass = $closeButtonClass;
        $this->downloadable = $downloadable;
        $this->fileExtension = pathinfo($fileUrl, PATHINFO_EXTENSION);
        $this->modalId = 'modal_' . md5($fileUrl);
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.attachment-viewer');
    }
}
