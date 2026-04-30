<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/security.php';
session_start();

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Session expirée. Veuillez réessayer.';
    } else {
        $name    = trim($_POST['name']    ?? '');
        $email   = trim($_POST['email']   ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');

        if (empty($name) || empty($email) || empty($subject) || empty($message)) {
            $error = 'Veuillez remplir tous les champs.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Adresse e-mail invalide.';
        } elseif (strlen($message) < 20) {
            $error = 'Votre message est trop court (minimum 20 caractères).';
        } else {
            $db = getDB();
            $db->prepare('INSERT INTO contact_message (name, email, subject, message) VALUES (?, ?, ?, ?)')
               ->execute([$name, $email, $subject, $message]);
            $success = 'Votre message a été envoyé. Notre équipe vous répondra dans les 24 heures.';
        }
    }
}

$page_title = 'Contact';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div class="page-header-inner">
        <h1>Nous contacter</h1>
        <p>Notre équipe est disponible du lundi au vendredi, 9h–18h</p>
    </div>
</div>

<div class="page-content">
    <div class="contact-layout">

        <!-- Coordonnées -->
        <aside class="contact-info">
            <div class="contact-info-card">
                <div class="contact-info-icon blue"><i class="fa-solid fa-envelope"></i></div>
                <h3>E-mail</h3>
                <p>contact@cyna-security.fr</p>
                <p style="font-size:0.8rem; color:var(--text-muted);">Réponse sous 24h</p>
            </div>
            <div class="contact-info-card">
                <div class="contact-info-icon green"><i class="fa-solid fa-phone"></i></div>
                <h3>Téléphone</h3>
                <p>+33 1 23 45 67 89</p>
                <p style="font-size:0.8rem; color:var(--text-muted);">Lun–Ven, 9h–18h</p>
            </div>
            <div class="contact-info-card">
                <div class="contact-info-icon purple"><i class="fa-solid fa-location-dot"></i></div>
                <h3>Adresse</h3>
                <p>42 Avenue de la Cybersécurité<br>75008 Paris, France</p>
            </div>
            <div class="contact-info-card">
                <div class="contact-info-icon orange"><i class="fa-solid fa-headset"></i></div>
                <h3>Support SOC</h3>
                <p>support@cyna-security.fr</p>
                <p style="font-size:0.8rem; color:var(--text-muted);">Disponible 24/7 pour les clients</p>
            </div>
        </aside>

        <!-- Formulaire -->
        <div>
            <div class="admin-card">
                <div class="admin-card-header">
                    <div class="admin-card-title">
                        <i class="fa-solid fa-paper-plane"></i> Envoyer un message
                    </div>
                </div>
                <div class="admin-card-body">

                    <?php if ($error):   ?><div class="alert alert-error"><i class="fa-solid fa-triangle-exclamation"></i> <?= escape($error) ?></div><?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><i class="fa-solid fa-check"></i> <?= escape($success) ?></div>
                    <?php endif; ?>

                    <?php if (!$success): ?>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:0;">
                            <div class="form-group">
                                <label>Nom complet <span style="color:var(--red)">*</span></label>
                                <input type="text" name="name" required
                                       value="<?= escape($_POST['name'] ?? '') ?>"
                                       placeholder="Jean Dupont">
                            </div>
                            <div class="form-group">
                                <label>Adresse e-mail <span style="color:var(--red)">*</span></label>
                                <input type="email" name="email" required
                                       value="<?= escape($_POST['email'] ?? '') ?>"
                                       placeholder="jean@entreprise.fr">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Sujet <span style="color:var(--red)">*</span></label>
                            <select name="subject" required>
                                <option value="">— Choisir un sujet —</option>
                                <option value="Demande commerciale"    <?= ($_POST['subject'] ?? '') === 'Demande commerciale'    ? 'selected' : '' ?>>Demande commerciale</option>
                                <option value="Support technique"      <?= ($_POST['subject'] ?? '') === 'Support technique'      ? 'selected' : '' ?>>Support technique</option>
                                <option value="Question sur une commande" <?= ($_POST['subject'] ?? '') === 'Question sur une commande' ? 'selected' : '' ?>>Question sur une commande</option>
                                <option value="Partenariat"            <?= ($_POST['subject'] ?? '') === 'Partenariat'            ? 'selected' : '' ?>>Partenariat</option>
                                <option value="Autre"                  <?= ($_POST['subject'] ?? '') === 'Autre'                  ? 'selected' : '' ?>>Autre</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Message <span style="color:var(--red)">*</span></label>
                            <textarea name="message" required rows="6"
                                      placeholder="Décrivez votre demande en détail…"
                                      style="width:100%; padding:0.75rem 1rem; background:var(--surface); border:1px solid var(--border); border-radius:8px; color:var(--text); font-size:0.9rem; resize:vertical; box-sizing:border-box;"><?= escape($_POST['message'] ?? '') ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-paper-plane"></i> Envoyer le message
                        </button>
                    </form>
                    <?php else: ?>
                        <div style="text-align:center; padding:2rem 0;">
                            <a href="/cyna/contact.php" class="btn btn-secondary">
                                <i class="fa-solid fa-arrow-left"></i> Envoyer un autre message
                            </a>
                            <a href="/cyna/" class="btn btn-primary" style="margin-left:0.75rem;">
                                <i class="fa-solid fa-house"></i> Accueil
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
