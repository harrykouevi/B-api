<?php
/*
 * File name: CouponController.php
 * Last modified: 2024.04.10 at 12:41:02
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Http\Controllers;

use App\Criteria\Coupons\CouponsOfUserCriteria;
use App\Criteria\EServices\EServicesOfUserCriteria;
use App\Criteria\Salons\AcceptedCriteria;
use App\Criteria\Salons\SalonsOfUserCriteria;
use App\DataTables\CouponDataTable;
use App\Http\Requests\CreateCouponRequest;
use App\Http\Requests\UpdateCouponRequest;
use App\Repositories\CategoryRepository;
use App\Repositories\CouponRepository;
use App\Repositories\CustomFieldRepository;
use App\Repositories\EServiceRepository;
use App\Repositories\SalonRepository;
use Flash;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Prettus\Repository\Exceptions\RepositoryException;
use Prettus\Validator\Exceptions\ValidatorException;

class CouponController extends Controller
{
    /** @var  CouponRepository */
    private CouponRepository $couponRepository;

    /**
     * @var CustomFieldRepository
     */
    private CustomFieldRepository $customFieldRepository;

    /**
     * @var EServiceRepository
     */
    private EServiceRepository $eServiceRepository;
    /**
     * @var SalonRepository
     */
    private SalonRepository $salonRepository;
    /**
     * @var CategoryRepository
     */
    private CategoryRepository $categoryRepository;

    public function __construct(CouponRepository $couponRepo, CustomFieldRepository $customFieldRepo, EServiceRepository $eServiceRepo
        , SalonRepository                        $salonRepo, CategoryRepository $categoryRepo)
    {
        parent::__construct();
        $this->couponRepository = $couponRepo;
        $this->customFieldRepository = $customFieldRepo;
        $this->eServiceRepository = $eServiceRepo;
        $this->salonRepository = $salonRepo;
        $this->categoryRepository = $categoryRepo;
    }

    /**
     * Display a listing of the Coupon.
     *
     * @param CouponDataTable $couponDataTable
     * @return mixed
     */
    public function index(CouponDataTable $couponDataTable): mixed
    {
        return $couponDataTable->render('coupons.index');
    }

    /**
     * Store a newly created Coupon in storage.
     *
     * @param CreateCouponRequest $request
     *
     * @return RedirectResponse
     */
    public function store(CreateCouponRequest $request): RedirectResponse
    {
        $input = $request->all();
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->couponRepository->model());
        try {
            $coupon = $this->couponRepository->create($input);
            $discountables = $this->initDiscountables($input);
            $coupon->discountables()->createMany($discountables);
            $coupon->customFieldsValues()->createMany(getCustomFieldsValues($customFields, $request));

        } catch (ValidatorException $e) {
            Flash::error($e->getMessage());
        }

        Flash::success(__('lang.saved_successfully', ['operator' => __('lang.coupon')]));

        return redirect(route('coupons.index'));
    }

    /**
     * Show the form for creating a new Coupon.
     *
     * @return View
     * @throws RepositoryException
     */
    public function create(): View
    {
        $this->eServiceRepository->pushCriteria(new EServicesOfUserCriteria(auth()->id()));
        $eService = $this->eServiceRepository->groupedBySalons();

        $this->salonRepository->pushCriteria(new SalonsOfUserCriteria(auth()->id()));
        $this->salonRepository->pushCriteria(new AcceptedCriteria());
        $salon = $this->salonRepository->pluck('name', 'id');

        $category = $this->categoryRepository->pluck('name', 'id');

        $eServicesSelected = [];
        $salonsSelected = [];
        $categoriesSelected = [];

        $hasCustomField = in_array($this->couponRepository->model(), setting('custom_field_models', []));
        if ($hasCustomField) {
            $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->couponRepository->model());
            $html = generateCustomField($customFields);
        }
        return view('coupons.create')->with("customFields", $html ?? false)->with("eService", $eService)->with("salon", $salon)->with("category", $category)->with("eServicesSelected", $eServicesSelected)->with("salonsSelected", $salonsSelected)->with("categoriesSelected", $categoriesSelected);
    }

    /**
     * @param array $input
     * @return array
     */
    private function initDiscountables(array $input): array
    {
        $discountables = [];
        if (isset($input['eServices'])) {
            foreach ($input['eServices'] as $eServiceId) {
                $discountables[] = ["discountable_type" => "App\Models\EService", "discountable_id" => $eServiceId];
            }
        }
        if (isset($input['salons'])) {
            foreach ($input['salons'] as $salonId) {
                $discountables[] = ["discountable_type" => "App\Models\Salon", "discountable_id" => $salonId];
            }
        }
        if (isset($input['categories'])) {
            foreach ($input['categories'] as $categoryId) {
                $discountables[] = ["discountable_type" => "App\Models\Category", "discountable_id" => $categoryId];
            }
        }
        return $discountables;
    }

    /**
     * Display the specified Coupon.
     *
     * @param int $id
     *
     * @return RedirectResponse|View
     */
    public function show(int $id): RedirectResponse|View
    {
        $coupon = $this->couponRepository->findWithoutFail($id);

        if (empty($coupon)) {
            Flash::error(__('lang.not_found', ['operator' => __('lang.coupon')]));

            return redirect(route('coupons.index'));
        }

        return view('coupons.show')->with('coupon', $coupon);
    }

    /**
     * Show the form for editing the specified Coupon.
     *
     * @param int $id
     *
     * @return RedirectResponse|View
     * @throws RepositoryException
     */
    public function edit(int $id): RedirectResponse|View
    {
        $this->couponRepository->pushCriteria(new CouponsOfUserCriteria(auth()->id()));

        $coupon = $this->couponRepository->all()->firstWhere('id', '=', $id);

        if (empty($coupon)) {
            Flash::error(__('lang.not_found', ['operator' => __('lang.coupon')]));

            return redirect(route('coupons.index'));
        }
        $this->eServiceRepository->pushCriteria(new EServicesOfUserCriteria(auth()->id()));
        $eService = $this->eServiceRepository->groupedBySalons();

        $this->salonRepository->pushCriteria(new SalonsOfUserCriteria(auth()->id()));
        $this->salonRepository->pushCriteria(new AcceptedCriteria());
        $salon = $this->salonRepository->pluck('name', 'id');

        $category = $this->categoryRepository->pluck('name', 'id');

        $eServicesSelected = $coupon->discountables()->where("discountable_type", "App\Models\EService")->pluck('discountable_id');
        $salonsSelected = $coupon->discountables()->where("discountable_type", "App\Models\Salon")->pluck('discountable_id');
        $categoriesSelected = $coupon->discountables()->where("discountable_type", "App\Models\Category")->pluck('discountable_id');

        $customFieldsValues = $coupon->customFieldsValues()->with('customField')->get();
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->couponRepository->model());
        $hasCustomField = in_array($this->couponRepository->model(), setting('custom_field_models', []));
        if ($hasCustomField) {
            $html = generateCustomField($customFields, $customFieldsValues);
        }

        return view('coupons.edit')->with('coupon', $coupon)->with("customFields", $html ?? false)->with("eService", $eService)->with("salon", $salon)->with("category", $category)->with("eServicesSelected", $eServicesSelected)->with("salonsSelected", $salonsSelected)->with("categoriesSelected", $categoriesSelected);
    }

    /**
     * Update the specified Coupon in storage.
     *
     * @param int $id
     * @param UpdateCouponRequest $request
     *
     * @return RedirectResponse
     * @throws RepositoryException
     */
    public function update(int $id, UpdateCouponRequest $request): RedirectResponse
    {
        $this->couponRepository->pushCriteria(new CouponsOfUserCriteria(auth()->id()));

        $coupon = $this->couponRepository->all()->firstWhere('id', '=', $id);

        if (empty($coupon)) {
            Flash::error(__('lang.not_found', ['operator' => __('lang.coupon')]));
            return redirect(route('coupons.index'));
        }
        $input = $request->all();
        unset($input['code']);
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->couponRepository->model());
        try {
            $coupon = $this->couponRepository->update($input, $id);
            $discountables = $this->initDiscountables($input);
            $coupon->discountables()->delete();
            $coupon->discountables()->createMany($discountables);


            foreach (getCustomFieldsValues($customFields, $request) as $value) {
                $coupon->customFieldsValues()
                    ->updateOrCreate(['custom_field_id' => $value['custom_field_id']], $value);
            }
        } catch (ValidatorException $e) {
            Flash::error($e->getMessage());
        }

        Flash::success(__('lang.updated_successfully', ['operator' => __('lang.coupon')]));

        return redirect(route('coupons.index'));
    }

    /**
     * Remove the specified Coupon from storage.
     *
     * @param int $id
     *
     * @return RedirectResponse
     */
    public function destroy(int $id): RedirectResponse
    {
        $coupon = $this->couponRepository->findWithoutFail($id);

        if (empty($coupon)) {
            Flash::error(__('lang.not_found', ['operator' => __('lang.coupon')]));

            return redirect(route('coupons.index'));
        }

        $this->couponRepository->delete($id);

        Flash::success(__('lang.deleted_successfully', ['operator' => __('lang.coupon')]));

        return redirect(route('coupons.index'));
    }
}
