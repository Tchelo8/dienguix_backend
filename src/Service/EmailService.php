<?php
// src/Service/EmailService.php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Psr\Log\LoggerInterface;

class EmailService
{
    private MailerInterface $mailer;
    private LoggerInterface $logger;
    private string $fromEmail = 'dienguix.app@gmail.com';

    public function __construct(MailerInterface $mailer, LoggerInterface $logger)
    {
        $this->mailer = $mailer;
        $this->logger = $logger;
    }

    public function sendOtpCode(string $toEmail, string $otpCode): bool
    {
        try {
            $email = (new Email())
                ->from($this->fromEmail)
                ->to($toEmail)
                ->subject('Code de vérification - Connexion')
                ->html($this->getOtpEmailTemplate($otpCode));

            $this->mailer->send($email);
            
            $this->logger->info('Code OTP envoyé par mail avec succès, consultez vos mails.', [
                'to' => $toEmail,
                'from' => $this->fromEmail
            ]);
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'envoi du code OTP', [
                'error' => $e->getMessage(),
                'to' => $toEmail
            ]);

            return false;
        }
    }

    private function getOtpEmailTemplate(string $otpCode): string
    {
        return "
            <html>
            <body style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
                <div style='background-color: #f8f9fa; padding: 30px; border-radius: 10px; text-align: center;'>
                    <h2 style='color: #333; margin-bottom: 20px;'>Code de vérification</h2>
                    
                    <p style='color: #666; font-size: 16px; margin-bottom: 30px;'>
                        Voici votre code de vérification pour vous connecter :
                    </p>
                    
                    <div style='background-color: #007bff; color: white; font-size: 32px; font-weight: bold; padding: 20px; border-radius: 8px; letter-spacing: 5px; margin: 20px 0;'>
                        {$otpCode}
                    </div>

                    <p style='color: #666; font-size: 14px; margin-top: 30px;'>
                        Ce code expire dans 10 minutes.<br>
                        Si vous n'avez pas demandé ce code, ignorez cet email.
                    </p>
                </div>
            </body>
            </html>
        ";
    }
}