<?php
/*
 * File name: SalonController.php
 * Last modified: 2024.04.18 at 17:22:50
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Http\Controllers;

use App\Criteria\Addresses\AddressesOfUserCriteria;
use App\Criteria\SalonLevels\EnabledCriteria;
use App\Criteria\Salons\SalonsOfUserCriteria;
use App\Criteria\Users\SalonsCustomersCriteria;
use App\DataTables\RequestedSalonDataTable;
use App\DataTables\SalonDataTable;
use App\Events\SalonChangedEvent;
use App\Http\Requests\CreateSalonRequest;
use App\Http\Requests\UpdateSalonRequest;
use App\Repositories\AddressRepository;
use App\Repositories\CustomFieldRepository;
use App\Repositories\SalonLevelRepository;
use App\Repositories\SalonRepository;
use App\Repositories\TaxRepository;
use App\Repositories\UploadRepository;
use App\Repositories\UserRepository;
use Exception;
use Flash;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Prettus\Repository\Exceptions\RepositoryException;
use Prettus\Validator\Exceptions\ValidatorException;

class SalonController extends Controller
{
    /** @var  SalonRepository */
    private SalonRepository $salonRepository;

    /**
     * @var CustomFieldRepository
     */
    private CustomFieldRepository $customFieldRepository;

    /**
     * @var UploadRepository
     */
    private UploadRepository $uploadRepository;
    /**
     * @var SalonLevelRepository
     */
    private SalonLevelRepository $salonLevelRepository;
    /**
     * @var UserRepository
     */
    private UserRepository $userRepository;
    /**
     * @var AddressRepository
     */
    private AddressRepository $addressRepository;
    /**
     * @var TaxRepository
     */
    private TaxRepository $taxRepository;

    public function __construct(SalonRepository $salonRepo, CustomFieldRepository $customFieldRepo, UploadRepository $uploadRepo
        , SalonLevelRepository                  $salonLevelRepo
        , UserRepository                        $userRepo
        , AddressRepository                     $addressRepo
        , TaxRepository                         $taxRepo)
    {
        parent::__construct();
        $this->salonRepository = $salonRepo;
        $this->customFieldRepository = $customFieldRepo;
        $this->uploadRepository = $uploadRepo;
        $this->salonLevelRepository = $salonLevelRepo;
        $this->userRepository = $userRepo;
        $this->addressRepository = $addressRepo;
        $this->taxRepository = $taxRepo;
    }

    /**
     * Display a listing of the Salon.
     *
     * @param SalonDataTable $salonDataTable
     * @return mixed
     */
    public function index(SalonDataTable $salonDataTable): mixed
    {
        return $salonDataTable->render('salons.index');
    }

    /**
     * Display a listing of the Salon.
     *
     * @param RequestedSalonDataTable $requestedSalonDataTable
     * @return mixed
     */
    public function requestedSalons(RequestedSalonDataTable $requestedSalonDataTable): mixed
    {
        return $requestedSalonDataTable->render('salons.requested');
    }

    /**
     * Store a newly created Salon in storage.
     *
     * @param CreateSalonRequest $request
     *
     * @return RedirectResponse
     */
    public function store(CreateSalonRequest $request): RedirectResponse
    {
        $input = $request->all();
        if (auth()->user()->hasRole(['provider', 'customer'])) {
            $input['users'] = [auth()->id()];
        }
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->salonRepository->model());
        try {
            $salon = $this->salonRepository->create($input);
            $salon->customFieldsValues()->createMany(getCustomFieldsValues($customFields, $request));
            if (isset($input['image']) && $input['image'] && is_array($input['image'])) {
                foreach ($input['image'] as $fileUuid) {
                    $cacheUpload = $this->uploadRepository->getByUuid($fileUuid);
                    $mediaItem = $cacheUpload->getMedia('image')->first();
                    $mediaItem->copy($salon, 'image');
                }
            }
            event(new SalonChangedEvent($salon, $salon));
        } catch (ValidatorException $e) {
            Flash::error($e->getMessage());
        }

        Flash::success(__('lang.saved_successfully', ['operator' => __('lang.salon')]));

        return redirect(route('salons.index'));
    }

    /**
     * Show the form for creating a new Salon.
     *
     * @return View
     */
    public function create(): View
    {
        $salonLevel = $this->salonLevelRepository->getByCriteria(new EnabledCriteria())->pluck('name', 'id');
        $user = $this->userRepository->getByCriteria(new SalonsCustomersCriteria())->pluck('name', 'id');
        $address = $this->addressRepository->getByCriteria(new AddressesOfUserCriteria(auth()->id()))->pluck('address', 'id');
        $tax = $this->taxRepository->pluck('name', 'id');
        $usersSelected = [];
        $taxesSelected = [];
        $hasCustomField = in_array($this->salonRepository->model(), setting('custom_field_models', []));
        if ($hasCustomField) {
            $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->salonRepository->model());
            $html = generateCustomField($customFields);
        }
        return view('salons.create')->with("customFields", $html ?? false)->with("salonLevel", $salonLevel)->with("user", $user)->with("usersSelected", $usersSelected)->with("address", $address)->with("tax", $tax)->with("taxesSelected", $taxesSelected);
    }

    /**
     * Display the specified Salon.
     *
     * @param int $id
     *
     * @return RedirectResponse|View
     * @throws RepositoryException
     */
    public function show(int $id): RedirectResponse|View
    {
        $this->salonRepository->pushCriteria(new SalonsOfUserCriteria(auth()->id()));
        $salon = $this->salonRepository->findWithoutFail($id);

        if (empty($salon)) {
            Flash::error(__('lang.not_found', ['operator' => __('lang.salon')]));

            return redirect(route('salons.index'));
        }

        return view('salons.show')->with('salon', $salon);
    }

    /**
     * Show the form for editing the specified Salon.
     *
     * @param int $id
     *
     * @return RedirectResponse|View
     * @throws RepositoryException
     */
    public function edit(int $id): RedirectResponse|View
    {
        $this->salonRepository->pushCriteria(new SalonsOfUserCriteria(auth()->id()));
        $salon = $this->salonRepository->findWithoutFail($id);
        if (empty($salon)) {
            Flash::error(__('lang.not_found', ['operator' => __('lang.salon')]));
            return redirect(route('salons.index'));
        }
        $salonLevel = $this->salonLevelRepository->getByCriteria(new EnabledCriteria())->pluck('name', 'id');
        $user = $this->userRepository->getByCriteria(new SalonsCustomersCriteria())->pluck('name', 'id');
        $address = $this->addressRepository->getByCriteria(new AddressesOfUserCriteria(auth()->id()))->pluck('address', 'id');
        $tax = $this->taxRepository->pluck('name', 'id');
        $usersSelected = $salon->users()->pluck('users.id')->toArray();
        $taxesSelected = $salon->taxes()->pluck('taxes.id')->toArray();

        $customFieldsValues = $salon->customFieldsValues()->with('customField')->get();
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->salonRepository->model());
        $hasCustomField = in_array($this->salonRepository->model(), setting('custom_field_models', []));
        if ($hasCustomField) {
            $html = generateCustomField($customFields, $customFieldsValues);
        }

        return view('salons.edit')->with('salon', $salon)->with("customFields", $html ?? false)->with("salonLevel", $salonLevel)->with("user", $user)->with("usersSelected", $usersSelected)->with("address", $address)->with("tax", $tax)->with("taxesSelected", $taxesSelected);
    }

    /**
     * Update the specified Salon in storage.
     *
     * @param int $id
     * @param UpdateSalonRequest $request
     *
     * @return RedirectResponse
     * @throws RepositoryException
     */
    public function update(int $id, UpdateSalonRequest $request): RedirectResponse
    {
        $this->salonRepository->pushCriteria(new SalonsOfUserCriteria(auth()->id()));
        $oldSalon = $this->salonRepository->findWithoutFail($id);

        if (empty($oldSalon)) {
            Flash::error(__('lang.not_found', ['operator' => __('lang.salon')]));
            return redirect(route('salons.index'));
        }
        $input = $request->all();
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->salonRepository->model());
        try {
            $input['users'] = $input['users'] ?? [];
            $input['taxes'] = $input['taxes'] ?? [];
            $salon = $this->salonRepository->update($input, $id);
            if (isset($input['image']) && $input['image'] && is_array($input['image'])) {
                foreach ($input['image'] as $fileUuid) {
                    $cacheUpload = $this->uploadRepository->getByUuid($fileUuid);
                    $mediaItem = $cacheUpload->getMedia('image')->first();
                    $mediaItem->copy($salon, 'image');
                }
            }
            foreach (getCustomFieldsValues($customFields, $request) as $value) {
                $salon->customFieldsValues()
                    ->updateOrCreate(['custom_field_id' => $value['custom_field_id']], $value);
            }
            event(new SalonChangedEvent($salon, $oldSalon));
        } catch (ValidatorException $e) {
            Flash::error($e->getMessage());
        }

        Flash::success(__('lang.updated_successfully', ['operator' => __('lang.salon')]));

        return redirect(route('salons.index'));
    }

    /**
     * Remove the specified Salon from storage.
     *
     * @param int $id
     *
     * @return RedirectResponse
     * @throws RepositoryException
     */
    public function destroy(int $id): RedirectResponse
    {
        if (config('installer.demo_app')) {
            Flash::warning('This is only demo app you can\'t change this section ');
            return redirect(route('salons.index'));
        }
        $this->salonRepository->pushCriteria(new SalonsOfUserCriteria(auth()->id()));
        $salon = $this->salonRepository->findWithoutFail($id);

        if (empty($salon)) {
            Flash::error(__('lang.not_found', ['operator' => __('lang.salon')]));

            return redirect(route('salons.index'));
        }

        $this->salonRepository->delete($id);

        Flash::success(__('lang.deleted_successfully', ['operator' => __('lang.salon')]));

        return redirect(route('salons.index'));
    }

    /**
     * Remove Media of Salon
     * @param Request $request
     */
    public function removeMedia(Request $request): void
    {
        $input = $request->all();
        $salon = $this->salonRepository->findWithoutFail($input['id']);
        try {
            if ($salon->hasMedia($input['collection'])) {
                $salon->getFirstMedia($input['collection'])->delete();
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
    }
}
