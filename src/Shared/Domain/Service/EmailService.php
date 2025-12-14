<?php

declare(strict_types=1);

namespace App\Shared\Domain\Service;

use App\Shared\Domain\Service\Email\EmailMessage;

interface EmailService
{
    /**
     * Envía un email.
     *
     * @param EmailMessage $emailMessage El mensaje de email a enviar
     *
     * @throws \RuntimeException Si ocurre un error al enviar el email
     */
    public function send(EmailMessage $emailMessage): void;
}
