<!-- Id Field -->
<div class="form-group align-items-start d-flex flex-column flex-md-row col-6">
    {!! Form::label('id', 'Id:', ['class' => 'col-md-3 control-label text-md-right mx-1']) !!}
    <div class="col-md-9">
        <p>{!! $story->uuid !!}</p>
    </div>
</div>



<!-- Description Field -->
<div class="form-group align-items-start d-flex flex-column flex-md-row col-6">
    {!! Form::label('description', 'Description:', ['class' => 'col-md-3 control-label text-md-right mx-1']) !!}
    <div class="col-md-9">
        <p>{!! $story->description !!}</p>
    </div>
</div>

<div class="form-group align-items-start d-flex flex-column flex-md-row col-6">
    {!! Form::label('image', trans("lang.story_image").':', ['class' => 'col-md-3 control-label text-md-right mx-1']) !!}
    <div class="col-md-9">
        <div style="width: 100%" class="dropzone image" id="image" data-field="cloudmedia">
        </div>
        <a href="#loadMediaModal" data-dropzone="image" data-toggle="modal" data-target="#mediaModal" class="btn btn-outline-{{setting('theme_color','primary')}} btn-sm float-right mt-1">{{ trans('lang.media_select')}}</a>
        <div class="form-text text-muted w-50">
            {{ trans("lang.story_image_help") }}
        </div>
    </div>
</div>

<div class="form-group align-items-start d-flex flex-column flex-md-row col-6">
    {!! Form::label('video', 'Video :', ['class' => 'col-md-3 control-label text-md-right mx-1']) !!}
    <div class="col-md-9">
        @if(!empty($story->vimeo_id))
            <iframe 
                src="https://player.vimeo.com/video/{{ $story->vimeo_id }}" 
                width="200" 
                height="112" 
                frameborder="0" 
                allow="autoplay; fullscreen" 
                allowfullscreen>
            </iframe>
        @else
            <span class="badge badge-secondary">Aucune vidéo</span>
        @endif
    </div>
</div>

<!-- Created At Field -->
<div class="form-group align-items-start d-flex flex-column flex-md-row  col-6">
    {!! Form::label('created_at', 'Created At:', ['class' => 'col-md-3 control-label text-md-right mx-1']) !!}
    <div class="col-md-9">
        <p>{!! $story->created_at !!}</p>
    </div>
</div>

<!-- Updated At Field -->
<div class="form-group align-items-start d-flex flex-column flex-md-row col-6">
    {!! Form::label('updated_at', 'Updated At:', ['class' => 'col-md-3 control-label text-md-right mx-1']) !!}
    <div class="col-md-9">
        <p>{!! $story->updated_at !!}</p>
    </div>
</div>

   

@prepend('scripts')
<script type="text/javascript">
    // --- Image Dropzone ---
    var var16110647911349350349ble = [];
    @if(isset($story) && $story->hasMedia('*'))
    @foreach($story->getMedia('*') as $media)
        var16110647911349350349ble.push({
            name: "{!! $media->name !!}",
            size: "{!! $media->size !!}",
            type: "{!! $media->mime_type !!}",
            uuid: "{!! $media->getCustomProperty('uuid'); !!}",
            thumb: "{!! $media->getFirstMediaUrl('thumb'); !!}",
            collection_name: "{!! $media->collection_name !!}"
        });
    @endforeach
    @endif

    var dz_var16110647911349350349ble = $(".dropzone.image").dropzone({
        url: "{!!url('uploads/store')!!}",
        addRemoveLinks: false,
        maxFiles: 5 - var16110647911349350349ble.length,
        clickable: "", // ✅ rend le bouton image cliquable
        init: function () {
            @if(isset($story) && $story->hasMedia('*'))
                var16110647911349350349ble.forEach(media => {
                    dzInit(this, media, media.thumb);
                });
            @endif
        },
        accept: function (file, done) { dzAccept(file, done, this.element, "{!!config('media-library.icons_folder')!!}"); },
        sending: function (file, xhr, formData) { dzSendingMultiple(this, file, formData, '{!! csrf_token() !!}'); },
        complete: function (file) { dzCompleteMultiple(this, file); },
        removedfile: function (file) {
            dzRemoveFileMultiple(file, var16110647911349350349ble, '{!! url("eServices_xxxxxxxxxxxxx/remove-media") !!}', 'image', '{!! isset($story) ? $story->id : 0 !!}', '{!! url("uploads/clear") !!}', '{!! csrf_token() !!}');
        }
    });
    dropzoneFields['image'] = dz_var16110647911349350349ble;

</script>
@endprepend
