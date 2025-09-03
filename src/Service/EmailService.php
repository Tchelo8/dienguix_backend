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

        // template du mail que l'utilisateur recevra avec le code otp !!!!##Tchelooooooooooooo
        return "
                <html>
                <head>
                    <meta charset='UTF-8'>
                    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                    <title>Code de vérification Dienguix</title>
                </head>
                <body style='font-family: \"Segoe UI\", Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; background-color: #1a1a1a;'>
                    <div style='max-width: 600px; margin: 0 auto; background-color: #1a1a1a;'>
                        <!-- Header avec logo -->
                        <div style='background-color: #000000; padding: 30px 20px; text-align: center; border-bottom: 3px solid #ffd700;'>
                            <h1 style='color: #ffd700; margin: 0; font-size: 28px; font-weight: bold; letter-spacing: 2px;'>
                                DIENGUIX
                            </h1>
                            <p style='color: #cccccc; margin: 5px 0 0 0; font-size: 12px; text-transform: uppercase; letter-spacing: 1px;'>
                                Plateforme de Transfert d'Argent
                            </p>
                        </div>
                        
                        <!-- Contenu principal -->
                        <div style='background-color: #ffffff; padding: 40px 30px;'>
                            <div style='text-align: center; margin-bottom: 30px;'>
                                <div style='width: 60px; height: 60px; background-color: #ffd700; border-radius: 50%; margin: 0 auto 20px auto; display: flex; align-items: center; justify-content: center;'>
                                    <span style='font-size: 24px; color: #000000;'>🔐</span>
                                </div>
                                <h2 style='color: #333333; margin: 0 0 10px 0; font-size: 24px; font-weight: 600;'>
                                    Code de Vérification
                                </h2>
                                <p style='color: #666666; margin: 0; font-size: 14px;'>
                                    Connexion sécurisée à votre compte
                                </p>
                            </div>
                            
                            <p style='color: #333333; font-size: 16px; line-height: 1.5; text-align: center; margin: 0 0 30px 0;'>
                                Bonjour,<br><br>
                                Voici votre code de vérification pour vous connecter à la plateforme <strong>Dienguix</strong> :
                            </p>
                            
                            <!-- Code OTP -->
                            <div style='text-align: center; margin: 30px 0;'>
                                <div style='background-color: #000000; color: #ffd700; font-size: 36px; font-weight: bold; padding: 25px; border-radius: 12px; letter-spacing: 8px; margin: 20px 0; border: 2px solid #ffd700; display: inline-block; min-width: 200px;'>
                                    {$otpCode}
                                </div>
                            </div>
                            
                            <!-- Informations de sécurité -->
                            <div style='background-color: #fff3cd; border: 1px solid #ffd700; border-radius: 8px; padding: 20px; margin: 30px 0;'>
                                <h3 style='color: #856404; margin: 0 0 15px 0; font-size: 16px; font-weight: 600;'>
                                    ⚠️ Informations importantes
                                </h3>
                                <ul style='color: #856404; margin: 0; padding-left: 20px; font-size: 14px; line-height: 1.6;'>
                                    <li style='margin-bottom: 8px;'>Ce code sert <strong>uniquement</strong> à vous connecter sur la plateforme Dienguix</li>
                                    <li style='margin-bottom: 8px;'><strong>Personne ne vous demandera jamais</strong> ce code par téléphone, email ou SMS</li>
                                    <li style='margin-bottom: 8px;'>Ne partagez <strong>jamais</strong> ce code avec qui que ce soit</li>
                                    <li>Ce code expire dans <strong>10 minutes</strong></li>
                                </ul>
                            </div>
                            
                            <p style='color: #666666; font-size: 14px; text-align: center; margin: 20px 0; line-height: 1.5;'>
                                Si vous n'avez pas demandé ce code de vérification, <br>
                                veuillez ignorer cet email ou contactez notre support.
                            </p>
                            
                            <!-- Bouton de contact support -->
                            <div style='text-align: center; margin: 30px 0;'>
                                <a href='mailto:support@dienguix.com' style='background-color: #ffd700; color: #000000; text-decoration: none; padding: 12px 25px; border-radius: 6px; font-weight: 600; font-size: 14px; display: inline-block;'>
                                    Contacter le Support
                                </a>
                            </div>
                        </div>
                        
                        <!-- Footer -->
                        <div style='background-color: #000000; padding: 25px 20px; text-align: center;'>
                            <p style='color: #cccccc; margin: 0 0 10px 0; font-size: 14px;'>
                                © 2024 Dienguix - Tous droits réservés
                            </p>
                            <p style='color: #888888; margin: 0; font-size: 12px; line-height: 1.4;'>
                                Cet email a été envoyé depuis une adresse automatisée.<br>
                                Merci de ne pas répondre directement à cet email.
                            </p>
                            <div style='margin-top: 15px;'>
                                <span style='color: #ffd700; font-size: 12px; text-transform: uppercase; letter-spacing: 1px;'>
                                    Sécurisé • Rapide • Fiable
                                </span>
                            </div>
                        </div>
                    </div>
                </body>
                </html>
                ";
    }


    public function sendInvitation(string $toEmail, string $role, string $message = '', string $inviteToken): bool
    {
        try {
            $email = (new Email())
                ->from($this->fromEmail)
                ->to($toEmail)
                ->subject('Invitation à rejoindre Dienguix')
                ->html($this->getInvitationEmailTemplate($toEmail, $role, $message, $inviteToken));

            $this->mailer->send($email);

            $this->logger->info('Invitation envoyée avec succès', [
                'to' => $toEmail,
                'role' => $role
            ]);
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'envoi de l\'invitation', [
                'error' => $e->getMessage(),
                'to' => $toEmail
            ]);
            return false;
        }
    }

    private function getInvitationEmailTemplate(string $toEmail, string $role, string $message, string $inviteToken): string
    {
        $encryptedLink = base64_encode("http://172.17.61.95:8080/register?token=" . $inviteToken);
        $verifyLink = "http://127.0.0.1:8000/api/users/verify-invite/" . $inviteToken;

        return "
                <html>
                <head>
                    <meta charset='UTF-8'>
                    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                    <title>Invitation Dienguix</title>
                </head>
                <body style='font-family: \"Segoe UI\", Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; background-color: #1a1a1a;'>
                    <div style='max-width: 600px; margin: 0 auto; background-color: #1a1a1a;'>
                        <!-- Header -->
                        <div style='background-color: #000000; padding: 30px 20px; text-align: center; border-bottom: 3px solid #ffd700;'>
                            <h1 style='color: #ffd700; margin: 0; font-size: 28px; font-weight: bold; letter-spacing: 2px;'>
                                DIENGUIX
                            </h1>
                            <p style='color: #cccccc; margin: 5px 0 0 0; font-size: 12px; text-transform: uppercase; letter-spacing: 1px;'>
                                Plateforme de Transfert d'Argent
                            </p>
                        </div>
                        
                        <!-- Contenu principal -->
                        <div style='background-color: #ffffff; padding: 40px 30px;'>
                            <div style='text-align: center; margin-bottom: 30px;'>
                                <div style='width: 60px; height: 60px; background-color: #ffd700; border-radius: 50%; margin: 0 auto 20px auto; display: flex; align-items: center; justify-content: center;'>
                                    <span style='font-size: 24px; color: #000000;'>✉️</span>
                                </div>
                                <h2 style='color: #333333; margin: 0 0 10px 0; font-size: 24px; font-weight: 600;'>
                                    Invitation à rejoindre Dienguix
                                </h2>
                                <p style='color: #666666; margin: 0; font-size: 14px;'>
                                    Vous êtes invité(e) en tant que <strong style='color: #ffd700;'>{$role}</strong>
                                </p>
                            </div>
                            
                            <p style='color: #333333; font-size: 16px; line-height: 1.5; text-align: center; margin: 0 0 20px 0;'>
                                Bonjour,<br><br>
                                Vous avez été invité(e) à rejoindre la plateforme <strong>Dienguix</strong> pour des transferts d'argent sécurisés.
                            </p>
                            
                            " . ($message ? "<div style='background-color: #f8f9fa; border-left: 4px solid #ffd700; padding: 20px; margin: 20px 0; border-radius: 0 8px 8px 0;'>
                                <h4 style='color: #333333; margin: 0 0 10px 0; font-size: 16px;'>Message personnel :</h4>
                                <p style='color: #666666; margin: 0; font-style: italic;'>\"{$message}\"</p>
                            </div>" : "") . "
                            
                            <!-- Bouton d'inscription -->
                            <div style='text-align: center; margin: 40px 0;'>
                                <a href='{$verifyLink}' style='background-color: #ffd700; color: #000000; text-decoration: none; padding: 15px 30px; border-radius: 8px; font-weight: 600; font-size: 16px; display: inline-block; text-transform: uppercase; letter-spacing: 1px;'>
                                    S'INSCRIRE MAINTENANT
                                </a>
                            </div>
                            
                            <!-- Instructions -->
                            <div style='background-color: #e8f5e8; border: 1px solid #28a745; border-radius: 8px; padding: 20px; margin: 30px 0;'>
                                <h3 style='color: #155724; margin: 0 0 15px 0; font-size: 16px; font-weight: 600;'>
                                    📋 Instructions
                                </h3>
                                <ol style='color: #155724; margin: 0; padding-left: 20px; font-size: 14px; line-height: 1.6;'>
                                    <li style='margin-bottom: 8px;'>Cliquez sur le bouton <strong>\"S'INSCRIRE MAINTENANT\"</strong> ci-dessus</li>
                                    <li style='margin-bottom: 8px;'>Vous serez redirigé vers la page d'inscription</li>
                                    <li style='margin-bottom: 8px;'>Remplissez le formulaire avec vos informations</li>
                                    <li>Votre compte sera créé avec le rôle : <strong>{$role}</strong></li>
                                </ol>
                            </div>
                            
                            <!-- Sécurité -->
                            <div style='background-color: #fff3cd; border: 1px solid #ffd700; border-radius: 8px; padding: 20px; margin: 30px 0;'>
                                <h3 style='color: #856404; margin: 0 0 15px 0; font-size: 16px; font-weight: 600;'>
                                    🔒 Sécurité
                                </h3>
                                <ul style='color: #856404; margin: 0; padding-left: 20px; font-size: 14px; line-height: 1.6;'>
                                    <li style='margin-bottom: 8px;'>Ce lien d'invitation est <strong>à usage unique</strong></li>
                                    <li style='margin-bottom: 8px;'>Une fois utilisé, il ne fonctionnera plus</li>
                                    <li style='margin-bottom: 8px;'>Ne partagez pas ce lien avec d'autres personnes</li>
                                    <li style='margin-bottom: 8px;'>Cette invitation expire dans <strong>7 jours</strong></li>
                                    <li>Si vous n'avez pas demandé cette invitation, ignorez cet email</li>
                                </ul>
                            </div>
                            
                            <p style='color: #666666; font-size: 14px; text-align: center; margin: 20px 0; line-height: 1.5;'>
                                Des questions ? Contactez notre équipe support :<br>
                                <a href='mailto:support@dienguix.com' style='color: #ffd700; text-decoration: none;'>support@dienguix.com</a>
                            </p>
                        </div>
                        
                        <!-- Footer -->
                        <div style='background-color: #000000; padding: 25px 20px; text-align: center;'>
                            <p style='color: #cccccc; margin: 0 0 10px 0; font-size: 14px;'>
                                © 2024 Dienguix - Tous droits réservés
                            </p>
                            <p style='color: #888888; margin: 0; font-size: 12px; line-height: 1.4;'>
                                Cet email a été envoyé depuis une adresse automatisée.<br>
                                Merci de ne pas répondre directement à cet email.
                            </p>
                            <div style='margin-top: 15px;'>
                                <span style='color: #ffd700; font-size: 12px; text-transform: uppercase; letter-spacing: 1px;'>
                                    Sécurisé • Rapide • Fiable
                                </span>
                            </div>
                        </div>
                    </div>
                </body>
                </html>";
    }
}
