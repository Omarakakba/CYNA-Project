<?php

use PHPUnit\Framework\TestCase;

class AuthTest extends TestCase
{
    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
        $_COOKIE  = [];
    }

    // --- isLoggedIn() ---

    public function testIsLoggedInReturnsFalseWhenNoSession(): void
    {
        $this->assertFalse(isLoggedIn());
    }

    public function testIsLoggedInReturnsTrueWhenSessionSet(): void
    {
        $_SESSION['user_id'] = 1;
        $this->assertTrue(isLoggedIn());
    }

    // --- isAdmin() ---

    public function testIsAdminReturnsFalseWhenNotLoggedIn(): void
    {
        $this->assertFalse(isAdmin());
    }

    public function testIsAdminReturnsFalseForRegularUser(): void
    {
        $_SESSION['user_id']   = 1;
        $_SESSION['user_role'] = 'user';
        $this->assertFalse(isAdmin());
    }

    public function testIsAdminReturnsTrueForAdmin(): void
    {
        $_SESSION['user_id']   = 1;
        $_SESSION['user_role'] = 'admin';
        $this->assertTrue(isAdmin());
    }

    // --- login() avec la base de données ---

    public function testLoginFailsWithWrongPassword(): void
    {
        $result = login('client@test.fr', 'mauvais_mot_de_passe');
        $this->assertFalse($result);
    }

    public function testLoginFailsWithUnknownEmail(): void
    {
        $result = login('inconnu_xxx@test.fr', 'Admin1234!');
        $this->assertFalse($result);
    }

    public function testLoginSucceedsWithValidCredentials(): void
    {
        // Récupère un admin existant en base pour le test
        $db    = getDB();
        $admin = $db->query("SELECT email FROM user WHERE role = 'admin' LIMIT 1")->fetch();
        if (!$admin) {
            $this->markTestSkipped('Aucun admin en base pour ce test.');
        }
        $result = login($admin['email'], 'Admin1234!');
        $this->assertTrue($result);
        $this->assertArrayHasKey('user_id', $_SESSION);
        $this->assertEquals('admin', $_SESSION['user_role']);
    }

    public function testLoginSetsCorrectSessionKeys(): void
    {
        $db   = getDB();
        $user = $db->query("SELECT email FROM user WHERE role = 'user' LIMIT 1")->fetch();
        if (!$user) {
            $this->markTestSkipped('Aucun user en base pour ce test.');
        }
        $result = login($user['email'], 'Admin1234!');
        if (!$result) {
            $this->markTestSkipped('Mot de passe différent en base locale — test valide uniquement avec seed.sql.');
        }
        $this->assertArrayHasKey('user_id', $_SESSION);
        $this->assertArrayHasKey('user_email', $_SESSION);
        $this->assertArrayHasKey('user_role', $_SESSION);
    }
}
