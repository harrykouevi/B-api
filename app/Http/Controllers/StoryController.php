<?php

namespace App\Http\Controllers;

use App\Criteria\Salons\SalonsOfUserCriteria;
use App\DataTables\StoryDataTable;
use App\Events\AttachModelToVideoUploadEvent;
use App\Http\Requests\CreateStoryRequest;
use App\Http\Requests\UpdateStoryRequest;
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

   
    /**
     * @var UploadRepository
     */
    private UploadRepository $uploadRepository;
   
    /**
     * @var SalonRepository
     */
    private SalonRepository $salonRepository;

 
    public function __construct(StoryRepository $storyRepo, UploadRepository $uploadRepo)
    {
        parent::__construct();
        $this->storyRepository = $storyRepo;
        $this->uploadRepository = $uploadRepo;
    }

    /*
     * @param StoryDataTable $storyDataTable
     * @return Response
     */
    public function index(StoryDataTable $storyDataTable): mixed
    {
        return $storyDataTable->render('stories.index');
    }

    /**
     * Store a newly created EService in storage.
     *
     * @return RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        $input = $request->all();
        try {
            
            $story = $this->storyRepository->create($input);
            if (isset($input['image']) && $input['image'] && is_array($input['image'])) {
                foreach ($input['image'] as $fileUuid) {
                    
                    event(new AttachModelToVideoUploadEvent($fileUuid, $story));
                }
            }
        } catch (ValidatorException $e) {
            Flash::error($e->getMessage());
        }

        Flash::success(__('lang.saved_successfully', ['operator' => __('lang.story')]));

        return redirect(route('stories.index'));
    }

    /**
     * Show the form for creating a new EService.
     *
     * @return View
     */
    public function create(): View
    {
        $hasCustomField = in_array($this->storyRepository->model(), setting('custom_field_models', []));
       
        return view('stories.create')->with("customFields", $html ?? false);
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
        $story = null ;
        if(is_numeric($id)){ 
            $story = $this->storyRepository->findWithoutFail($id);
        }else if(Str::isUuid($id)){ 
            $story = $this->storyRepository->findByField('uuid', $id)->first();
        }

        if (is_null($story)) {
            Flash::error('Story not found');

            return redirect(route('stories.index'));
        }

        return view('stories.show')->with('story', $story);
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
        $this->storyRepository->pushCriteria(new EServicesOfUserCriteria(auth()->id()));
        $story = $this->storyRepository->findWithoutFail($id);
        if (empty($story)) {
            Flash::error(__('lang.not_found', ['operator' => __('lang.e_service')]));

            return redirect(route('eServices.index'));
        }
        
        $salon = $this->salonRepository->getByCriteria(new SalonsOfUserCriteria(auth()->id()))->pluck('name', 'id');

    
        return view('e_services.edit')->with('eService', $story)->with("customFields", $html ?? false)->with("salon", $salon);
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
        $this->storyRepository->pushCriteria(new EServicesOfUserCriteria(auth()->id()));
        $story = $this->storyRepository->findWithoutFail($id);

        if (empty($story)) {
            Flash::error('E Service not found');
            return redirect(route('eServices.index'));
        }

       

        $input = $request->all();
        try {

           
            
            $story = $this->storyRepository->update($input, $id);
            if (isset($input['image']) && $input['image'] && is_array($input['image'])) {
                foreach ($input['image'] as $fileUuid) {
                    $cacheUpload = $this->uploadRepository->getByUuid($fileUuid);
                    $mediaItem = $cacheUpload->getMedia('image')->first();
                    $mediaItem->copy($story, 'image');
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
        $story = null ;
        if(is_numeric($id)){ 
            $story = $this->storyRepository->findWithoutFail($id);
        }else if(Str::isUuid($id)){ 
            $story = $this->storyRepository->findByField('uuid', $id)->first();

          
        }

        if (is_null($story)) {

            Flash::error('Story not found');

            return redirect(route('stories.index'));
        }

        $this->storyRepository->delete($story->id);

        Flash::success(__('lang.deleted_successfully', ['operator' => __('lang.story')]));

        return redirect(route('stories.index'));
    }

    /**
     * Remove Media of EService
     * @param Request $request
     */
    public function removeMedia(Request $request): void
    {
        $input = $request->all();
        $story = $this->storyRepository->findWithoutFail($input['id']);
        try {
            if ($story->hasMedia($input['collection'])) {
                $story->getFirstMedia($input['collection'])->delete();
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
    }
}
