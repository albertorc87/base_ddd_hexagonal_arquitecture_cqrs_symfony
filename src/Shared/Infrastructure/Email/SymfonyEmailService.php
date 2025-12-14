<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Email;

use App\Shared\Domain\Service\Email\EmailMessage;
use App\Shared\Domain\Service\EmailService;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

final class SymfonyEmailService implements EmailService
{
    public function __construct(
        private readonly MailerInterface $mailer,
    ) {
    }

    public function send(EmailMessage $emailMessage): void
    {
        $email = (new Email())
            ->from(new Address($emailMessage->from(), $emailMessage->fromName()))
            ->to(...$this->convertToAddresses($emailMessage->to()))
            ->subject($emailMessage->subject());

        // Añadir CC si existe
        if (!empty($emailMessage->cc())) {
            $email->cc(...$this->convertToAddresses($emailMessage->cc()));
        }

        // Añadir BCC si existe
        if (!empty($emailMessage->bcc())) {
            $email->bcc(...$this->convertToAddresses($emailMessage->bcc()));
        }

        // Configurar el cuerpo del email (HTML o texto plano)
        if ($emailMessage->isHtml()) {
            $email->html($emailMessage->body());
        } else {
            $email->text($emailMessage->body());
        }

        // Añadir archivos adjuntos
        foreach ($emailMessage->attachments() as $attachment) {
            $email->attachFromPath(
                $attachment->path(),
                $attachment->name(),
                $attachment->contentType()
            );
        }

        try {
            $this->mailer->send($email);
        } catch (\Exception $e) {
            throw new \RuntimeException("Failed to send email: {$e->getMessage()}", previous: $e);
        }
    }

    /**
     * Convierte un array de strings de email a un array de objetos Address.
     *
     * @param string[] $emails
     *
     * @return Address[]
     */
    private function convertToAddresses(array $emails): array
    {
        return array_map(
            fn (string $email) => new Address($email),
            $emails
        );
    }
}
