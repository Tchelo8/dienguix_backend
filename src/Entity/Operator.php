<?php

namespace App\Entity;

use App\Repository\OperatorRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OperatorRepository::class)]
class Operator implements \JsonSerializable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'operators')]
    private ?Country $country = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $code = null;

    #[ORM\Column(length: 255)]
    private ?string $type = null;

    #[ORM\Column(length: 255)]
    private ?string $logo = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    private ?string $min_amount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    private ?string $max_amount = null;

    #[ORM\Column(nullable: true)]
    private ?int $fees_structure = null;

    #[ORM\Column]
    private ?bool $is_active = null;

    #[ORM\Column]
    private ?bool $status = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column]
    private ?\DateTime $updated_at = null;

    /**
     * @var Collection<int, Transaction>
     */
    #[ORM\OneToMany(targetEntity: Transaction::class, mappedBy: 'operator_sender')]
    private Collection $transaction_operator_sender;

    /**
     * @var Collection<int, Transaction>
     */
    #[ORM\OneToMany(targetEntity: Transaction::class, mappedBy: 'operator_receiver')]
    private Collection $transaction_operator_receiver;

    /**
     * @var Collection<int, UserProfile>
     */
    #[ORM\OneToMany(targetEntity: UserProfile::class, mappedBy: 'operator')]
    private Collection $userProfiles;

    public function __construct()
    {
        $this->transaction_operator_sender = new ArrayCollection();
        $this->transaction_operator_receiver = new ArrayCollection();
        $this->userProfiles = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCountry(): ?Country
    {
        return $this->country;
    }

    public function setCountry(?Country $country): static
    {
        $this->country = $country;

        return $this;
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

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(string $logo): static
    {
        $this->logo = $logo;

        return $this;
    }

    public function getMinAmount(): ?string
    {
        return $this->min_amount;
    }

    public function setMinAmount(string $min_amount): static
    {
        $this->min_amount = $min_amount;

        return $this;
    }

    public function getMaxAmount(): ?string
    {
        return $this->max_amount;
    }

    public function setMaxAmount(string $max_amount): static
    {
        $this->max_amount = $max_amount;

        return $this;
    }

    public function getFeesStructure(): ?int
    {
        return $this->fees_structure;
    }

    public function setFeesStructure(?int $fees_structure): static
    {
        $this->fees_structure = $fees_structure;

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
     * @return Collection<int, Transaction>
     */
    public function getTransactionOperatorSender(): Collection
    {
        return $this->transaction_operator_sender;
    }

    public function addTransactionOperatorSender(Transaction $transactionOperatorSender): static
    {
        if (!$this->transaction_operator_sender->contains($transactionOperatorSender)) {
            $this->transaction_operator_sender->add($transactionOperatorSender);
            $transactionOperatorSender->setOperatorSender($this);
        }

        return $this;
    }

    public function removeTransactionOperatorSender(Transaction $transactionOperatorSender): static
    {
        if ($this->transaction_operator_sender->removeElement($transactionOperatorSender)) {
            // set the owning side to null (unless already changed)
            if ($transactionOperatorSender->getOperatorSender() === $this) {
                $transactionOperatorSender->setOperatorSender(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Transaction>
     */
    public function getTransactionOperatorReceiver(): Collection
    {
        return $this->transaction_operator_receiver;
    }

    public function addTransactionOperatorReceiver(Transaction $transactionOperatorReceiver): static
    {
        if (!$this->transaction_operator_receiver->contains($transactionOperatorReceiver)) {
            $this->transaction_operator_receiver->add($transactionOperatorReceiver);
            $transactionOperatorReceiver->setOperatorReceiver($this);
        }

        return $this;
    }

    public function removeTransactionOperatorReceiver(Transaction $transactionOperatorReceiver): static
    {
        if ($this->transaction_operator_receiver->removeElement($transactionOperatorReceiver)) {
            // set the owning side to null (unless already changed)
            if ($transactionOperatorReceiver->getOperatorReceiver() === $this) {
                $transactionOperatorReceiver->setOperatorReceiver(null);
            }
        }

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'country' => $this->country ? [
                'id' => $this->country->getId(),
                'name' => $this->country->getName(),
            ] : null,
            'name' => $this->name,
            'code' => $this->code,
            'type' => $this->type,
            'logo' => $this->logo,
            'min_amount' => $this->min_amount,
            'max_amount' => $this->max_amount,
            'fees_structure' => $this->fees_structure,
            'is_active' => $this->is_active,
            'status' => $this->status,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * @return Collection<int, UserProfile>
     */
    public function getUserProfiles(): Collection
    {
        return $this->userProfiles;
    }

    public function addUserProfile(UserProfile $userProfile): static
    {
        if (!$this->userProfiles->contains($userProfile)) {
            $this->userProfiles->add($userProfile);
            $userProfile->setOperator($this);
        }

        return $this;
    }

    public function removeUserProfile(UserProfile $userProfile): static
    {
        if ($this->userProfiles->removeElement($userProfile)) {
            // set the owning side to null (unless already changed)
            if ($userProfile->getOperator() === $this) {
                $userProfile->setOperator(null);
            }
        }

        return $this;
    }
}
