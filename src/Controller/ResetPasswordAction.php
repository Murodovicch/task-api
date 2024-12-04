<?php

declare(strict_types=1);

namespace App\Controller;

use ApiPlatform\Validator\ValidatorInterface;
use App\Component\User\CurrentUser;
use App\Component\User\Dtos\ResetPasswordDto;
use App\Component\User\UserManager;
use App\Controller\Base\AbstractController;
use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

class ResetPasswordAction extends AbstractController
{
    public function __construct(
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        CurrentUser $currentUser,
        private ResetPasswordHelperInterface $resetPasswordHelper,
        private UserManager $userManager
    ) {
        parent::__construct($serializer, $validator, $currentUser);
    }

    public function __invoke(Request $request): Response|User
    {
        /** @var  ResetPasswordDto $resetPassword */
        $resetPassword = $this->getDtoFromRequest($request, ResetPasswordDto::class);
        $this->validate($resetPassword);

        $token = $resetPassword->getResetToken();

        try {
            /** @var User $user */
            $user = $this->resetPasswordHelper->validateTokenAndFetchUser($token);
        } catch (ResetPasswordExceptionInterface $e) {
            return $this->response('There was a problem validating your reset request - ' . $e->getReason(), 208);
        }

        // A password reset token should be used only once, remove it.
        $this->resetPasswordHelper->removeResetRequest($token);

        // Encode the plain password, and set it.
        $this->userManager->hashPassword(
            $user,
            $resetPassword->getNewPassword()
        );

        $this->userManager->save($user, true);

        return $user;
    }
}
