<?php
/*
 * File name: TaxController.php
 * Last modified: 2024.04.18 at 17:22:51
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Http\Controllers;

use App\DataTables\TaxDataTable;
use App\Http\Requests\CreateTaxRequest;
use App\Http\Requests\UpdateTaxRequest;
use App\Repositories\CustomFieldRepository;
use App\Repositories\TaxRepository;
use Exception;
use Flash;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;
use Prettus\Validator\Exceptions\ValidatorException;

class TaxController extends Controller
{
    /** @var  TaxRepository */
    private TaxRepository $taxRepository;

    /**
     * @var CustomFieldRepository
     */
    private CustomFieldRepository $customFieldRepository;


    public function __construct(TaxRepository $taxRepo, CustomFieldRepository $customFieldRepo)
    {
        parent::__construct();
        $this->taxRepository = $taxRepo;
        $this->customFieldRepository = $customFieldRepo;

    }

    /**
     * Display a listing of the Tax.
     *
     * @param TaxDataTable $taxDataTable
     * @return Response
     */
    public function index(TaxDataTable $taxDataTable): mixed
    {
        return $taxDataTable->render('settings.taxes.index');
    }

    /**
     * Store a newly created Tax in storage.
     *
     * @param CreateTaxRequest $request
     *
     * @return RedirectResponse
     */
    public function store(CreateTaxRequest $request): RedirectResponse
    {
        $input = $request->all();
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->taxRepository->model());
        try {
            $tax = $this->taxRepository->create($input);
            $tax->customFieldsValues()->createMany(getCustomFieldsValues($customFields, $request));

        } catch (ValidatorException $e) {
            Flash::error($e->getMessage());
        }

        Flash::success(__('lang.saved_successfully', ['operator' => __('lang.tax')]));

        return redirect(route('taxes.index'));
    }

    /**
     * Show the form for creating a new Tax.
     *
     * @return View
     */
    public function create(): View
    {


        $hasCustomField = in_array($this->taxRepository->model(), setting('custom_field_models', []));
        if ($hasCustomField) {
            $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->taxRepository->model());
            $html = generateCustomField($customFields);
        }
        return view('settings.taxes.create')->with("customFields", $html ?? false);
    }

    /**
     * Display the specified Tax.
     *
     * @param int $id
     *
     * @return RedirectResponse|View
     */
    public function show(int $id): RedirectResponse|View
    {
        $tax = $this->taxRepository->findWithoutFail($id);

        if (empty($tax)) {
            Flash::error('Tax not found');

            return redirect(route('taxes.index'));
        }

        return view('settings.taxes.show')->with('tax', $tax);
    }

    /**
     * Show the form for editing the specified Tax.
     *
     * @param int $id
     *
     * @return RedirectResponse|View
     */
    public function edit(int $id): RedirectResponse|View
    {
        $tax = $this->taxRepository->findWithoutFail($id);


        if (empty($tax)) {
            Flash::error(__('lang.not_found', ['operator' => __('lang.tax')]));

            return redirect(route('taxes.index'));
        }
        $customFieldsValues = $tax->customFieldsValues()->with('customField')->get();
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->taxRepository->model());
        $hasCustomField = in_array($this->taxRepository->model(), setting('custom_field_models', []));
        if ($hasCustomField) {
            $html = generateCustomField($customFields, $customFieldsValues);
        }

        return view('settings.taxes.edit')->with('tax', $tax)->with("customFields", $html ?? false);
    }

    /**
     * Update the specified Tax in storage.
     *
     * @param int $id
     * @param UpdateTaxRequest $request
     *
     * @return RedirectResponse
     */
    public function update(int $id, UpdateTaxRequest $request): RedirectResponse
    {
        $tax = $this->taxRepository->findWithoutFail($id);

        if (empty($tax)) {
            Flash::error('Tax not found');
            return redirect(route('taxes.index'));
        }
        $input = $request->all();
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->taxRepository->model());
        try {
            $tax = $this->taxRepository->update($input, $id);


            foreach (getCustomFieldsValues($customFields, $request) as $value) {
                $tax->customFieldsValues()
                    ->updateOrCreate(['custom_field_id' => $value['custom_field_id']], $value);
            }
        } catch (ValidatorException $e) {
            Flash::error($e->getMessage());
        }

        Flash::success(__('lang.updated_successfully', ['operator' => __('lang.tax')]));

        return redirect(route('taxes.index'));
    }

    /**
     * Remove the specified Tax from storage.
     *
     * @param int $id
     *
     * @return RedirectResponse
     */
    public function destroy(int $id): RedirectResponse
    {
        $tax = $this->taxRepository->findWithoutFail($id);

        if (empty($tax)) {
            Flash::error('Tax not found');

            return redirect(route('taxes.index'));
        }

        $this->taxRepository->delete($id);

        Flash::success(__('lang.deleted_successfully', ['operator' => __('lang.tax')]));

        return redirect(route('taxes.index'));
    }

    /**
     * Remove Media of Tax
     * @param Request $request
     */
    public function removeMedia(Request $request): void
    {
        $input = $request->all();
        $tax = $this->taxRepository->findWithoutFail($input['id']);
        try {
            if ($tax->hasMedia($input['collection'])) {
                $tax->getFirstMedia($input['collection'])->delete();
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
    }
}
