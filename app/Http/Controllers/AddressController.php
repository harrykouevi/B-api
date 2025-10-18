<?php
/*
 * File name: AddressController.php
 * Last modified: 2024.04.10 at 12:26:06
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Http\Controllers;

use App\Criteria\Addresses\AddressesOfUserCriteria;
use App\DataTables\AddressDataTable;
use App\Http\Requests\CreateAddressRequest;
use App\Http\Requests\UpdateAddressRequest;
use App\Models\Address;
use App\Repositories\AddressRepository;
use App\Repositories\CustomFieldRepository;
use Flash;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Prettus\Repository\Exceptions\RepositoryException;
use Prettus\Validator\Exceptions\ValidatorException;

class AddressController extends Controller
{
    /** @var  AddressRepository */
    private AddressRepository $addressRepository;

    /**
     * @var CustomFieldRepository
     */
    private CustomFieldRepository $customFieldRepository;

    public function __construct(AddressRepository $addressRepo, CustomFieldRepository $customFieldRepo)
    {
        parent::__construct();
        $this->addressRepository = $addressRepo;
        $this->customFieldRepository = $customFieldRepo;
    }

    /**
     * Display a listing of the Address.
     *
     * @param AddressDataTable $addressDataTable
     * @return mixed
     */
    public function index(AddressDataTable $addressDataTable): mixed
    {
        return $addressDataTable->render('addresses.index');
    }

    /**
     * Store a newly created Address in storage.
     *
     * @param CreateAddressRequest $request
     *
     * @return RedirectResponse
     */
    public function store(CreateAddressRequest $request): RedirectResponse
    {
        $input = $request->all();
        $input['user_id'] = Auth::id();
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->addressRepository->model());
        try {
            $address = $this->addressRepository->create($input);
            $address->customFieldsValues()->createMany(getCustomFieldsValues($customFields, $request));

        } catch (ValidatorException $e) {
            Flash::error($e->getMessage());
            return redirect(route('addresses.create'));
        }

        Flash::success(__('lang.saved_successfully', ['operator' => __('lang.address')]));

        return redirect(route('addresses.edit', $address->id));
    }

    /**
     * Show the form for creating a new Address.
     *
     * @return View
     */
    public function create(): View
    {
        $address = new Address();
        $address->latitude = 6.253907;   // Latitude du centre du Togo
        $address->longitude = 1.213699;

        $hasCustomField = in_array($this->addressRepository->model(), setting('custom_field_models', []));
        if ($hasCustomField) {
            $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->addressRepository->model());
            $html = generateCustomField($customFields);
        }
        return view('addresses.create', compact('address'))->with("customFields", $html ?? false);
    }

    /**
     * Display the specified Address.
     *
     * @param int $id
     *
     * @return RedirectResponse|View
     * @throws RepositoryException
     */
    public function show(int $id): RedirectResponse|View
    {
        $this->addressRepository->pushCriteria(new AddressesOfUserCriteria(auth()->id()));
        $address = $this->addressRepository->findWithoutFail($id);

        if (empty($address)) {
            Flash::error('Address not found');

            return redirect(route('addresses.index'));
        }

        return view('addresses.show')->with('address', $address);
    }

    /**
     * Show the form for editing the specified Address.
     *
     * @param int $id
     *
     * @return RedirectResponse|View
     * @throws RepositoryException
     */
    public function edit(int $id): RedirectResponse|View
    {
        $this->addressRepository->pushCriteria(new AddressesOfUserCriteria(auth()->id()));
        $address = $this->addressRepository->findWithoutFail($id);

        if (empty($address)) {
            Flash::error(__('lang.not_found', ['operator' => __('lang.address')]));

            return redirect(route('addresses.index'));
        }
        $customFieldsValues = $address->customFieldsValues()->with('customField')->get();
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->addressRepository->model());
        $hasCustomField = in_array($this->addressRepository->model(), setting('custom_field_models', []));
        if ($hasCustomField) {
            $html = generateCustomField($customFields, $customFieldsValues);
        }

        return view('addresses.edit')->with('address', $address)->with("customFields", $html ?? false);
    }

    /**
     * Update the specified Address in storage.
     *
     * @param int $id
     * @param UpdateAddressRequest $request
     *
     * @return RedirectResponse
     * @throws RepositoryException
     */
    public function update(int $id, UpdateAddressRequest $request): RedirectResponse
    {
        $this->addressRepository->pushCriteria(new AddressesOfUserCriteria(auth()->id()));
        $address = $this->addressRepository->findWithoutFail($id);

        if (empty($address)) {
            Flash::error('Address not found');
            return redirect(route('addresses.index'));
        }
        $input = $request->all();
        $input['user_id'] = $address->user->id;
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->addressRepository->model());
        try {
            $address = $this->addressRepository->update($input, $id);


            foreach (getCustomFieldsValues($customFields, $request) as $value) {
                $address->customFieldsValues()
                    ->updateOrCreate(['custom_field_id' => $value['custom_field_id']], $value);
            }
        } catch (ValidatorException $e) {
            Flash::error($e->getMessage());
        }

        Flash::success(__('lang.updated_successfully', ['operator' => __('lang.address')]));

        return redirect(route('addresses.index'));
    }

    /**
     * Remove the specified Address from storage.
     *
     * @param int $id
     *
     * @return RedirectResponse
     * @throws RepositoryException
     */
    public function destroy(int $id): RedirectResponse
    {
        $this->addressRepository->pushCriteria(new AddressesOfUserCriteria(auth()->id()));
        $address = $this->addressRepository->findWithoutFail($id);

        if (empty($address)) {
            Flash::error('Address not found');

            return redirect(route('addresses.index'));
        }

        $this->addressRepository->delete($id);

        Flash::success(__('lang.deleted_successfully', ['operator' => __('lang.address')]));

        return redirect(route('addresses.index'));
    }
}
