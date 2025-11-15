<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class CreateAccountRequest
{
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['CHECKING', 'SAVINGS', 'BUSINESS'])]
    public string $accountType = 'CHECKING';

    #[Assert\NotBlank]
    #[Assert\Currency]
    public string $currency = 'USD';

    #[Assert\Type(type: 'numeric')]
    #[Assert\PositiveOrZero]
    public string $initialBalance = '0.00';
}
