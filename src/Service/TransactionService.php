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
     * 
     * @param array $data Données simplifiées depuis le frontend
     * @param User $currentUser L'utilisateur connecté (sender)
     * @return Transaction
     */
    public function makeTransaction(array $data, User $currentUser): Transaction
    {
        // Validation des données requises (simplifiées)
        $this->validateTransactionData($data);

        $transaction = new Transaction();

        // Récupération du receiver
        $receiver = $this->entityManager->getRepository(User::class)->find($data['receiver_id']);
        if (!$receiver) {
            throw new BadRequestException('Destinataire introuvable');
        }

        // Récupération automatique des pays depuis les utilisateurs
        $fromCountry = $currentUser->getCountry();
        $toCountry = $receiver->getCountry();
        
        if (!$fromCountry || !$toCountry) {
            throw new BadRequestException('Les utilisateurs doivent avoir un pays associé');
        }

        // Récupération de l'exchange_rate sélectionné (devise SOURCE)
        $sourceExchangeRate = $this->entityManager->getRepository(EchangeRate::class)->find($data['exchange_rate_id']);
        if (!$sourceExchangeRate) {
            throw new BadRequestException('Taux de change introuvable');
        }

        // Récupération de l'exchange_rate de la devise DESTINATION
        $destinationExchangeRate = $this->getUsdToCurrencyRate($toCountry->getCurrencyCode());
        if (!$destinationExchangeRate) {
            throw new BadRequestException(
                "Taux de change introuvable pour la devise destination ({$toCountry->getCurrencyCode()})"
            );
        }

        $amountSent = (float) $data['amount_sent'];
        
        // Conversion avec calcul dynamique de amount_received et amount_win
        $conversion = $this->convertCurrency(
            $amountSent,
            $sourceExchangeRate,
            $destinationExchangeRate
        );

        // Génération automatique des codes sécurisés
        $transactionRef = $this->generateTransactionRef();
        $amountSendCode = $this->generateSecureCode('SEND');
        $amountReceivedCode = $this->generateSecureCode('RECV');

        // Détermination automatique du type de transaction
        $transactionType = $data['transaction_type'] ?? 'ENVOI';

        // Configuration de la transaction
        $transaction->setSender($currentUser)
            ->setReceiver($receiver)
            ->setFromCountry($fromCountry)
            ->setToCountry($toCountry)
            ->setAmountSent($amountSent)
            ->setAmountReceived($conversion['amount_received'])
            ->setAmountSendCode($amountSendCode)
            ->setAmountReceivedCode($amountReceivedCode)
            ->setExchangeRate($sourceExchangeRate)
            ->setTransactionType($transactionType)
            ->setPaymentMethod($data['payment_method'])
            ->setTransactionRef($transactionRef)
            ->setStatus(true)
            ->setTransStatus('INITIATED')
            ->setAmountWin($conversion['amount_win'])
            ->setIniatedAt(new \DateTimeImmutable())
            ->setCreatedAt(new \DateTimeImmutable());

        // Champs optionnels
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
     * ===================================================================
     * MÉTHODES PRIVÉES
     * ===================================================================
     */

    private function findTransactionById(int $id): Transaction
    {
        $transaction = $this->entityManager->getRepository(Transaction::class)->find($id);

        if (!$transaction || !$transaction->isStatus()) {
            throw new NotFoundHttpException('Transaction non trouvée');
        }

        return $transaction;
    }

    /**
     * Validation simplifiée - les codes sont générés automatiquement
     */
    private function validateTransactionData(array $data): void
    {
        $required = [
            'receiver_id',
            'amount_sent',
            'exchange_rate_id',
            'payment_method'
        ];

        foreach ($required as $field) {
            if (!isset($data[$field]) || ($field !== 'receiver_id' && empty($data[$field]))) {
                throw new BadRequestException("Le champ '{$field}' est requis");
            }
        }

        // Validation du montant envoyé
        if (!is_numeric($data['amount_sent']) || $data['amount_sent'] <= 0) {
            throw new BadRequestException('Le montant envoyé doit être un nombre positif');
        }
    }

    /**
     * ===================================================================
     * MÉTHODES DE GÉNÉRATION SÉCURISÉE
     * ===================================================================
     */

    /**
     * Génère une référence de transaction unique et sécurisée
     * Format: TXN_YYYYMMDD_RANDOM16_TIMESTAMP
     * 
     * @return string
     */
    private function generateTransactionRef(): string
    {
        $date = date('Ymd');
        $randomBytes = bin2hex(random_bytes(8)); // 16 caractères hexadécimaux
        $timestamp = microtime(true) * 10000; // Timestamp avec microsecondes
        
        return sprintf('TXN_%s_%s_%d', $date, strtoupper($randomBytes), $timestamp);
    }

    /**
     * Génère un code sécurisé pour amount_send_code ou amount_received_code
     * Format: PREFIX_YYYYMMDD_RANDOM12_CHECKSUM
     * 
     * @param string $prefix Le préfixe (SEND, RECV, etc.)
     * @return string
     */
    private function generateSecureCode(string $prefix): string
    {
        $date = date('Ymd');
        $randomBytes = bin2hex(random_bytes(6)); // 12 caractères hexadécimaux
        
        // Génération d'un checksum pour validation ultérieure
        $data = $prefix . $date . $randomBytes;
        $checksum = substr(hash('sha256', $data), 0, 6);
        
        return sprintf('%s_%s_%s_%s', $prefix, $date, strtoupper($randomBytes), strtoupper($checksum));
    }

    /**
     * Génère un code PIN sécurisé pour authentification
     * 
     * @param int $length Longueur du code (par défaut 6)
     * @return string
     */
    private function generateSecurePin(int $length = 6): string
    {
        $pin = '';
        for ($i = 0; $i < $length; $i++) {
            $pin .= random_int(0, 9);
        }
        return $pin;
    }

    /**
     * Génère un token sécurisé pour validation
     * 
     * @param int $length Longueur du token en bytes (par défaut 32)
     * @return string
     */
    private function generateSecureToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }

    /**
     * Vérifie si un code a le bon format et checksum
     * 
     * @param string $code Le code à vérifier
     * @param string $prefix Le préfixe attendu
     * @return bool
     */
    private function validateSecureCode(string $code, string $prefix): bool
    {
        $parts = explode('_', $code);
        
        if (count($parts) !== 4 || $parts[0] !== $prefix) {
            return false;
        }

        // Recalcul du checksum pour validation
        $data = $parts[0] . $parts[1] . $parts[2];
        $expectedChecksum = substr(hash('sha256', $data), 0, 6);
        
        return strtoupper($parts[3]) === strtoupper($expectedChecksum);
    }

    /**
     * ===================================================================
     * MÉTHODES DE CONVERSION DE DEVISES
     * ===================================================================
     */

    /**
     * Récupérer le taux de change USD vers une devise
     */
    private function getUsdToCurrencyRate(string $currencyCode): ?EchangeRate
    {
        return $this->entityManager->getRepository(EchangeRate::class)
            ->createQueryBuilder('er')
            ->where('er.from_currency = :usd')
            ->andWhere('er.to_currency = :currency')
            ->andWhere('er.is_active = true')
            ->setParameter('usd', 'USD')
            ->setParameter('currency', $currencyCode)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Convertir un montant via USD comme devise pivot
     * 
     * @param float $amount Montant à convertir
     * @param EchangeRate $sourceRate Taux USD→devise_source (ex: USD→XAF)
     * @param EchangeRate $destinationRate Taux USD→devise_destination (ex: USD→RUB)
     * @return array
     */
    private function convertCurrency(
        float $amount, 
        EchangeRate $sourceRate,
        EchangeRate $destinationRate
    ): array {
        // ÉTAPE 1 : Convertir devise_source → USD
        $usdAmount = $amount / (float) $sourceRate->getRate();

        // ÉTAPE 2 : Convertir USD → devise_destination
        $amountReceived = $usdAmount * (float) $destinationRate->getRate();

        // ÉTAPE 3 : Calculer amount_win avec la marge du sourceRate
        $margeProfit = (float) $sourceRate->getMargeProfit() ?? 0;
        $amountWin = ($amount * $margeProfit) / 100;

        return [
            'amount_received' => round($amountReceived, 2),
            'usd_amount' => round($usdAmount, 2),
            'amount_win' => round($amountWin, 2)
        ];
    }

    /**
     * ===================================================================
     * MÉTHODES UTILITAIRES
     * ===================================================================
     */

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