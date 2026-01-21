@if($customFields)
    <h5 class="col-12 pb-4">{!! trans('lang.main_fields') !!}</h5>
@endif

<div class="d-flex flex-column col-sm-12 col-md-5 px-4">

    <!-- Image Field -->
    <div class="form-group align-items-start d-flex flex-column flex-md-row">
        {!! Form::label('image', trans("lang.post_image"), ['class' => 'col-md-3 control-label text-md-right mx-1']) !!}
        <div class="col-md-9">
            <div style="width: 100%" class="dropzone image" id="image" data-field="image">
            </div>
            <a href="#loadMediaModal" data-dropzone="image" data-toggle="modal" data-target="#mediaModal" class="btn btn-outline-{{setting('theme_color','primary')}} btn-sm float-right mt-1">{{ trans('lang.media_select')}}</a>
            <div class="form-text text-muted w-50">
                {{ trans("lang.post_image_help") }}
            </div>
        </div>
    </div>
    
<!-- Description Field -->
    <div class="form-group align-items-baseline d-flex flex-column flex-md-row ">
        {!! Form::label('description', trans("lang.post_description"), ['class' => 'col-md-3 control-label text-md-right mx-1']) !!}
        <div class="col-md-9">
            {!! Form::textarea('description', null, ['class' => 'form-control','placeholder'=>
             trans("lang.post_description_placeholder")  ]) !!}
            <div class="form-text text-muted">{{ trans("lang.post_description_help") }}</div>
        </div>
    </div>

</div>
<div class="d-flex flex-column col-sm-12 col-md-3 px-4">
    {{-- <div class="d-flex flex-row justify-content-between align-items-center mb-3">
        {!! Form::label('featured', trans("lang.post_featured"),['class' => 'control-label my-0 mx-3']) !!} {!! Form::hidden('featured', 0, ['id'=>"hidden_featured"]) !!}
        <span class="icheck-{{setting('theme_color')}}">
            {!! Form::checkbox('featured', 1, null) !!} <label for="featured"></label> </span>
    </div>
    <div class="d-flex flex-row justify-content-between align-items-center mb-3">
        {!!  Form::label('enable_booking', trans("lang.post_enable_booking"),['class' => 'control-label my-0 mx-3'], false)  !!} {!! Form::hidden('enable_booking', 0, ['id'=>"hidden_enable_booking"]) !!}
        <span class="icheck-{{setting('theme_color')}}">
            {!! Form::checkbox('enable_booking', 1, null) !!} <label for="enable_booking"></label> </span>
    </div>
    <div class="d-flex flex-row justify-content-between align-items-center mb-3">
        {!!  Form::label('enable_at_salon', trans("lang.post_enable_at_salon"),['class' => 'control-label my-0 mx-3'], false)  !!} {!! Form::hidden('enable_at_salon', 0, ['id'=>"hidden_enable_at_salon"]) !!}
        <span class="icheck-{{setting('theme_color')}}">
            {!! Form::checkbox('enable_at_salon', 1, null) !!} <label for="enable_at_salon"></label> </span>
    </div>
    <div class="d-flex flex-row justify-content-between align-items-center mb-3">
        {!!  Form::label('enable_at_customer_address', trans("lang.post_enable_at_customer_address"),['class' => 'control-label my-0 mx-3'], false)  !!} {!! Form::hidden('enable_at_customer_address', 0, ['id'=>"hidden_enable_at_customer_address"]) !!}
        <span class="icheck-{{setting('theme_color')}}">
            {!! Form::checkbox('enable_at_customer_address', 1, null) !!} <label for="enable_at_customer_address"></label> </span>
    </div>
    <div class="d-flex flex-row justify-content-between align-items-center mb-3">
        {!! Form::label('available', trans("lang.post_available"),['class' => 'control-label my-0 mx-3']) !!} {!! Form::hidden('available', 0, ['id'=>"hidden_available"]) !!}
        <span class="icheck-{{setting('theme_color')}}">
            {!! Form::checkbox('available', 1, null) !!} <label for="available"></label> </span>
    </div> --}}
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
        <i class="fa fa-save"></i> {{trans('lang.save')}} {{trans('lang.post')}}</button>
    <a href="{!! route('posts.index') !!}" class="btn btn-default"><i class="fa fa-undo"></i> {{trans('lang.cancel')}}</a>
</div>


@prepend('scripts')
    <script type="text/javascript">
        var var16110647911349350349ble = [];
        @if(isset($eService) && $post->hasMedia('image'))
        @forEach($post->getMedia('image') as $media)
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
                @if(isset($eService) && $post->hasMedia('image'))
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
                    file, var16110647911349350349ble, '{!! url("eServices_xxxxxxxxxxxxx/remove-media") !!}',
                    'image', '{!! isset($eService) ? $post->id : 0 !!}', '{!! url("uploads/clear") !!}', '{!! csrf_token() !!}'
                );
            }
        });
        dz_var16110647911349350349ble[0].mockFile = var16110647911349350349ble;
        dropzoneFields['image'] = dz_var16110647911349350349ble;
   
</script>
@endpush
