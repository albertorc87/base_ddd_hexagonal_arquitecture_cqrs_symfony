<?php

declare(strict_types=1);

namespace App\Shared\Domain\Service\Email;

final class EmailMessage
{
    /**
     * @param string[]          $to          Direcciones de email destinatarias
     * @param string            $from        Dirección de email del remitente
     * @param string            $fromName    Nombre del remitente
     * @param string            $subject     Asunto del email
     * @param string            $body        Cuerpo del email (puede ser HTML o texto plano)
     * @param string[]          $cc          Direcciones de email con copia (opcional)
     * @param string[]          $bcc         Direcciones de email con copia oculta (opcional)
     * @param EmailAttachment[] $attachments Archivos adjuntos (opcional)
     */
    public function __construct(
        private readonly array $to,
        private readonly string $from,
        private readonly string $fromName,
        private readonly string $subject,
        private readonly string $body,
        private readonly array $cc = [],
        private readonly array $bcc = [],
        private readonly array $attachments = [],
        private readonly bool $isHtml = true,
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        if (empty($this->to)) {
            throw new \InvalidArgumentException('At least one recipient (to) is required');
        }

        foreach ($this->to as $email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new \InvalidArgumentException("Invalid email address in 'to': {$email}");
            }
        }

        if (!filter_var($this->from, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("Invalid email address in 'from': {$this->from}");
        }

        foreach ($this->cc as $email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new \InvalidArgumentException("Invalid email address in 'cc': {$email}");
            }
        }

        foreach ($this->bcc as $email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new \InvalidArgumentException("Invalid email address in 'bcc': {$email}");
            }
        }
    }

    public function to(): array
    {
        return $this->to;
    }

    public function from(): string
    {
        return $this->from;
    }

    public function fromName(): string
    {
        return $this->fromName;
    }

    public function subject(): string
    {
        return $this->subject;
    }

    public function body(): string
    {
        return $this->body;
    }

    public function cc(): array
    {
        return $this->cc;
    }

    public function bcc(): array
    {
        return $this->bcc;
    }

    /**
     * @return EmailAttachment[]
     */
    public function attachments(): array
    {
        return $this->attachments;
    }

    public function isHtml(): bool
    {
        return $this->isHtml;
    }
}
