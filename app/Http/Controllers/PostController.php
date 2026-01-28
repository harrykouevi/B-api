<?php

namespace App\Http\Controllers;

use App\Criteria\Salons\SalonsOfUserCriteria;
use App\DataTables\PostDataTable;
use App\Http\Requests\CreatePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Repositories\PostRepository;
use App\Repositories\SalonRepository;
use App\Repositories\UploadRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Prettus\Validator\Exceptions\ValidatorException;
use Exception;
use Flash;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;


class PostController extends Controller
{
     /** @var  PostRepository */
    private PostRepository $postRepository;

    /**
     * @var UploadRepository
     */
    private UploadRepository $uploadRepository;
   
    /**
     * @var SalonRepository
     */
    private SalonRepository $salonRepository;

 
    public function __construct(PostRepository $postRepo, UploadRepository $uploadRepo
        , SalonRepository                          $salonRepo)
    {
        parent::__construct();
        $this->postRepository = $postRepo;
        $this->uploadRepository = $uploadRepo;
        $this->salonRepository = $salonRepo;
    }

    /*
     * @param PostDataTable $postDataTable
     * @return Response
     */
    public function index(PostDataTable $postDataTable): mixed
    {
        return $postDataTable->render('posts.index');
    }

    /**
     * Store a newly created EService in storage.
     *
     * @param CreateEServiceRequest $request
     *
     * @return RedirectResponse
     */
    public function store(CreatePostRequest $request): RedirectResponse
    {
        $input = $request->all();
        try {
            
            
            $post = $this->postRepository->create($input);
            if (isset($input['image']) && $input['image'] && is_array($input['image'])) {
                foreach ($input['image'] as $fileUuid) {
                    $cacheUpload = $this->uploadRepository->getByUuid($fileUuid);
                    $mediaItem = $cacheUpload->getMedia('image')->first();
                    $mediaItem->copy($post, 'image');
                }
            }
        } catch (ValidatorException $e) {
            Flash::error($e->getMessage());
        }

        Flash::success(__('lang.saved_successfully', ['operator' => __('lang.e_service')]));

        return redirect(route('eServices.index'));
    }

    /**
     * Show the form for creating a new EService.
     *
     * @return View
     */
    public function create(): View
    {
        $salon = $this->salonRepository->getByCriteria(new SalonsOfUserCriteria(auth()->id()))->pluck('name', 'id');
        $hasCustomField = in_array($this->postRepository->model(), setting('custom_field_models', []));
       
        return view('posts.create')->with("customFields", $html ?? false)->with("salon", $salon);
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
        $this->postRepository->pushCriteria(new EServicesOfUserCriteria(auth()->id()));
        $post = $this->postRepository->findWithoutFail($id);

        if (empty($post)) {
            Flash::error('E Service not found');

            return redirect(route('eServices.index'));
        }

        return view('e_services.show')->with('eService', $post);
    }

    /**
     * Show the form for editing the specified EService.
     *
     * @param int $id
     *
     * @return RedirectResponse|View
     * @throws RepositoryException
     */
    public function edit(int $id): RedirectResponse|View
    {
        $this->postRepository->pushCriteria(new EServicesOfUserCriteria(auth()->id()));
        $post = $this->postRepository->findWithoutFail($id);
        if (empty($post)) {
            Flash::error(__('lang.not_found', ['operator' => __('lang.e_service')]));

            return redirect(route('eServices.index'));
        }
        
        $salon = $this->salonRepository->getByCriteria(new SalonsOfUserCriteria(auth()->id()))->pluck('name', 'id');

    
        return view('e_services.edit')->with('eService', $post)->with("customFields", $html ?? false)->with("salon", $salon);
    }

    /**
     * Update the specified EService in storage.
     *
     * @param int $id
     * @param UpdateEServiceRequest $request
     *
     * @return RedirectResponse
     * @throws RepositoryException
     */
    public function update(int $id, UpdatePostRequest $request): RedirectResponse
    {
        $this->postRepository->pushCriteria(new EServicesOfUserCriteria(auth()->id()));
        $post = $this->postRepository->findWithoutFail($id);

        if (empty($post)) {
            Flash::error('E Service not found');
            return redirect(route('eServices.index'));
        }

       

        $input = $request->all();
        try {

           
            
            $post = $this->postRepository->update($input, $id);
            if (isset($input['image']) && $input['image'] && is_array($input['image'])) {
                foreach ($input['image'] as $fileUuid) {
                    $cacheUpload = $this->uploadRepository->getByUuid($fileUuid);
                    $mediaItem = $cacheUpload->getMedia('image')->first();
                    $mediaItem->copy($post, 'image');
                }
            }
           
        } catch (ValidatorException $e) {
            Flash::error($e->getMessage());
        }

        Flash::success(__('lang.updated_successfully', ['operator' => __('lang.e_service')]));

        return redirect(route('eServices.index'));
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
        $this->postRepository->pushCriteria(new EServicesOfUserCriteria(auth()->id()));
        $post = $this->postRepository->findWithoutFail($id);

        if (empty($post)) {
            Flash::error('E Service not found');

            return redirect(route('eServices.index'));
        }

        $this->postRepository->delete($id);

        Flash::success(__('lang.deleted_successfully', ['operator' => __('lang.e_service')]));

        return redirect(route('eServices.index'));
    }

    /**
     * Remove Media of EService
     * @param Request $request
     */
    public function removeMedia(Request $request): void
    {
        $input = $request->all();
        $post = $this->postRepository->findWithoutFail($input['id']);
        try {
            if ($post->hasMedia($input['collection'])) {
                $post->getFirstMedia($input['collection'])->delete();
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
    }
}
