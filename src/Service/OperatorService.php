<?php

namespace App\Service;

use App\Entity\Country;
use App\Entity\Operator;
use App\Entity\Transaction;
use Doctrine\ORM\EntityManagerInterface;

class OperatorService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Récupère tous les pays avec leurs opérateurs et statistiques de transactions
     */
    public function getCountriesWithOperatorsStats(): array
    {
        // Récupérer tous les pays actifs avec leurs opérateurs
        $countries = $this->entityManager->getRepository(Country::class)
            ->createQueryBuilder('c')
            ->leftJoin('c.operators', 'o')
            ->where('c.is_active = :active')
            ->setParameter('active', true)
            ->getQuery()
            ->getResult();

        $result = [];

        foreach ($countries as $country) {
            $countryData = [
                'id' => $country->getId(),
                'name' => $country->getName(),
                'iso_code' => $country->getIsoCode(),
                'currency_code' => $country->getCurrencyCode(),
                'is_active' => $country->isActive(),
                'status' => $country->isStatus(),
                'created_at' => $country->getCreatedAt()?->format('Y-m-d H:i:s'),
                'updated_at' => $country->getUpdatedAt()?->format('Y-m-d H:i:s'),
                'operators' => []
            ];

            // Récupérer les opérateurs actifs de ce pays
            $operators = $this->entityManager->getRepository(Operator::class)
                ->createQueryBuilder('op')
                ->where('op.country = :country')
                ->andWhere('op.is_active = :active')
                ->setParameter('country', $country)
                ->setParameter('active', true)
                ->getQuery()
                ->getResult();

            foreach ($operators as $operator) {
                $operatorStats = $this->calculateOperatorStats($operator);

                $operatorData = [
                    'id' => $operator->getId(),
                    'name' => $operator->getName(),
                    'code' => $operator->getCode(),
                    'type' => $operator->getType(),
                    'logo' => $operator->getLogo(),
                    'min_amount' => $operator->getMinAmount(),
                    'max_amount' => $operator->getMaxAmount(),
                    'fees_structure' => $operator->getFeesStructure(),
                    'is_active' => $operator->isActive(),
                    'status' => $operator->isStatus(),
                    'created_at' => $operator->getCreatedAt()?->format('Y-m-d H:i:s'),
                    'updated_at' => $operator->getUpdatedAt()?->format('Y-m-d H:i:s'),
                    'statistics' => $operatorStats
                ];

                $countryData['operators'][] = $operatorData;
            }

            $result[] = $countryData;
        }

        return $result;
    }


    /**
     * Récupère tous les opérateurs actifs avec leurs statistiques
     */
    public function getAllOperators(): array
    {
        // Récupérer tous les opérateurs actifs
        $operators = $this->entityManager->getRepository(Operator::class)
            ->createQueryBuilder('op')
            ->leftJoin('op.country', 'c')
            ->where('op.is_active = :active')
            ->setParameter('active', true)
            ->orderBy('c.name', 'ASC')
            ->addOrderBy('op.name', 'ASC')
            ->getQuery()
            ->getResult();

        $result = [];

        foreach ($operators as $operator) {
            $operatorStats = $this->calculateOperatorStats($operator);

            $result[] = [
                'id' => $operator->getId(),
                'name' => $operator->getName(),
                'code' => $operator->getCode(),
                'type' => $operator->getType(),
                'logo' => $operator->getLogo(),
                'min_amount' => $operator->getMinAmount(),
                'max_amount' => $operator->getMaxAmount(),
                'fees_structure' => $operator->getFeesStructure(),
                'is_active' => $operator->isActive(),
                'status' => $operator->isStatus(),
                'created_at' => $operator->getCreatedAt()?->format('Y-m-d H:i:s'),
                'updated_at' => $operator->getUpdatedAt()?->format('Y-m-d H:i:s'),
                'country' => [
                    'id' => $operator->getCountry()?->getId(),
                    'name' => $operator->getCountry()?->getName(),
                    'iso_code' => $operator->getCountry()?->getIsoCode(),
                    'currency_code' => $operator->getCountry()?->getCurrencyCode()
                ],
                'statistics' => $operatorStats
            ];
        }

        return $result;
    }

    /**
     * Récupère les opérateurs d'un pays spécifique (format simplifié)
     */
    public function getOperatorsByUserCountry(int $countryId): array
    {
        $operators = $this->entityManager->getRepository(Operator::class)
            ->createQueryBuilder('op')
            ->where('op.country = :countryId')
            ->andWhere('op.is_active = :active')
            ->setParameter('countryId', $countryId)
            ->setParameter('active', true)
            ->orderBy('op.name', 'ASC')
            ->getQuery()
            ->getResult();

        $result = [];
        foreach ($operators as $operator) {
            $result[] = [
                'id' => $operator->getId(),
                'name' => $operator->getName(),
                'code' => $operator->getCode(),
                'type' => $operator->getType()
            ];
        }

        return $result;
    }

    /**
     * Calcule les statistiques d'un opérateur
     */
    private function calculateOperatorStats(Operator $operator): array
    {
        // Statistiques quand l'opérateur est sender
        $senderStats = $this->entityManager->getRepository(Transaction::class)
            ->createQueryBuilder('t')
            ->select(
                'COUNT(t.id) as nombre_envois',
                'SUM(t.amount_sent) as montant_total_envois',
                'SUM(t.amount_win) as commission',
                'AVG(t.amount_sent) as montant_moyen'
            )
            ->where('t.operator_sender = :operator')
            ->andWhere('t.status = :status')
            ->setParameter('operator', $operator)
            ->setParameter('status', true)
            ->getQuery()
            ->getSingleResult();

        // Statistiques quand l'opérateur est receiver
        $receiverStats = $this->entityManager->getRepository(Transaction::class)
            ->createQueryBuilder('t')
            ->select(
                'COUNT(t.id) as nombre_receptions',
                'SUM(t.amount_received) as montant_total_receptions'
            )
            ->where('t.operator_receiver = :operator')
            ->andWhere('t.status = :status')
            ->setParameter('operator', $operator)
            ->setParameter('status', true)
            ->getQuery()
            ->getSingleResult();

        // Calculs
        $nombreEnvois = (int) ($senderStats['nombre_envois'] ?? 0);
        $nombreReceptions = (int) ($receiverStats['nombre_receptions'] ?? 0);
        $montantTotalEnvois = (float) ($senderStats['montant_total_envois'] ?? 0);
        $montantTotalReceptions = (float) ($receiverStats['montant_total_receptions'] ?? 0);

        return [
            'nombre_envois' => $nombreEnvois,
            'nombre_receptions' => $nombreReceptions,
            'nombre_total_transactions' => $nombreEnvois + $nombreReceptions,
            'montant_total_envois' => number_format($montantTotalEnvois, 2, '.', ''),
            'montant_total_receptions' => number_format($montantTotalReceptions, 2, '.', ''),
            'commission' => number_format((float) ($senderStats['commission'] ?? 0), 2, '.', ''),
            'montant_moyen' => number_format((float) ($senderStats['montant_moyen'] ?? 0), 2, '.', '')
        ];
    }

    /**
     * Récupère les statistiques d'un opérateur spécifique
     */
    public function getOperatorStats(int $operatorId): ?array
    {
        $operator = $this->entityManager->getRepository(Operator::class)->find($operatorId);

        if (!$operator) {
            return null;
        }

        $stats = $this->calculateOperatorStats($operator);

        return [
            'operator' => [
                'id' => $operator->getId(),
                'name' => $operator->getName(),
                'code' => $operator->getCode(),
                'country' => [
                    'id' => $operator->getCountry()?->getId(),
                    'name' => $operator->getCountry()?->getName()
                ]
            ],
            'statistics' => $stats
        ];
    }

    /**
     * Récupère les opérateurs d'un pays spécifique avec leurs stats
     */
    public function getOperatorsByCountry(int $countryId): array
    {
        $country = $this->entityManager->getRepository(Country::class)->find($countryId);

        if (!$country) {
            return [];
        }

        $operators = $this->entityManager->getRepository(Operator::class)
            ->createQueryBuilder('op')
            ->where('op.country = :country')
            ->andWhere('op.is_active = :active')
            ->setParameter('country', $country)
            ->setParameter('active', true)
            ->getQuery()
            ->getResult();

        $result = [
            'country' => [
                'id' => $country->getId(),
                'name' => $country->getName(),
                'iso_code' => $country->getIsoCode(),
                'currency_code' => $country->getCurrencyCode()
            ],
            'operators' => []
        ];

        foreach ($operators as $operator) {
            $operatorStats = $this->calculateOperatorStats($operator);

            $result['operators'][] = [
                'id' => $operator->getId(),
                'name' => $operator->getName(),
                'code' => $operator->getCode(),
                'type' => $operator->getType(),
                'statistics' => $operatorStats
            ];
        }

        return $result;
    }

    /**
     * Récupère le top des opérateurs par montant total envoyé
     */
    public function getTopOperatorsByAmount(int $limit = 10): array
    {
        $results = $this->entityManager->getRepository(Transaction::class)
            ->createQueryBuilder('t')
            ->select(
                'op.id',
                'op.name',
                'op.code',
                'c.name as country_name',
                'SUM(t.amount_sent) as total_amount',
                'COUNT(t.id) as total_transactions'
            )
            ->innerJoin('t.operator_sender', 'op')
            ->innerJoin('op.country', 'c')
            ->where('t.status = :status')
            ->groupBy('op.id', 'op.name', 'op.code', 'c.name')
            ->orderBy('total_amount', 'DESC')
            ->setParameter('status', true)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return array_map(function ($result) {
            return [
                'operator_id' => $result['id'],
                'operator_name' => $result['name'],
                'operator_code' => $result['code'],
                'country_name' => $result['country_name'],
                'total_amount' => number_format((float) $result['total_amount'], 2, '.', ''),
                'total_transactions' => (int) $result['total_transactions'],
                'average_amount' => number_format(
                    (float) $result['total_amount'] / (int) $result['total_transactions'],
                    2,
                    '.',
                    ''
                )
            ];
        }, $results);
    }
}
