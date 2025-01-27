<?php
/*
 * File name: SalonPayoutController.php
 * Last modified: 2024.04.10 at 14:21:46
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Http\Controllers;

use App\Criteria\Salons\SalonsOfUserCriteria;
use App\DataTables\SalonPayoutDataTable;
use App\Http\Requests\CreateSalonPayoutRequest;
use App\Repositories\CustomFieldRepository;
use App\Repositories\EarningRepository;
use App\Repositories\SalonPayoutRepository;
use App\Repositories\SalonRepository;
use Carbon\Carbon;
use Flash;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Prettus\Repository\Exceptions\RepositoryException;
use Prettus\Validator\Exceptions\ValidatorException;

class SalonPayoutController extends Controller
{
    /** @var  SalonPayoutRepository */
    private SalonPayoutRepository $salonPayoutRepository;

    /**
     * @var CustomFieldRepository
     */
    private CustomFieldRepository $customFieldRepository;

    /**
     * @var SalonRepository
     */
    private SalonRepository $salonRepository;
    /**
     * @var EarningRepository
     */
    private EarningRepository $earningRepository;

    public function __construct(SalonPayoutRepository $salonPayoutRepo, CustomFieldRepository $customFieldRepo, SalonRepository $salonRepo, EarningRepository $earningRepository)
    {
        parent::__construct();
        $this->salonPayoutRepository = $salonPayoutRepo;
        $this->customFieldRepository = $customFieldRepo;
        $this->salonRepository = $salonRepo;
        $this->earningRepository = $earningRepository;
    }

    /**
     * Display a listing of the SalonPayout.
     *
     * @param SalonPayoutDataTable $salonPayoutDataTable
     * @return mixed
     */
    public function index(SalonPayoutDataTable $salonPayoutDataTable): mixed
    {
        return $salonPayoutDataTable->render('salon_payouts.index');
    }

    /**
     * Store a newly created SalonPayout in storage.
     *
     * @param CreateSalonPayoutRequest $request
     *
     * @return RedirectResponse
     */
    public function store(CreateSalonPayoutRequest $request): RedirectResponse
    {
        $input = $request->all();
        $earning = $this->earningRepository->findByField('salon_id', $input['salon_id'])->first();
        $totalPayout = $this->salonPayoutRepository->findByField('salon_id', $input['salon_id'])->sum("amount");
        $input['amount'] = $earning->salon_earning - $totalPayout;
        if ($input['amount'] <= 0) {
            Flash::error(__('lang.not_found', ['operator' => __('lang.earning')]));
            return redirect(route('salonPayouts.index'));
        }
        $input['paid_date'] = Carbon::now();
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->salonPayoutRepository->model());
        try {
            $salonPayout = $this->salonPayoutRepository->create($input);
            $salonPayout->customFieldsValues()->createMany(getCustomFieldsValues($customFields, $request));

        } catch (ValidatorException $e) {
            Flash::error($e->getMessage());
        }

        Flash::success(__('lang.saved_successfully', ['operator' => __('lang.salon_payout')]));

        return redirect(route('salonPayouts.index'));
    }

    /**
     * Show the form for creating a new SalonPayout.
     *
     * @param int $id
     * @return RedirectResponse|View
     * @throws RepositoryException
     */
    public function create(int $id): RedirectResponse|View
    {
        $this->salonRepository->pushCriteria(new SalonsOfUserCriteria(auth()->id()));
        $salon = $this->salonRepository->findWithoutFail($id);
        if (empty($salon)) {
            Flash::error(__('lang.not_found', ['operator' => __('lang.salon')]));
            return redirect(route('salonPayouts.index'));
        }
        $earning = $this->earningRepository->findByField('salon_id', $id)->first();
        $totalPayout = $this->salonPayoutRepository->findByField('salon_id', $id)->sum("amount");

        $hasCustomField = in_array($this->salonPayoutRepository->model(), setting('custom_field_models', []));
        if ($hasCustomField) {
            $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->salonPayoutRepository->model());
            $html = generateCustomField($customFields);
        }
        return view('salon_payouts.create')->with("customFields", $html ?? false)->with("salon", $salon)->with("amount", $earning->salon_earning - $totalPayout);
    }
}
