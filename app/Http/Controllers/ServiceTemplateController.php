<?php
/*
 * File name: ServiceTemplateController.php
 * Last modified: 2024.04.18 at 17:22:51
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Http\Controllers;

use App\Http\Requests\CreateServiceTemplateRequest;
use App\Http\Requests\UpdateServiceTemplateRequest;
use App\Repositories\ServiceTemplateRepository;
use App\Repositories\CategoryRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Prettus\Validator\Exceptions\ValidatorException;
use App\Criteria\Categories\CategoriesDescendantsCriteria;
use App\Criteria\EServices\EServicesOfUserCriteria;
use App\Criteria\Salons\SalonsOfUserCriteria;
use App\DataTables\ModelServiceDataTable;
use App\Http\Requests\CreateEServiceRequest;
use App\Http\Requests\UpdateEServiceRequest;
use App\Repositories\CustomFieldRepository;
use App\Repositories\SalonRepository;
use App\Repositories\UploadRepository;
use App\Services\categoryTemplateService;
use Exception;
use Flash;
use InfyOm\Generator\Criteria\LimitOffsetCriteria;
use Prettus\Repository\Exceptions\RepositoryException;

class ServiceTemplateController extends Controller
{
    /** @var  ServiceTemplateRepository */
    private ServiceTemplateRepository $serviceTemplateRepository;

    /** @var  CategoryRepository */
    private CategoryRepository $categoryRepository;

   
   

    /**
     * @var CustomFieldRepository
     */
    private CustomFieldRepository $customFieldRepository;

    /**
     * @var UploadRepository
     */
    private UploadRepository $uploadRepository;

    /**
     * @var SalonRepository
     */
    private SalonRepository $salonRepository;

    /**
     * @var CategoryTemplateService
     */
    private CategoryTemplateService $categoryTemplateService;


     public function __construct(ServiceTemplateRepository $serviceTemplateRepo, CustomFieldRepository $customFieldRepo, UploadRepository $uploadRepo
        , CategoryRepository                       $categoryRepo
        , categoryTemplateService $categoryTemplateService
        , SalonRepository                          $salonRepo)
    {
        parent::__construct();
        $this->serviceTemplateRepository = $serviceTemplateRepo;
        $this->categoryRepository = $categoryRepo;
        $this->customFieldRepository = $customFieldRepo;
        $this->uploadRepository = $uploadRepo;
        $this->categoryRepository = $categoryRepo;
        $this->salonRepository = $salonRepo;

        $this->categoryTemplateService = $categoryTemplateService;
    }


   
    /**
     * Display a listing of the ModelService.
     *
     * @param ModelServiceDataTable $modelServiceDataTable
     * @return mixed
     */
    public function index(ModelServiceDataTable $modelServiceDataTable): mixed
    {
        return $modelServiceDataTable->render('service_templates.index');
    }

    /**
     * Store a newly created EService in storage.
     *
     * @param CreateServiceTemplateRequest $request
     *
     * @return RedirectResponse
     */
    public function store(CreateServiceTemplateRequest $request): RedirectResponse
    {
        $input = $request->all();
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->serviceTemplateRepository->model());
        try {
            $service = $this->serviceTemplateRepository->create($input);
            $service->customFieldsValues()->createMany(getCustomFieldsValues($customFields, $request));
            if (isset($input['image']) && $input['image'] && is_array($input['image'])) {
                foreach ($input['image'] as $fileUuid) {
                    $cacheUpload = $this->uploadRepository->getByUuid($fileUuid);
                    $mediaItem = $cacheUpload->getMedia('image')->first();
                    $mediaItem->copy($service, 'image');
                }
            }
        } catch (ValidatorException $e) {
            Flash::error($e->getMessage());
        }

        Flash::success(__('lang.saved_successfully', ['operator' => __('lang.e_service')]));

        return redirect(route('model-services.index'));
    }

    /**
     * Show the form for creating a new EService.
     *
     * @return View
     */
    public function create(): View
    {
     
        $allcategory = $this->categoryTemplateService->getRootCategoriesWithChildren( false ) ;    
        $category = $this->categoryTemplateService->flattenCategoriesForAdminFront($allcategory) ;
      
        $categoriesSelected = [];
        $hasCustomField = in_array($this->serviceTemplateRepository->model(), setting('custom_field_models', []));
        if ($hasCustomField) {
            $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->serviceTemplateRepository->model());
            $html = generateCustomField($customFields);
        }
        return view('service_templates.create')->with("customFields", $html ?? false)->with("category", $category)->with("categoriesSelected", $categoriesSelected);
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
        $this->serviceTemplateRepository->pushCriteria(new EServicesOfUserCriteria(auth()->id()));
        $eService = $this->serviceTemplateRepository->findWithoutFail($id);

        if (empty($eService)) {
            Flash::error('E Service not found');

            return redirect(route('model-services.index'));
        }

        return view('service_templates.show')->with('eService', $eService);
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
        $eService = $this->serviceTemplateRepository->findWithoutFail($id);
        
        if (empty($eService)) {
            Flash::error(__('lang.not_found', ['operator' => __('lang.e_service')]));

            return redirect(route('model-services.index'));
        }

        $allcategory = $this->categoryTemplateService->getRootCategoriesWithChildren( false ) ;    
        $category = $this->categoryTemplateService->flattenCategoriesForAdminFront($allcategory) ;
      
        
        $categoriesSelected = [$eService->category_id];

        $customFieldsValues = $eService->customFieldsValues()->with('customField')->get();
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->serviceTemplateRepository->model());
        $hasCustomField = in_array($this->serviceTemplateRepository->model(), setting('custom_field_models', []));
        if ($hasCustomField) {
            $html = generateCustomField($customFields, $customFieldsValues);
        }

        return view('service_templates.edit')->with('eService', $eService)->with("customFields", $html ?? false)->with("category", $category)->with("categoriesSelected", $categoriesSelected);
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
    public function update(int $id, UpdateServiceTemplateRequest $request): RedirectResponse
    {
        $eService = $this->serviceTemplateRepository->findWithoutFail($id);

        if (empty($eService)) {
            Flash::error('E Service not found');
            return redirect(route('eServices.index'));
        }
        $input = $request->all();
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->serviceTemplateRepository->model());
        try {
            // $input['categories'] = $input['categories'] ?? [];
            $eService = $this->serviceTemplateRepository->update($input, $id);
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

        Flash::success(__('lang.updated_successfully', ['operator' => __('lang.model_service')]));

        return redirect(route('model-services.index'));
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
        $this->serviceTemplateRepository->pushCriteria(new EServicesOfUserCriteria(auth()->id()));
        $eService = $this->serviceTemplateRepository->findWithoutFail($id);

        if (empty($eService)) {
            Flash::error('E Service not found');

            return redirect(route('model-services.index'));
        }

        $this->serviceTemplateRepository->delete($id);

        Flash::success(__('lang.deleted_successfully', ['operator' => __('lang.e_service')]));

        return redirect(route('model-services.index'));
    }

    /**
     * Remove Media of EService
     * @param Request $request
     */
    public function removeMedia(Request $request): void
    {
        $input = $request->all();
        $eService = $this->serviceTemplateRepository->findWithoutFail($input['id']);
        try {
            if ($eService->hasMedia($input['collection'])) {
                $eService->getFirstMedia($input['collection'])->delete();
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
    }
}
