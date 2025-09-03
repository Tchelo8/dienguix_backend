<?php

namespace App\Entity;

use App\Repository\CountryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CountryRepository::class)]
class Country implements \JsonSerializable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $iso_code = null;

    #[ORM\Column(length: 255)]
    private ?string $currency_code = null;

    #[ORM\Column]
    private ?bool $is_active = null;

    #[ORM\Column]
    private ?bool $status = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column]
    private ?\DateTime $updated_at = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'country')]
    private Collection $users;

    /**
     * @var Collection<int, Operator>
     */
    #[ORM\OneToMany(targetEntity: Operator::class, mappedBy: 'country')]
    private Collection $operators;

    /**
     * @var Collection<int, Transaction>
     */
    #[ORM\OneToMany(targetEntity: Transaction::class, mappedBy: 'from_country')]
    private Collection $transaction_from_country;

    /**
     * @var Collection<int, Transaction>
     */
    #[ORM\OneToMany(targetEntity: Transaction::class, mappedBy: 'to_country')]
    private Collection $transaction_to_country;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->operators = new ArrayCollection();
        $this->transaction_from_country = new ArrayCollection();
        $this->transaction_to_country = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getIsoCode(): ?string
    {
        return $this->iso_code;
    }

    public function setIsoCode(string $iso_code): static
    {
        $this->iso_code = $iso_code;

        return $this;
    }

    public function getCurrencyCode(): ?string
    {
        return $this->currency_code;
    }

    public function setCurrencyCode(string $currency_code): static
    {
        $this->currency_code = $currency_code;

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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeImmutable $created_at): static
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

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->setCountry($this);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        if ($this->users->removeElement($user)) {
            // set the owning side to null (unless already changed)
            if ($user->getCountry() === $this) {
                $user->setCountry(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Operator>
     */
    public function getOperators(): Collection
    {
        return $this->operators;
    }

    public function addOperator(Operator $operator): static
    {
        if (!$this->operators->contains($operator)) {
            $this->operators->add($operator);
            $operator->setCountry($this);
        }

        return $this;
    }

    public function removeOperator(Operator $operator): static
    {
        if ($this->operators->removeElement($operator)) {
            // set the owning side to null (unless already changed)
            if ($operator->getCountry() === $this) {
                $operator->setCountry(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Transaction>
     */
    public function getTransactionFromCountry(): Collection
    {
        return $this->transaction_from_country;
    }

    public function addTransactionFromCountry(Transaction $transactionFromCountry): static
    {
        if (!$this->transaction_from_country->contains($transactionFromCountry)) {
            $this->transaction_from_country->add($transactionFromCountry);
            $transactionFromCountry->setFromCountry($this);
        }

        return $this;
    }

    public function removeTransactionFromCountry(Transaction $transactionFromCountry): static
    {
        if ($this->transaction_from_country->removeElement($transactionFromCountry)) {
            // set the owning side to null (unless already changed)
            if ($transactionFromCountry->getFromCountry() === $this) {
                $transactionFromCountry->setFromCountry(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Transaction>
     */
    public function getTransactionToCountry(): Collection
    {
        return $this->transaction_to_country;
    }

    public function addTransactionToCountry(Transaction $transactionToCountry): static
    {
        if (!$this->transaction_to_country->contains($transactionToCountry)) {
            $this->transaction_to_country->add($transactionToCountry);
            $transactionToCountry->setToCountry($this);
        }

        return $this;
    }

    public function removeTransactionToCountry(Transaction $transactionToCountry): static
    {
        if ($this->transaction_to_country->removeElement($transactionToCountry)) {
            // set the owning side to null (unless already changed)
            if ($transactionToCountry->getToCountry() === $this) {
                $transactionToCountry->setToCountry(null);
            }
        }

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->getName(),
            'iso_code' => $this->getIsoCode(),
            'currency_code' => $this->getCurrencyCode(),
            'is_active' => $this->isActive(),
            'status' => $this->isStatus(),
            'created_at' => $this->getCreatedAt(),
            'updated_at' => $this->getUpdatedAt(),
        ];
    }
}
