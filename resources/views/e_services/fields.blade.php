@if($customFields)
    <h5 class="col-12 pb-4">{!! trans('lang.main_fields') !!}</h5>
@endif
<div class="d-flex flex-column col-sm-12 col-md-4 px-4">
    <!-- Use Template Checkbox -->
    <div class="form-group align-items-baseline d-flex flex-column flex-md-row">
        {!! Form::label('use_template', trans("lang.e_service_use_template"), ['class' => 'col-md-3 control-label text-md-right mx-1']) !!}
        <div class="col-md-9 d-flex align-items-center">
            <span class="icheck-{{ setting('theme_color') }}">
                {!! Form::checkbox('use_template', 1, null, ['id' => 'use_template']) !!}
                <label for="use_template">{{ trans("lang.e_service_use_template_label") }}</label>
            </span>
        </div>
    </div>
    <!-- Template Service Field -->
    <div class="form-group align-items-baseline d-flex flex-column flex-md-row" id="template_field">
        {!! Form::label('template_service_id', trans("lang.e_service_template_service"), ['class' => 'col-md-3 control-label text-md-right mx-1']) !!}
        <div class="col-md-9">
            {{-- {!! Form::select('template_service_id', 
                ["" => trans("lang.e_service_template_service_placeholder")] , 
                null, 
                ['class' => 'select2 form-control not-required', 'id' => 'template_service_id']
            ) !!} --}}
            
            <select name="template_service_id"  class="select2 form-control not-required" id="template_service_id">
                
                @foreach($category_services as $id => $service)
                    <option value="{{ $id }}"  
                        
                        @if(array_key_exists("level" ,$service)) disabled  @endif
                        @if(in_array($id, $serviceegoriesSelected ?? [])) selected @endif>
                        {{ $service['label'] }}
                    </option>
                   
                @endforeach
            </select>
            <div class="form-text text-muted">
                {{ trans("lang.e_service_template_service_help") }}
            </div>
        </div>
    </div>
    <!-- Name Field -->
    <div class="form-group align-items-baseline d-flex flex-column flex-md-row" id="name_field">
        {!! Form::label('name', trans("lang.e_service_name"), ['class' => 'col-md-3 control-label text-md-right mx-1']) !!}
        <div class="col-md-9">
            {!! Form::text('name', null,  ['class' => 'form-control','placeholder'=>  trans("lang.e_service_name_placeholder")]) !!}
            <div class="form-text text-muted">
                {{ trans("lang.e_service_name_help") }}
            </div>
        </div>
    </div>

    <!-- Categories Field -->
    <div class="form-group align-items-baseline d-flex flex-column flex-md-row " id="category_field">
        {!! Form::label('categories[]', trans("lang.e_service_categories"),['class' => 'col-md-3 control-label text-md-right mx-1']) !!}
        <div class="col-md-9">
            {{-- {!! Form::select('categories[]', $category, $categoriesSelected, ['class' => 'select2 form-control not-required' , 'data-empty'=>trans('lang.e_service_categories_placeholder'),'multiple'=>'multiple']) !!} --}}
            <select name="category_id"  class="select2 form-control not-required" >
                @foreach($category as $id => $cat)
                    <option value="{{ $id }}"  
                        data-level="{{ $cat['level'] }}"
                        @if($cat['level'] < 2) disabled  @endif
                        @if(in_array($id, $categoriesSelected ?? [])) selected @endif>
                        {{ $cat['label'] }}
                    </option>
                @endforeach
            </select>
            <div class="form-text text-muted">{{ trans("lang.e_service_categories_help") }}</div>
        </div>
    </div>

    <!-- Price Field -->
    <div class="form-group align-items-baseline d-flex flex-column flex-md-row ">
        {!! Form::label('price', trans("lang.e_service_price"), ['class' => 'col-md-3 control-label text-md-right mx-1']) !!}
        <div class="col-md-9">
            <div class="input-group">
                {!! Form::number('price', null, ['class' => 'form-control','step'=>'any', 'min'=>'0', 'placeholder'=> trans("lang.e_service_price_placeholder")]) !!}
                <div class="input-group-append">
                    <div class="input-group-text text-bold px-3">{{setting('default_currency','$')}}</div>
                </div>
            </div>
            <div class="form-text text-muted">
                {{ trans("lang.e_service_price_help") }}
            </div>
        </div>
    </div>

    <!-- Discount Price Field -->
    <div class="form-group align-items-baseline d-flex flex-column flex-md-row ">
        {!! Form::label('discount_price', trans("lang.e_service_discount_price"), ['class' => 'col-md-3 control-label text-md-right mx-1']) !!}
        <div class="col-md-9">
            <div class="input-group">
                {!! Form::number('discount_price', null, ['class' => 'form-control','step'=>'any', 'min'=>'0', 'placeholder'=> trans("lang.e_service_discount_price_placeholder")]) !!}
                <div class="input-group-append">
                    <div class="input-group-text text-bold px-3">{{setting('default_currency','$')}}</div>
                </div>
            </div>
            <div class="form-text text-muted">
                {{ trans("lang.e_service_discount_price_help") }}
            </div>
        </div>
    </div>

    <!-- Duration Field -->
    <div class="form-group align-items-baseline d-flex flex-column flex-md-row ">
        {!! Form::label('duration', trans("lang.e_service_duration"), ['class' => 'col-md-3 control-label text-md-right mx-1']) !!}
        <div class="col-md-9">
            <div class="input-group timepicker duration" data-target-input="nearest">
                {!! Form::text('duration', null,  ['class' => 'form-control datetimepicker-input','placeholder'=>  trans("lang.e_service_duration_placeholder"), 'data-target'=>'.timepicker.duration','data-toggle'=>'datetimepicker','autocomplete'=>'off']) !!}
                <div id="widgetParentId"></div>
                <div class="input-group-append" data-target=".timepicker.duration" data-toggle="datetimepicker">
                    <div class="input-group-text"><i class="fas fa-business-time"></i></div>
                </div>
            </div>
            <div class="form-text text-muted">
                {{ trans("lang.e_service_duration_help") }}
            </div>
        </div>
    </div>

    <!-- E Provider Id Field -->
    <div class="form-group align-items-baseline d-flex flex-column flex-md-row ">
        {!! Form::label('salon_id', trans("lang.e_service_salon_id"),['class' => 'col-md-3 control-label text-md-right mx-1']) !!}
        <div class="col-md-9">
            {!! Form::select('salon_id', $salon, null, ['class' => 'select2 form-control']) !!}
            <div class="form-text text-muted">{{ trans("lang.e_service_salon_id_help") }}</div>
        </div>
    </div>
</div>
<div class="d-flex flex-column col-sm-12 col-md-5 px-4">

    <!-- Image Field -->
    <div class="form-group align-items-start d-flex flex-column flex-md-row">
        {!! Form::label('image', trans("lang.e_service_image"), ['class' => 'col-md-3 control-label text-md-right mx-1']) !!}
        <div class="col-md-9">
            <div style="width: 100%" class="dropzone image" id="image" data-field="image">
            </div>
            <a href="#loadMediaModal" data-dropzone="image" data-toggle="modal" data-target="#mediaModal" class="btn btn-outline-{{setting('theme_color','primary')}} btn-sm float-right mt-1">{{ trans('lang.media_select')}}</a>
            <div class="form-text text-muted w-50">
                {{ trans("lang.e_service_image_help") }}
            </div>
        </div>
    </div>
    
<!-- Description Field -->
    <div class="form-group align-items-baseline d-flex flex-column flex-md-row ">
        {!! Form::label('description', trans("lang.e_service_description"), ['class' => 'col-md-3 control-label text-md-right mx-1']) !!}
        <div class="col-md-9">
            {!! Form::textarea('description', null, ['class' => 'form-control','placeholder'=>
             trans("lang.e_service_description_placeholder")  ]) !!}
            <div class="form-text text-muted">{{ trans("lang.e_service_description_help") }}</div>
        </div>
    </div>

</div>
<div class="d-flex flex-column col-sm-12 col-md-3 px-4">
    <div class="d-flex flex-row justify-content-between align-items-center mb-3">
        {!! Form::label('featured', trans("lang.e_service_featured"),['class' => 'control-label my-0 mx-3']) !!} {!! Form::hidden('featured', 0, ['id'=>"hidden_featured"]) !!}
        <span class="icheck-{{setting('theme_color')}}">
            {!! Form::checkbox('featured', 1, null) !!} <label for="featured"></label> </span>
    </div>
    <div class="d-flex flex-row justify-content-between align-items-center mb-3">
        {!!  Form::label('enable_booking', trans("lang.e_service_enable_booking"),['class' => 'control-label my-0 mx-3'], false)  !!} {!! Form::hidden('enable_booking', 0, ['id'=>"hidden_enable_booking"]) !!}
        <span class="icheck-{{setting('theme_color')}}">
            {!! Form::checkbox('enable_booking', 1, null) !!} <label for="enable_booking"></label> </span>
    </div>
    <div class="d-flex flex-row justify-content-between align-items-center mb-3">
        {!!  Form::label('enable_at_salon', trans("lang.e_service_enable_at_salon"),['class' => 'control-label my-0 mx-3'], false)  !!} {!! Form::hidden('enable_at_salon', 0, ['id'=>"hidden_enable_at_salon"]) !!}
        <span class="icheck-{{setting('theme_color')}}">
            {!! Form::checkbox('enable_at_salon', 1, null) !!} <label for="enable_at_salon"></label> </span>
    </div>
    <div class="d-flex flex-row justify-content-between align-items-center mb-3">
        {!!  Form::label('enable_at_customer_address', trans("lang.e_service_enable_at_customer_address"),['class' => 'control-label my-0 mx-3'], false)  !!} {!! Form::hidden('enable_at_customer_address', 0, ['id'=>"hidden_enable_at_customer_address"]) !!}
        <span class="icheck-{{setting('theme_color')}}">
            {!! Form::checkbox('enable_at_customer_address', 1, null) !!} <label for="enable_at_customer_address"></label> </span>
    </div>
    <div class="d-flex flex-row justify-content-between align-items-center mb-3">
        {!! Form::label('available', trans("lang.e_service_available"),['class' => 'control-label my-0 mx-3']) !!} {!! Form::hidden('available', 0, ['id'=>"hidden_available"]) !!}
        <span class="icheck-{{setting('theme_color')}}">
            {!! Form::checkbox('available', 1, null) !!} <label for="available"></label> </span>
    </div>
</div>
@if($customFields)
    <div class="clearfix"></div>
    <div class="col-12 custom-field-container">
        <h5 class="col-12 pb-4">{!! trans('lang.custom_field_plural') !!}</h5>
        {!! $customFields !!}
    </div>
@endif
<!-- Submit Field -->
<div class="form-group col-12 d-flex flex-column flex-md-row justify-content-md-end justify-content-sm-center border-top pt-4">
    <button type="submit" class="btn bg-{{setting('theme_color')}} mx-md-3 my-lg-0 my-xl-0 my-md-0 my-2">
        <i class="fa fa-save"></i> {{trans('lang.save')}} {{trans('lang.e_service')}}</button>
    <a href="{!! route('eServices.index') !!}" class="btn btn-default"><i class="fa fa-undo"></i> {{trans('lang.cancel')}}</a>
</div>


@prepend('scripts')
    <script type="text/javascript">
        var var16110647911349350349ble = [];
        @if(isset($eService) && $eService->hasMedia('image'))
        @forEach($eService->getMedia('image') as $media)
        var16110647911349350349ble.push({
            name: "{!! $media->name !!}",
            size: "{!! $media->size !!}",
            type: "{!! $media->mime_type !!}",
            uuid: "{!! $media->getCustomProperty('uuid'); !!}",
            thumb: "{!! $media->getUrl('thumb'); !!}",
            collection_name: "{!! $media->collection_name !!}"
        });
        @endforeach
        @endif
        var dz_var16110647911349350349ble = $(".dropzone.image").dropzone({
            url: "{!!url('uploads/store')!!}",
            addRemoveLinks: true,
            maxFiles: 5 - var16110647911349350349ble.length,
            init: function () {
                @if(isset($eService) && $eService->hasMedia('image'))
                var16110647911349350349ble.forEach(media => {
                    dzInit(this, media, media.thumb);
                });
                @endif
            },
            accept: function (file, done) {
                dzAccept(file, done, this.element, "{!!config('media-library.icons_folder')!!}");
            },
            sending: function (file, xhr, formData) {
                dzSendingMultiple(this, file, formData, '{!! csrf_token() !!}');
            },
            complete: function (file) {
                dzCompleteMultiple(this, file);
                dz_var16110647911349350349ble[0].mockFile = file;
            },
            removedfile: function (file) {
                dzRemoveFileMultiple(
                    file, var16110647911349350349ble, '{!! url("eServices/remove-media") !!}',
                    'image', '{!! isset($eService) ? $eService->id : 0 !!}', '{!! url("uploads/clear") !!}', '{!! csrf_token() !!}'
                );
            }
        });
        dz_var16110647911349350349ble[0].mockFile = var16110647911349350349ble;
        dropzoneFields['image'] = dz_var16110647911349350349ble;
   
    
        function toggleTemplateMode() {
           
            const useTemplate = $('#use_template').is(':checked');

             if (useTemplate) {
                // afficher le champ template
                $('#template_field').css('display', 'flex'); // pour conserver la mise en forme flex
                // masquer les champs name + category
                $('#name_field').attr('style', 'display: none !important');
                $('#category_field').attr('style', 'display: none !important');
            } else {
                // masquer le champ template
                $('#template_field').attr('style', 'display: none !important');
                // réafficher les champs normaux
                $('#name_field').css('display', 'flex'); // ton form-group est en flex
                $('#category_field').css('display', 'flex');
            }
        }

        // Exécuter au changement et au chargement initial
        $('#use_template').on('change', toggleTemplateMode);
        toggleTemplateMode();

</script>
@endpush
