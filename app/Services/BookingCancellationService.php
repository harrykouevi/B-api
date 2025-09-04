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
    private PaymentService $paymentService;

    public function __construct(
        BookingRepository $bookingRepository,
        BookingStatusRepository $bookingStatusRepository,
        PaymentService $paymentService
    ) {
        $this->bookingRepository = $bookingRepository;
        $this->bookingStatusRepository = $bookingStatusRepository;
        $this->paymentService = $paymentService;
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
            $refundAmount = $this->calculateRefund($booking, $cancelledBy);
            
            // ÉTAPE 3 : Obtenir le statut "Failed" (ID 7)
            $failedStatus = $this->getFailedStatus();
            
            // ÉTAPE 4 : Marquer le rendez-vous comme annulé
            $this->markBookingAsCancelled($booking, $failedStatus, $reason, $cancelledBy);
            
            // ÉTAPE 5 : Traiter les remboursements (TODO)
            if ($refundAmount > 0) {
                $this->processRefund($booking, $refundAmount, $cancelledBy);
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

    private function calculateRefund(Booking $booking, string $cancelledBy): float
    {
         $hoursUntilBooking = now()->diffInHours($booking->booking_at, false);
        
        if ($hoursUntilBooking < 0) {
            // Vérifie si l'heure du rdv est déjà dépassée
            return 0.0;
        }
        
        $totalAmount = $booking->getTotal();

        // Logique de remboursement selon qui annule
        switch ($cancelledBy) {
            case 'customer':
                // Remboursement partiel selon le délai
                if ($hoursUntilBooking >= 24) {
                    return $totalAmount; // 100%
                } elseif ($hoursUntilBooking >= 12) {
                    return $totalAmount * 0.8; // 80%
                } elseif ($hoursUntilBooking >= 2) {
                    return $totalAmount * 0.5; // 50%
                } else {
                    return 0.0; // 0%
                }
                
            case 'salon_owner':
                // Remboursement total mais débit du compte du salon
                return $totalAmount;
                
            case 'admin':
                // Remboursement total
                return $totalAmount;
                
            default:
                return 0.0;
        }
    }

    /**
     * REMBOURSEMENT : Traite le remboursement (TODO)
     */
    private function processRefund(Booking $booking, float $amount, string $cancelledBy): void
    {
        switch ($cancelledBy) {
            case 'customer':
                // Remboursement du client par la plateforme
                $this->paymentService->createPayment($amount, setting('app_default_wallet_id'), $booking->user);
                break;
                
            case 'salon_owner':
                // Remboursement total : débit du compte du salon et crédit du client
                // 1. Débit du compte du salon
                $salonOwner = $booking->salon->users()->first(); // Correction : utilisation de la méthode users() au lieu de la propriété users
                if ($salonOwner) {
                    $salonWallet = $this->paymentService->walletRepository->findByField('user_id', $salonOwner->id)->first();
                    if ($salonWallet) {
                        $this->paymentService->createPayment($amount, $salonWallet->id, \App\Models\User::find(setting('app_admin_user_id')));
                    }
                }
                // 2. Crédit du client par la plateforme
                $this->paymentService->createPayment($amount, setting('app_default_wallet_id'), $booking->user);
                break;
                
            case 'admin':
                // Remboursement par la plateforme
                $this->paymentService->createPayment($amount, setting('app_default_wallet_id'), $booking->user);
                break;
        }
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

            return $booking->salon && 
               collect($booking->salon->users)->contains('id', $userId);
        }

        // L'admin peut tout annuler
        if (in_array('admin', $userRoles)) {
            return true;
        }

        return false;
    }
}