@extends('layouts.app')
{{--
@push('css_lib')
    <link rel="stylesheet" href="{{asset('vendor/summernote/summernote-bs4.min.css')}}">
@endpush
--}}

@section('content')
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-bold">{{ trans('lang.campaign_create') }}
                        <small class="mx-3">|</small><small>{{ trans('lang.campaign_desc') }}</small>
                    </h1>
                </div><!-- /.col -->
                <div class="col-sm-6">
                    <ol class="breadcrumb bg-white float-sm-right rounded-pill px-4 py-2 d-none d-md-flex">
                        <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}"><i class="fas fa-tachometer-alt"></i> {{ trans('lang.dashboard') }}</a></li>
                        <li class="breadcrumb-item active">{{ trans('lang.campaign_create') }}</li>
                    </ol>
                </div><!-- /.col -->
            </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    <div class="content">
        <div class="clearfix"></div>
        @include('flash::message')
        @include('adminlte-templates::common.errors')
        <div class="clearfix"></div>

        <div class="card shadow-sm">
            <div class="card-header">
                <ul class="nav nav-tabs d-flex flex-row align-items-start card-header-tabs">
                    @can('campaigns.index')
                        <li class="nav-item">
                            <a class="nav-link" href="{!! route('campaigns.index') !!}"><i class="fas fa-list mr-2"></i>{{ trans('lang.campaign_table') }}</a>
                        </li>
                    @endcan
                    <li class="nav-item">
                        <a class="nav-link active" href="{!! url()->current() !!}"><i class="fas fa-bullhorn mr-2"></i>{{ trans('lang.campaign_create') }}</a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                {!! Form::open(['route' => 'campaigns.store', 'files' => true]) !!}
                <div class="row">
                    <div class="d-flex flex-column col-sm-12 col-md-6">
                        <div class="form-group align-items-baseline d-flex flex-column flex-md-row">
                            {!! Form::label('title', trans('lang.campaign_title'), ['class' => 'col-md-3 control-label text-md-right mx-1']) !!}
                            <div class="col-md-9">
                                {!! Form::text('title', old('title', $defaultTitle), ['class' => 'form-control', 'placeholder' => trans('lang.campaign_title_placeholder')]) !!}
                                <div class="form-text text-muted">{{ trans('lang.campaign_title_help') }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-column col-sm-12 col-md-6">
                        <div class="form-group align-items-baseline d-flex flex-column flex-md-row">
                            {!! Form::label('audience', trans('lang.campaign_audience'), ['class' => 'col-md-3 control-label text-md-right mx-1']) !!}
                            <div class="col-md-9">
                                {!! Form::select('audience', [
                                    'all' => trans('lang.campaign_audience_all'),
                                    'salon' => trans('lang.campaign_audience_salon'),
                                    'client' => trans('lang.campaign_audience_client'),
                                ], old('audience', 'all'), ['class' => 'form-control']) !!}
                                <div class="form-text text-muted">{{ trans('lang.campaign_audience_help') }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-column col-sm-12 col-md-6">
                        <div class="form-group align-items-baseline d-flex flex-column flex-md-row">
                            {!! Form::label('image_file', trans('lang.campaign_image_file'), ['class' => 'col-md-3 control-label text-md-right mx-1']) !!}
                            <div class="col-md-9">
                                {!! Form::file('image_file', ['class' => 'form-control']) !!}
                                <div class="form-text text-muted">{{ trans('lang.campaign_image_file_help') }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-column col-sm-12 col-md-6">
                        <div class="form-group align-items-baseline d-flex flex-column flex-md-row">
                            {!! Form::label('image_url', trans('lang.campaign_image_url'), ['class' => 'col-md-3 control-label text-md-right mx-1']) !!}
                            <div class="col-md-9">
                                {!! Form::text('image_url', old('image_url'), ['class' => 'form-control', 'placeholder' => trans('lang.campaign_image_url_placeholder')]) !!}
                                <div class="form-text text-muted">{{ trans('lang.campaign_image_url_help') }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-column col-sm-12">
                        <div class="form-group align-items-baseline d-flex flex-column flex-md-row">
                            {!! Form::label('message', trans('lang.campaign_message'), ['class' => 'col-md-3 control-label text-md-right mx-1']) !!}
                            <div class="col-md-9">
                                {{-- {!! Form::hidden('message_format', 'html') !!} --}}
                                {!! Form::textarea('message', old('message'), ['class' => 'form-control', 'rows' => 4, 'placeholder' => trans('lang.campaign_message_placeholder'), 'id' => 'campaign-message']) !!}
                                <div class="form-text text-muted">{{ trans('lang.campaign_message_help') }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group col-12 d-flex flex-column flex-md-row justify-content-md-end justify-content-sm-center border-top pt-4">
                        <button type="submit" class="btn bg-{{ setting('theme_color') }} mx-md-3 my-lg-0 my-xl-0 my-md-0 my-2">
                            <i class="fas fa-paper-plane"></i> {{ trans('lang.campaign_send') }}
                        </button>
                        <a href="{!! route('campaigns.create') !!}" class="btn btn-default"><i class="fas fa-undo"></i> {{ trans('lang.reset') }}</a>
                    </div>
                </div>
                {!! Form::close() !!}
                <div class="clearfix"></div>
            </div>
        </div>
    </div>
@endsection
@push('scripts_lib')
    <script>
        if (typeof jQuery !== 'undefined' && !$.fn.summernote) {
            $.fn.summernote = function () {
                return this;
            };
        }
    </script>
@endpush
