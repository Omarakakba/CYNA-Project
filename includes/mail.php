<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function sendMail(string $to, string $subject, string $html_body): bool {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $html_body;

        $mail->send();
        $sent = true;
    } catch (Exception $e) {
        $sent = false;
        // Log l'erreur pour debug
        $log_dir = __DIR__ . '/../logs';
        if (!is_dir($log_dir)) mkdir($log_dir, 0755, true);
        file_put_contents($log_dir . '/mail.log',
            '[' . date('Y-m-d H:i:s') . '] ERREUR → ' . $mail->ErrorInfo . "\n",
            FILE_APPEND
        );
    }

    // Log chaque envoi
    $log_dir = __DIR__ . '/../logs';
    if (!is_dir($log_dir)) mkdir($log_dir, 0755, true);
    file_put_contents($log_dir . '/mail.log',
        '[' . date('Y-m-d H:i:s') . '] TO: ' . $to . ' | SUBJECT: ' . $subject . ' | SENT: ' . ($sent ? 'yes' : 'no') . "\n",
        FILE_APPEND
    );

    return $sent;
}

function sendWelcomeEmail(string $email): void {
    $subject = 'Bienvenue chez CYNA — Votre compte est créé';
    $link    = 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/cyna/espace-client.php';

    $html = <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#0d1117;font-family:'Segoe UI',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#0d1117;padding:40px 20px;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="background:#161b22;border-radius:12px;overflow:hidden;max-width:600px;width:100%;">
  <tr>
    <td style="background:linear-gradient(135deg,#0d1117 0%,#1a237e 100%);padding:40px 40px 30px;text-align:center;">
      <div style="font-size:2rem;font-weight:900;color:#ffffff;letter-spacing:1px;">CY<span style="color:#3b82f6;">NA</span></div>
      <div style="font-size:0.8rem;color:rgba(255,255,255,0.4);margin-top:4px;">Solutions SaaS Cybersécurité</div>
    </td>
  </tr>
  <tr>
    <td style="padding:40px;">
      <h1 style="color:#ffffff;font-size:1.4rem;font-weight:700;margin:0 0 12px;">Bienvenue chez CYNA</h1>
      <p style="color:#8b949e;font-size:0.95rem;line-height:1.7;margin:0 0 20px;">
        Votre compte a bien été créé avec l'adresse <strong style="color:#ffffff;">{$email}</strong>.<br>
        Vous pouvez dès maintenant accéder à votre espace client et explorer nos solutions de cybersécurité.
      </p>
      <div style="background:#0d1117;border:1px solid #30363d;border-radius:8px;padding:20px;margin:24px 0;">
        <p style="color:#8b949e;font-size:0.85rem;margin:0 0 8px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;">Ce que vous pouvez faire :</p>
        <p style="color:#c9d1d9;font-size:0.9rem;line-height:1.8;margin:0;">
          + Parcourir le catalogue EDR, SOC, VPN<br>
          + Ajouter des solutions à votre panier<br>
          + Gérer vos abonnements et commandes<br>
          + Télécharger vos factures
        </p>
      </div>
      <div style="text-align:center;margin:32px 0;">
        <a href="{$link}" style="background:#3b82f6;color:#ffffff;text-decoration:none;padding:14px 32px;border-radius:8px;font-weight:700;font-size:0.95rem;display:inline-block;">
          Accéder à mon espace client →
        </a>
      </div>
    </td>
  </tr>
  <tr>
    <td style="background:#0d1117;border-top:1px solid #30363d;padding:24px 40px;text-align:center;">
      <p style="color:#484f58;font-size:0.78rem;margin:0;line-height:1.6;">
        © 2025 CYNA Security SAS — 42 Avenue de la Cybersécurité, 75008 Paris<br>
        Cet email a été envoyé à {$email} suite à la création de votre compte.
      </p>
    </td>
  </tr>
</table>
</td></tr>
</table>
</body>
</html>
HTML;

    sendMail($email, $subject, $html);
}

function sendResetEmail(string $email, string $reset_link): void {
    $subject = 'CYNA — Réinitialisation de votre mot de passe';

    $html = <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#0d1117;font-family:'Segoe UI',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#0d1117;padding:40px 20px;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="background:#161b22;border-radius:12px;overflow:hidden;max-width:600px;width:100%;">
  <tr>
    <td style="background:linear-gradient(135deg,#0d1117 0%,#1a237e 100%);padding:40px 40px 30px;text-align:center;">
      <div style="font-size:2rem;font-weight:900;color:#ffffff;letter-spacing:1px;">CY<span style="color:#3b82f6;">NA</span></div>
      <div style="font-size:0.8rem;color:rgba(255,255,255,0.4);margin-top:4px;">Solutions SaaS Cybersécurité</div>
    </td>
  </tr>
  <tr>
    <td style="padding:40px;">
      <h1 style="color:#ffffff;font-size:1.4rem;font-weight:700;margin:0 0 12px;">Réinitialisation de mot de passe</h1>
      <p style="color:#8b949e;font-size:0.95rem;line-height:1.7;margin:0 0 20px;">
        Nous avons reçu une demande de réinitialisation du mot de passe associé à
        <strong style="color:#ffffff;">{$email}</strong>.<br>
        Ce lien est valable <strong style="color:#ffffff;">1 heure</strong>.
      </p>
      <div style="text-align:center;margin:32px 0;">
        <a href="{$reset_link}" style="background:#3b82f6;color:#ffffff;text-decoration:none;padding:14px 32px;border-radius:8px;font-weight:700;font-size:0.95rem;display:inline-block;">
          Réinitialiser mon mot de passe →
        </a>
      </div>
      <div style="background:#0d1117;border:1px solid #30363d;border-radius:8px;padding:16px;margin-top:24px;">
        <p style="color:#8b949e;font-size:0.82rem;margin:0;line-height:1.6;">
          Si vous n'avez pas fait cette demande, ignorez cet e-mail — votre mot de passe reste inchangé.<br>
          Le lien expirera automatiquement dans 1 heure.
        </p>
      </div>
    </td>
  </tr>
  <tr>
    <td style="background:#0d1117;border-top:1px solid #30363d;padding:24px 40px;text-align:center;">
      <p style="color:#484f58;font-size:0.78rem;margin:0;line-height:1.6;">
        © 2025 CYNA Security SAS — 42 Avenue de la Cybersécurité, 75008 Paris<br>
        Cet email a été envoyé à {$email} suite à une demande de réinitialisation.
      </p>
    </td>
  </tr>
</table>
</td></tr>
</table>
</body>
</html>
HTML;

    sendMail($email, $subject, $html);
}
