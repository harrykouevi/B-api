<?php
/*
 * File name: OptionTemplateController.php
 * Last modified: 2024.04.18 at 17:22:51
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Http\Controllers;

use App\Http\Requests\CreateOptionTemplateRequest;
use App\Http\Requests\UpdateOptionTemplateRequest;
use App\Repositories\OptionTemplateRepository;
use App\Repositories\ServiceTemplateRepository;
use Flash;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Prettus\Validator\Exceptions\ValidatorException;

class OptionTemplateController extends Controller
{
    /** @var  OptionTemplateRepository */
    private OptionTemplateRepository $optionTemplateRepository;

    /** @var  ServiceTemplateRepository */
    private ServiceTemplateRepository $serviceTemplateRepository;

    public function __construct(OptionTemplateRepository $optionTemplateRepo, ServiceTemplateRepository $serviceTemplateRepo)
    {
        parent::__construct();
        $this->optionTemplateRepository = $optionTemplateRepo;
        $this->serviceTemplateRepository = $serviceTemplateRepo;
    }

    /**
     * Display a listing of the OptionTemplate.
     *
     * @return View
     */
    public function index(): View
    {
        $optionTemplates = $this->optionTemplateRepository->all();
        return view('option_templates.index')->with('optionTemplates', $optionTemplates);
    }

    /**
     * Store a newly created OptionTemplate in storage.
     *
     * @param CreateOptionTemplateRequest $request
     *
     * @return RedirectResponse
     */
    public function store(CreateOptionTemplateRequest $request): RedirectResponse
    {
        $input = $request->all();
        
        try {
            $optionTemplate = $this->optionTemplateRepository->create($input);
        } catch (ValidatorException $e) {
            Flash::error($e->getMessage());
        }

        Flash::success(__('lang.saved_successfully', ['operator' => __('lang.option_template')]));

        return redirect(route('option_templates.index'));
    }

    /**
     * Show the form for creating a new OptionTemplate.
     *
     * @return View
     */
    public function create(): View
    {
        $serviceTemplates = $this->serviceTemplateRepository->pluck('name', 'id');

        return view('option_templates.create')->with('serviceTemplates', $serviceTemplates);
    }

    /**
     * Display the specified OptionTemplate.
     *
     * @param int $id
     *
     * @return RedirectResponse|View
     */
    public function show(int $id): RedirectResponse|View
    {
        $optionTemplate = $this->optionTemplateRepository->findWithoutFail($id);

        if (empty($optionTemplate)) {
            Flash::error('Option Template not found');

            return redirect(route('option_templates.index'));
        }

        return view('option_templates.show')->with('optionTemplate', $optionTemplate);
    }

    /**
     * Show the form for editing the specified OptionTemplate.
     *
     * @param int $id
     *
     * @return RedirectResponse|View
     */
    public function edit(int $id): RedirectResponse|View
    {
        $optionTemplate = $this->optionTemplateRepository->findWithoutFail($id);
        $serviceTemplates = $this->serviceTemplateRepository->pluck('name', 'id');

        if (empty($optionTemplate)) {
            Flash::error(__('lang.not_found', ['operator' => __('lang.option_template')]));

            return redirect(route('option_templates.index'));
        }

        return view('option_templates.edit')->with('optionTemplate', $optionTemplate)->with('serviceTemplates', $serviceTemplates);
    }

    /**
     * Update the specified OptionTemplate in storage.
     *
     * @param int $id
     * @param UpdateOptionTemplateRequest $request
     *
     * @return RedirectResponse
     */
    public function update(int $id, UpdateOptionTemplateRequest $request): RedirectResponse
    {
        $optionTemplate = $this->optionTemplateRepository->findWithoutFail($id);

        if (empty($optionTemplate)) {
            Flash::error('Option Template not found');
            return redirect(route('option_templates.index'));
        }
        
        $input = $request->all();
        
        try {
            $optionTemplate = $this->optionTemplateRepository->update($input, $id);
        } catch (ValidatorException $e) {
            Flash::error($e->getMessage());
        }

        Flash::success(__('lang.updated_successfully', ['operator' => __('lang.option_template')]));

        return redirect(route('option_templates.index'));
    }

    /**
     * Remove the specified OptionTemplate from storage.
     *
     * @param int $id
     *
     * @return RedirectResponse
     */
    public function destroy(int $id): RedirectResponse
    {
        $optionTemplate = $this->optionTemplateRepository->findWithoutFail($id);

        if (empty($optionTemplate)) {
            Flash::error('Option Template not found');

            return redirect(route('option_templates.index'));
        }

        $this->optionTemplateRepository->delete($id);

        Flash::success(__('lang.deleted_successfully', ['operator' => __('lang.option_template')]));

        return redirect(route('option_templates.index'));
    }
}
