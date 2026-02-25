<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\DataTables\CommentDataTable;
use App\Repositories\CommentRepository;
use Illuminate\Http\RedirectResponse;
use Prettus\Validator\Exceptions\ValidatorException;
use Exception;
use Flash;
use Illuminate\View\View;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;


class CommentController extends Controller
{
      /** @var  CommentRepository */
    private CommentRepository $commentRepository;

 
  
 
    public function __construct(CommentRepository $commentRepo)
    {
        parent::__construct();
        $this->commentRepository = $commentRepo;
    }

    /*
     * @param CommentDataTable $commentDataTable
     * @return Response
     */
    public function index(CommentDataTable $commentDataTable): mixed
    {
        return $commentDataTable->render('comments.index');
    }


   
    /**
     * Display the specified EService.
     *
     * @param int $id
     *
     * @return RedirectResponse|View
     * @throws RepositoryException
     */
    public function show(int $id): RedirectResponse|View
    {
        // $this->commentRepository->pushCriteria(new EServicesOfUserCriteria(auth()->id()));
        $comment = $this->commentRepository->findWithoutFail($id);

        if (empty($comment)) {
            Flash::error('E Service not found');

            return redirect(route('comments.index'));
        }

        return view('comments.show')->with('comment', $comment);
    }

   

    

    /**
     * Remove the specified EService from storage.
     *
     * @param int $id
     *
     * @return RedirectResponse
     * @throws RepositoryException
     */
    public function destroy(int $id): RedirectResponse
    {
        // $this->commentRepository->pushCriteria(new EServicesOfUserCriteria(auth()->id()));
        $comment = $this->commentRepository->findWithoutFail($id);

        if (empty($comment)) {
            Flash::error('Comment not found');

            return redirect(route('comments.index'));
        }

        $this->commentRepository->delete($id);

        Flash::success(__('lang.deleted_successfully', ['operator' => __('lang.comment')]));

        return redirect(route('comments.index'));
    }

    
}
