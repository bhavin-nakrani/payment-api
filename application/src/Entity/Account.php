<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\AccountRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: AccountRepository::class)]
#[ORM\Table(name: 'accounts')]
#[ORM\Index(columns: ['account_number'], name: 'idx_account_number')]
#[ORM\Index(columns: ['user_id'], name: 'idx_account_user')]
#[ORM\HasLifecycleCallbacks]
class Account
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\Column(length: 20, unique: true)]
    private string $accountNumber;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'accounts')]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(type: Types::DECIMAL, precision: 19, scale: 4)]
    private string $balance = '0.0000';

    #[ORM\Column(length: 3)]
    private string $currency = 'USD';

    #[ORM\Column(length: 50)]
    private string $accountType = 'CHECKING';

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    #[ORM\OneToMany(targetEntity: Transaction::class, mappedBy: 'sourceAccount')]
    private Collection $outgoingTransactions;

    #[ORM\OneToMany(targetEntity: Transaction::class, mappedBy: 'destinationAccount')]
    private Collection $incomingTransactions;

    #[ORM\Version]
    #[ORM\Column(type: Types::INTEGER)]
    private int $version = 0;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->outgoingTransactions = new ArrayCollection();
        $this->incomingTransactions = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        
        if (empty($this->accountNumber)) {
            $this->accountNumber = $this->generateAccountNumber();
        }
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    private function generateAccountNumber(): string
    {
        return sprintf('%020d', random_int(10000000000000000000, 99999999999999999999));
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getAccountNumber(): string
    {
        return $this->accountNumber;
    }

    public function setAccountNumber(string $accountNumber): self
    {
        $this->accountNumber = $accountNumber;
        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getBalance(): string
    {
        return $this->balance;
    }

    public function setBalance(string $balance): self
    {
        $this->balance = $balance;
        return $this;
    }

    public function credit(string $amount): self
    {
        $this->balance = bcadd($this->balance, $amount, 4);
        return $this;
    }

    public function debit(string $amount): self
    {
        $this->balance = bcsub($this->balance, $amount, 4);
        return $this;
    }

    public function hasEnoughBalance(string $amount): bool
    {
        return bccomp($this->balance, $amount, 4) >= 0;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;
        return $this;
    }

    public function getAccountType(): string
    {
        return $this->accountType;
    }

    public function setAccountType(string $accountType): self
    {
        $this->accountType = $accountType;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    /**
     * @return Collection<int, Transaction>
     */
    public function getOutgoingTransactions(): Collection
    {
        return $this->outgoingTransactions;
    }

    /**
     * @return Collection<int, Transaction>
     */
    public function getIncomingTransactions(): Collection
    {
        return $this->incomingTransactions;
    }
}
