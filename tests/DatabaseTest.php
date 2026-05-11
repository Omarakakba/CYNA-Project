<?php

use PHPUnit\Framework\TestCase;

class DatabaseTest extends TestCase
{
    private PDO $db;

    protected function setUp(): void
    {
        $this->db = getDB();
    }

    // --- Connexion BDD ---

    public function testDatabaseConnectionSucceeds(): void
    {
        $this->assertInstanceOf(PDO::class, $this->db);
    }

    public function testDatabaseReturnsSingletonInstance(): void
    {
        $db1 = getDB();
        $db2 = getDB();
        $this->assertSame($db1, $db2);
    }

    // --- Tables existent ---

    public function testTableUserExists(): void
    {
        $result = $this->db->query("SHOW TABLES LIKE 'user'")->fetchColumn();
        $this->assertEquals('user', $result);
    }

    public function testTableProductExists(): void
    {
        $result = $this->db->query("SHOW TABLES LIKE 'product'")->fetchColumn();
        $this->assertEquals('product', $result);
    }

    public function testTableOrderExists(): void
    {
        $result = $this->db->query("SHOW TABLES LIKE 'order'")->fetchColumn();
        $this->assertEquals('order', $result);
    }

    public function testTableRateLimitExists(): void
    {
        $result = $this->db->query("SHOW TABLES LIKE 'rate_limit'")->fetchColumn();
        $this->assertEquals('rate_limit', $result);
    }

    // --- Données seed présentes ---

    public function testAdminUserExistsInSeed(): void
    {
        $count = (int) $this->db->query("SELECT COUNT(*) FROM user WHERE role = 'admin'")->fetchColumn();
        $this->assertGreaterThan(0, $count, 'Au moins un administrateur doit exister en base');
    }

    public function testProductsExistInSeed(): void
    {
        $count = (int) $this->db->query("SELECT COUNT(*) FROM product")->fetchColumn();
        $this->assertGreaterThan(0, $count);
    }

    // --- Requêtes préparées (protection injection SQL) ---

    public function testPreparedStatementRejectsInjection(): void
    {
        $malicious = "' OR '1'='1";
        $stmt = $this->db->prepare("SELECT id FROM user WHERE email = ?");
        $stmt->execute([$malicious]);
        $this->assertFalse($stmt->fetch());
    }
}
