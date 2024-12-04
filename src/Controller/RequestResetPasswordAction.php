<?php

declare(strict_types=1);

namespace App\Controller;

use ApiPlatform\Validator\ValidatorInterface;
use App\Component\User\CurrentUser;
use App\Component\User\Dtos\CheckEmailDto;
use App\Component\Core\ParameterGetter;
use App\Controller\Base\AbstractController;
use App\Repository\UserRepository;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Serializer\SerializerInterface;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

class RequestResetPasswordAction extends AbstractController
{
    public function __construct(
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        CurrentUser $currentUser,
        private ResetPasswordHelperInterface $resetPasswordHelper,
        private UserRepository $userRepository,
        private ParameterGetter $parameterGetter,
        private MailerInterface $mailer
    ) {
        parent::__construct($serializer, $validator, $currentUser);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function __invoke(Request $request): Response
    {
        /** @var CheckEmailDto $checkEmailDto */
        $checkEmailDto = $this->getDtoFromRequest($request, CheckEmailDto::class);
        $this->validate($checkEmailDto);

        $subject = $this->getSubject($checkEmailDto->getLocale());

        return $this->processSendingPasswordResetEmail(
            $checkEmailDto->getEmail(),
            $subject,
            $checkEmailDto->getLocale()
        );
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ContainerExceptionInterface
     */
    private function processSendingPasswordResetEmail(
        string $emailFormData,
        string $subject,
        string $locale
    ): Response {
        $user = $this->userRepository->findOneByEmail($emailFormData);

        if ($user === null) {
            throw new NotFoundHttpException('User is not found with this email');
        }

        try {
            $resetToken = $this->resetPasswordHelper->generateResetToken($user);
        } catch (ResetPasswordExceptionInterface $e) {
            return $this->response(
                'There was a problem handling your password reset request - ' . $e->getReason(),
                208
            );
        }

        $email = (new TemplatedEmail())
            ->from(
                new Address($this->parameterGetter->get('mailer_address'), $this->parameterGetter->get('mailer_name'))
            )
            ->to($user->getEmail())
            ->subject($subject)
            ->htmlTemplate('reset_password/email.html.twig')
            ->context([
                'locale' => $locale,
                'resetToken' => $resetToken,
                'user' => $user->getUsername() . '!'
            ]);

        $this->mailer->send($email);

        return $this->responseEmpty();
    }

    public function getSubject(string $locale): string
    {
        if ($locale === 'ru') {
            return 'Ваш запрос на сброс пароля';
        } elseif ($locale === 'en') {
            return 'Your password reset request';
        }

        return 'Parolni tiklash so\'rovingiz';
    }
}
