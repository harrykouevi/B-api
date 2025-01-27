<?php
/*
 * File name: WalletController.php
 * Last modified: 2024.04.18 at 17:22:50
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Http\Controllers;

use App\Criteria\Users\CustomersCriteria;
use App\DataTables\WalletDataTable;
use App\Http\Requests\CreateWalletRequest;
use App\Http\Requests\UpdateWalletRequest;
use App\Repositories\CurrencyRepository;
use App\Repositories\CustomFieldRepository;
use App\Repositories\UserRepository;
use App\Repositories\WalletRepository;
use Exception;
use Flash;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;
use Prettus\Repository\Exceptions\RepositoryException;
use Prettus\Validator\Exceptions\ValidatorException;

class WalletController extends Controller
{
    /** @var  WalletRepository */
    private WalletRepository $walletRepository;

    /**
     * @var CustomFieldRepository
     */
    private CustomFieldRepository $customFieldRepository;

    /**
     * @var CurrencyRepository
     */
    private CurrencyRepository $currencyRepository;
    /**
     * @var UserRepository
     */
    private UserRepository $userRepository;

    public function __construct(WalletRepository $walletRepo, CustomFieldRepository $customFieldRepo, CurrencyRepository $currencyRepo
        , UserRepository                         $userRepo)
    {
        parent::__construct();
        $this->walletRepository = $walletRepo;
        $this->customFieldRepository = $customFieldRepo;
        $this->currencyRepository = $currencyRepo;
        $this->userRepository = $userRepo;
    }

    /**
     * Display a listing of the Wallet.
     *
     * @param WalletDataTable $walletDataTable
     * @return Response
     */
    public function index(WalletDataTable $walletDataTable): mixed
    {
        return $walletDataTable->render('wallets.index');
    }

    /**
     * Store a newly created Wallet in storage.
     *
     * @param CreateWalletRequest $request
     *
     * @return RedirectResponse
     */
    public function store(CreateWalletRequest $request): RedirectResponse
    {
        $input = $request->all();
        $currency = $this->currencyRepository->find($input['currency']);
        $input['currency'] = $currency;
        unset($input['balance']);
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->walletRepository->model());
        try {
            $wallet = $this->walletRepository->create($input);
            $wallet->customFieldsValues()->createMany(getCustomFieldsValues($customFields, $request));
        } catch (ValidatorException $e) {
            Flash::error($e->getMessage());
        }

        Flash::success(__('lang.saved_successfully', ['operator' => __('lang.wallet')]));

        return redirect(route('wallets.index'));
    }

    /**
     * Show the form for creating a new Wallet.
     *
     * @return View
     * @throws RepositoryException
     */
    public function create(): View
    {
        $currency = $this->currencyRepository->pluck('name', 'id');
        $this->userRepository->pushCriteria(new CustomersCriteria());
        $user = $this->userRepository->pluck('name', 'id');
        $hasCustomField = in_array($this->walletRepository->model(), setting('custom_field_models', []));
        if ($hasCustomField) {
            $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->walletRepository->model());
            $html = generateCustomField($customFields);
        }
        return view('wallets.create')->with("customFields", $html ?? false)->with("currency", $currency)->with("user", $user);
    }

    /**
     * Show the form for editing the specified Wallet.
     *
     * @param string $id
     *
     * @return RedirectResponse|View
     * @throws RepositoryException
     */
    public function edit(string $id): RedirectResponse|View
    {
        $wallet = $this->walletRepository->findWithoutFail($id);
        if (empty($wallet)) {
            Flash::error(__('lang.not_found', ['operator' => __('lang.wallet')]));
            return redirect(route('wallets.index'));
        }
        $currency = $this->currencyRepository->pluck('name', 'id');
        $this->userRepository->pushCriteria(new CustomersCriteria());
        $user = $this->userRepository->pluck('name', 'id');

        $customFieldsValues = $wallet->customFieldsValues()->with('customField')->get();
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->walletRepository->model());
        $hasCustomField = in_array($this->walletRepository->model(), setting('custom_field_models', []));
        if ($hasCustomField) {
            $html = generateCustomField($customFields, $customFieldsValues);
        }
        return view('wallets.edit')->with('wallet', $wallet)->with("customFields", $html ?? false)->with("currency", $currency)->with("user", $user);
    }

    /**
     * Update the specified Wallet in storage.
     *
     * @param string $id
     * @param UpdateWalletRequest $request
     *
     * @return RedirectResponse
     */
    public function update(string $id, UpdateWalletRequest $request): RedirectResponse
    {
        $wallet = $this->walletRepository->findWithoutFail($id);

        if (empty($wallet)) {
            Flash::error(__('lang.not_found', ['operator' => __('lang.wallet')]));
            return redirect(route('wallets.index'));
        }
        $input = $request->all();
        $currency = $this->currencyRepository->find($input['currency']);
        $input['currency'] = $currency;
        unset($input['balance']);
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->walletRepository->model());
        try {
            $wallet = $this->walletRepository->update($input, $id);
            foreach (getCustomFieldsValues($customFields, $request) as $value) {
                $wallet->customFieldsValues()
                    ->updateOrCreate(['custom_field_id' => $value['custom_field_id']], $value);
            }
        } catch (ValidatorException $e) {
            Flash::error($e->getMessage());
        }
        Flash::success(__('lang.updated_successfully', ['operator' => __('lang.wallet')]));
        return redirect(route('wallets.index'));
    }

    /**
     * Remove the specified Wallet from storage.
     *
     * @param string $id
     *
     * @return RedirectResponse
     */
    public function destroy(string $id): RedirectResponse
    {
        $wallet = $this->walletRepository->findWithoutFail($id);

        if (empty($wallet)) {
            Flash::error(__('lang.not_found', ['operator' => __('lang.wallet')]));

            return redirect(route('wallets.index'));
        }
        $this->walletRepository->delete($id);
        Flash::success(__('lang.deleted_successfully', ['operator' => __('lang.wallet')]));
        return redirect(route('wallets.index'));
    }

    /**
     * Remove Media of Wallet
     * @param Request $request
     */
    public function removeMedia(Request $request): void
    {
        $input = $request->all();
        $wallet = $this->walletRepository->findWithoutFail($input['id']);
        try {
            if ($wallet->hasMedia($input['collection'])) {
                $wallet->getFirstMedia($input['collection'])->delete();
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
    }

}
