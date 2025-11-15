<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class RegisterUserRequest
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email;

    #[Assert\NotBlank]
    #[Assert\Length(min: 8, max: 100)]
    public string $password;

    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 100)]
    public string $firstName;

    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 100)]
    public string $lastName;
}
