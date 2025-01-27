<?php
/*
 * File name: NotificationController.php
 * Last modified: 2024.04.10 at 14:47:28
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Http\Controllers;

use App\DataTables\NotificationDataTable;
use App\Http\Requests\CreateNotificationRequest;
use App\Http\Requests\UpdateNotificationRequest;
use App\Repositories\CustomFieldRepository;
use App\Repositories\NotificationRepository;
use App\Repositories\UserRepository;
use Carbon\Carbon;
use Exception;
use Flash;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Prettus\Validator\Exceptions\ValidatorException;

class NotificationController extends Controller
{
    /** @var  NotificationRepository */
    private NotificationRepository $notificationRepository;

    /**
     * @var CustomFieldRepository
     */
    private CustomFieldRepository $customFieldRepository;

    private UserRepository $userRepository;

    public function __construct(NotificationRepository $notificationRepo, CustomFieldRepository $customFieldRepo,
                                UserRepository         $userRepo)
    {
        parent::__construct();
        $this->notificationRepository = $notificationRepo;
        $this->customFieldRepository = $customFieldRepo;
        $this->userRepository = $userRepo;
    }

    /**
     * Display a listing of the Notification.
     *
     * @param NotificationDataTable $notificationDataTable
     * @return mixed
     */
    public function index(NotificationDataTable $notificationDataTable): mixed
    {
        return $notificationDataTable->render('notifications.index');
    }

    /**
     * Store a newly created Notification in storage.
     *
     * @param CreateNotificationRequest $request
     *
     * @return RedirectResponse
     */
    public function store(CreateNotificationRequest $request): RedirectResponse
    {
        $input = $request->all();
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->notificationRepository->model());
        try {
            $notification = $this->notificationRepository->create($input);
            $notification->customFieldsValues()->createMany(getCustomFieldsValues($customFields, $request));

        } catch (ValidatorException $e) {
            Flash::error($e->getMessage());
        }

        Flash::success(__('lang.saved_successfully', ['operator' => __('lang.notification')]));

        return redirect(route('notifications.index'));
    }

    /**
     * Show the form for creating a new Notification.
     *
     * @return View
     */
    public function create(): View
    {
        $user = $this->userRepository->pluck('name', 'id');

        $hasCustomField = in_array($this->notificationRepository->model(), setting('custom_field_models', []));
        if ($hasCustomField) {
            $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->notificationRepository->model());
            $html = generateCustomField($customFields);
        }
        return view('notifications.create')->with("customFields", $html ?? false)->with("user", $user);
    }

    /**
     * Display the specified Notification.
     *
     * @param string $id
     *
     * @return RedirectResponse|View
     */
    public function show(string $id): RedirectResponse|View
    {
        $notification = $this->notificationRepository->findWithoutFail($id);

        if (empty($notification)) {
            Flash::error(__('lang.not_found', ['operator' => __('lang.notification')]));

            return redirect(route('notifications.index'));
        }
        try {
            $this->notificationRepository->update(['read_at' => (new Carbon())], $id);
        } catch (Exception $e) {
            Flash::error($e->getMessage());
        }

        return redirect(route('notifications.index'));
    }

    /**
     * Update the specified Notification in storage.
     *
     * @param int $id
     * @param UpdateNotificationRequest $request
     *
     * @return RedirectResponse
     */
    public function update(int $id, UpdateNotificationRequest $request): RedirectResponse
    {
        $notification = $this->notificationRepository->findWithoutFail($id);

        if (empty($notification)) {
            Flash::error(__('lang.not_found', ['operator' => __('lang.notification')]));
            return redirect(route('notifications.index'));
        }
        $input = $request->all();
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->notificationRepository->model());
        try {
            $notification = $this->notificationRepository->update($input, $id);
            foreach (getCustomFieldsValues($customFields, $request) as $value) {
                $notification->customFieldsValues()
                    ->updateOrCreate(['custom_field_id' => $value['custom_field_id']], $value);
            }
        } catch (ValidatorException $e) {
            Flash::error($e->getMessage());
        }

        Flash::success(__('lang.updated_successfully', ['operator' => __('lang.notification')]));

        return redirect(route('notifications.index'));
    }

    /**
     * Show the form for editing the specified Notification.
     *
     * @param int $id
     *
     * @return RedirectResponse|View
     */
    public function edit(int $id): RedirectResponse|View
    {
        $notification = $this->notificationRepository->findWithoutFail($id);
        $user = $this->userRepository->pluck('name', 'id');


        if (empty($notification)) {
            Flash::error(__('lang.not_found', ['operator' => __('lang.notification')]));

            return redirect(route('notifications.index'));
        }
        $customFieldsValues = $notification->customFieldsValues()->with('customField')->get();
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->notificationRepository->model());
        $hasCustomField = in_array($this->notificationRepository->model(), setting('custom_field_models', []));
        if ($hasCustomField) {
            $html = generateCustomField($customFields, $customFieldsValues);
        }

        return view('notifications.edit')->with('notification', $notification)->with("customFields", $html ?? false)->with("user", $user);
    }

    /**
     * Remove the specified Notification from storage.
     *
     * @param int $id
     *
     * @return RedirectResponse
     */
    public function destroy(int $id): RedirectResponse
    {
        $notification = $this->notificationRepository->findWithoutFail($id);

        if (empty($notification)) {
            Flash::error(__('lang.not_found', ['operator' => __('lang.notification')]));

            return redirect(route('notifications.index'));
        }

        $this->notificationRepository->delete($id);

        Flash::success(__('lang.deleted_successfully', ['operator' => __('lang.notification')]));

        return redirect(route('notifications.index'));
    }
}
