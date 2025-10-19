<div class='btn-group btn-group-sm'>
    @can('eServices.show')
        <a data-toggle="tooltip" data-placement="left"  href="{{ route('eServices.show', $id) }}" class='btn btn-link'>
            <i class="fas fa-eye"></i> </a> @endcan

    @can('eServices.edit')
        <a data-toggle="tooltip" data-placement="left"  href="{{ route('eServices.edit', $id) }}" class='btn btn-link'>
            <i class="fas fa-edit"></i> </a> @endcan

    @can('eServices.destroy') {!! Form::open(['route' => ['eServices.destroy', $id], 'method' => 'delete']) !!} {!! Form::button('<i class="fas fa-trash"></i>', [ 'type' => 'submit', 'class' => 'btn btn-link text-danger', 'onclick' => "return confirm('Are you sure?')" ]) !!} {!! Form::close() !!} @endcan
</div>
