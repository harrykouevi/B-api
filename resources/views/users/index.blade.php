@extends('layouts.users.default')
@section('x_title',trans('lang.user_table'))
@section('x_content')
    @include('flash::message')
    <div class="card shadow-sm">
        <div class="card-header">
            <ul class="nav nav-tabs d-flex flex-md-row flex-column-reverse align-items-start card-header-tabs">
                <div class="d-flex flex-row">
                    <li class="nav-item">
                        <a class="nav-link active" href="{!! url()->current() !!}"><i class="fas fa-list mr-2"></i>{{trans('lang.user_table')}}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link " href="{!! route('users.create') !!}"><i class="fas fa-plus mr-2"></i>{{trans('lang.user_create')}}</a>
                    </li>
                </div>
                @include('layouts.right_toolbar', compact('dataTable'))
            </ul>
        </div>
        <div class="card-body">
            @include('users.table')
            <div class="clearfix"></div>
        </div>
    </div>
    </div>
@endsection

