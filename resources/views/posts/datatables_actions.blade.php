<div class='btn-group btn-group-sm'>
    @can('posts.show')
        <a data-toggle="tooltip" data-placement="left"  href="{{ route('posts.show', $row->id) }}" class='btn btn-link'>
            <i class="fas fa-eye"></i> </a> @endcan

    {{-- @can('posts.edit')
        <a data-toggle="tooltip" data-placement="left"  href="{{ route('posts.edit', $id) }}" class='btn btn-link'>
            <i class="fas fa-edit"></i> </a> @endcan --}}

    @can('posts.destroy') {!! Form::open(['route' => ['posts.destroy', $id], 'method' => 'delete']) !!} {!! Form::button('<i class="fas fa-trash"></i>', [ 'type' => 'submit', 'class' => 'btn btn-link text-danger', 'onclick' => "return confirm('Are you sure?')" ]) !!} {!! Form::close() !!} @endcan
</div>
 
