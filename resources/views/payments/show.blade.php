@extends('layouts.app')

@section('content')
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-md-6">
                    <h1 class="m-0 text-dark">{{trans('lang.payment_plural')}} <small>{{trans('lang.payment_desc')}}</small></h1>
                </div><!-- /.col -->
                <div class="col-md-6">
                    <ol class="breadcrumb bg-white float-sm-right rounded-pill px-4 py-2 d-none d-md-flex">
                        <li class="breadcrumb-item"><a href="{{url('/')}}"><i class="fa fa-dashboard"></i> {{trans('lang.dashboard')}}</a></li>
                        <li class="breadcrumb-itema ctive"><a href="{!! route('payments.index') !!}">{{trans('lang.payment_plural')}}</a>
                        </li>
                    </ol>
                </div><!-- /.col -->
            </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->
    <div class="content">
        <div class="card shadow-sm">
            <div class="card-header">
                <ul class="nav nav-tabs align-items-end card-header-tabs w-100">
                    <li class="nav-item">
                        <a class="nav-link" href="{!! route('payments.index') !!}"><i class="fa fa-list mr-2"></i>{{trans('lang.payment_table')}}        
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="{!!  url()->current() !!}"><i class="fas fa-info-circle mr-2"></i>{{trans('lang.payment')}}     
                        </a>
                    </li>

                </ul>
            </div>
            <div class="card-body">
                <div class="row">
                @include('payments.fields')


                </div>
                <div class="clearfix"></div>
            </div>
        </div>
    </div>
@endsection