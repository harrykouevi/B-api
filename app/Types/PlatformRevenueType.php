<?php
/*
 * File name: PlatformRevenueType.php
 * Last modified: 2025.12.18
 * Author: CHARM Platform
 * Copyright (c) 2025
 */

namespace App\Types;

/**
 * Enum PlatformRevenueType
 *
 * Définit les différents types de revenus de la plateforme
 */
enum PlatformRevenueType: string
{
    /**
     * Commission sur les réservations payées
     * Prélevée sur le montant du service (ex: 10% de 1000F = 100F)
     */
    case COMMISSION = 'commission';

    /**
     * Pénalité d'annulation (client ou salon)
     * Montant fixe défini dans les settings (cancellation_charge)
     */
    case CANCELLATION_PENALTY = 'penalty';

    /**
     * Frais de réservation payés par le client
     * Défini dans les settings (booking_price)
     * Jamais remboursé
     */
    case BOOKING_FEE = 'booking_fee';

    /**
     * Frais de report de réservation
     * Défini dans les settings (postpone_charge)
     */
    case POSTPONE_FEE = 'postpone_fee';

    /**
     * Remboursement de commission (cas CLIENT annule)
     * Montant négatif dans la table
     */
    case COMMISSION_REFUND = 'commission_refund';
}
