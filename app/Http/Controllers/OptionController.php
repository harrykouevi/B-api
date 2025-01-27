<?php
/*
 * File name: OptionController.php
 * Last modified: 2024.04.18 at 17:22:50
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Http\Controllers;

use App\Criteria\EServices\EServicesOfUserCriteria;
use App\Criteria\Options\OptionsOfUserCriteria;
use App\DataTables\OptionDataTable;
use App\Http\Requests\CreateOptionRequest;
use App\Http\Requests\UpdateOptionRequest;
use App\Repositories\CustomFieldRepository;
use App\Repositories\EServiceRepository;
use App\Repositories\OptionGroupRepository;
use App\Repositories\OptionRepository;
use App\Repositories\UploadRepository;
use Exception;
use Flash;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;
use Prettus\Repository\Exceptions\RepositoryException;
use Prettus\Validator\Exceptions\ValidatorException;

class OptionController extends Controller
{
    /** @var  OptionRepository */
    private OptionRepository $optionRepository;

    /**
     * @var CustomFieldRepository
     */
    private CustomFieldRepository $customFieldRepository;

    /**
     * @var UploadRepository
     */
    private UploadRepository $uploadRepository;
    /**
     * @var EServiceRepository
     */
    private EServiceRepository $eServiceRepository;
    /**
     * @var OptionGroupRepository
     */
    private OptionGroupRepository $optionGroupRepository;

    public function __construct(OptionRepository $optionRepo, CustomFieldRepository $customFieldRepo, UploadRepository $uploadRepo
        , EServiceRepository                     $eServiceRepo
        , OptionGroupRepository                  $optionGroupRepo)
    {
        parent::__construct();
        $this->optionRepository = $optionRepo;
        $this->customFieldRepository = $customFieldRepo;
        $this->uploadRepository = $uploadRepo;
        $this->eServiceRepository = $eServiceRepo;
        $this->optionGroupRepository = $optionGroupRepo;
    }

    /**
     * Display a listing of the Option.
     *
     * @param OptionDataTable $optionDataTable
     * @return Response
     */
    public function index(OptionDataTable $optionDataTable): mixed
    {
        return $optionDataTable->render('options.index');
    }

    /**
     * Store a newly created Option in storage.
     *
     * @param CreateOptionRequest $request
     *
     * @return RedirectResponse
     */
    public function store(CreateOptionRequest $request): RedirectResponse
    {
        $input = $request->all();
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->optionRepository->model());
        try {
            $option = $this->optionRepository->create($input);
            $option->customFieldsValues()->createMany(getCustomFieldsValues($customFields, $request));
            if (isset($input['image']) && $input['image']) {
                $cacheUpload = $this->uploadRepository->getByUuid($input['image']);
                $mediaItem = $cacheUpload->getMedia('image')->first();
                $mediaItem = $mediaItem->forgetCustomProperty('generated_conversions');
                $mediaItem->copy($option, 'image');
            }
        } catch (ValidatorException $e) {
            Flash::error($e->getMessage());
        }

        Flash::success(__('lang.saved_successfully', ['operator' => __('lang.option')]));

        return redirect(route('options.index'));
    }

    /**
     * Show the form for creating a new Option.
     *
     * @return View
     * @throws RepositoryException
     */
    public function create(): View
    {
        $this->eServiceRepository->pushCriteria(new EServicesOfUserCriteria(auth()->id()));
        $eService = $this->eServiceRepository->groupedBySalons();
        $optionGroup = $this->optionGroupRepository->pluck('name', 'id');

        $hasCustomField = in_array($this->optionRepository->model(), setting('custom_field_models', []));
        if ($hasCustomField) {
            $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->optionRepository->model());
            $html = generateCustomField($customFields);
        }
        return view('options.create')->with("customFields", $html ?? false)->with("eService", $eService)->with("optionGroup", $optionGroup);
    }

    /**
     * Show the form for editing the specified Option.
     *
     * @param int $id
     *
     * @return RedirectResponse|View
     * @throws RepositoryException
     */
    public function edit(int $id): RedirectResponse|View
    {
        $this->optionRepository->pushCriteria(new OptionsOfUserCriteria(auth()->id()));
        $option = $this->optionRepository->findWithoutFail($id);
        if (empty($option)) {
            Flash::error(__('lang.not_found', ['operator' => __('lang.option')]));
            return redirect(route('options.index'));
        }
        $this->eServiceRepository->pushCriteria(new EServicesOfUserCriteria(auth()->id()));
        $eService = $this->eServiceRepository->groupedBySalons();
        $optionGroup = $this->optionGroupRepository->pluck('name', 'id');


        $customFieldsValues = $option->customFieldsValues()->with('customField')->get();
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->optionRepository->model());
        $hasCustomField = in_array($this->optionRepository->model(), setting('custom_field_models', []));
        if ($hasCustomField) {
            $html = generateCustomField($customFields, $customFieldsValues);
        }

        return view('options.edit')->with('option', $option)->with("customFields", $html ?? false)->with("eService", $eService)->with("optionGroup", $optionGroup);
    }

    /**
     * Update the specified Option in storage.
     *
     * @param int $id
     * @param UpdateOptionRequest $request
     *
     * @return RedirectResponse
     * @throws RepositoryException
     */
    public function update(int $id, UpdateOptionRequest $request): RedirectResponse
    {
        $this->optionRepository->pushCriteria(new OptionsOfUserCriteria(auth()->id()));

        $option = $this->optionRepository->findWithoutFail($id);

        if (empty($option)) {
            Flash::error('Option not found');
            return redirect(route('options.index'));
        }
        $input = $request->all();
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->optionRepository->model());
        try {
            $option = $this->optionRepository->update($input, $id);

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

        Flash::success(__('lang.updated_successfully', ['operator' => __('lang.option')]));

        return redirect(route('options.index'));
    }

    /**
     * Remove the specified Option from storage.
     *
     * @param int $id
     *
     * @return RedirectResponse
     * @throws RepositoryException
     */
    public function destroy(int $id): RedirectResponse
    {
        $this->optionRepository->pushCriteria(new OptionsOfUserCriteria(auth()->id()));
        $option = $this->optionRepository->findWithoutFail($id);

        if (empty($option)) {
            Flash::error('Option not found');

            return redirect(route('options.index'));
        }

        $this->optionRepository->delete($id);

        Flash::success(__('lang.deleted_successfully', ['operator' => __('lang.option')]));

        return redirect(route('options.index'));
    }

    /**
     * Remove Media of Option
     * @param Request $request
     * @throws RepositoryException
     */
    public function removeMedia(Request $request): void
    {
        $input = $request->all();
        $this->optionRepository->pushCriteria(new OptionsOfUserCriteria(auth()->id()));
        $option = $this->optionRepository->findWithoutFail($input['id']);
        try {
            if ($option->hasMedia($input['collection'])) {
                $option->getFirstMedia($input['collection'])->delete();
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
    }
}
