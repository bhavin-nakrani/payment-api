<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class TransferFundsRequest
{
    #[Assert\NotBlank]
    #[Assert\Length(exactly: 20)]
    public string $sourceAccountNumber;

    #[Assert\NotBlank]
    #[Assert\Length(exactly: 20)]
    public string $destinationAccountNumber;

    #[Assert\NotBlank]
    #[Assert\Type(type: 'numeric')]
    #[Assert\Positive]
    #[Assert\LessThanOrEqual(value: '1000000.0000')]
    public string $amount;

    #[Assert\Length(max: 500)]
    public ?string $description = null;
}
