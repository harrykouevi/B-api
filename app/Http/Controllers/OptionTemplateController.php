<?php
/*
 * File name: OptionTemplateController.php
 * Last modified: 2024.04.18 at 17:22:51
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Http\Controllers;

use App\Http\Requests\CreateOptionTemplateRequest;
use App\Http\Requests\UpdateOptionTemplateRequest;
use App\Repositories\OptionTemplateRepository;
use App\Repositories\ServiceTemplateRepository;
use App\DataTables\OptionDataTable;
use App\DataTables\OptionTemplateDataTable;
use App\Repositories\CustomFieldRepository;
use App\Repositories\OptionGroupRepository;
use App\Repositories\UploadRepository;
use App\Services\CategoryTemplateService;
use Flash;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Prettus\Validator\Exceptions\ValidatorException;

class OptionTemplateController extends Controller
{
    /**
     * @var CustomFieldRepository
     */
    private CustomFieldRepository $customFieldRepository;

    /**
     * @var UploadRepository
     */
    private UploadRepository $uploadRepository;
    
     /**
     * @var OptionGroupRepository
     */
    private OptionGroupRepository $optionGroupRepository;


    /** @var  OptionTemplateRepository */
    private OptionTemplateRepository $optionTemplateRepository;

    /** @var  ServiceTemplateRepository */
    private ServiceTemplateRepository $serviceTemplateRepository;

     /**
     * @var CategoryTemplateService
     */
    private CategoryTemplateService $categoryTemplateService;

    public function __construct(OptionTemplateRepository $optionTemplateRepo, ServiceTemplateRepository $serviceTemplateRepo
    , CategoryTemplateService $categoryTemplateService 
    , CustomFieldRepository $customFieldRepo
    , OptionGroupRepository                 $optionGroupRepo

    , UploadRepository $uploadRepo)
    {
        parent::__construct();
        $this->optionTemplateRepository = $optionTemplateRepo;
        $this->serviceTemplateRepository = $serviceTemplateRepo;
        $this->customFieldRepository = $customFieldRepo;
        $this->categoryTemplateService = $categoryTemplateService;
        $this->optionGroupRepository = $optionGroupRepo;

        $this->uploadRepository = $uploadRepo;


    }

    

    /**
     * Display a listing of the ModelService.
     *
     * @param ModelServiceDataTable $modelServiceDataTable
     * @return mixed
     */
    public function index(OptionTemplateDataTable $optionTemplateDataTable): mixed
    {
        return $optionTemplateDataTable->render('option_templates.index');
    }

    /**
     * Store a newly created OptionTemplate in storage.
     *
     * @param CreateOptionTemplateRequest $request
     *
     * @return RedirectResponse
     */
    public function store(CreateOptionTemplateRequest $request): RedirectResponse
    {
        $input = $request->all();
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->optionTemplateRepository->model());
        
        try {
            $optionTemplate = $this->optionTemplateRepository->create($input);
            $optionTemplate->customFieldsValues()->createMany(getCustomFieldsValues($customFields, $request));

        } catch (ValidatorException $e) {
            Flash::error($e->getMessage());
        }

        Flash::success(__('lang.saved_successfully', ['operator' => __('lang.option_template')]));

        return redirect(route('option-templates.index'));
    }

    /**
     * Show the form for creating a new OptionTemplate.
     *
     * @return View
     */
    public function create(): View
    {
        $serviceTemplates = $this->serviceTemplateRepository->pluck('name', 'id');
        
        $allcategory = $this->categoryTemplateService->getRootCategoriesWithChildren( true ) ;    
        $category_services = $this->categoryTemplateService->flattenTemplatesForAdminFront($allcategory) ;
        $optionGroup = $this->optionGroupRepository->pluck('name', 'id');
        

        $hasCustomField = in_array($this->optionTemplateRepository->model(), setting('custom_field_models', []));
        if ($hasCustomField) {
            $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->optionTemplateRepository->model());
            $html = generateCustomField($customFields);
        }
        return view('option_templates.create')->with("customFields", $html ?? false)->with("category_services", $category_services)
        ->with("selectedServicetemplate", [])
        ->with("optionGroup", $optionGroup)
        ->with('serviceTemplates', $serviceTemplates);
    }

    /**
     * Display the specified OptionTemplate.
     *
     * @param int $id
     *
     * @return RedirectResponse|View
     */
    public function show(int $id): RedirectResponse|View
    {
        $optionTemplate = $this->optionTemplateRepository->findWithoutFail($id);

        if (empty($optionTemplate)) {
            Flash::error('Option Template not found');

            return redirect(route('option-templates.index'));
        }

        return view('option_templates.show')->with('optionTemplate', $optionTemplate);
    }

    /**
     * Show the form for editing the specified OptionTemplate.
     *
     * @param int $id
     *
     * @return RedirectResponse|View
     */
    public function edit(int $id): RedirectResponse|View
    {
        $optionTemplate = $this->optionTemplateRepository->findWithoutFail($id);
        
        if (empty($optionTemplate)) {
            Flash::error(__('lang.not_found', ['operator' => __('lang.option_template')]));

            return redirect(route('option-templates.index'));
        }
        $selectedServicetemplate = $optionTemplate->serviceTemplate()->pluck('id')->toArray();
        $allcategory = $this->categoryTemplateService->getRootCategoriesWithChildren( true ) ;    
        $category_services = $this->categoryTemplateService->flattenTemplatesForAdminFront($allcategory) ;
        
        $optionGroup = $this->optionGroupRepository->pluck('name', 'id');

        $customFieldsValues = $optionTemplate->customFieldsValues()->with('customField')->get();
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->optionTemplateRepository->model());
        $hasCustomField = in_array($this->optionTemplateRepository->model(), setting('custom_field_models', []));
        if ($hasCustomField) {
            $html = generateCustomField($customFields,$customFieldsValues);
        }

        return view('option_templates.edit')->with('optionTemplate', $optionTemplate)
        ->with("customFields", $html ?? false)
        ->with("optionGroup", $optionGroup)
        ->with("selectedServicetemplate", $selectedServicetemplate)
        ->with("category_services", $category_services);
    }

    /**
     * Update the specified OptionTemplate in storage.
     *
     * @param int $id
     * @param UpdateOptionTemplateRequest $request
     *
     * @return RedirectResponse
     */
    public function update(int $id, UpdateOptionTemplateRequest $request): RedirectResponse
    {
        $optionTemplate = $this->optionTemplateRepository->findWithoutFail($id);

        if (empty($optionTemplate)) {
            Flash::error('Option Template not found');
            return redirect(route('option-templates.index'));
        }
        
        $input = $request->all();
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->optionTemplateRepository->model());

        try {
            $optionTemplate = $this->optionTemplateRepository->update($input, $id);

            if (isset($input['image']) && $input['image']) {
                $cacheUpload = $this->uploadRepository->getByUuid($input['image']);
                $mediaItem = $cacheUpload->getMedia('image')->first();
                $mediaItem = $mediaItem->forgetCustomProperty('generated_conversions');
                $mediaItem->copy($option, 'image');
            }
            foreach (getCustomFieldsValues($customFields, $request) as $value) {
                $option->customFieldsValues()
                    ->updateOrCreate(['custom_field_id' => $value['custom_field_id']], $value);
            }
        } catch (ValidatorException $e) {
            Flash::error($e->getMessage());
        }

        Flash::success(__('lang.updated_successfully', ['operator' => __('lang.option_template')]));

        return redirect(route('option-templates.index'));
    }

    /**
     * Remove the specified OptionTemplate from storage.
     *
     * @param int $id
     *
     * @return RedirectResponse
     */
    public function destroy(int $id): RedirectResponse
    {
        $optionTemplate = $this->optionTemplateRepository->findWithoutFail($id);

        if (empty($optionTemplate)) {
            Flash::error('Option Template not found');

            return redirect(route('option-templates.index'));
        }

        $this->optionTemplateRepository->delete($id);

        Flash::success(__('lang.deleted_successfully', ['operator' => __('lang.option_template')]));

        return redirect(route('option-templates.index'));
    }
}
