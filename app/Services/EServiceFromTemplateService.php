<?php

namespace App\Services;

use App\Models\EService;
use App\Models\Option;
use App\Models\OptionTemplate;
use App\Models\Salon;
use App\Models\ServiceTemplate;
use App\Repositories\EServiceRepository;
use Exception;
use Illuminate\Database\Eloquent\Model;

class EServiceFromTemplateService
{
    private EServiceRepository $eServiceRepository;

    public function __construct(EServiceRepository $eServiceRepository)
    {
        $this->eServiceRepository = $eServiceRepository;
    }

    /**
     * Create an EService from a ServiceTemplate with options
     *
     * @param array $data Input data containing template_id, price, duration, options, etc.
     * @param int $salonId The salon ID
     * @return EService The created EService with its options
     * @throws Exception
     */
    public function create(array $data, int $salonId): EService
    {
        // Validate salon exists
        Salon::findOrFail($salonId);

        // Get the service template
        $template = ServiceTemplate::findOrFail($data['template_id'] ?? null);

        // Prepare EService data
        $eServiceData = [
            'name' => $template->name,
            'description' => $template->description,
            'category_id' => $template->category_id,
            'salon_id' => $salonId,
            // Override with provided data
            'price' => $data['price'] ?? 0,
            'discount_price' => $data['discount_price'] ?? null,
            'duration' => $data['duration'] ?? null,
            'featured' => $data['featured'] ?? false,
            'enable_booking' => $data['enable_booking'] ?? true,
            'enable_at_salon' => $data['enable_at_salon'] ?? true,
            'enable_at_customer_address' => $data['enable_at_customer_address'] ?? false,
            'available' => $data['available'] ?? true,
        ];

        // Create the EService
        $eService = $this->eServiceRepository->create($eServiceData);

        // Copy images from template to the new service
        $this->copyImages($template, $eService);

        // Create options if provided
        if (isset($data['options']) && is_array($data['options'])) {
            $this->createOptionsFromTemplates($eService, $data['options']);
        }

        return $eService->load('options');
    }

    /**
     * Copy images from template to EService
     *
     * @param ServiceTemplate $template
     * @param EService $eService
     * @return void
     */
    private function copyImages(ServiceTemplate $template, EService $eService): void
    {
        if ($template->hasMedia('image')) {
            foreach ($template->getMedia('image') as $mediaItem) {
                $mediaItem->copy($eService, 'image');
            }
        }
    }

    /**
     * Create options from option templates
     *
     * @param EService $eService
     * @param array $optionsData Array of option data [['option_id' => id, 'price' => price], ...]
     * @return void
     * @throws Exception
     */
    private function createOptionsFromTemplates(EService $eService, array $optionsData): void
    {
        foreach ($optionsData as $optionData) {
            // Get the option template
            $optionTemplate = OptionTemplate::findOrFail($optionData['option_id'] ?? null);

            // Prepare option data
            $newOptionData = [
                'name' => $optionTemplate->name,
                'description' => $optionTemplate->description,
                'price' => $optionData['price'] ?? $optionTemplate->price,
                'e_service_id' => $eService->id,
                'option_group_id' => $optionData['option_group_id'] ?? null, // Optional now
            ];

            // Create the option
            Option::create($newOptionData);
        }
    }

    /**
     * Update an existing EService from template data
     *
     * @param int $eServiceId
     * @param array $data
     * @param int $salonId
     * @return EService
     * @throws Exception
     */
    public function update(int $eServiceId, array $data, int $salonId): EService
    {
        // Validate salon exists
        Salon::findOrFail($salonId);

        $eService = EService::findOrFail($eServiceId);

        // Verify the service belongs to the salon
        if ($eService->salon_id !== $salonId) {
            throw new Exception('Unauthorized: Service does not belong to this salon');
        }

        // Get the service template if provided
        if (isset($data['template_id'])) {
            $template = ServiceTemplate::findOrFail($data['template_id']);

            // Update service data from template
            $eService->update([
                'name' => $template->name,
                'description' => $template->description,
                'category_id' => $template->category_id,
            ]);

            // Update images if template changed
            if ($eService->hasMedia('image')) {
                $eService->getMedia('image')->each->delete();
            }
            $this->copyImages($template, $eService);
        }

        // Update provided fields
        $updateData = [];
        if (isset($data['price'])) {
            $updateData['price'] = $data['price'];
        }
        if (isset($data['discount_price'])) {
            $updateData['discount_price'] = $data['discount_price'];
        }
        if (isset($data['duration'])) {
            $updateData['duration'] = $data['duration'];
        }
        if (isset($data['featured'])) {
            $updateData['featured'] = $data['featured'];
        }
        if (isset($data['enable_booking'])) {
            $updateData['enable_booking'] = $data['enable_booking'];
        }
        if (isset($data['enable_at_salon'])) {
            $updateData['enable_at_salon'] = $data['enable_at_salon'];
        }
        if (isset($data['enable_at_customer_address'])) {
            $updateData['enable_at_customer_address'] = $data['enable_at_customer_address'];
        }
        if (isset($data['available'])) {
            $updateData['available'] = $data['available'];
        }

        if (!empty($updateData)) {
            $eService->update($updateData);
        }

        // Update options if provided
        if (isset($data['options']) && is_array($data['options'])) {
            // Delete existing options
            $eService->options()->delete();
            // Create new ones
            $this->createOptionsFromTemplates($eService, $data['options']);
        }

        return $eService->fresh()->load('options');
    }
}