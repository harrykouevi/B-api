@extends('layouts.app')
@section('content')
    <!-- Content Header (Page header) -->
    <!-- Content Header (Page header) -->
    <section class="content-header content-header{{ setting('fixed_header') }}">
        <div class="container-fluid">
            <div class="row mb-3 align-items-center">
                <div class="col-sm-6">
                    <h1 class="m-0 font-weight-bold text-dark">
                        {{ trans('lang.dashboard') }}
                        <small class="mx-3 text-muted">|</small>
                        <small class="text-muted">{{ trans('lang.dashboard_overview') }}</small>
                    </h1>
                </div>
                <div class="col-sm-6 text-sm-right">
                    <ol class="breadcrumb bg-light rounded-pill px-4 py-2 shadow-sm d-none d-md-inline-flex">
                        <li class="breadcrumb-item">
                            <a href="#" class="text-decoration-none">
                                <i class="fas fa-tachometer-alt text-primary"></i> {{ trans('lang.dashboard') }}
                            </a>
                        </li>
                        <li class="breadcrumb-item active text-muted">{{ trans('lang.dashboard') }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="content">
        <div class="row">
            <!-- Total Bookings -->
            <div class="col-lg-3 col-6 mb-4">
                <div class="small-box bg-white shadow-sm border-start border-4 border-primary">
                    <div class="inner">
                        <h3 class="text-{{ setting('theme_color', 'primary') }}">{{ $bookingsCount }}</h3>
                        <p class="text-muted">{{ trans('lang.dashboard_total_bookings') }}</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-calendar-check text-primary"></i>
                    </div>
                    <a href="{{ route('bookings.index') }}" class="small-box-footer text-primary">
                        {{ trans('lang.dashboard_more_info') }} <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>

            <!-- Total Earnings -->
            <div class="col-lg-3 col-6 mb-4">
                <div class="small-box bg-white shadow-sm border-start border-4 border-success">
                    <div class="inner">
                        <h3 class="text-{{ setting('theme_color', 'primary') }}">
                            @if(setting('currency_right', false))
                                {{ $earning }}{{ setting('default_currency') }}
                            @else
                                {{ setting('default_currency') }}{{ $earning }}
                            @endif
                        </h3>
                        <p class="text-muted">
                            {{ trans('lang.dashboard_total_earnings') }}
                            <span class="d-block small">({{ trans('lang.dashboard_after taxes') }})</span>
                        </p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-hand-holding-usd text-success"></i>
                    </div>
                    <a href="{{ route('earnings.index') }}" class="small-box-footer text-success">
                        {{ trans('lang.dashboard_more_info') }} <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>

            <!-- Total Salons -->
            <div class="col-lg-3 col-6 mb-4">
                <div class="small-box bg-white shadow-sm border-start border-4 border-warning">
                    <div class="inner">
                        <h3 class="text-{{ setting('theme_color', 'primary') }}">{{ $salonsCount }}</h3>
                        <p class="text-muted">{{ trans('lang.salon_plural') }}</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-users-cog text-warning"></i>
                    </div>
                    <a href="{{ route('salons.index') }}" class="small-box-footer text-warning">
                        {{ trans('lang.dashboard_more_info') }} <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>

            <!-- Total Customers -->
            <div class="col-lg-3 col-6 mb-4">
                <div class="small-box bg-white shadow-sm border-start border-4 border-info">
                    <div class="inner">
                        <h3 class="text-{{ setting('theme_color', 'primary') }}">{{ $membersCount }}</h3>
                        <p class="text-muted">{{ trans('lang.dashboard_total_customers') }}</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-users text-info"></i>
                    </div>
                    <a href="{{ route('users.index') }}" class="small-box-footer text-info">
                        {{ trans('lang.dashboard_more_info') }} <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
        </div>
        <hr class="my-4 border-top border-3 border-default opacity-50 rounded-pill">

        <!-- Earnings Chart and Salons Table -->
        <div class="row">
            <!-- Earnings Chart -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-cyan border-bottom-0  justify-content-between align-items-center">
                        <h3 class="card-title mb-0 text-dark">{{ trans('lang.earning_plural') }}</h3>
                        <a href="{{ route('payments.index') }}" class="btn btn-sm btn-outline-secondary float-right">
                            <i class="fas  fa-angle-right  ml-1 "></i>
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-3">
                            <div>
                                <h4 class="text-{{ setting('theme_color', 'primary') }} mb-0 font-weight-bold">
                                    @if(setting('currency_right', false))
                                        {{ $earning }}{{ setting('default_currency') }}
                                    @else
                                        {{ setting('default_currency') }}{{ $earning }}
                                    @endif
                                </h4>
                                <small class="text-muted">{{ trans('lang.dashboard_earning_over_time') }}</small>
                            </div>
                            <div class="text-right">
                                <h4 class="text-success mb-0">{{ $bookingsCount }}</h4>
                                <small class="text-muted">{{ trans('lang.dashboard_total_bookings') }}</small>
                            </div>
                        </div>
                        <div class="position-relative mb-4">
                            <canvas id="sales-chart" height="200"></canvas>
                        </div>
                        <div class="text-end">
                            <span class="me-2">
                                <i class="fas fa-square" style="color: {{ setting('main_color', '#007bff') }}"></i>
                                {{ trans('lang.dashboard_this_year') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Salons Table -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-cyan border-bottom-0  justify-content-between align-items-center">
                        <h3 class="card-title mb-0 text-dark">{{ trans('lang.salon_plural') }}</h3>
                        <a href="{{ route('salons.index') }}" class="btn btn-sm btn-outline-secondary float-right">
                            <i class="fas  fa-angle-right  ml-1 "></i>
                        </a>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-hover table-striped align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ trans('lang.salon_image') }}</th>
                                    <th>{{ trans('lang.salon') }}</th>
                                    <th>{{ trans('lang.salon_address') }}</th>
                                    <th class="text-center">{{ trans('lang.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($salons as $salon)
                                    <tr>
                                        <td>{!! getMediaColumn($salon, 'image', 'img-circle me-2') !!}</td>
                                        <td>{!! $salon->name !!}</td>
                                        <td>{!! $salon->address->address ?? '' !!}</td>
                                        <td class="text-center">
                                            <a href="{{ route('salons.edit', $salon->id) }}" class="text-muted">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>


@endsection
@push('scripts_lib')
    <script src="{{asset('vendor/chart.js/Chart.min.js')}}"></script>
@endpush
@push('scripts')
    <script type="text/javascript">
        var data = [1000, 2000, 3000, 2500, 2700, 2500, 3000];
        var labels = ['JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'];

        function renderChart(chartNode, data, labels) {
            var ticksStyle = {
                fontColor: '#495057',
                fontStyle: 'bold'
            };

            var mode = 'index';
            var intersect = true;
            return new Chart(chartNode, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            backgroundColor: '{{setting("main_color","#007bff")}}',
                            borderColor: '{{setting("main_color","#007bff")}}',
                            data: data
                        }
                    ]
                },
                options: {
                    maintainAspectRatio: false,
                    tooltips: {
                        mode: mode,
                        intersect: intersect
                    },
                    hover: {
                        mode: mode,
                        intersect: intersect
                    },
                    legend: {
                        display: false
                    },
                    scales: {
                        yAxes: [{
                            // display: false,
                            gridLines: {
                                display: true,
                                lineWidth: '4px',
                                color: 'rgba(0, 0, 0, .2)',
                                zeroLineColor: 'transparent'
                            },
                            ticks: $.extend({
                                beginAtZero: true,

                                // Include a dollar sign in the ticks
                                callback: function (value, index, values) {
                                    @if(setting('currency_right', '0') == '0')
                                        return "{{setting('default_currency')}} " + value;
                                    @else
                                        return value + " {{setting('default_currency')}}";
                                    @endif

                                }
                            }, ticksStyle)
                        }],
                        xAxes: [{
                            display: true,
                            gridLines: {
                                display: false
                            },
                            ticks: ticksStyle
                        }]
                    }
                }
            })
        }

        $(function () {
            'use strict'

            var $salesChart = $('#sales-chart')
            $.ajax({
                url: "{!! $ajaxEarningUrl !!}",
                success: function (result) {
                    $("#loadingMessage").html("");
                    var data = result.data[0];
                    var labels = result.data[1];
                    renderChart($salesChart, data, labels)
                },
                error: function (err) {
                    $("#loadingMessage").html("Error");
                }
            });
            //var salesChart = renderChart($salesChart, data, labels);
        })

    </script>
@endpush
