<?php

namespace App\Service;

use App\Repository\TransactionRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class DashboardService
{
    private TransactionRepository $transactionRepository;
    private UserRepository $userRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(
        TransactionRepository $transactionRepository,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->transactionRepository = $transactionRepository;
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
    }

    /**
     * Récupère les statistiques globales du dashboard
     */
    public function getGlobalStats(): array
    {
        // Total des transactions
        $totalTransactions = $this->transactionRepository->count([]);

        // Volume d'affaires pour le Gabon (transactions SUCCESS)
        $businessVolume = $this->getBusinessVolumeForGabon();

        // Utilisateurs actifs avec au moins une transaction
        $activeUsers = $this->getActiveUsersCount();

        // Taux d'erreur des transactions
        $errorRate = $this->getTransactionErrorRate();

        return [
            'total_transactions' => $totalTransactions,
            'business_volume' => $businessVolume,
            'active_users' => $activeUsers,
            'error_rate' => $errorRate
        ];
    }

    /**
     * Récupère les 5 dernières transactions avec informations minimales
     */
    public function getLastTransactions(): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        
        $transactions = $qb->select('t.id, t.amount_sent, t.trans_status, t.transaction_ref, t.created_at')
            ->addSelect('s.first_name as sender_first_name, s.last_name as sender_last_name')
            ->addSelect('r.first_name as receiver_first_name, r.last_name as receiver_last_name')
            ->addSelect('sc.currency_code as sender_currency_code')
            ->from('App\Entity\Transaction', 't')
            ->leftJoin('t.sender', 's')
            ->leftJoin('t.receiver', 'r')
            ->leftJoin('s.country', 'sc') // Join avec le pays de l'expéditeur
            ->orderBy('t.created_at', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        return array_map(function($transaction) {
            $currencyCode = $transaction['sender_currency_code'] ?? 'USD'; // Devise par défaut si pas trouvée
            $amount = $transaction['amount_sent'];
            
            return [
                'sender' => $transaction['sender_first_name'] . ' ' . $transaction['sender_last_name'],
                'receiver' => $transaction['receiver_first_name'] . ' ' . $transaction['receiver_last_name'],
                'amount' => $amount,
                'formatted_amount' => number_format((float)$amount, 0, '.', ',') . ' ' . $currencyCode,
                'currency_code' => $currencyCode,
                'status' => $transaction['trans_status'],
                'transaction_ref' => $transaction['transaction_ref'],
                'date' => $transaction['created_at']->format('Y-m-d H:i:s')
            ];
        }, $transactions);
    }

    /**
     * Récupère les performances des dernières 24h (depuis minuit)
     */
    public function getDailyPerformance(): array
    {
        $startOfDay = new \DateTime('today'); // Minuit aujourd'hui
        $now = new \DateTime();

        // Transactions traitées avec succès depuis minuit
        $successfulTransactions = $this->getSuccessfulTransactionsSince($startOfDay);

        // Nombre total de transactions depuis minuit
        $totalTransactions = $this->getTotalTransactionsSince($startOfDay);

        // Nouveaux utilisateurs depuis minuit
        $newUsers = $this->getNewUsersSince($startOfDay);

        // Taux d'erreur depuis minuit
        $errorRate = $this->getErrorRateSince($startOfDay);

        return [
            'successful_transactions' => $successfulTransactions,
            'total_transactions' => $totalTransactions,
            'new_users' => $newUsers,
            'error_rate' => $errorRate,
            'period_start' => $startOfDay->format('Y-m-d H:i:s'),
            'period_end' => $now->format('Y-m-d H:i:s')
        ];
    }

    /**
     * Calcule le volume d'affaires pour le Gabon
     */
    private function getBusinessVolumeForGabon(): float
    {
        $qb = $this->entityManager->createQueryBuilder();
        
        $result = $qb->select('SUM(t.amount_sent) as total_volume')
            ->from('App\Entity\Transaction', 't')
            ->leftJoin('t.from_country', 'fc')
            ->where('(UPPER(t.trans_status) = :success1 OR UPPER(t.trans_status) = :success2)')
            ->andWhere('fc.name = :gabon')
            ->setParameter('success1', 'SUCCESS')
            ->setParameter('success2', 'SUCCES')
            ->setParameter('gabon', 'Gabon')
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    /**
     * Compte les utilisateurs actifs avec au moins une transaction
     */
    private function getActiveUsersCount(): int
    {
        $qb = $this->entityManager->createQueryBuilder();
        
        return $qb->select('COUNT(DISTINCT u.id)')
            ->from('App\Entity\User', 'u')
            ->leftJoin('u.transaction_sender', 'ts')
            ->leftJoin('u.transaction_receiver', 'tr')
            ->where('ts.id IS NOT NULL OR tr.id IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Calcule le taux d'erreur global des transactions
     */
    private function getTransactionErrorRate(): float
    {
        $totalTransactions = $this->transactionRepository->count([]);
        
        if ($totalTransactions === 0) {
            return 0;
        }

        $qb = $this->entityManager->createQueryBuilder();
        
        $failedTransactions = $qb->select('COUNT(t.id)')
            ->from('App\Entity\Transaction', 't')
            ->where('UPPER(t.trans_status) IN (:failed_statuses)')
            ->setParameter('failed_statuses', ['ECHOUÉE', 'FAILED', 'EN COURS'])
            ->getQuery()
            ->getSingleScalarResult();

        return round(($failedTransactions / $totalTransactions) * 100, 2);
    }

    /**
     * Compte les transactions avec succès depuis une date donnée
     */
    private function getSuccessfulTransactionsSince(\DateTime $since): int
    {
        $qb = $this->entityManager->createQueryBuilder();
        
        return $qb->select('COUNT(t.id)')
            ->from('App\Entity\Transaction', 't')
            ->where('t.created_at >= :since')
            ->andWhere('(UPPER(t.trans_status) = :success1 OR UPPER(t.trans_status) = :success2)')
            ->setParameter('since', $since)
            ->setParameter('success1', 'SUCCESS')
            ->setParameter('success2', 'SUCCES')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte le total des transactions depuis une date donnée
     */
    private function getTotalTransactionsSince(\DateTime $since): int
    {
        $qb = $this->entityManager->createQueryBuilder();
        
        return $qb->select('COUNT(t.id)')
            ->from('App\Entity\Transaction', 't')
            ->where('t.created_at >= :since')
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte les nouveaux utilisateurs depuis une date donnée
     */
    private function getNewUsersSince(\DateTime $since): int
    {
        $qb = $this->entityManager->createQueryBuilder();
        
        return $qb->select('COUNT(u.id)')
            ->from('App\Entity\User', 'u')
            ->where('u.created_at >= :since')
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Calcule le taux d'erreur depuis une date donnée
     */
    private function getErrorRateSince(\DateTime $since): float
    {
        $totalTransactions = $this->getTotalTransactionsSince($since);
        
        if ($totalTransactions === 0) {
            return 0;
        }

        $qb = $this->entityManager->createQueryBuilder();
        
        $failedTransactions = $qb->select('COUNT(t.id)')
            ->from('App\Entity\Transaction', 't')
            ->where('t.created_at >= :since')
            ->andWhere('UPPER(t.trans_status) IN (:failed_statuses)')
            ->setParameter('since', $since)
            ->setParameter('failed_statuses', ['ECHOUÉE', 'FAILED', 'EN COURS'])
            ->getQuery()
            ->getSingleScalarResult();

        return round(($failedTransactions / $totalTransactions) * 100, 2);
    }
}