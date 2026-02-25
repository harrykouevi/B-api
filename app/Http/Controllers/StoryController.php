<?php

namespace App\Http\Controllers;

use App\Criteria\Salons\SalonsOfUserCriteria;
use App\DataTables\StoryDataTable;
use App\Http\Requests\CreateStoryRequest;
use App\Http\Requests\UpdateStoryRequest;
use App\Repositories\PostRepository;
use App\Repositories\StoryRepository;
use App\Repositories\SalonRepository;
use App\Repositories\UploadRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Prettus\Validator\Exceptions\ValidatorException;
use Illuminate\Support\Str;
use Exception;
use Flash;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;


class StoryController extends Controller
{
     /** @var  StoryRepository */
    private StoryRepository $storyRepository;

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
     * @param StoryDataTable $postDataTable
     * @return Response
     */
    public function index(StoryDataTable $storyDataTable): mixed
    {
        return $storyDataTable->render('stories.index');
    }

    /**
     * Store a newly created EService in storage.
     *
     * @param CreateEServiceRequest $request
     *
     * @return RedirectResponse
     */
    public function store(CreateStoryRequest $request): RedirectResponse
    {
        $input = $request->all();
        try {
            //j'envoie la vidéo dans le cloudflare 
            //A la fin de l'enregistrement je recupere l'id de la vidéo et je le stocke dans la table posts
            
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

        Flash::success(__('lang.saved_successfully', ['operator' => __('lang.post')]));

        return redirect(route('stories.index'));
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
       
        return view('stories.create')->with("customFields", $html ?? false)->with("salon", $salon);
    }

    /**
     * Display the specified EService.
     *
     * @param $id
     *
     * @return RedirectResponse|View
     * @throws RepositoryException
     */
    public function show($id): RedirectResponse|View
    {
        $post = null ;
        if(is_numeric($id)){ 
            $post = $this->postRepository->findWithoutFail($id);
        }else if(Str::isUuid($id)){ 
            $post = $this->postRepository->findByField('uuid', $id)->first();
        }

        if (is_null($post)) {
            Flash::error('Story not found');

            return redirect(route('eServices.index'));
        }

        return view('stories.show')->with('post', $post);
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
    public function update(int $id, UpdateStoryRequest $request): RedirectResponse
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
     * @param $id
     *
     * @return RedirectResponse
     * @throws RepositoryException
     */
    public function destroy($id): RedirectResponse
    {
        $post = null ;
        if(is_numeric($id)){ 
            $post = $this->postRepository->findWithoutFail($id);
        }else if(Str::isUuid($id)){ 
            $post = $this->postRepository->findByField('uuid', $id)->first();

          
        }

        if (is_null($post)) {

            Flash::error('Story not found');

            return redirect(route('stories.index'));
        }

        $this->postRepository->delete($post->id);

        Flash::success(__('lang.deleted_successfully', ['operator' => __('lang.post')]));

        return redirect(route('stories.index'));
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
