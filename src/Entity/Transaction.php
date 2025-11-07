<?php

namespace App\Entity;

use App\Repository\TransactionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TransactionRepository::class)]
class Transaction implements \JsonSerializable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'transaction_sender')]
    private ?User $sender = null;

    #[ORM\ManyToOne(inversedBy: 'transaction_receiver')]
    private ?User $receiver = null;

    #[ORM\ManyToOne(inversedBy: 'transaction_from_country')]
    private ?Country $from_country = null;

    #[ORM\ManyToOne(inversedBy: 'transaction_to_country')]
    private ?Country $to_country = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $amount_sent = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $trans_fees = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $amount_received = null;

    #[ORM\Column(length: 255)]
    private ?string $amount_send_code = null;

    #[ORM\Column(length: 255)]
    private ?string $amount_received_code = null;

    #[ORM\Column]
    private ?bool $status = null;

    #[ORM\ManyToOne(inversedBy: 'transaction_exchange_rate')]
    private ?EchangeRate $exchange_rate = null;

    #[ORM\Column(length: 255)]
    private ?string $trans_status = null;

    #[ORM\Column(length: 255)]
    private ?string $transaction_type = null;

    #[ORM\ManyToOne(inversedBy: 'transaction_operator_sender')]
    private ?Operator $operator_sender = null;

    #[ORM\ManyToOne(inversedBy: 'transaction_operator_receiver')]
    private ?Operator $operator_receiver = null;

    #[ORM\Column(length: 255)]
    private ?string $payment_method = null;

    #[ORM\Column(length: 1000,  nullable: true)]
    private ?string $note = null;

    #[ORM\Column(length: 255)]
    private ?string $transaction_ref = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $iniated_at = null;

    #[ORM\Column (nullable: true)]
    private ?\DateTimeImmutable $completed_at = null;

    #[ORM\Column (nullable: true)]
    private ?\DateTimeImmutable $failed_at = null;

    #[ORM\Column (nullable: true)]
    private ?\DateTime $updated_at = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $amount_win = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSender(): ?User
    {
        return $this->sender;
    }

    public function setSender(?User $sender): static
    {
        $this->sender = $sender;

        return $this;
    }

    public function getReceiver(): ?User
    {
        return $this->receiver;
    }

    public function setReceiver(?User $receiver): static
    {
        $this->receiver = $receiver;

        return $this;
    }

    public function getFromCountry(): ?Country
    {
        return $this->from_country;
    }

    public function setFromCountry(?Country $from_country): static
    {
        $this->from_country = $from_country;

        return $this;
    }

    public function getToCountry(): ?Country
    {
        return $this->to_country;
    }

    public function setToCountry(?Country $to_country): static
    {
        $this->to_country = $to_country;

        return $this;
    }

    public function getAmountSent(): ?string
    {
        return $this->amount_sent;
    }

    public function setAmountSent(string $amount_sent): static
    {
        $this->amount_sent = $amount_sent;

        return $this;
    }

    public function getTransFees(): ?string
    {
        return $this->trans_fees;
    }

    public function setTransFees(?string $trans_fees): static
    {
        $this->trans_fees = $trans_fees;

        return $this;
    }

    public function getAmountReceived(): ?string
    {
        return $this->amount_received;
    }

    public function setAmountReceived(string $amount_received): static
    {
        $this->amount_received = $amount_received;

        return $this;
    }

    public function getAmountSendCode(): ?string
    {
        return $this->amount_send_code;
    }

    public function setAmountSendCode(string $amount_send_code): static
    {
        $this->amount_send_code = $amount_send_code;

        return $this;
    }

    public function getAmountReceivedCode(): ?string
    {
        return $this->amount_received_code;
    }

    public function setAmountReceivedCode(string $amount_received_code): static
    {
        $this->amount_received_code = $amount_received_code;

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

    public function getExchangeRate(): ?EchangeRate
    {
        return $this->exchange_rate;
    }

    public function setExchangeRate(?EchangeRate $exchange_rate): static
    {
        $this->exchange_rate = $exchange_rate;

        return $this;
    }

    public function getTransStatus(): ?string
    {
        return $this->trans_status;
    }

    public function setTransStatus(string $trans_status): static
    {
        $this->trans_status = $trans_status;

        return $this;
    }

    public function getTransactionType(): ?string
    {
        return $this->transaction_type;
    }

    public function setTransactionType(string $transaction_type): static
    {
        $this->transaction_type = $transaction_type;

        return $this;
    }

    public function getOperatorSender(): ?Operator
    {
        return $this->operator_sender;
    }

    public function setOperatorSender(?Operator $operator_sender): static
    {
        $this->operator_sender = $operator_sender;

        return $this;
    }

    public function getOperatorReceiver(): ?Operator
    {
        return $this->operator_receiver;
    }

    public function setOperatorReceiver(?Operator $operator_receiver): static
    {
        $this->operator_receiver = $operator_receiver;

        return $this;
    }

    public function getPaymentMethod(): ?string
    {
        return $this->payment_method;
    }

    public function setPaymentMethod(string $payment_method): static
    {
        $this->payment_method = $payment_method;

        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(string $note): static
    {
        $this->note = $note;

        return $this;
    }

    public function getTransactionRef(): ?string
    {
        return $this->transaction_ref;
    }

    public function setTransactionRef(string $transaction_ref): static
    {
        $this->transaction_ref = $transaction_ref;

        return $this;
    }

    public function getIniatedAt(): ?\DateTimeImmutable
    {
        return $this->iniated_at;
    }

    public function setIniatedAt(\DateTimeImmutable $iniated_at): static
    {
        $this->iniated_at = $iniated_at;

        return $this;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completed_at;
    }

    public function setCompletedAt(\DateTimeImmutable $completed_at): static
    {
        $this->completed_at = $completed_at;

        return $this;
    }

    public function getFailedAt(): ?\DateTimeImmutable
    {
        return $this->failed_at;
    }

    public function setFailedAt(\DateTimeImmutable $failed_at): static
    {
        $this->failed_at = $failed_at;

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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeImmutable $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'amount_sent' => $this->amount_sent,
            'trans_fees' => $this->trans_fees,
            'amount_received' => $this->amount_received,
            'amount_send_code' => $this->amount_send_code,
            'amount_received_code' => $this->amount_received_code,
            'status' => $this->status,
            'trans_status' => $this->trans_status,
            'transaction_type' => $this->transaction_type,
            'payment_method' => $this->payment_method,
            'note' => $this->note,
            'transaction_ref' => $this->transaction_ref,
            'iniated_at' => $this->iniated_at?->format('Y-m-d H:i:s'),
            'completed_at' => $this->completed_at?->format('Y-m-d H:i:s'),
            'failed_at' => $this->failed_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'amount_win' => $this->amount_win,
            
            // Relations - avec vérification pour éviter les références circulaires
            'sender' => $this->sender ? [
                'id' => $this->sender->getId(),
                'first_name' => $this->sender->getFirstName(),
                'last_name' => $this->sender->getLastName(),
                'email' => $this->sender->getEmail(),
                'phone' => $this->sender->getPhone(),
            ] : null,
            
            'receiver' => $this->receiver ? [
                'id' => $this->receiver->getId(),
                'first_name' => $this->receiver->getFirstName(),
                'last_name' => $this->receiver->getLastName(),
                'email' => $this->receiver->getEmail(),
                'phone' => $this->receiver->getPhone(),
            ] : null,
            
            'from_country' => $this->from_country ? [
                'id' => $this->from_country->getId(),
                'name' => $this->from_country->getName(),
                'code' => $this->from_country->getCurrencyCode(),
            ] : null,
            
            'to_country' => $this->to_country ? [
                'id' => $this->to_country->getId(),
                'name' => $this->to_country->getName(),
                'code' => $this->to_country->getCurrencyCode(),
            ] : null,
            
            'exchange_rate' => $this->exchange_rate ? [
                'id' => $this->exchange_rate->getId(),
                'rate' => $this->exchange_rate->getRate(),
                'real_rate' => $this->exchange_rate->getRealRate(),
                'marge_profit' => $this->exchange_rate->getMargeProfit(),
                'from_currency' => $this->exchange_rate->getFromCurrency(),
                'to_curency' => $this->exchange_rate->getToCurrency(),
            ] : null,
            
            'operator_sender' => $this->operator_sender ? [
                'id' => $this->operator_sender->getId(),
                'name' => $this->operator_sender->getName(),
            ] : null,
            
            'operator_receiver' => $this->operator_receiver ? [
                'id' => $this->operator_receiver->getId(),
                'name' => $this->operator_receiver->getName(),
            ] : null,
        ];
        
    }

    public function getAmountWin(): ?string
    {
        return $this->amount_win;
    }

    public function setAmountWin(?string $amount_win): static
    {
        $this->amount_win = $amount_win;

        return $this;
    }
}
