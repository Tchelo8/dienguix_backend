<?php

namespace App\Entity;

use App\Repository\EchangeRateRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EchangeRateRepository::class)]
class EchangeRate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $from_currency = null;

    #[ORM\Column(length: 255)]
    private ?string $to_currency = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 8)]
    private ?string $rate = null;

    #[ORM\Column(length: 255)]
    private ?string $source = null;

    #[ORM\Column]
    private ?\DateTime $created_at = null;

    #[ORM\Column]
    private ?\DateTime $updated_at = null;

    #[ORM\Column]
    private ?bool $is_active = null;

    #[ORM\Column]
    private ?bool $status = null;

    /**
     * @var Collection<int, Transaction>
     */
    #[ORM\OneToMany(targetEntity: Transaction::class, mappedBy: 'exchange_rate')]
    private Collection $transaction_exchange_rate;

    public function __construct()
    {
        $this->transaction_exchange_rate = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFromCurrency(): ?string
    {
        return $this->from_currency;
    }

    public function setFromCurrency(string $from_currency): static
    {
        $this->from_currency = $from_currency;

        return $this;
    }

    public function getToCurrency(): ?string
    {
        return $this->to_currency;
    }

    public function setToCurrency(string $to_currency): static
    {
        $this->to_currency = $to_currency;

        return $this;
    }

    public function getRate(): ?string
    {
        return $this->rate;
    }

    public function setRate(string $rate): static
    {
        $this->rate = $rate;

        return $this;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(string $source): static
    {
        $this->source = $source;

        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTime $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(\DateTime $updated_at): static
    {
        $this->updated_at = $updated_at;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->is_active;
    }

    public function setIsActive(bool $is_active): static
    {
        $this->is_active = $is_active;

        return $this;
    }

    public function isStatus(): ?bool
    {
        return $this->status;
    }

    public function setStatus(bool $status): static
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return Collection<int, Transaction>
     */
    public function getTransactionExchangeRate(): Collection
    {
        return $this->transaction_exchange_rate;
    }

    public function addTransactionExchangeRate(Transaction $transactionExchangeRate): static
    {
        if (!$this->transaction_exchange_rate->contains($transactionExchangeRate)) {
            $this->transaction_exchange_rate->add($transactionExchangeRate);
            $transactionExchangeRate->setExchangeRate($this);
        }

        return $this;
    }

    public function removeTransactionExchangeRate(Transaction $transactionExchangeRate): static
    {
        if ($this->transaction_exchange_rate->removeElement($transactionExchangeRate)) {
            // set the owning side to null (unless already changed)
            if ($transactionExchangeRate->getExchangeRate() === $this) {
                $transactionExchangeRate->setExchangeRate(null);
            }
        }

        return $this;
    }

     public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'from_currency' => $this->from_currency,
            'to_currency' => $this->to_currency,
            'rate' => $this->rate ? floatval($this->rate) : null,
            'source' => $this->source,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'is_active' => $this->is_active,
            'status' => $this->status,
            'transactions_count' => $this->transaction_exchange_rate->count(),
        ];
    }
}




