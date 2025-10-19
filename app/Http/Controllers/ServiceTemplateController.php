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
use App\Repositories\UploadRepository;
use Exception;
use Flash;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Prettus\Validator\Exceptions\ValidatorException;

class ServiceTemplateController extends Controller
{
    /** @var  ServiceTemplateRepository */
    private ServiceTemplateRepository $serviceTemplateRepository;

    /** @var  CategoryRepository */
    private CategoryRepository $categoryRepository;

    /**
     * @var UploadRepository
     */
    private UploadRepository $uploadRepository;

    public function __construct(ServiceTemplateRepository $serviceTemplateRepo, CategoryRepository $categoryRepo, UploadRepository $uploadRepository)
    {
        parent::__construct();
        $this->serviceTemplateRepository = $serviceTemplateRepo;
        $this->categoryRepository = $categoryRepo;
        $this->uploadRepository = $uploadRepository;
    }

    /**
     * Display a listing of the ServiceTemplate.
     *
     * @return View
     */
    public function index(): View
    {
        $serviceTemplates = $this->serviceTemplateRepository->all();
        return view('service_templates.index')->with('serviceTemplates', $serviceTemplates);
    }

    /**
     * Store a newly created ServiceTemplate in storage.
     *
     * @param CreateServiceTemplateRequest $request
     *
     * @return RedirectResponse
     */
    public function store(CreateServiceTemplateRequest $request): RedirectResponse
    {
        $input = $request->all();
        
        try {
            $serviceTemplate = $this->serviceTemplateRepository->create($input);
            
            // Handle image uploads
            if (isset($input['image']) && $input['image'] && is_array($input['image'])) {
                foreach ($input['image'] as $fileUuid) {
                    $cacheUpload = $this->uploadRepository->getByUuid($fileUuid);
                    $mediaItem = $cacheUpload->getMedia('image')->first();
                    $mediaItem->copy($serviceTemplate, 'image');
                }
            }
        } catch (ValidatorException $e) {
            Flash::error($e->getMessage());
        }

        Flash::success(__('lang.saved_successfully', ['operator' => __('lang.service_template')]));

        return redirect(route('service_templates.index'));
    }

    /**
     * Show the form for creating a new ServiceTemplate.
     *
     * @return View
     */
    public function create(): View
    {
        $categories = $this->categoryRepository->pluck('name', 'id');

        return view('service_templates.create');
    }

    /**
     * Display the specified ServiceTemplate.
     *
     * @param int $id
     *
     * @return RedirectResponse|View
     */
    public function show(int $id): RedirectResponse|View
    {
        $serviceTemplate = $this->serviceTemplateRepository->findWithoutFail($id);

        if (empty($serviceTemplate)) {
            Flash::error('Service Template not found');

            return redirect(route('service_templates.index'));
        }

        return view('service_templates.show')->with('serviceTemplate', $serviceTemplate);
    }

    /**
     * Show the form for editing the specified ServiceTemplate.
     *
     * @param int $id
     *
     * @return RedirectResponse|View
     */
    public function edit(int $id): RedirectResponse|View
    {
        $serviceTemplate = $this->serviceTemplateRepository->findWithoutFail($id);
        $categories = $this->categoryRepository->pluck('name', 'id');

        if (empty($serviceTemplate)) {
            Flash::error(__('lang.not_found', ['operator' => __('lang.service_template')]));

            return redirect(route('service_templates.index'));
        }

        return view('service_templates.edit')->with('serviceTemplate', $serviceTemplate)->with("categories", $categories);
    }

    /**
     * Update the specified ServiceTemplate in storage.
     *
     * @param int $id
     * @param UpdateServiceTemplateRequest $request
     *
     * @return RedirectResponse
     */
    public function update(int $id, UpdateServiceTemplateRequest $request): RedirectResponse
    {
        $serviceTemplate = $this->serviceTemplateRepository->findWithoutFail($id);

        if (empty($serviceTemplate)) {
            Flash::error('Service Template not found');
            return redirect(route('service_templates.index'));
        }
        
        $input = $request->all();
        
        try {
            $serviceTemplate = $this->serviceTemplateRepository->update($input, $id);
            
            // Handle image uploads
            if (isset($input['image']) && $input['image'] && is_array($input['image'])) {
                foreach ($input['image'] as $fileUuid) {
                    $cacheUpload = $this->uploadRepository->getByUuid($fileUuid);
                    $mediaItem = $cacheUpload->getMedia('image')->first();
                    $mediaItem->copy($serviceTemplate, 'image');
                }
            }
        } catch (ValidatorException $e) {
            Flash::error($e->getMessage());
        }

        Flash::success(__('lang.updated_successfully', ['operator' => __('lang.service_template')]));

        return redirect(route('service_templates.index'));
    }

    /**
     * Remove the specified ServiceTemplate from storage.
     *
     * @param int $id
     *
     * @return RedirectResponse
     */
    public function destroy(int $id): RedirectResponse
    {
        $serviceTemplate = $this->serviceTemplateRepository->findWithoutFail($id);

        if (empty($serviceTemplate)) {
            Flash::error('Service Template not found');

            return redirect(route('service_templates.index'));
        }

        $this->serviceTemplateRepository->delete($id);

        Flash::success(__('lang.deleted_successfully', ['operator' => __('lang.service_template')]));

        return redirect(route('service_templates.index'));
    }

    /**
     * Remove Media of ServiceTemplate
     * @param Request $request
     */
    public function removeMedia(Request $request): void
    {
        $input = $request->all();
        $serviceTemplate = $this->serviceTemplateRepository->findWithoutFail($input['id']);
        try {
            if ($serviceTemplate->hasMedia($input['collection'])) {
                $serviceTemplate->getFirstMedia($input['collection'])->delete();
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
    }
}
