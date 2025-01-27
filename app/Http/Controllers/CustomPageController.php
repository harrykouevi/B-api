<?php
/*
 * File name: CustomPageController.php
 * Last modified: 2024.04.10 at 14:47:27
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Http\Controllers;

use App\DataTables\CustomPageDataTable;
use App\Http\Requests\CreateCustomPageRequest;
use App\Http\Requests\UpdateCustomPageRequest;
use App\Repositories\CustomFieldRepository;
use App\Repositories\CustomPageRepository;
use Flash;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;
use Prettus\Validator\Exceptions\ValidatorException;

class CustomPageController extends Controller
{
    /** @var  CustomPageRepository */
    private CustomPageRepository $customPageRepository;

    /**
     * @var CustomFieldRepository
     */
    private CustomFieldRepository $customFieldRepository;


    public function __construct(CustomPageRepository $customPageRepo, CustomFieldRepository $customFieldRepo)
    {
        parent::__construct();
        $this->customPageRepository = $customPageRepo;
        $this->customFieldRepository = $customFieldRepo;

    }

    /**
     * Display a listing of the CustomPage.
     *
     * @param CustomPageDataTable $customPageDataTable
     * @return Response
     */
    public function index(CustomPageDataTable $customPageDataTable): mixed
    {
        return $customPageDataTable->render('settings.custom_pages.index');
    }

    /**
     * Store a newly created CustomPage in storage.
     *
     * @param CreateCustomPageRequest $request
     *
     * @return RedirectResponse
     */
    public function store(CreateCustomPageRequest $request): RedirectResponse
    {
        $input = $request->all();
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->customPageRepository->model());
        try {
            $customPage = $this->customPageRepository->create($input);
            $customPage->customFieldsValues()->createMany(getCustomFieldsValues($customFields, $request));

        } catch (ValidatorException $e) {
            Flash::error($e->getMessage());
        }

        Flash::success(__('lang.saved_successfully', ['operator' => __('lang.custom_page')]));

        return redirect(route('customPages.index'));
    }

    /**
     * Show the form for creating a new CustomPage.
     *
     * @return View
     */
    public function create(): View
    {


        $hasCustomField = in_array($this->customPageRepository->model(), setting('custom_field_models', []));
        if ($hasCustomField) {
            $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->customPageRepository->model());
            $html = generateCustomField($customFields);
        }
        return view('settings.custom_pages.create')->with("customFields", $html ?? false);
    }

    /**
     * Display the specified CustomPage.
     *
     * @param int $id
     *
     * @return RedirectResponse|View
     */
    public function show(int $id): RedirectResponse|View
    {
        $customPage = $this->customPageRepository->findWithoutFail($id);

        if (empty($customPage)) {
            Flash::error(__('lang.not_found', ['operator' => __('lang.custom_page')]));
            return redirect(route('customPages.index'));
        }
        return view('settings.custom_pages.show')->with('customPage', $customPage);
    }

    /**
     * Show the form for editing the specified CustomPage.
     *
     * @param int $id
     *
     * @return RedirectResponse|View
     */
    public function edit(int $id): RedirectResponse|View
    {
        $customPage = $this->customPageRepository->findWithoutFail($id);


        if (empty($customPage)) {
            Flash::error(__('lang.not_found', ['operator' => __('lang.custom_page')]));

            return redirect(route('customPages.index'));
        }
        $customFieldsValues = $customPage->customFieldsValues()->with('customField')->get();
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->customPageRepository->model());
        $hasCustomField = in_array($this->customPageRepository->model(), setting('custom_field_models', []));
        if ($hasCustomField) {
            $html = generateCustomField($customFields, $customFieldsValues);
        }
        return view('settings.custom_pages.edit')->with('customPage', $customPage)->with("customFields", $html ?? false);
    }

    /**
     * Update the specified CustomPage in storage.
     *
     * @param int $id
     * @param UpdateCustomPageRequest $request
     *
     * @return RedirectResponse
     */
    public function update(int $id, UpdateCustomPageRequest $request): RedirectResponse
    {
        $customPage = $this->customPageRepository->findWithoutFail($id);

        if (empty($customPage)) {
            Flash::error(__('lang.not_found', ['operator' => __('lang.custom_page')]));
            return redirect(route('customPages.index'));
        }
        $input = $request->all();
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->customPageRepository->model());
        try {
            $customPage = $this->customPageRepository->update($input, $id);


            foreach (getCustomFieldsValues($customFields, $request) as $value) {
                $customPage->customFieldsValues()
                    ->updateOrCreate(['custom_field_id' => $value['custom_field_id']], $value);
            }
        } catch (ValidatorException $e) {
            Flash::error($e->getMessage());
        }
        Flash::success(__('lang.updated_successfully', ['operator' => __('lang.custom_page')]));
        return redirect(route('customPages.index'));
    }

    /**
     * Remove the specified CustomPage from storage.
     *
     * @param int $id
     *
     * @return RedirectResponse
     */
    public function destroy(int $id): RedirectResponse
    {
        $customPage = $this->customPageRepository->findWithoutFail($id);

        if (empty($customPage)) {
            Flash::error(__('lang.not_found', ['operator' => __('lang.custom_page')]));

            return redirect(route('customPages.index'));
        }

        $this->customPageRepository->delete($id);

        Flash::success(__('lang.deleted_successfully', ['operator' => __('lang.custom_page')]));
        return redirect(route('customPages.index'));
    }

}
