
    <{{ $isLinkBtn == true ? 'a href='.$link.' ' : 'button'}} class="btn btn_{{ $isOutline == true ? 'outline_':'' }}{{ $type }} btn-{{ $size }} {{ $class }}" onClick="{{$onClick}}" >
       <span>{{ $name }}</span> 
       <span class="icon_btn"></span> 
    </{{ $isLinkBtn == true ? 'a href='.$link.'' : 'button'}}>
