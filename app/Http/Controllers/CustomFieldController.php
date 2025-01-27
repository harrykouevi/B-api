<?php
/*
 * File name: CustomFieldController.php
 * Last modified: 2024.04.18 at 17:22:49
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Http\Controllers;

use App\DataTables\CustomFieldDataTable;
use App\Http\Requests\CreateCustomFieldRequest;
use App\Http\Requests\UpdateCustomFieldRequest;
use App\Repositories\CustomFieldRepository;
use Flash;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Prettus\Validator\Exceptions\ValidatorException;

class CustomFieldController extends Controller
{
    /** @var  CustomFieldRepository */
    private CustomFieldRepository $customFieldRepository;


    public function __construct(CustomFieldRepository $customFieldRepo)
    {
        parent::__construct();
        $this->customFieldRepository = $customFieldRepo;

    }

    /**
     * Display a listing of the CustomField.
     *
     * @param CustomFieldDataTable $customFieldDataTable
     * @return mixed
     */
    public function index(CustomFieldDataTable $customFieldDataTable): mixed
    {
        return $customFieldDataTable->render('settings.custom_fields.index');
    }

    /**
     * Store a newly created CustomField in storage.
     *
     * @param CreateCustomFieldRequest $request
     *
     * @return RedirectResponse
     */
    public function store(CreateCustomFieldRequest $request): RedirectResponse
    {
        $input = $request->all();
        try {
            $this->customFieldRepository->create($input);
        } catch (ValidatorException $e) {
            Flash::error($e->getMessage());
        }


        Flash::success('saved successfully.');

        return redirect(route('customFields.index'));
    }

    /**
     * Show the form for creating a new CustomField.
     *
     * @return View
     */
    public function create(): View
    {
        $customFieldsTypes = [];
        foreach (config('app_generator.fields') as $type) {
            $customFieldsTypes[$type] = trans('lang.' . $type);
        }

        $customFieldModels = getModelsClasses(app_path('Models'));
        $customFieldValues = [];


        return view('settings.custom_fields.create', compact(['customFieldsTypes', 'customFieldModels', 'customFieldValues']));
    }

    /**
     * Display the specified CustomField.
     *
     * @param int $id
     *
     * @return RedirectResponse|View
     */
    public function show(int $id): RedirectResponse|View
    {
        $customField = $this->customFieldRepository->findWithoutFail($id);

        if (empty($customField)) {
            Flash::error('Custom Field not found');

            return redirect(route('customFields.index'));
        }

        return view('settings.custom_fields.show')->with('customField', $customField);
    }

    /**
     * Show the form for editing the specified CustomField.
     *
     * @param int $id
     *
     * @return RedirectResponse|View
     */
    public function edit(int $id): RedirectResponse|View
    {
        $customField = $this->customFieldRepository->findWithoutFail($id);
        if (empty($customField)) {
            Flash::error('Custom Field not found');

            return redirect(route('customFields.index'));
        }

        $customFieldsTypes = [];
        foreach (config('app_generator.fields') as $type) {
            $customFieldsTypes[$type] = trans('lang.' . $type);
        }

        $customFieldModels = getModelsClasses(app_path('Models'));
        $customFieldValues = $customField['values'] ? $customField['values'] : [];

        return view('settings.custom_fields.edit', compact(['customFieldsTypes', 'customFieldModels', 'customField', 'customFieldValues']));
    }

    /**
     * Update the specified CustomField in storage.
     *
     * @param int $id
     * @param UpdateCustomFieldRequest $request
     *
     * @return RedirectResponse
     */
    public function update(int $id, UpdateCustomFieldRequest $request): RedirectResponse
    {
        $customField = $this->customFieldRepository->findWithoutFail($id);

        if (empty($customField)) {
            Flash::error('Custom Field not found');

            return redirect(route('customFields.index'));
        }

        $input = $request->all();
        try {
            if (!isset($input['values'])) {
                $input['values'] = null;
            }
            $this->customFieldRepository->update($input, $id);
        } catch (ValidatorException $e) {
            Flash::error($e->getMessage());
        }


        Flash::success('Custom Field updated successfully.');

        return redirect(route('customFields.index'));
    }

    /**
     * Remove the specified CustomField from storage.
     *
     * @param int $id
     *
     * @return RedirectResponse
     */
    public function destroy(int $id): RedirectResponse
    {
        $customField = $this->customFieldRepository->findWithoutFail($id);

        if (empty($customField)) {
            Flash::error('Custom Field not found');

            return redirect(route('customFields.index'));
        }

        $this->customFieldRepository->delete($id);

        Flash::success('Custom Field deleted successfully.');

        return redirect(route('customFields.index'));
    }

    /**
     * Remove Media of CustomField
     * @param Request $request
     */
    public function removeMedia(Request $request): void
    {
        $input = $request->all();
        $customField = $this->customFieldRepository->findWithoutFail($input['id']);
        try {
            if ($customField->hasMedia($input['collection'])) {
                $customField->getFirstMedia($input['collection'])->delete();
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }
}
