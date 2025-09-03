<?php

namespace App\Service;

use App\Entity\Transaction;
use App\Entity\User;
use App\Entity\Country;
use App\Entity\EchangeRate;
use App\Entity\Operator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

class TransactionService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Créer une nouvelle transaction
     */
    public function makeTransaction(array $data): Transaction
    {
        // Validation des données requises
        $this->validateTransactionData($data);

        $transaction = new Transaction();

        // Récupération des entités liées
        $sender = $this->entityManager->getRepository(User::class)->find($data['sender_id']);
        $receiver = $this->entityManager->getRepository(User::class)->find($data['receiver_id']);
        $fromCountry = $this->entityManager->getRepository(Country::class)->find($data['from_country_id']);
        $toCountry = $this->entityManager->getRepository(Country::class)->find($data['to_country_id']);
        $exchangeRate = $this->entityManager->getRepository(EchangeRate::class)->find($data['exchange_rate_id']);

        if (!$sender || !$receiver || !$fromCountry || !$toCountry || !$exchangeRate) {
            throw new BadRequestException('Une ou plusieurs entités liées sont introuvables');
        }

        // Configuration de la transaction
        $transaction->setSender($sender)
            ->setReceiver($receiver)
            ->setFromCountry($fromCountry)
            ->setToCountry($toCountry)
            ->setAmountSent($data['amount_sent'])
            ->setAmountReceived($data['amount_received'])
            ->setAmountSendCode($data['amount_send_code'])
            ->setAmountReceivedCode($data['amount_received_code'])
            ->setExchangeRate($exchangeRate)
            ->setTransactionType($data['transaction_type'])
            ->setPaymentMethod($data['payment_method'])
            ->setTransactionRef($this->generateTransactionRef())
            ->setStatus(true)
            ->setTransStatus('INITIATED')
            ->setIniatedAt(new \DateTimeImmutable())
            ->setCreatedAt(new \DateTimeImmutable());

        // Champs optionnels
        if (isset($data['trans_fees'])) {
            $transaction->setTransFees($data['trans_fees']);

            // Calcul automatique de amount_win pour définir combien on gagne à chaque transaction by #tcheloooo
            $amountSent = (float) $data['amount_sent'];
            $feePercentage = (float) $data['trans_fees'];
            $amountWin = ($amountSent * $feePercentage) / 100;
            $transaction->setAmountWin(number_format($amountWin, 2, '.', ''));
        }

        if (isset($data['note'])) {
            $transaction->setNote($data['note']);
        }

        if (isset($data['operator_sender_id'])) {
            $operatorSender = $this->entityManager->getRepository(Operator::class)->find($data['operator_sender_id']);
            if ($operatorSender) {
                $transaction->setOperatorSender($operatorSender);
            }
        }

        if (isset($data['operator_receiver_id'])) {
            $operatorReceiver = $this->entityManager->getRepository(Operator::class)->find($data['operator_receiver_id']);
            if ($operatorReceiver) {
                $transaction->setOperatorReceiver($operatorReceiver);
            }
        }

        try {
            $this->entityManager->persist($transaction);
            $this->entityManager->flush();

            return $transaction;
        } catch (UniqueConstraintViolationException $e) {
            throw new BadRequestException('Une transaction avec cette référence existe déjà');
        }
    }

    /**
     * Mettre à jour une transaction
     */
    public function updateTransaction(int $id, array $data): Transaction
    {
        $transaction = $this->findTransactionById($id);

        // Mise à jour des champs modifiables
        if (isset($data['amount_sent'])) {
            $transaction->setAmountSent($data['amount_sent']);
        }

        if (isset($data['amount_received'])) {
            $transaction->setAmountReceived($data['amount_received']);
        }

        if (isset($data['trans_fees'])) {
            $transaction->setTransFees($data['trans_fees']);
        }

        if (isset($data['trans_status'])) {
            $transaction->setTransStatus($data['trans_status']);

            // Mise à jour automatique des timestamps selon le statut
            switch (strtoupper($data['trans_status'])) {
                case 'COMPLETED':
                    $transaction->setCompletedAt(new \DateTimeImmutable());
                    break;
                case 'FAILED':
                    $transaction->setFailedAt(new \DateTimeImmutable());
                    break;
            }
        }

        if (isset($data['note'])) {
            $transaction->setNote($data['note']);
        }

        if (isset($data['payment_method'])) {
            $transaction->setPaymentMethod($data['payment_method']);
        }

        // Mise à jour des relations si nécessaire
        if (isset($data['exchange_rate_id'])) {
            $exchangeRate = $this->entityManager->getRepository(EchangeRate::class)->find($data['exchange_rate_id']);
            if ($exchangeRate) {
                $transaction->setExchangeRate($exchangeRate);
            }
        }

        $transaction->setUpdatedAt(new \DateTime());

        $this->entityManager->flush();

        return $transaction;
    }

    /**
     * Soft delete d'une transaction
     */
    public function softDeleteTransaction(int $id): bool
    {
        $transaction = $this->findTransactionById($id);

        $transaction->setStatus(false);
        $transaction->setTransStatus('DELETED');
        $transaction->setUpdatedAt(new \DateTime());

        $this->entityManager->flush();

        return true;
    }

    /**
     * Récupérer une transaction par ID
     */
    public function getTransactionById(int $id): Transaction
    {
        return $this->findTransactionById($id);
    }

    /**
     * Récupérer toutes les transactions d'un utilisateur (envoyées et reçues)
     */
    public function getUserTransactions(int $userId, array $filters = []): array
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('t')
            ->from(Transaction::class, 't')
            ->leftJoin('t.sender', 's')
            ->leftJoin('t.receiver', 'r')
            ->where('s.id = :userId OR r.id = :userId')
            ->andWhere('t.status = true')
            ->setParameter('userId', $userId);

        // Filtres optionnels
        if (isset($filters['trans_status'])) {
            $qb->andWhere('t.trans_status = :trans_status')
                ->setParameter('trans_status', $filters['trans_status']);
        }

        if (isset($filters['transaction_type'])) {
            $qb->andWhere('t.transaction_type = :transaction_type')
                ->setParameter('transaction_type', $filters['transaction_type']);
        }

        if (isset($filters['date_from'])) {
            $qb->andWhere('t.created_at >= :date_from')
                ->setParameter('date_from', new \DateTime($filters['date_from']));
        }

        if (isset($filters['date_to'])) {
            $qb->andWhere('t.created_at <= :date_to')
                ->setParameter('date_to', new \DateTime($filters['date_to']));
        }

        // Tri par date de création décroissante
        $qb->orderBy('t.created_at', 'DESC');

        // Pagination
        if (isset($filters['limit'])) {
            $qb->setMaxResults($filters['limit']);
        }

        if (isset($filters['offset'])) {
            $qb->setFirstResult($filters['offset']);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Récupérer les transactions envoyées par un utilisateur
     */
    public function getSentTransactions(int $userId, array $filters = []): array
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('t')
            ->from(Transaction::class, 't')
            ->join('t.sender', 's')
            ->where('s.id = :userId')
            ->andWhere('t.status = true')
            ->setParameter('userId', $userId)
            ->orderBy('t.created_at', 'DESC');

        $this->applyFilters($qb, $filters);

        return $qb->getQuery()->getResult();
    }

    /**
     * Récupérer les transactions reçues par un utilisateur
     */
    public function getReceivedTransactions(int $userId, array $filters = []): array
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('t')
            ->from(Transaction::class, 't')
            ->join('t.receiver', 'r')
            ->where('r.id = :userId')
            ->andWhere('t.status = true')
            ->setParameter('userId', $userId)
            ->orderBy('t.created_at', 'DESC');

        $this->applyFilters($qb, $filters);

        return $qb->getQuery()->getResult();
    }

    /**
     * Récupérer toutes les transactions (admin)
     */
    public function getAllTransactions(array $filters = []): array
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('t')
            ->from(Transaction::class, 't')
            ->orderBy('t.created_at', 'DESC');

        // Ne pas filtrer par status si on veut voir les transactions supprimées
        if (!isset($filters['include_deleted']) || !$filters['include_deleted']) {
            $qb->where('t.status = true');
        }

        $this->applyFilters($qb, $filters);

        return $qb->getQuery()->getResult();
    }

    /**
     * Statistiques des transactions d'un utilisateur
     */
    public function getUserTransactionStats(int $userId): array
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('COUNT(t.id) as total, SUM(t.amount_sent) as total_sent, t.trans_status')
            ->from(Transaction::class, 't')
            ->leftJoin('t.sender', 's')
            ->leftJoin('t.receiver', 'r')
            ->where('s.id = :userId OR r.id = :userId')
            ->andWhere('t.status = true')
            ->setParameter('userId', $userId)
            ->groupBy('t.trans_status');

        return $qb->getQuery()->getResult();
    }

    /**
     * Rechercher des transactions par référence
     */
    public function searchByReference(string $reference): array
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('t')
            ->from(Transaction::class, 't')
            ->where('t.transaction_ref LIKE :reference')
            ->andWhere('t.status = true')
            ->setParameter('reference', '%' . $reference . '%')
            ->orderBy('t.created_at', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Méthodes privées
     */
    private function findTransactionById(int $id): Transaction
    {
        $transaction = $this->entityManager->getRepository(Transaction::class)->find($id);

        if (!$transaction || !$transaction->isStatus()) {
            throw new NotFoundHttpException('Transaction non trouvée');
        }

        return $transaction;
    }

    private function validateTransactionData(array $data): void
    {
        $required = [
            'sender_id',
            'receiver_id',
            'from_country_id',
            'to_country_id',
            'amount_sent',
            'amount_received',
            'amount_send_code',
            'amount_received_code',
            'exchange_rate_id',
            'transaction_type',
            'payment_method'
        ];

        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new BadRequestException("Le champ '{$field}' est requis");
            }
        }

        // Validation des montants
        if (!is_numeric($data['amount_sent']) || $data['amount_sent'] <= 0) {
            throw new BadRequestException('Le montant envoyé doit être un nombre positif');
        }

        if (!is_numeric($data['amount_received']) || $data['amount_received'] <= 0) {
            throw new BadRequestException('Le montant reçu doit être un nombre positif');
        }
    }

    private function generateTransactionRef(): string
    {
        return 'TXN_' . strtoupper(uniqid()) . '_' . time();
    }

    private function applyFilters($qb, array $filters): void
    {
        if (isset($filters['trans_status'])) {
            $qb->andWhere('t.trans_status = :trans_status')
                ->setParameter('trans_status', $filters['trans_status']);
        }

        if (isset($filters['transaction_type'])) {
            $qb->andWhere('t.transaction_type = :transaction_type')
                ->setParameter('transaction_type', $filters['transaction_type']);
        }

        if (isset($filters['date_from'])) {
            $qb->andWhere('t.created_at >= :date_from')
                ->setParameter('date_from', new \DateTime($filters['date_from']));
        }

        if (isset($filters['date_to'])) {
            $qb->andWhere('t.created_at <= :date_to')
                ->setParameter('date_to', new \DateTime($filters['date_to']));
        }

        if (isset($filters['limit'])) {
            $qb->setMaxResults($filters['limit']);
        }

        if (isset($filters['offset'])) {
            $qb->setFirstResult($filters['offset']);
        }
    }
}
