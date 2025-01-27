<?php


/*
 * File name: CurrencyController.php
 * Last modified: 2024.04.18 at 17:22:50
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Http\Controllers;

use App\DataTables\CurrencyDataTable;
use App\Http\Requests\CreateCurrencyRequest;
use App\Http\Requests\UpdateCurrencyRequest;
use App\Repositories\CurrencyRepository;
use App\Repositories\CustomFieldRepository;
use Flash;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;
use Prettus\Validator\Exceptions\ValidatorException;

class CurrencyController extends Controller
{
    /** @var  CurrencyRepository */
    private CurrencyRepository $currencyRepository;

    /**
     * @var CustomFieldRepository
     */
    private CustomFieldRepository $customFieldRepository;


    public function __construct(CurrencyRepository $currencyRepo, CustomFieldRepository $customFieldRepo)
    {
        parent::__construct();
        $this->currencyRepository = $currencyRepo;
        $this->customFieldRepository = $customFieldRepo;

    }

    /**
     * Display a listing of the Currency.
     *
     * @param CurrencyDataTable $currencyDataTable
     * @return Response
     */
    public function index(CurrencyDataTable $currencyDataTable): mixed
    {
        return $currencyDataTable->render('settings.currencies.index');
    }

    /**
     * Store a newly created Currency in storage.
     *
     * @param CreateCurrencyRequest $request
     *
     * @return RedirectResponse
     */
    public function store(CreateCurrencyRequest $request): RedirectResponse
    {
        $input = $request->all();
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->currencyRepository->model());
        try {
            $currency = $this->currencyRepository->create($input);
            $currency->customFieldsValues()->createMany(getCustomFieldsValues($customFields, $request));

        } catch (ValidatorException $e) {
            Flash::error($e->getMessage());
        }

        Flash::success(__('lang.saved_successfully', ['operator' => __('lang.currency')]));

        return redirect(route('currencies.index'));
    }

    /**
     * Show the form for creating a new Currency.
     *
     * @return View
     */
    public function create(): View
    {


        $hasCustomField = in_array($this->currencyRepository->model(), setting('custom_field_models', []));
        if ($hasCustomField) {
            $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->currencyRepository->model());
            $html = generateCustomField($customFields);
        }
        return view('settings.currencies.create')->with("customFields", $html ?? false);
    }

    /**
     * Display the specified Currency.
     *
     * @param int $id
     *
     * @return RedirectResponse|View
     */
    public function show(int $id): RedirectResponse|View
    {
        $currency = $this->currencyRepository->findWithoutFail($id);

        if (empty($currency)) {
            Flash::error('Currency not found');

            return redirect(route('currencies.index'));
        }

        return view('settings.currencies.show')->with('currency', $currency);
    }

    /**
     * Show the form for editing the specified Currency.
     *
     * @param int $id
     *
     * @return RedirectResponse|View
     */
    public function edit(int $id): RedirectResponse|View
    {
        $currency = $this->currencyRepository->findWithoutFail($id);


        if (empty($currency)) {
            Flash::error(__('lang.not_found', ['operator' => __('lang.currency')]));

            return redirect(route('currencies.index'));
        }
        $customFieldsValues = $currency->customFieldsValues()->with('customField')->get();
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->currencyRepository->model());
        $hasCustomField = in_array($this->currencyRepository->model(), setting('custom_field_models', []));
        if ($hasCustomField) {
            $html = generateCustomField($customFields, $customFieldsValues);
        }

        return view('settings.currencies.edit')->with('currency', $currency)->with("customFields", $html ?? false);
    }

    /**
     * Update the specified Currency in storage.
     *
     * @param int $id
     * @param UpdateCurrencyRequest $request
     *
     * @return RedirectResponse
     */
    public function update(int $id, UpdateCurrencyRequest $request): RedirectResponse
    {
        $currency = $this->currencyRepository->findWithoutFail($id);

        if (empty($currency)) {
            Flash::error('Currency not found');
            return redirect(route('currencies.index'));
        }
        $input = $request->all();
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->currencyRepository->model());
        try {
            $currency = $this->currencyRepository->update($input, $id);


            foreach (getCustomFieldsValues($customFields, $request) as $value) {
                $currency->customFieldsValues()
                    ->updateOrCreate(['custom_field_id' => $value['custom_field_id']], $value);
            }
        } catch (ValidatorException $e) {
            Flash::error($e->getMessage());
        }

        Flash::success(__('lang.updated_successfully', ['operator' => __('lang.currency')]));

        return redirect(route('currencies.index'));
    }

    /**
     * Remove the specified Currency from storage.
     *
     * @param int $id
     *
     * @return RedirectResponse
     */
    public function destroy(int $id): RedirectResponse
    {
        $currency = $this->currencyRepository->findWithoutFail($id);

        if (empty($currency)) {
            Flash::error('Currency not found');

            return redirect(route('currencies.index'));
        }

        $this->currencyRepository->delete($id);

        Flash::success(__('lang.deleted_successfully', ['operator' => __('lang.currency')]));

        return redirect(route('currencies.index'));
    }

    /**
     * Remove Media of Currency
     * @param Request $request
     */
    public function removeMedia(Request $request): void
    {
        $input = $request->all();
        $currency = $this->currencyRepository->findWithoutFail($input['id']);
        try {
            if ($currency->hasMedia($input['collection'])) {
                $currency->getFirstMedia($input['collection'])->delete();
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }
}
