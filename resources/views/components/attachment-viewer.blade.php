<div>
    {{-- Button to Open Modal --}}
    <button type="button" class="{{ $buttonClass }}" data-bs-toggle="modal" data-bs-target="#{{ $modalId }}">
        @if ($iconClass)
            <i class="me-2 {{ $iconClass }}"></i>
        @endif
        {{ $title }}
    </button>

    {{-- Modal --}}
    <div class="modal fade @if($modalScrollable) modal-dialog-scrollable @endif" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}_label" aria-hidden="true">
        <div class="modal-dialog {{ $modalSize }} modal-dialog-centered">
            <div class="modal-content" style="background-color: {{ $backgroundColor }};">
                <div class="modal-header">
                    <h5 class="modal-title" id="{{ $modalId }}_label">Attachment Preview</h5>
                    <button type="button" class="btn-close {{ $closeButtonClass }}" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body text-center" 
                     style="
                         {{ $previewWidth ? 'max-width:' . $previewWidth . ';' : '' }}
                         {{ $previewHeight ? 'height:' . $previewHeight . '; overflow:auto;' : '' }}
                         {{ $borderRadius ? 'border-radius:' . $borderRadius . ';' : '' }}
                     ">

                    {{-- Image Preview --}}
                    @if(in_array(strtolower($fileExtension), ['jpg', 'jpeg', 'png', 'gif', 'webp']))
                        <img src="{{ $fileUrl }}" class="img-fluid w-100"  style="height: auto !important;" alt="Attachment">
                    
                    {{-- PDF Preview (Google Docs Viewer) --}}
                    @elseif(strtolower($fileExtension) == 'pdf')
                        <object data="{{ $fileUrl }}" type="application/pdf" width="100%" height="500px">
                            <p>Cannot preview this file. <a href="{{ $fileUrl }}" target="_blank">Open in new tab</a></p>
                        </object>

                    {{-- Word, Excel, PowerPoint (Office Viewer) --}}
                    @elseif(in_array(strtolower($fileExtension), ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx']))
                        <iframe src="https://view.officeapps.live.com/op/view.aspx?src={{ urlencode($fileUrl) }}" width="100%" height="500px"></iframe>

                    {{-- Text Files --}}
                    @elseif(strtolower($fileExtension) == 'txt')
                        <iframe src="data:text/plain;charset=utf-8,{{ urlencode(file_get_contents($fileUrl)) }}" width="100%" height="500px"></iframe>

                    {{-- Other File Types (Download Option) --}}
                    @else
                        <p>Cannot preview this file type.</p>
                        @if ($downloadable)
                            <a href="{{ $fileUrl }}" download class="btn btn_secondary">Download File</a>
                        @endif
                    @endif
                </div>

                {{-- Footer with Buttons --}}
                <div class="modal-footer">
                    {{-- View in New Tab --}}
                    <a href="{{ $fileUrl }}" target="_blank" class="btn btn_outline_secondary">
                        <i class="me-2 fa-solid fa-arrow-up-right-from-square"></i> View in New Tab
                    </a>

                    {{-- Download Button (If Enabled) --}}
                    @if ($downloadable)
                        <a href="{{ $fileUrl }}" download class="btn btn_secondary">
                            <i class="me-2 fa-solid fa-download"></i> Download
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
