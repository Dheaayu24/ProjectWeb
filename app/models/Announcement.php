<?php
/**
 * ============================================================================
 * Mochi Mood – Model Pengumuman (Announcements)
 * File: app/models/Announcement.php
 * Deskripsi: Model untuk mengelola pengumuman dan broadcast banner.
 * ============================================================================
 */

require_once __DIR__ . '/../../config/database.php';

class Announcement
{
    protected PDO $db;
    protected string $table = 'announcements';

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function getAll(): array
    {
        $stmt = $this->db->query("SELECT * FROM {$this->table} ORDER BY id DESC");
        return $stmt->fetchAll();
    }

    public function getActive(): ?array
    {
        $stmt = $this->db->query("SELECT * FROM {$this->table} WHERE is_active = 1 ORDER BY id DESC LIMIT 1");
        $res = $stmt->fetch();
        return $res ?: null;
    }

    public function create(string $title, string $message): int
    {
        $stmt = $this->db->prepare("INSERT INTO {$this->table} (title, message, is_active, created_at) VALUES (?, ?, 1, NOW())");
        $stmt->execute([trim($title), trim($message)]);
        return (int)$this->db->lastInsertId();
    }

    public function toggleActive(int $id, int $status): bool
    {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET is_active = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }
}

