<?php
/*
 * File name: AwardController.php
 * Last modified: 2024.04.10 at 12:26:07
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Http\Controllers;

use App\Criteria\Awards\AwardsOfUserCriteria;
use App\Criteria\Salons\SalonsOfUserCriteria;
use App\DataTables\AwardDataTable;
use App\Http\Requests\CreateAwardRequest;
use App\Http\Requests\UpdateAwardRequest;
use App\Repositories\AwardRepository;
use App\Repositories\CustomFieldRepository;
use App\Repositories\SalonRepository;
use Flash;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Prettus\Repository\Exceptions\RepositoryException;
use Prettus\Validator\Exceptions\ValidatorException;

class AwardController extends Controller
{
    /** @var  AwardRepository */
    private AwardRepository $awardRepository;

    /**
     * @var CustomFieldRepository
     */
    private CustomFieldRepository $customFieldRepository;

    /**
     * @var SalonRepository
     */
    private SalonRepository $salonRepository;

    public function __construct(AwardRepository $awardRepo, CustomFieldRepository $customFieldRepo, SalonRepository $salonRepo)
    {
        parent::__construct();
        $this->awardRepository = $awardRepo;
        $this->customFieldRepository = $customFieldRepo;
        $this->salonRepository = $salonRepo;
    }

    /**
     * Display a listing of the Award.
     *
     * @param AwardDataTable $awardDataTable
     * @return mixed
     */
    public function index(AwardDataTable $awardDataTable): mixed
    {
        return $awardDataTable->render('awards.index');
    }

    /**
     * Store a newly created Award in storage.
     *
     * @param CreateAwardRequest $request
     *
     * @return RedirectResponse
     */
    public function store(CreateAwardRequest $request): RedirectResponse
    {
        $input = $request->all();
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->awardRepository->model());
        try {
            $award = $this->awardRepository->create($input);
            $award->customFieldsValues()->createMany(getCustomFieldsValues($customFields, $request));

        } catch (ValidatorException $e) {
            Flash::error($e->getMessage());
        }

        Flash::success(__('lang.saved_successfully', ['operator' => __('lang.award')]));

        return redirect(route('awards.index'));
    }

    /**
     * Show the form for creating a new Award.
     *
     * @return View
     */
    public function create(): View
    {
        $salon = $this->salonRepository->getByCriteria(new SalonsOfUserCriteria(auth()->id()))->pluck('name', 'id');

        $hasCustomField = in_array($this->awardRepository->model(), setting('custom_field_models', []));
        if ($hasCustomField) {
            $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->awardRepository->model());
            $html = generateCustomField($customFields);
        }
        return view('awards.create')->with("customFields", $html ?? false)->with("salon", $salon);
    }

    /**
     * Display the specified Award.
     *
     * @param int $id
     *
     * @return View|RedirectResponse
     * @throws RepositoryException
     */
    public function show(int $id): RedirectResponse|View
    {
        $this->awardRepository->pushCriteria(new AwardsOfUserCriteria(auth()->id()));
        $award = $this->awardRepository->findWithoutFail($id);

        if (empty($award)) {
            Flash::error('Award not found');

            return redirect(route('awards.index'));
        }

        return view('awards.show')->with('award', $award);
    }

    /**
     * Show the form for editing the specified Award.
     *
     * @param int $id
     *
     * @return View|RedirectResponse
     * @throws RepositoryException
     */
    public function edit(int $id): RedirectResponse|View
    {
        $this->awardRepository->pushCriteria(new AwardsOfUserCriteria(auth()->id()));
        $award = $this->awardRepository->findWithoutFail($id);
        $salon = $this->salonRepository->getByCriteria(new SalonsOfUserCriteria(auth()->id()))->pluck('name', 'id');


        if (empty($award)) {
            Flash::error(__('lang.not_found', ['operator' => __('lang.award')]));

            return redirect(route('awards.index'));
        }
        $customFieldsValues = $award->customFieldsValues()->with('customField')->get();
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->awardRepository->model());
        $hasCustomField = in_array($this->awardRepository->model(), setting('custom_field_models', []));
        if ($hasCustomField) {
            $html = generateCustomField($customFields, $customFieldsValues);
        }

        return view('awards.edit')->with('award', $award)->with("customFields", $html ?? false)->with("salon", $salon);
    }

    /**
     * Update the specified Award in storage.
     *
     * @param int $id
     * @param UpdateAwardRequest $request
     *
     * @return RedirectResponse
     * @throws RepositoryException
     */
    public function update(int $id, UpdateAwardRequest $request): RedirectResponse
    {
        $this->awardRepository->pushCriteria(new AwardsOfUserCriteria(auth()->id()));
        $award = $this->awardRepository->findWithoutFail($id);

        if (empty($award)) {
            Flash::error('Award not found');
            return redirect(route('awards.index'));
        }
        $input = $request->all();
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->awardRepository->model());
        try {
            $award = $this->awardRepository->update($input, $id);


            foreach (getCustomFieldsValues($customFields, $request) as $value) {
                $award->customFieldsValues()
                    ->updateOrCreate(['custom_field_id' => $value['custom_field_id']], $value);
            }
        } catch (ValidatorException $e) {
            Flash::error($e->getMessage());
        }

        Flash::success(__('lang.updated_successfully', ['operator' => __('lang.award')]));

        return redirect(route('awards.index'));
    }

    /**
     * Remove the specified Award from storage.
     *
     * @param int $id
     *
     * @return RedirectResponse
     * @throws RepositoryException
     */
    public function destroy(int $id): RedirectResponse
    {
        $this->awardRepository->pushCriteria(new AwardsOfUserCriteria(auth()->id()));
        $award = $this->awardRepository->findWithoutFail($id);

        if (empty($award)) {
            Flash::error('Award not found');

            return redirect(route('awards.index'));
        }

        $this->awardRepository->delete($id);

        Flash::success(__('lang.deleted_successfully', ['operator' => __('lang.award')]));

        return redirect(route('awards.index'));
    }
}
