<?php

declare(strict_types=1);

namespace App\Component\User\Dtos;

use Symfony\Component\Serializer\Annotation\Groups;

class ResetPasswordDto
{
    public function __construct(
        #[Groups(['user:write'])]
        private string $newPassword,

        #[Groups(['user:write'])]
        private string $resetToken
    ) {
    }

    public function getNewPassword(): string
    {
        return $this->newPassword;
    }

    public function getResetToken(): string
    {
        return $this->resetToken;
    }
}
