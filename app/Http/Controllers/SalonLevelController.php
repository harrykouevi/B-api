<?php
/*
 * File name: SalonLevelController.php
 * Last modified: 2024.04.18 at 17:22:50
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Http\Controllers;

use App\DataTables\SalonLevelDataTable;
use App\Http\Requests\CreateSalonLevelRequest;
use App\Http\Requests\UpdateSalonLevelRequest;
use App\Repositories\CustomFieldRepository;
use App\Repositories\SalonLevelRepository;
use Exception;
use Flash;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;
use Prettus\Validator\Exceptions\ValidatorException;

class SalonLevelController extends Controller
{
    /** @var  SalonLevelRepository */
    private SalonLevelRepository $salonLevelRepository;

    /**
     * @var CustomFieldRepository
     */
    private CustomFieldRepository $customFieldRepository;


    public function __construct(SalonLevelRepository $salonLevelRepo, CustomFieldRepository $customFieldRepo)
    {
        parent::__construct();
        $this->salonLevelRepository = $salonLevelRepo;
        $this->customFieldRepository = $customFieldRepo;

    }

    /**
     * Display a listing of the SalonLevel.
     *
     * @param SalonLevelDataTable $salonLevelDataTable
     * @return Response
     */
    public function index(SalonLevelDataTable $salonLevelDataTable): mixed
    {
        return $salonLevelDataTable->render('salon_levels.index');
    }

    /**
     * Store a newly created SalonLevel in storage.
     *
     * @param CreateSalonLevelRequest $request
     *
     * @return RedirectResponse
     */
    public function store(CreateSalonLevelRequest $request): RedirectResponse
    {
        $input = $request->all();
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->salonLevelRepository->model());
        try {
            $salonLevel = $this->salonLevelRepository->create($input);
            $salonLevel->customFieldsValues()->createMany(getCustomFieldsValues($customFields, $request));

        } catch (ValidatorException $e) {
            Flash::error($e->getMessage());
        }

        Flash::success(__('lang.saved_successfully', ['operator' => __('lang.salon_level')]));

        return redirect(route('salonLevels.index'));
    }

    /**
     * Show the form for creating a new SalonLevel.
     *
     * @return View
     */
    public function create(): View
    {


        $hasCustomField = in_array($this->salonLevelRepository->model(), setting('custom_field_models', []));
        if ($hasCustomField) {
            $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->salonLevelRepository->model());
            $html = generateCustomField($customFields);
        }
        return view('salon_levels.create')->with("customFields", $html ?? false);
    }

    /**
     * Display the specified SalonLevel.
     *
     * @param int $id
     *
     * @return RedirectResponse|View
     */
    public function show(int $id): RedirectResponse|View
    {
        $salonLevel = $this->salonLevelRepository->findWithoutFail($id);

        if (empty($salonLevel)) {
            Flash::error('E Provider Type not found');

            return redirect(route('salonLevels.index'));
        }

        return view('salon_levels.show')->with('salonLevel', $salonLevel);
    }

    /**
     * Show the form for editing the specified SalonLevel.
     *
     * @param int $id
     *
     * @return RedirectResponse|View
     */
    public function edit(int $id): RedirectResponse|View
    {
        $salonLevel = $this->salonLevelRepository->findWithoutFail($id);


        if (empty($salonLevel)) {
            Flash::error(__('lang.not_found', ['operator' => __('lang.salon_level')]));

            return redirect(route('salonLevels.index'));
        }
        $customFieldsValues = $salonLevel->customFieldsValues()->with('customField')->get();
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->salonLevelRepository->model());
        $hasCustomField = in_array($this->salonLevelRepository->model(), setting('custom_field_models', []));
        if ($hasCustomField) {
            $html = generateCustomField($customFields, $customFieldsValues);
        }

        return view('salon_levels.edit')->with('salonLevel', $salonLevel)->with("customFields", $html ?? false);
    }

    /**
     * Update the specified SalonLevel in storage.
     *
     * @param int $id
     * @param UpdateSalonLevelRequest $request
     *
     * @return RedirectResponse
     */
    public function update(int $id, UpdateSalonLevelRequest $request): RedirectResponse
    {
        $salonLevel = $this->salonLevelRepository->findWithoutFail($id);

        if (empty($salonLevel)) {
            Flash::error('E Provider Type not found');
            return redirect(route('salonLevels.index'));
        }
        $input = $request->all();
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->salonLevelRepository->model());
        try {
            $salonLevel = $this->salonLevelRepository->update($input, $id);


            foreach (getCustomFieldsValues($customFields, $request) as $value) {
                $salonLevel->customFieldsValues()
                    ->updateOrCreate(['custom_field_id' => $value['custom_field_id']], $value);
            }
        } catch (ValidatorException $e) {
            Flash::error($e->getMessage());
        }

        Flash::success(__('lang.updated_successfully', ['operator' => __('lang.salon_level')]));

        return redirect(route('salonLevels.index'));
    }

    /**
     * Remove the specified SalonLevel from storage.
     *
     * @param int $id
     *
     * @return RedirectResponse
     */
    public function destroy(int $id): RedirectResponse
    {
        $salonLevel = $this->salonLevelRepository->findWithoutFail($id);

        if (empty($salonLevel)) {
            Flash::error('E Provider Type not found');

            return redirect(route('salonLevels.index'));
        }

        $this->salonLevelRepository->delete($id);

        Flash::success(__('lang.deleted_successfully', ['operator' => __('lang.salon_level')]));

        return redirect(route('salonLevels.index'));
    }

    /**
     * Remove Media of SalonLevel
     * @param Request $request
     */
    public function removeMedia(Request $request): void
    {
        $input = $request->all();
        $salonLevel = $this->salonLevelRepository->findWithoutFail($input['id']);
        try {
            if ($salonLevel->hasMedia($input['collection'])) {
                $salonLevel->getFirstMedia($input['collection'])->delete();
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
    }
}
