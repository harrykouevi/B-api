<?php
/*
 * File name: OptionGroupController.php
 * Last modified: 2024.04.18 at 17:22:50
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Http\Controllers;

use App\DataTables\OptionGroupDataTable;
use App\Http\Requests\CreateOptionGroupRequest;
use App\Http\Requests\UpdateOptionGroupRequest;
use App\Repositories\CustomFieldRepository;
use App\Repositories\OptionGroupRepository;
use Exception;
use Flash;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;
use Prettus\Validator\Exceptions\ValidatorException;

class OptionGroupController extends Controller
{
    /** @var  OptionGroupRepository */
    private OptionGroupRepository $optionGroupRepository;

    /**
     * @var CustomFieldRepository
     */
    private CustomFieldRepository $customFieldRepository;


    public function __construct(OptionGroupRepository $optionGroupRepo, CustomFieldRepository $customFieldRepo)
    {
        parent::__construct();
        $this->optionGroupRepository = $optionGroupRepo;
        $this->customFieldRepository = $customFieldRepo;

    }

    /**
     * Display a listing of the OptionGroup.
     *
     * @param OptionGroupDataTable $optionGroupDataTable
     * @return Response
     */
    public function index(OptionGroupDataTable $optionGroupDataTable): mixed
    {
        return $optionGroupDataTable->render('option_groups.index');
    }

    /**
     * Store a newly created OptionGroup in storage.
     *
     * @param CreateOptionGroupRequest $request
     *
     * @return RedirectResponse
     */
    public function store(CreateOptionGroupRequest $request): RedirectResponse
    {
        $input = $request->all();
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->optionGroupRepository->model());
        try {
            $optionGroup = $this->optionGroupRepository->create($input);
            $optionGroup->customFieldsValues()->createMany(getCustomFieldsValues($customFields, $request));

        } catch (ValidatorException $e) {
            Flash::error($e->getMessage());
        }

        Flash::success(__('lang.saved_successfully', ['operator' => __('lang.option_group')]));

        return redirect(route('optionGroups.index'));
    }

    /**
     * Show the form for creating a new OptionGroup.
     *
     * @return View
     */
    public function create(): View
    {


        $hasCustomField = in_array($this->optionGroupRepository->model(), setting('custom_field_models', []));
        if ($hasCustomField) {
            $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->optionGroupRepository->model());
            $html = generateCustomField($customFields);
        }
        return view('option_groups.create')->with("customFields", $html ?? false);
    }

    /**
     * Show the form for editing the specified OptionGroup.
     *
     * @param int $id
     *
     * @return RedirectResponse|View
     */
    public function edit(int $id): RedirectResponse|View
    {
        $optionGroup = $this->optionGroupRepository->findWithoutFail($id);

        if (empty($optionGroup)) {
            Flash::error(__('lang.not_found', ['operator' => __('lang.option_group')]));

            return redirect(route('optionGroups.index'));
        }
        $customFieldsValues = $optionGroup->customFieldsValues()->with('customField')->get();
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->optionGroupRepository->model());
        $hasCustomField = in_array($this->optionGroupRepository->model(), setting('custom_field_models', []));
        if ($hasCustomField) {
            $html = generateCustomField($customFields, $customFieldsValues);
        }

        return view('option_groups.edit')->with('optionGroup', $optionGroup)->with("customFields", $html ?? false);
    }

    /**
     * Update the specified OptionGroup in storage.
     *
     * @param int $id
     * @param UpdateOptionGroupRequest $request
     *
     * @return RedirectResponse
     */
    public function update(int $id, UpdateOptionGroupRequest $request): RedirectResponse
    {
        $optionGroup = $this->optionGroupRepository->findWithoutFail($id);

        if (empty($optionGroup)) {
            Flash::error('Option Group not found');
            return redirect(route('optionGroups.index'));
        }
        $input = $request->all();
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->optionGroupRepository->model());
        try {
            $optionGroup = $this->optionGroupRepository->update($input, $id);


            foreach (getCustomFieldsValues($customFields, $request) as $value) {
                $optionGroup->customFieldsValues()
                    ->updateOrCreate(['custom_field_id' => $value['custom_field_id']], $value);
            }
        } catch (ValidatorException $e) {
            Flash::error($e->getMessage());
        }

        Flash::success(__('lang.updated_successfully', ['operator' => __('lang.option_group')]));

        return redirect(route('optionGroups.index'));
    }

    /**
     * Remove the specified OptionGroup from storage.
     *
     * @param int $id
     *
     * @return RedirectResponse
     */
    public function destroy(int $id): RedirectResponse
    {
        $optionGroup = $this->optionGroupRepository->findWithoutFail($id);

        if (empty($optionGroup)) {
            Flash::error('Option Group not found');

            return redirect(route('optionGroups.index'));
        }

        $this->optionGroupRepository->delete($id);

        Flash::success(__('lang.deleted_successfully', ['operator' => __('lang.option_group')]));

        return redirect(route('optionGroups.index'));
    }

    /**
     * Remove Media of OptionGroup
     * @param Request $request
     */
    public function removeMedia(Request $request): void
    {
        $input = $request->all();
        $optionGroup = $this->optionGroupRepository->findWithoutFail($input['id']);
        try {
            if ($optionGroup->hasMedia($input['collection'])) {
                $optionGroup->getFirstMedia($input['collection'])->delete();
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
    }
}
