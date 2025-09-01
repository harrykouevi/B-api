<?php

namespace App\Services;

use Exception;
use App\Models\Booking;
use Illuminate\Support\Facades\DB;
use App\Repositories\BookingRepository;
use App\Events\BookingStatusChangedEvent;
use App\Repositories\BookingStatusRepository;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

/**
 * Service de gestion des reports de rendez-vous
 * 
 * Ce service encapsule toute la logique métier pour :
 * - Reporter un rendez-vous (créer nouveau + marquer ancien)
 * - Récupérer l'historique des reports
 * - Valider les conditions de report
 */
class BookingReportService
{
    private BookingRepository $bookingRepository;
    private BookingStatusRepository $bookingStatusRepository;

    public function __construct(
        BookingRepository $bookingRepository,
        BookingStatusRepository $bookingStatusRepository
    ) {
        $this->bookingRepository = $bookingRepository;
        $this->bookingStatusRepository = $bookingStatusRepository;
    }

    /**
     * MÉTHODE PRINCIPALE : Reporte un rendez-vous
     */
    public function reportBooking(int $originalBookingId, array $newBookingData, ?string $reason = null): array
    {
        return DB::transaction(function () use ($originalBookingId, $newBookingData, $reason) {
            
            $originalBooking = $this->validateBookingForReport($originalBookingId);
            
            $reportedStatus = $this->getReportedStatus();
            
            $this->markBookingAsReported($originalBooking, $reportedStatus, $reason);
            
            $newBooking = $this->createReportedBooking($originalBooking, $newBookingData);
            
            event(new BookingStatusChangedEvent($originalBooking->fresh()));
            
            return [
                'original_booking' => $originalBooking->fresh(),
                'new_booking' => $newBooking->fresh(),
                'message' => 'Rendez-vous reporté avec succès'
            ];
        });
    }

    /**
     * VALIDATION : Vérifie qu'un rendez-vous peut être reporté
     */
    private function validateBookingForReport(int $bookingId): Booking
    {
        $booking = $this->bookingRepository->findWithoutFail($bookingId);
        
        if (empty($booking)) {
            throw new ResourceNotFoundException('Rendez-vous introuvable');
        }

        if (!$booking->canBeReported()) {
            throw new Exception('Ce rendez-vous ne peut pas être reporté');
        }

        return $booking;
    }

    /**
     * UTILITAIRE : Récupère le statut "Reported"
     */
    private function getReportedStatus()
    {
        $status = $this->bookingStatusRepository->findByField('status', 'Reported')->first();
        if (!$status) {
            throw new ResourceNotFoundException('Statut "Reported" introuvable. Vérifiez les seeders.');
        }
        return $status;
    }

    /**
     * MISE À JOUR : Marque le rendez-vous original comme reporté
     */
    private function markBookingAsReported(Booking $booking, $reportedStatus, ?string $reason): void
    {
        $this->bookingRepository->update([
            'booking_status_id' => $reportedStatus->id,
            'report_reason' => $reason
        ], $booking->id);
    }

    /**
     * CRÉATION : Nouveau rendez-vous avec données copiées
     */
    private function createReportedBooking(Booking $originalBooking, array $newBookingData): Booking
    {
        $newBookingInput = [
            // === DONNÉES COPIÉES DE L'ORIGINAL ===
            'salon' => $originalBooking->salon,
            'e_services' => $originalBooking->e_services,
            'options' => $originalBooking->options,
            'quantity' => $originalBooking->quantity,
            'user_id' => $originalBooking->user_id,
            'employee_id' => $originalBooking->employee_id,
            'address' => $originalBooking->address,
            'payment_id' => $originalBooking->payment_id,
            'coupon' => $originalBooking->coupon,
            'taxes' => $originalBooking->taxes,
            'hint' => $originalBooking->hint,
            
            // === NOUVEAU CYCLE DE VIE ===
            'booking_status_id' => 1, // Received - recommence à zéro
            'cancel' => false,
            
            // === LIENS DE TRAÇABILITÉ ===
            'reported_from_id' => $originalBooking->id,
            'original_booking_id' => $originalBooking->original_booking_id ?: $originalBooking->id,
            
            // === NOUVELLES DATES ===
            'booking_at' => $newBookingData['booking_at'],
            'start_at' => $newBookingData['start_at'] ?? null,
            'ends_at' => $newBookingData['ends_at'] ?? null,
        ];

        return $this->bookingRepository->create($newBookingInput);
    }

    /**
     * HISTORIQUE : Récupère tous les reports d'une chaîne
     */
    public function getReportHistory(int $bookingId): array
    {
        $booking = $this->bookingRepository->findWithoutFail($bookingId);
        if (empty($booking)) {
            throw new ResourceNotFoundException('Rendez-vous introuvable');
        }

        // Identifier la racine de la chaîne
        $originalId = $booking->original_booking_id ?: $bookingId;
        
        // Récupérer TOUS les rendez-vous de cette chaîne
        $chainBookings = $this->bookingRepository->scopeQuery(function($query) use ($originalId) {
            return $query->where(function($q) use ($originalId) {
                $q->where('id', $originalId)
                  ->orWhere('original_booking_id', $originalId);
            })->orderBy('created_at');
        })->all();
        
        return $chainBookings->map(function ($booking) {
            return [
                'id' => $booking->id,
                'booking_at' => $booking->booking_at,
                'status' => $booking->bookingStatus->status ?? 'Unknown',
                'report_reason' => $booking->report_reason,
                'created_at' => $booking->created_at,
                'is_original' => is_null($booking->reported_from_id),
                'is_current' => !in_array($booking->booking_status_id, [6, 7, 8]) // Actif
            ];
        })->toArray();
    }

    /**
     * VÉRIFICATION : Disponibilité d'un créneau
     */
    public function isTimeSlotAvailable(array $newBookingData, int $salonId, ?int $employeeId = null): bool
    {
        $bookingDate = new \DateTime($newBookingData['booking_at']);
        
        // Vérification basique : date future
        if ($bookingDate <= new \DateTime()) {
            return false;
        }

        // TODO: Ajouter vérifications avancées :

        
        return true;
    }
}