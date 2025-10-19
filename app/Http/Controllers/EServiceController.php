<?php
/*
 * File name: EServiceController.php
 * Last modified: 2024.04.18 at 17:22:51
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Http\Controllers;

use App\Criteria\EServices\EServicesOfUserCriteria;
use App\Criteria\Salons\SalonsOfUserCriteria;
use App\DataTables\EServiceDataTable;
use App\Http\Requests\CreateEServiceRequest;
use App\Http\Requests\UpdateEServiceRequest;
use App\Repositories\CategoryRepository;
use App\Repositories\CustomFieldRepository;
use App\Repositories\EServiceRepository;
use App\Repositories\SalonRepository;
use App\Repositories\ServiceTemplateRepository;
use App\Repositories\UploadRepository;
use App\Services\CategoryTemplateService;
use Exception;
use Flash;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;
use Prettus\Repository\Exceptions\RepositoryException;
use Prettus\Validator\Exceptions\ValidatorException;

class EServiceController extends Controller
{
    /** @var  EServiceRepository */
    private EServiceRepository $eServiceRepository;

    /**
     * @var CustomFieldRepository
     */
    private CustomFieldRepository $customFieldRepository;

    /**
     * @var UploadRepository
     */
    private UploadRepository $uploadRepository;
    /**
     * @var CategoryRepository
     */
    private CategoryRepository $categoryRepository;
    /**
     * @var SalonRepository
     */
    private SalonRepository $salonRepository;

    /**
     * @var CategoryTemplateService
     */
    private CategoryTemplateService $categoryTemplateService;


     /**
     * @var ServiceTemplateRepository
     */
    private ServiceTemplateRepository $serviceTemplateRepository;

    public function __construct(CategoryTemplateService $categoryTemplateService ,EServiceRepository $eServiceRepo, CustomFieldRepository $customFieldRepo, UploadRepository $uploadRepo
        , CategoryRepository                       $categoryRepo
        , ServiceTemplateRepository  $serviceTemplateRepo
        , SalonRepository                          $salonRepo)
    {
        parent::__construct();
        $this->eServiceRepository = $eServiceRepo;
        $this->customFieldRepository = $customFieldRepo;
        $this->uploadRepository = $uploadRepo;
        $this->categoryRepository = $categoryRepo;
        $this->salonRepository = $salonRepo;
        $this->serviceTemplateRepository = $serviceTemplateRepo;
        $this->categoryTemplateService = $categoryTemplateService;
    }

    /**
     * Display a listing of the EService.
     *
     * @param EServiceDataTable $eServiceDataTable
     * @return Response
     */
    public function index(EServiceDataTable $eServiceDataTable): mixed
    {
        return $eServiceDataTable->render('e_services.index');
    }

    /**
     * Store a newly created EService in storage.
     *
     * @param CreateEServiceRequest $request
     *
     * @return RedirectResponse
     */
    public function store(CreateEServiceRequest $request): RedirectResponse
    {
        $input = $request->all();
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->eServiceRepository->model());
        try {
            // Get the service template
            $template = $this->serviceTemplateRepository->findWithoutFail($input['template_id'] ?? null);
            if($template){
                $input['name'] = $template->name ;
                $input['categories'] = [$template->category_id] ;
            }else{
                $input['categories'] = ($input['category_id'])? [$input['category_id']] : [];
            }

            $eService = $this->eServiceRepository->create($input);
            $eService->customFieldsValues()->createMany(getCustomFieldsValues($customFields, $request));
            if (isset($input['image']) && $input['image'] && is_array($input['image'])) {
                foreach ($input['image'] as $fileUuid) {
                    $cacheUpload = $this->uploadRepository->getByUuid($fileUuid);
                    $mediaItem = $cacheUpload->getMedia('image')->first();
                    $mediaItem->copy($eService, 'image');
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
        // $category = $this->categoryRepository->pluck('name', 'id');
        $allcategory = $this->categoryTemplateService->getRootCategoriesWithChildren( true ) ;    
        $category = $this->categoryTemplateService->flattenCategoriesForAdminFront($allcategory) ;
        $salon = $this->salonRepository->getByCriteria(new SalonsOfUserCriteria(auth()->id()))->pluck('name', 'id');
        $category_services = $this->categoryTemplateService->flattenTemplatesForAdminFront($allcategory) ;
        $categoriesSelected = [];
        $hasCustomField = in_array($this->eServiceRepository->model(), setting('custom_field_models', []));
        if ($hasCustomField) {
            $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->eServiceRepository->model());
            $html = generateCustomField($customFields);
        }
        return view('e_services.create')->with("customFields", $html ?? false)->with("category", $category)->with("category_services", $category_services)->with("categoriesSelected", $categoriesSelected)->with("salon", $salon);
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
        $this->eServiceRepository->pushCriteria(new EServicesOfUserCriteria(auth()->id()));
        $eService = $this->eServiceRepository->findWithoutFail($id);

        if (empty($eService)) {
            Flash::error('E Service not found');

            return redirect(route('eServices.index'));
        }

        return view('e_services.show')->with('eService', $eService);
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
        $this->eServiceRepository->pushCriteria(new EServicesOfUserCriteria(auth()->id()));
        $eService = $this->eServiceRepository->findWithoutFail($id);
        if (empty($eService)) {
            Flash::error(__('lang.not_found', ['operator' => __('lang.e_service')]));

            return redirect(route('eServices.index'));
        }
        $allcategory = $this->categoryTemplateService->getRootCategoriesWithChildren( true ) ;    
        $category = $this->categoryTemplateService->flattenCategoriesForAdminFront($allcategory) ;
        $category_services = $this->categoryTemplateService->flattenTemplatesForAdminFront($allcategory) ;
        
        $salon = $this->salonRepository->getByCriteria(new SalonsOfUserCriteria(auth()->id()))->pluck('name', 'id');
        $categoriesSelected = $eService->categories()->pluck('categories.id')->toArray();

        $customFieldsValues = $eService->customFieldsValues()->with('customField')->get();
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->eServiceRepository->model());
        $hasCustomField = in_array($this->eServiceRepository->model(), setting('custom_field_models', []));
        if ($hasCustomField) {
            $html = generateCustomField($customFields, $customFieldsValues);
        }

        return view('e_services.edit')->with('eService', $eService)->with("category_services", $category_services)->with("customFields", $html ?? false)->with("category", $category)->with("categoriesSelected", $categoriesSelected)->with("salon", $salon);
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
    public function update(int $id, UpdateEServiceRequest $request): RedirectResponse
    {
        $this->eServiceRepository->pushCriteria(new EServicesOfUserCriteria(auth()->id()));
        $eService = $this->eServiceRepository->findWithoutFail($id);

        if (empty($eService)) {
            Flash::error('E Service not found');
            return redirect(route('eServices.index'));
        }

       

        $input = $request->all();
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->eServiceRepository->model());
        try {

            // Get the service template
            $template = $this->serviceTemplateRepository->findWithoutFail($input['template_id'] ?? null);
            if($template){
                $input['name'] = $template->name ;
                $input['categories'] = [$template->category_id] ;

            }else{
                $input['categories'] = ($input['category_id'])? [$input['category_id']] : [];
            }
            
            $eService = $this->eServiceRepository->update($input, $id);
            if (isset($input['image']) && $input['image'] && is_array($input['image'])) {
                foreach ($input['image'] as $fileUuid) {
                    $cacheUpload = $this->uploadRepository->getByUuid($fileUuid);
                    $mediaItem = $cacheUpload->getMedia('image')->first();
                    $mediaItem->copy($eService, 'image');
                }
            }
            foreach (getCustomFieldsValues($customFields, $request) as $value) {
                $eService->customFieldsValues()
                    ->updateOrCreate(['custom_field_id' => $value['custom_field_id']], $value);
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
        $this->eServiceRepository->pushCriteria(new EServicesOfUserCriteria(auth()->id()));
        $eService = $this->eServiceRepository->findWithoutFail($id);

        if (empty($eService)) {
            Flash::error('E Service not found');

            return redirect(route('eServices.index'));
        }

        $this->eServiceRepository->delete($id);

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
        $eService = $this->eServiceRepository->findWithoutFail($input['id']);
        try {
            if ($eService->hasMedia($input['collection'])) {
                $eService->getFirstMedia($input['collection'])->delete();
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
    }
}
