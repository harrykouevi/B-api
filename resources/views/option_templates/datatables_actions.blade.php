<div class='btn-group btn-group-sm'>
    @can('option-templates.show')
        <a data-toggle="tooltip" data-placement="left" title="{{trans('lang.view_details')}}" href="{{ route('option-templates.show', $id) }}" class='btn btn-link'>
            <i class="fas fa-eye"></i> </a> @endcan

    @can('option-templates.edit')
        <a data-toggle="tooltip" data-placement="left" title="{{trans('lang.option_edit')}}" href="{{ route('option-templates.edit', $id) }}" class='btn btn-link'>
            <i class="fas fa-edit"></i> </a> @endcan

    @can('option-templates.destroy') {!! Form::open(['route' => ['option-templates.destroy', $id], 'method' => 'delete']) !!} {!! Form::button('<i class="fas fa-trash"></i>', [ 'type' => 'submit', 'class' => 'btn btn-link text-danger', 'onclick' => "return confirm('Are you sure?')" ]) !!} {!! Form::close() !!} @endcan
</div>
