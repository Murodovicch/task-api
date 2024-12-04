<?php

declare(strict_types=1);

namespace App\Component\User\Dtos;

use Symfony\Component\Serializer\Annotation\Groups;

class CheckEmailDto
{
    public function __construct(
        #[Groups(['users:password_reset:write'])]
        private string $email,

        #[Groups(['users:password_reset:write'])]
        private string $locale = 'uz'
    ) {
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }
}
