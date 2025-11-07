<?php

namespace App\Service;

use App\Repository\EchangeRateRepository;
use App\Repository\TransactionRepository;
use App\Repository\CountryRepository;
use Doctrine\ORM\EntityManagerInterface;

class ExchangeRateService
{
    public function __construct(
        private EchangeRateRepository $exchangeRateRepository,
        private TransactionRepository $transactionRepository,
        private CountryRepository $countryRepository,
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * Récupérer tous les taux de change actifs
     */
    public function getActiveExchangeRates(): array
    {
        // Récupérer tous les taux de change actifs
        $exchangeRates = $this->exchangeRateRepository->findBy(
            ['is_active' => true],
            ['created_at' => 'DESC']
        );

        $result = [];

        foreach ($exchangeRates as $rate) {
            $result[] = $rate->jsonSerialize();
        }

        return $result;
    }

    public function getDashboardStats(): array
    {
        // Taux USD actifs
        $activeRatesCount = $this->exchangeRateRepository->count(['is_active' => true]);

        // Volume total du jour
        $todayVolume = $this->getTodayTotalVolume();

        // Bénéfices par devise (calculés depuis amount_win)
        $beneficeXaf = $this->calculateProfitByCurrency('XAF');
        $beneficeRub = $this->calculateProfitByCurrency('RUB');

        // Transactions par taux de change avec profit et volume
        $exchangeRatesStats = $this->getExchangeRatesStats();

        return [
            'taux_usd_actif' => $activeRatesCount,
            'volume_total_jour' => $todayVolume,
            'benefice_xaf' => $beneficeXaf,
            'benefice_russie' => $beneficeRub,
            'exchange_rates' => $exchangeRatesStats
        ];
    }

    private function getTodayTotalVolume(): float
    {
        $startOfDay = new \DateTime('today');
        $endOfDay = new \DateTime('tomorrow');

        $qb = $this->entityManager->createQueryBuilder();
        $result = $qb->select('SUM(t.amount_sent)')
            ->from('App\Entity\Transaction', 't')
            ->where('t.created_at >= :start')
            ->andWhere('t.created_at < :end')
            ->andWhere('t.status = :status')
            ->setParameter('start', $startOfDay)
            ->setParameter('end', $endOfDay)
            ->setParameter('status', true)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    /**
     * Calculer le profit total pour une devise de destination
     * En sommant les amount_win des transactions où to_currency correspond
     */
    private function calculateProfitByCurrency(string $currencyCode): float
    {
        $qb = $this->entityManager->createQueryBuilder();

        $result = $qb->select('SUM(t.amount_win)')
            ->from('App\Entity\Transaction', 't')
            ->leftJoin('t.exchange_rate', 'er')
            ->where('er.to_currency = :currency')
            ->andWhere('t.status = :status')
            ->andWhere('er.is_active = :active')
            ->setParameter('currency', $currencyCode)
            ->setParameter('status', true)
            ->setParameter('active', true)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    private function getExchangeRatesStats(): array
    {
        // Récupérer uniquement les taux actifs
        $exchangeRates = $this->exchangeRateRepository->findBy(['is_active' => true]);
        $stats = [];

        foreach ($exchangeRates as $rate) {
            $rateStats = $this->getStatsForExchangeRate($rate->getId());

            $stats[] = [
                'exchange_rate_id' => $rate->getId(),
                'from_currency' => $rate->getFromCurrency(),
                'real_rate' => $rate->getRealRate(),
                'to_currency' => $rate->getToCurrency(),
                'rate' => (float) $rate->getRate(),
                'margin' => (float) ($rate->getMargeProfit() ?? 0), // ✅ Utiliser marge_profit
                'transactions_count' => $rateStats['count'],
                'profit' => $rateStats['profit'], // ✅ Calculé depuis amount_win
                'volume' => $rateStats['volume'],
                'created_at' => $rate->getCreatedAt()?->format('Y-m-d H:i:s'),
                'updated_at' => $rate->getUpdatedAt()?->format('Y-m-d H:i:s')
            ];
        }

        return $stats;
    }

    /**
     * Calculer les stats pour un exchange_rate spécifique
     * Le profit vient de la somme des amount_win des transactions
     */
    private function getStatsForExchangeRate(int $exchangeRateId): array
    {
        $qb = $this->entityManager->createQueryBuilder();

        $result = $qb->select(
            'COUNT(t.id) as transaction_count',
            'SUM(t.amount_sent) as total_volume',
            'SUM(t.amount_win) as total_profit' //  Somme des amount_win
        )
            ->from('App\Entity\Transaction', 't')
            ->where('t.exchange_rate = :rateId')
            ->andWhere('t.status = :status')
            ->setParameter('rateId', $exchangeRateId)
            ->setParameter('status', true)
            ->getQuery()
            ->getSingleResult();

        return [
            'count' => (int) ($result['transaction_count'] ?? 0),
            'volume' => (float) ($result['total_volume'] ?? 0),
            'profit' => (float) ($result['total_profit'] ?? 0)
        ];
    }

    /**
     * Méthode additionnelle pour obtenir des stats détaillées par période
     */
    public function getStatsByPeriod(\DateTime $startDate, \DateTime $endDate): array
    {
        $qb = $this->entityManager->createQueryBuilder();

        $result = $qb->select(
            'COUNT(t.id) as transaction_count',
            'SUM(t.amount_sent) as total_volume'
        )
            ->from('App\Entity\Transaction', 't')
            ->where('t.created_at >= :start')
            ->andWhere('t.created_at < :end')
            ->andWhere('t.status = :status')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->setParameter('status', true)
            ->getQuery()
            ->getSingleResult();

        return [
            'period' => [
                'start' => $startDate->format('Y-m-d H:i:s'),
                'end' => $endDate->format('Y-m-d H:i:s')
            ],
            'transaction_count' => (int) ($result['transaction_count'] ?? 0),
            'total_volume' => (float) ($result['total_volume'] ?? 0)
        ];
    }

    public function createExchangeRate(array $data): array
    {
        // Validation des données requises
        if (
            !isset($data['from_currency']) || !isset($data['to_currency']) ||
            !isset($data['rate']) || !isset($data['real_rate'])
        ) {
            throw new \InvalidArgumentException(
                'Les champs from_currency, to_currency, rate et real_rate sont requis'
            );
        }

        // Validation des valeurs numériques
        $rate = (float) $data['rate'];
        $realRate = (float) $data['real_rate'];

        if ($rate <= 0 || $realRate <= 0) {
            throw new \InvalidArgumentException('Les taux doivent être des nombres positifs');
        }

        // Calcul automatique de la marge en pourcentage
        // Formule : ((rate - real_rate) / real_rate) * 100
        // Exemple : rate=665, real_rate=650 → marge = ((665-650)/650)*100 = 2.31%
        $margeProfit = (($rate - $realRate) / $realRate) * 100;

        // Création de l'entité EchangeRate
        $exchangeRate = new \App\Entity\EchangeRate();
        $exchangeRate->setFromCurrency(strtoupper($data['from_currency']))
            ->setToCurrency(strtoupper($data['to_currency']))
            ->setRate((string) $rate)
            ->setRealRate((string) $realRate)
            ->setMargeProfit((string) round($margeProfit, 2))
            ->setSource('Manuelle') // Toujours "Manuelle" pour création manuelle
            ->setIsActive($data['is_active'] ?? true)
            ->setStatus($data['status'] ?? true)
            ->setCreatedAt(new \DateTime())
            ->setUpdatedAt(new \DateTime());

        // Persister en base de données
        $this->entityManager->persist($exchangeRate);
        $this->entityManager->flush();

        // Retourner les données créées
        return [
            'id' => $exchangeRate->getId(),
            'from_currency' => $exchangeRate->getFromCurrency(),
            'to_currency' => $exchangeRate->getToCurrency(),
            'rate' => (float) $exchangeRate->getRate(),
            'real_rate' => (float) $exchangeRate->getRealRate(),
            'marge_profit' => (float) $exchangeRate->getMargeProfit(),
            'source' => $exchangeRate->getSource(),
            'is_active' => $exchangeRate->isActive(),
            'status' => $exchangeRate->isStatus(),
            'created_at' => $exchangeRate->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => $exchangeRate->getUpdatedAt()->format('Y-m-d H:i:s')
        ];
    }

    /**
     * Mettre à jour uniquement le taux de change (rate)
     */
    public function updateRate(int $exchangeRateId, float $newRate): ?array
    {
        // Récupérer le taux de change
        $exchangeRate = $this->exchangeRateRepository->find($exchangeRateId);

        if (!$exchangeRate) {
            return null;
        }

        // Mettre à jour le taux
        $exchangeRate->setRate((string) $newRate);
        $exchangeRate->setUpdatedAt(new \DateTime());

        // Persister les changements
        $this->entityManager->flush();

        // Retourner les données mises à jour
        return [
            'exchange_rate_id' => $exchangeRate->getId(),
            'from_currency' => $exchangeRate->getFromCurrency(),
            'to_currency' => $exchangeRate->getToCurrency(),
            'rate' => (float) $exchangeRate->getRate(),
            'margin' => (float) $exchangeRate->getMargeProfit(),
            'is_active' => $exchangeRate->isActive(),
            'updated_at' => $exchangeRate->getUpdatedAt()?->format('Y-m-d H:i:s')
        ];
    }
}