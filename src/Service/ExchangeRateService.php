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

    public function getDashboardStats(): array
    {
        // Taux USD actifs
        $activeRatesCount = $this->exchangeRateRepository->count(['is_active' => true]);

        // Volume total du jour
        $todayVolume = $this->getTodayTotalVolume();

        // Bénéfices par pays (dynamique pour tous les pays)
        $beneficesByCountry = $this->calculateAllCountriesProfits();

        // Transactions par taux de change avec profit et volume
        $exchangeRatesStats = $this->getExchangeRatesStats();

        return [
            'taux_usd_actif' => $activeRatesCount,
            'volume_total_jour' => $todayVolume,
            'benefice_xaf' => $beneficesByCountry[1] ?? 0, // Gabon
            'benefice_russie' => $beneficesByCountry[2] ?? 0, // Russie
            'benefices_par_pays' => $beneficesByCountry, // Tous les pays
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
            ->setParameter('status', true) // Uniquement les transactions réussies
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    private function calculateAllCountriesProfits(): array
    {
        // Récupérer tous les pays
        $countries = $this->countryRepository->findAll();
        $profits = [];

        foreach ($countries as $country) {
            $profits[$country->getId()] = $this->calculateCountryProfit($country->getId());
        }

        return $profits;
    }

    private function calculateCountryProfit(int $countryId): float
    {
        // Récupérer toutes les transactions avec le pays comme expéditeur
        $qb = $this->entityManager->createQueryBuilder();
        
        $transactions = $qb->select('t', 'er')
            ->from('App\Entity\Transaction', 't')
            ->leftJoin('t.exchange_rate', 'er')
            ->where('t.from_country = :countryId')
            ->andWhere('t.status = :status')
            ->andWhere('er.is_active = :active')
            ->setParameter('countryId', $countryId)
            ->setParameter('status', true)
            ->setParameter('active', true)
            ->getQuery()
            ->getResult();

        $totalProfit = 0;

        foreach ($transactions as $transaction) {
            $exchangeRate = $transaction->getExchangeRate();
            
            if ($exchangeRate && $exchangeRate->getSource()) {
                // La source contient la marge en pourcentage (ex: 2.5)
                $margin = (float) $exchangeRate->getSource();
                $amountSent = (float) $transaction->getAmountSent();
                
                // Calcul du bénéfice : montant × (marge / 100)
                $profit = $amountSent * ($margin / 100);
                $totalProfit += $profit;
            }
        }

        return $totalProfit;
    }

    private function getExchangeRatesStats(): array
    {
        // Récupérer uniquement les taux actifs
        $exchangeRates = $this->exchangeRateRepository->findBy(['is_active' => true]);
        $stats = [];

        foreach ($exchangeRates as $rate) {
            $rateStats = $this->getStatsForExchangeRate($rate->getId(), $rate->getSource());
            
            $stats[] = [
                'exchange_rate_id' => $rate->getId(),
                'from_currency' => $rate->getFromCurrency(),
                'to_currency' => $rate->getToCurrency(),
                'rate' => (float) $rate->getRate(),
                'margin' => (float) $rate->getSource(), // La marge en %
                'transactions_count' => $rateStats['count'],
                'profit' => $rateStats['profit'],
                'volume' => $rateStats['volume'],
                'created_at' => $rate->getCreatedAt()?->format('Y-m-d H:i:s'),
                'updated_at' => $rate->getUpdatedAt()?->format('Y-m-d H:i:s')
            ];
        }

        return $stats;
    }

    private function getStatsForExchangeRate(int $exchangeRateId, ?string $margin): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        
        $result = $qb->select(
                'COUNT(t.id) as transaction_count',
                'SUM(t.amount_sent) as total_volume'
            )
            ->from('App\Entity\Transaction', 't')
            ->where('t.exchange_rate = :rateId')
            ->andWhere('t.status = :status')
            ->setParameter('rateId', $exchangeRateId)
            ->setParameter('status', true)
            ->getQuery()
            ->getSingleResult();

        $transactionCount = (int) ($result['transaction_count'] ?? 0);
        $totalVolume = (float) ($result['total_volume'] ?? 0);
        
        // Calcul du profit total pour ce taux de change
        $marginPercentage = (float) ($margin ?? 0);
        $totalProfit = $totalVolume * ($marginPercentage / 100);

        return [
            'count' => $transactionCount,
            'volume' => $totalVolume,
            'profit' => $totalProfit
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
}