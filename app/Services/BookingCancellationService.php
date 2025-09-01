<?php

namespace App\Services;

use App\Events\BookingStatusChangedEvent;
use App\Models\Booking;
use App\Repositories\BookingRepository;
use App\Repositories\BookingStatusRepository;
use Illuminate\Support\Facades\DB;
use Exception;

/**
 * Service de gestion des annulations de rendez-vous
 * 
 * Ce service encapsule toute la logique métier pour :
 * - Annuler un rendez-vous avec motif obligatoire
 * - Valider les conditions d'annulation
 * - Gérer les remboursements (TODO)
 */
class BookingCancellationService
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
     * Annulation d'un booking avec rembourssement le rembourssement n'est pas implemente 
     * mais les fonctions sont presentes et la logique sera a la place des TODO (precise dans l'entete)
     * j'ai essaye de commente les etapes pour te facilter la tache
     */
    public function cancelBooking(int $bookingId, string $reason, string $cancelledBy): array
    {
        return DB::transaction(function () use ($bookingId, $reason, $cancelledBy) {
            
            // ÉTAPE 1 : Valider le rendez-vous
            $booking = $this->validateBookingForCancellation($bookingId);
            
            // ÉTAPE 2 : Calculer le remboursement (TODO)
            $refundAmount = $this->calculateRefund($booking);
            
            // ÉTAPE 3 : Obtenir le statut "Failed" (ID 7)
            $failedStatus = $this->getFailedStatus();
            
            // ÉTAPE 4 : Marquer le rendez-vous comme annulé
            $this->markBookingAsCancelled($booking, $failedStatus, $reason, $cancelledBy);
            
            // ÉTAPE 5 : Traiter les remboursements (TODO)
            if ($refundAmount > 0) {
                $this->processRefund($booking, $refundAmount);
            }
            
            // ÉTAPE 6 : Déclencher les événements pour notifications
            event(new BookingStatusChangedEvent($booking->fresh()));
            
            return [
                'booking' => $booking->fresh(),
                'refund_amount' => $refundAmount,
                'message' => 'Rendez-vous annulé avec succès'
            ];
        });
    }

    private function validateBookingForCancellation(int $bookingId): Booking
    {
        $booking = $this->bookingRepository->findWithoutFail($bookingId);
        
        if (empty($booking)) {
            throw new Exception('Rendez-vous introuvable');
        }

        if (!$booking->canBeCancelled()) {
            throw new Exception('Ce rendez-vous ne peut pas être annulé');
        }

        return $booking;
    }

    private function getFailedStatus()
    {
        $status = $this->bookingStatusRepository->findByField('status', 'Failed')->first();
        if (!$status) {
            throw new Exception('Statut "Failed" introuvable. Vérifiez les seeders.');
        }
        return $status;
    }

    /**
     * MISE À JOUR : Marque le rendez-vous comme annulé
     */
    private function markBookingAsCancelled(Booking $booking, $failedStatus, string $reason, string $cancelledBy): void
    {
        $this->bookingRepository->update([
            'booking_status_id' => $failedStatus->id,
            'cancel' => true,
            'cancellation_reason' => $reason,
            'cancelled_by' => $cancelledBy,
            'cancelled_at' => now()
        ], $booking->id);
    }

    private function calculateRefund(Booking $booking): float
    {
        // TODO: Implémenter la logique de remboursement
        
        $hoursUntilBooking = now()->diffInHours($booking->booking_at, false);
        
        if ($hoursUntilBooking < 0) {
            // verifie si l'heure du rdv est deja depasse
            return 0.0;
        }
        
        $totalAmount = $booking->getTotal();


        // Calcul du pourcentage de remboursement en fonction du délai au cas ou daonc peut-etre modifer 
        if ($hoursUntilBooking >= 24) {
            return $totalAmount; // 100%
        } elseif ($hoursUntilBooking >= 12) {
            return $totalAmount * 0.8; // 80%
        } elseif ($hoursUntilBooking >= 2) {
            return $totalAmount * 0.5; // 50%
        } else {
            return 0.0; // 0%
        }
    }

    /**
     * REMBOURSEMENT : Traite le remboursement (TODO)
     */
    private function processRefund(Booking $booking, float $amount): void
    {
        // TODO: Implémenter la logique de remboursement

    }

    public function getUserCancellationHistory(int $userId): array
    {
        $cancelledBookings = $this->bookingRepository->findWhere([
            'user_id' => $userId,
            'cancel' => true
        ]);

        return $cancelledBookings->map(function ($booking) {
            return [
                'id' => $booking->id,
                'booking_at' => $booking->booking_at,
                'cancelled_at' => $booking->cancelled_at,
                'cancellation_reason' => $booking->cancellation_reason,
                'cancelled_by' => $booking->cancelled_by,
                'salon_name' => $booking->salon->name ?? 'Salon inconnu',
                'total_amount' => $booking->getTotal()
            ];
        })->toArray();
    }

    public function canUserCancelBooking(Booking $booking, int $userId, array $userRoles): bool
    {
        // Le client peut annuler ses propres rendez-vous
        if ($booking->user_id === $userId) {
            return true;
        }

        // Le propriétaire du salon peut annuler les rendez-vous de son salon
        if (in_array('salon owner', $userRoles)) {
            // TODO: Vérifier que l'utilisateur est bien propriétaire de ce salon
            return true;
        }

        // L'admin peut tout annuler
        if (in_array('admin', $userRoles)) {
            return true;
        }

        return false;
    }
}