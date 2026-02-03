
@props([
   'class'=>'', 
   'inputName'=>null, 
   'inputOpt'=>null,
   'inputType'=>'text',
   'rightIcon'=>null,
   'placeHolder'=>null,
   'isLabel'=>false,
   'label'=>null,
   'isDate'=>false,
   'isIcon'=>false,
   'iconName', 'onIconClick'=>''
])

<style>
    .rs_input{
    input{
        border: unset;
        width: 100%;
    }
    &.input_price,
    &.input_search{
        border-radius: 5px;
        padding: 8px 12px;
        border: 1px solid var(--primary-300);
        display: flex;
        gap: 8px;
        input{
            border: unset;
            &:focus-visible{
                outline: unset;
            }
            width: 65%;
        }
    }
    &.input_search{
        input{
            width: 88%;
        }
    }
    &.input_custom_icon{
        border-radius: 5px;
        padding: 8px 12px;
        border: 1px solid var(--primary-300);
        display: flex;
        gap: 8px;
        justify-content: space-between;
        input{
            border: unset;
            &:focus-visible{
                outline: unset;
            }
            width: 86%;
        }
        .right_icon{
            i{
                &::before{
                    vertical-align: -0.22em;
                }
                &.bi-plus{
                    &::before{
                    font-size: 28px !important;
                    background: var(--primary);
                    border-radius: 5px;
                    color: var(--white);
                    cursor: pointer;
                    }
                }
            }
        }
    }
    &.with_label{
        input{
            /* border-radius: 5px;
            padding: 8px 12px;
            border: 1px solid var(--primary-300); */
            border:0;
            &:focus-visible{
                outline: unset;
            }
        }

    }
    &.with_date{
        border-radius: 5px;
        padding: 8px 12px;
        border: 1px solid var(--primary-300);
        input{
            &:focus-visible{
                outline: unset;
            }
        }

    }
    .right_icon{
        color:var(--primary-600);
    }
}
</style>

@if($isLabel == True)
<label class="mb-2" for="{{ $inputName }}">{{ $label }}</label>
@endif
<div class='rs_input | {{ $inputOpt }} {{ $isLabel == True ? "with_label" : ""}} {{ $isDate == True? "with_date" : "" }} {{$class}} ' >
   @if ($inputOpt == 'input_price')
      {{ getPoundSymbol() }}
   @endif

   <input type="{{ $inputType }}" name= "{{ $inputName }}" placeholder="{{ $placeHolder}}" />

   @if ($rightIcon)
   <div class="right_icon">{{ $rightIcon }}</div>
   @endif
   @if ($isIcon)
   <div class="right_icon" onClick="{{ $onIconClick }}()"><i class="bi {{ $iconName }}"></i></div>
   @endif
</div>