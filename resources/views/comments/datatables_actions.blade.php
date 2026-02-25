<div class='btn-group btn-group-sm'>
    @can('comments.show')
        <a data-toggle="tooltip" data-placement="left"  href="{{ route('comments.show', $id) }}" class='btn btn-link'>
            <i class="fas fa-eye"></i> </a> @endcan

    {{-- @can('comments.edit')
        <a data-toggle="tooltip" data-placement="left"  href="{{ route('comments.edit', $id) }}" class='btn btn-link'>
            <i class="fas fa-edit"></i> </a> @endcan --}}

    @can('comments.destroy') {!! Form::open(['route' => ['comments.destroy', $id], 'method' => 'delete']) !!} {!! Form::button('<i class="fas fa-trash"></i>', [ 'type' => 'submit', 'class' => 'btn btn-link text-danger', 'onclick' => "return confirm('Are you sure?')" ]) !!} {!! Form::close() !!} @endcan
</div>
