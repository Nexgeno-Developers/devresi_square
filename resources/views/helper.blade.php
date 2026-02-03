<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="{{ asset('asset/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('asset/css/main-style.css') }}" rel="stylesheet">
    <link href="{{ asset('asset/backend/css/style.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <title>Help Document v1.2</title>
    <style>
        html, body, .row{
            height: 100%;
        }

        blockquote,
        xmp{
            margin: 0;
            padding: 0;
            white-space: pre-line;
        }
        .gap-16{
            gap: 16px;
        }
        /* blockquote{
            background: #ecf3fa;
            padding: 16px;
        } */
        .ml_0{
            margin-left: 0;
        }
        .h_sidebar{
            height: 100%;
            background: #f3f3f3;
            padding: 24px 24px 24px 32px;
            ul{
                list-style: number;
                padding-left:20px;
                padding: unset;

                li{

                    a{
                        text-decoration: none;
                        color:#000;
                    }
                }
            }
        }
        .h_section_wrapper{
            margin: 60px 0;
            .h_section{
                background: #f3f3f3;
                padding:16px;
                border-radius:6px;
                width:fit-content;
            }
        }

        table,
        .rs_input,
        .pv_content_wrapper{
            background: #fff;
        }
        .pv_card_wrapper{
            height: unset;
        }



        @media (max-width: 766px) {
            .h_section{
                width: 100% !important;
            }

        }
    </style>
</head>
<body>
    {{-- 
    code usage
    &#x2774; = {
    &#x2775; = }
    &#36;    = $
    &lt;     = < 
    &gt;     = > 
    --}}
    <div class="row">
        <div class="col-lg-2">
            <div class="h_sidebar">

                <ul>
                    <li><a href="#buttons"> Buttons</a></li>
                    <li><a href="#cards"> Cards</a></li>
                    <li><a href="#tables"> Tables</a></li>
                    <li><a href="#dropdown"> Dropdown</a></li>
                    <li><a href="#inputs"> Inputs</a></li>
                    <li><a href="#search"> Search</a></li>
                </ul>
            </div>
        </div>
        <div class="col-lg-10">
            <div class="h_section_wrapper" id="buttons">
                <h2>Buttons</h2>
                <blockquote>
                    <p>"Type" should be "secondary" or "primary".
                    "isOutline" to make button ouline make it "True" or "False"
                    If "isLinkBtn" is true button will be  < a > tag, its required link prop and if its false button will be < button >
                    "Size" should be "sm" or "lg".</p>
                </blockquote>
                <div class="row gap-16 ml_0">
                    <div class="h_section">
                        <div class="" >
                            <h6>Primary Link Button</h6>
                            
                            <x-backend.forms.button 
                                class=''
                                name='Primary'
                                type='primary'
                                size='sm'
                                isOutline={{false}}
                                isLinkBtn={{false}}
                                link='https://#'
                                onClick='copyHtml()'
                            />
                            <blockquote id="primary_btn">
                            &lt;x-backend.forms.button
                                class=""
                                name="Primary"
                                type="primary"
                                size="sm"
                                isOutline=&#x2774;&#x2774; false &#x2775;&#x2775;
                                isLinkBtn=&#x2774;&#x2774; false &#x2775;&#x2775;
                                link=""
                                onclick="copyHtml()"
                            /&gt;
                            </blockquote>
                        </div>
                    </div>
                    <div class="h_section">
                        <div class="">
                            <h6>Secondary Link Button</h6>
                           
                            <x-backend.forms.button 
                                class=''
                                name='Secondary'
                                type='secondary'
                                size='sm'
                                isOutline={{false}}
                                isLinkBtn={{false}}
                                link='https://#'
                                onClick='copyHtml()'
                            />
                            <blockquote>
                            &lt;x-backend.forms.button 
                                class=""
                                name="Secondary"
                                type="secondary"
                                size="sm"
                                isOutline=&#x2774;&#x2774; false &#x2775;&#x2775;
                                isLinkBtn=&#x2774;&#x2774; false &#x2775;&#x2775;
                                link=""
                                onclick=""
                            /&gt;
                            </blockquote>
                        </div>
                    </div>
                    <div class="h_section">
                        <div class="">
                            <h6>Primary Outline Link Button</h6>
                            <x-backend.forms.button 
                                class=''
                                name='Outline Primary'
                                type='primary'
                                size='sm'
                                isOutline={{true}}
                                isLinkBtn={{false}}
                                link='https://#'
                                onClick='copyHtml()'
                            />
                            <blockquote>
                                &lt;x-bbackend.forms.button
                                class=""
                                name="Outline primary"
                                type="primary"
                                size="sm"
                                isOutline=&#x2774;&#x2774; true &#x2775;&#x2775;
                                isLinkBtn=&#x2774;&#x2774; false &#x2775;&#x2775;
                                link=""
                                onclick=""
                            /&gt;
                            </blockquote>
                        </div>
                    </div>
                    <div class="h_section">
                        <div class="">
                            <h6>Secondary Outline Link Button</h6>
                            
                            <x-backend.forms.button 
                                class=''
                                name='Outline Secondary'
                                type='secondary'
                                size='sm'
                                isOutline={{true}}
                                isLinkBtn={{false}}
                                link='https://#'
                                onClick='copyHtml()'
                            />
                            <blockquote>
                            &lt;x-backend.forms.button 
                                class=""
                                name="Outline Secondary"
                                type="secondary"
                                size="sm"
                                isOutline=&#x2774;&#x2774; true &#x2775;&#x2775;
                                isLinkBtn=&#x2774;&#x2774; false &#x2775;&#x2775;
                                link=""
                                onclick=""
                            /&gt;
                            </blockquote>
                        </div>
                    </div>
                    <div class="h_section">
                        <div class="">
                            <h6>Secondary Link(&lt;a&gt;) Button</h6>
                           <p>isLink button need to be 'True'</p>
                            <x-backend.forms.button 
                                class=''
                                name='Secondary'
                                type='secondary'
                                size='sm'
                                isOutline={{false}}
                                isLinkBtn={{true}}
                                link='https://#'
                                onClick='copyHtml()'
                            />
                            <blockquote>
                            &lt;x-backend.forms.button 
                                class=""
                                name="Secondary"
                                type="secondary"
                                size="sm"
                                isOutline=&#x2774;&#x2774; false &#x2775;&#x2775;
                                isLinkBtn=&#x2774;&#x2774; true &#x2775;&#x2775;
                                link="https://#"
                                onclick=""
                            /&gt;
                            </blockquote>
                        </div>
                    </div>
                    <div class="h_section">
                        <div class="">
                            <h6>Primary Outline Link(&lt;a&gt;) Button</h6>
                            <x-backend.forms.button 
                                class=''
                                name='Outline Primary'
                                type='primary'
                                size='sm'
                                isOutline={{true}}
                                isLinkBtn={{true}}
                                link='https://#'
                                onClick='copyHtml()'
                            />
                            <blockquote>
                                &lt;x-bbackend.forms.button
                                class=""
                                name="Outline primary"
                                type="primary"
                                size="sm"
                                isOutline=&#x2774;&#x2774; true &#x2775;&#x2775;
                                isLinkBtn=&#x2774;&#x2774; true &#x2775;&#x2775;
                                link=""
                                onclick=""
                            /&gt;
                            </blockquote>
                        </div>
                    </div>
                    
                </div>
            </div>
            <div class="h_section_wrapper" id="cards">
                <h2>Cards</h2>
                <div class="row gap-16 ml_0">
                    <div class="h_section">
                        <div class="">
                            <h6>Property horizontal card</h6>
                            <div class="pv_card_wrapper">
                                <x-backend.property-card
                                    class=""
                                    propertyName="169-173 Portland Rd, Hove, East Sussex, BN3 5QJ"
                                    bed="2"
                                    bath="2"
                                    floor="6"
                                    living="1"
                                    type="Appartment"
                                    available="02/05/2025"
                                    price="6542"
                                    lettingPrice="200"
                                    cardStyle=""
                                    propertyId="1"
                                />
                            </div>
                            <blockquote>
                            &lt;x-backend.property-card
                                class=""
                                propertyName="&#x2774;&#x2774; &#36;property['prop_name'] &#x2775;&#x2775;"
                                bed="&#x2774;&#x2774; &#36;pproperty['bedroom'] &#x2775;&#x2775;"
                                bath="&#x2774;&#x2774; &#36;pproperty['bathroom'] &#x2775;&#x2775;"
                                floor="&#x2774;&#x2774; &#36;pproperty['floor'] &#x2775;&#x2775;"
                                living="&#x2774;&#x2774; &#36;pproperty['reception'] &#x2775;&#x2775;"
                                type="&#x2774;&#x2774; &#36;pproperty['property_type'] &#x2775;&#x2775;"
                                available="&#x2774;&#x2774; &#36;pproperty['available_from'] &#x2775;&#x2775;"
                                price="&#x2774;&#x2774; &#36;pproperty['price'] &#x2775;&#x2775;"
                                cardStyle="" //for horizontal keep blank //
                                propertyId="&#x2774;&#x2774; &#36;property['id'] &#x2775;&#x2775;"
                                /&gt;
                           </blockquote>
                        </div>
                    </div>
                    <div class="h_section">
                        <div class="">
                            <h6>Property Vertical card</h6>
                            <div class="pv_card_wrapper">
                                <x-backend.property-card
                                    class=""
                                    propertyName="169-173 Portland Rd, Hove, East Sussex, BN3 5QJ"
                                    bed="2"
                                    bath="2"
                                    floor="6"
                                    living="1"
                                    type="Appartment"
                                    available="02/05/2025"
                                    price="6542"
                                    lettingPrice="200" 
                                    cardStyle="vertical"
                                    propertyId="1"

                                />
                            </div>
                            <blockquote>
                            &lt;x-backend.property-card
                                class=""
                                propertyName="&#x2774;&#x2774; &#36;property['prop_name'] &#x2775;&#x2775;"
                                bed="&#x2774;&#x2774; &#36;pproperty['bedroom'] &#x2775;&#x2775;"
                                bath="&#x2774;&#x2774; &#36;pproperty['bathroom'] &#x2775;&#x2775;"
                                floor="&#x2774;&#x2774; &#36;pproperty['floor'] &#x2775;&#x2775;"
                                living="&#x2774;&#x2774; &#36;pproperty['reception'] &#x2775;&#x2775;"
                                type="&#x2774;&#x2774; &#36;pproperty['property_type'] &#x2775;&#x2775;"
                                available="&#x2774;&#x2774; &#36;pproperty['available_from'] &#x2775;&#x2775;"
                                price="&#x2774;&#x2774; &#36;pproperty['price'] &#x2775;&#x2775;"
                                cardStyle="vertical" //for Vertical chnage only this //
                                propertyId="&#x2774;&#x2774; &#36;property['id'] &#x2775;&#x2775;"
                                /&gt;
                           </blockquote>
                        </div>
                    </div>
                </div>
            </div>
            <div class="h_section_wrapper" id="tables">
                <h2>Tables</h2>
                <div class="row gap-16 ml_0">
                    <div class="h_section">
                        <div class="">
                            <h6>Responsive Table</h6>
                            {{-- table  compoent start--}}
                                @php
                                $headers = ['id'=>'id','Name', 'Position', 'Phone', 'email', 'City'];
                                $rows = [
                                    ['id'=>1, 'John Doe', 'Owner', '456798462', 'john@example.com', 'London'],
                                    ['id'=>2, 'Jane Smith', 'Owner', '974511268', 'jane@example.com', 'Mumbai'],
                                ];
                            @endphp
                            <x-backend.dynamic-table :headers="$headers" :rows="$rows" class='' />
                            <blockquote>
                                &lt;x-backend.dynamic-table
                                    :headers="&#36;headers"
                                    :rows="&#36;rows"
                                    class = ""
                                /&gt;
                            </blockquote>
                        </div>
                    </div>
                </div>
            </div>
            <div class="h_section_wrapper" id="dropdown">
                <h2>Dropdown</h2>
                <blockquote>
                    Default Dropdown use "isIcon" false</br>Icon Dropdown use "isIcon" true </br>
                </blockquote>
                <div class="row gap-16 ml_0">
                    <div class="h_section">
                        <div class="">
                            <h6>Dropdown</h6>
                            @php
                            $countries = [ 'us' => 'United States', 'ca' => 'Canada', 'uk' => 'United Kingdom', ];
                            $selectedCountry = 'ca';
                            @endphp
                            <x-backend.forms.dropdown
                                class=''
                                :options="$countries"
                                :selected="$selectedCountry"
                                isIcon="{{false}}"
                            />
                            <blockquote>
                                &lt;x-backend.forms.dropdown
                                    class=''
                                    :options="&#36;countries"
                                    :selected="&#36;selectedCountry"
                                    isIcon=&#x2774;&#x2774;false &#x2775;&#x2775;
                                /&gt;
                                
                            </blockquote>
                        </div>
                    </div>
                    <div class="h_section">
                        <div class="">
                            <h6>Dropdown Icon Button</h6>
                            @php
                            $action = [ 'edit' => 'Edit', 'delete' => 'Delete' ];
                            $selectedAction = 'edit';
                            @endphp
                            <x-backend.forms.dropdown
                                class=''
                                :options="$action"
                                :selected="$selectedAction"
                                isIcon="{{true}}"
                            />
                            <blockquote>
                                &lt;x-backend.forms.dropdown
                                    class="right_icon"
                                    :options="&#36;action"
                                    :selected="&#36;selectedAction"
                                    isIcon=&#x2774;&#x2774;true &#x2775;&#x2775;
                                /&gt;
                            </blockquote>
                        </div>
                    </div>
                </div>
            </div>
            <div class="h_section_wrapper" id="inputs">
                <h2>Inputs</h2>
                <blockquote>
                    Default Dropdown use "isIcon" false</br>Icon Dropdown use "isIcon" true </br>
                </blockquote>

                <div class="row gap-16 ml_0">
                    <div class="h_section">
                        <div class="">
                            <h6>Text Input With Label</h6>
                            
                            <x-backend.forms.input
                                class='' 
                                inputName='full_name' 
                                placeHolder='Full Name'
                                inputOpt=''
                                inputType='text' 
                                rightIcon=''
                                isLabel=''
                                label='Full Name'
                                isDate={{True}}
                                isIcon={{false}}
                                iconName='' 
                                onIconClick=''
                            />
                            <blockquote>
                                &lt;x-backend.forms.input
                                    class=""
                                    inputName="full_name"
                                    placeHolder="Full Name"
                                    inputOpt=""
                                    inputType="text"
                                    inputType='text' 
                                    rightIcon=''
                                    isLabel=''
                                    label='Full Name'
                                    isDate=&#x2774;&#x2774; True &#x2775;&#x2775;
                                    isIcon=&#x2774;&#x2774; false &#x2775;&#x2775;
                                    iconName='' 
                                    onIconClick='copyHtml()'
                                /&gt;
                            </blockquote>
                        </div>
                    </div>
                    {{-- h_section end  --}}

                    <div class="h_section">
                        <div class="">
                            <h6>Price without Label</h6>
                            <x-backend.forms.input
                                class='' 
                                inputName='price' 
                                placeHolder='Full Name'
                                inputOpt='input_price'
                                inputType='number' 
                                rightIcon='Per Month'
                                isLabel={{false}}
                                label='Price'
                                isDate={{false}}
                                isIcon={{false}}
                                iconName='' 
                                onIconClick=''
                            />
                            <blockquote>
                                
                                &lt;x-backend.forms.input
                                    class='' 
                                    inputName='price' 
                                    placeHolder='Full Name'
                                    inputOpt='input_price'
                                    inputType='number' 
                                    rightIcon='Per Month'
                                    isLabel=&#x2774;&#x2774; false &#x2775;&#x2775;
                                    label='Price'
                                    isDate=&#x2774;&#x2774; false &#x2775;&#x2775;
                                    isIcon=&#x2774;&#x2774; false &#x2775;&#x2775;
                                    iconName='' 
                                    onIconClick=''
                                /&gt;
                            </blockquote>
                        </div>
                    </div>
                    {{-- h_section end  --}}

                    <div class="h_section">
                        <div class="">
                            <h6>Date Input</h6>
                            <x-backend.forms.input
                                class='' 
                                inputName='from_date' 
                                placeHolder=''
                                inputOpt=''
                                inputType='date' 
                                rightIcon=''
                                isLabel={{false}}
                                label=''
                                isDate={{true}}
                                isIcon={{false}}
                                iconName='' 
                                onIconClick=''
                            />
                            <blockquote>
                                &lt;x-backend.forms.input
                                    class='' 
                                    inputName='from_date' 
                                    placeHolder=''
                                    inputOpt=''
                                    inputType='date' 
                                    rightIcon=''
                                    isLabel=&#x2774;&#x2774; false &#x2775;&#x2775;
                                    label=''
                                    isDate=&#x2774;&#x2774; true &#x2775;&#x2775;
                                    isIcon=&#x2774;&#x2774; false &#x2775;&#x2775;
                                    iconName='' 
                                    onIconClick=''
                                /&gt;
                            </blockquote>
                        </div>
                    </div>
                    {{-- h_section end  --}}
                    <div class="h_section">
                        <div class="">
                            <h6>Custom Icon Input</h6>
                            <x-backend.forms.input
                                class='' 
                                inputName='custom' 
                                placeHolder='Custom Icon'
                                inputOpt='input_custom_icon'
                                inputType='text' 
                                rightIcon=''
                                isLabel={{false}}
                                label=''
                                isDate={{false}}
                                isIcon={{true}}
                                iconName='bi-gear-fill' 
                                onIconClick='onClick()'
                            />
                            <blockquote>
                                &lt;x-backend.forms.input
                                    class='' 
                                    inputName='custom' 
                                    placeHolder='Custom Icon'
                                    inputOpt='input_custom_icon'
                                    inputType='text' 
                                    rightIcon=''
                                    isLabel=&#x2774;&#x2774; false &#x2775;&#x2775;
                                    label=''
                                    isDate=&#x2774;&#x2774; false &#x2775;&#x2775;
                                    isIcon=&#x2774;&#x2774; true &#x2775;&#x2775;
                                    iconName='bi-gear-fill' 
                                    onIconClick=''
                                /&gt;
                            </blockquote>
                        </div>
                    </div>
                    {{-- h_section end  --}}

                </div>
            </div>
            {{-- h_section_wrapper end --}}
            <div class="h_section_wrapper" id="search">
                <h2>Search</h2>
                <blockquote>
                    
                </blockquote>

                <div class="row gap-16 ml_0">
                    <div class="h_section">
                        <div class="">
                            <h6>Search Input</h6>
                            <x-backend.forms.search 
                                class=''
                                placeholder='Search'
                                value=''
                                onClick='onClick()'
                            />
                            <blockquote>
                                &lt;x-backend.forms.search 
                                    class=''
                                    placeholder='Search'
                                    type='secondary'
                                    onClick='onClick()'
                                /&gt;
                            </blockquote>
                        </div>
                    </div>
                    {{-- h_section end  --}}
                </div>
            </div>
            {{-- h_section_wrapper end --}}
        </div>
    </div>

</body>
</html>