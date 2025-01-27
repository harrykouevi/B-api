<?php
/*
 * File name: UpdateSalonEarningTableListener.php
 * Last modified: 2024.04.18 at 17:35:01
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Listeners;

use App\Repositories\EarningRepository;
use Prettus\Validator\Exceptions\ValidatorException;

/**
 * Class UpdateSalonEarningTableListener
 * @package App\Listeners
 */
class UpdateSalonEarningTableListener
{
    /**
     * @var EarningRepository
     */
    private EarningRepository $earningRepository;

    /**
     * EarningTableListener constructor.
     */
    public function __construct(EarningRepository $earningRepository)
    {

        $this->earningRepository = $earningRepository;
    }


    /**
     * Handle the event.
     *
     * @param object $event
     * @return void
     */
    public function handle(object $event): void
    {
        if ($event->newSalon->accepted) {
            $uniqueInput = ['salon_id' => $event->newSalon->id];
            try {
                $this->earningRepository->updateOrCreate($uniqueInput);
            } catch (ValidatorException $e) {
            }
        }
    }
}
