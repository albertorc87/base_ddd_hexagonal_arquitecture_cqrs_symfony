<?php

declare(strict_types=1);

namespace App\User\User\Application\EventHandler;

use App\Shared\Domain\Service\Email\EmailMessage;
use App\Shared\Domain\Service\EmailService;
use App\User\User\Domain\Event\UserCreated;

final class SendUserConfirmationEmailHandler
{
    public function __construct(
        private readonly EmailService $emailService,
    ) {
    }

    public function __invoke(UserCreated $event): void
    {
        $emailMessage = new EmailMessage(
            to: [$event->email()],
            from: 'noreply@example.com',
            fromName: 'Mi Aplicación',
            subject: 'Bienvenido - Confirma tu cuenta',
            body: $this->buildEmailBody($event->name()),
            isHtml: true
        );

        $this->emailService->send($emailMessage);
    }

    private function buildEmailBody(string $userName): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenido</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; background-color: #f4f4f4; line-height: 1.6;">
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #f4f4f4;">
        <tr>
            <td align="center" style="padding: 20px 0;">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" style="max-width: 600px; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="background-color: #667eea; padding: 40px 20px; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 28px; font-weight: 600;">¡Bienvenido!</h1>
                        </td>
                    </tr>
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <p style="font-size: 18px; color: #333333; margin: 0 0 20px 0;">
                                Hola <strong style="color: #667eea;">{$userName}</strong>,
                            </p>
                            <p style="color: #666666; font-size: 16px; margin: 0 0 20px 0;">
                                Gracias por registrarte en nuestra aplicación. Estamos emocionados de tenerte con nosotros.
                            </p>
                            <p style="color: #666666; font-size: 16px; margin: 0 0 30px 0;">
                                Para completar tu registro y comenzar a disfrutar de todos nuestros servicios, por favor confirma tu cuenta haciendo clic en el botón de abajo.
                            </p>
                            <!-- Button -->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                <tr>
                                    <td align="center" style="padding: 20px 0;">
                                        <a href="#" style="display: inline-block; padding: 14px 40px; background-color: #667eea; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 16px;">Confirmar mi cuenta</a>
                                    </td>
                                </tr>
                            </table>
                            <!-- Divider -->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                <tr>
                                    <td style="padding: 30px 0;">
                                        <div style="height: 1px; background-color: #e0e0e0;"></div>
                                    </td>
                                </tr>
                            </table>
                            <!-- Footer -->
                            <p style="color: #999999; font-size: 14px; text-align: center; margin: 0; line-height: 1.5;">
                                Si no has solicitado esta cuenta, puedes ignorar este email de forma segura.<br>
                                Si tienes alguna pregunta, no dudes en <a href="#" style="color: #667eea; text-decoration: none;">contactarnos</a>.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }
}
