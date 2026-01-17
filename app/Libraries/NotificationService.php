<?php

/**
 * File Path: app/Libraries/NotificationService.php
 * 
 * Notification Service
 * Menyediakan service untuk mengelola notifikasi internal sistem
 * 
 * @package    SIB-K
 * @subpackage Libraries
 * @category   Communication
 * @author     Development Team
 * @created    2025-01-01
 */

namespace App\Libraries;

class NotificationService
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    /**
     * Send notification to user(s)
     * 
     * @param int|array   $userIds
     * @param string      $title
     * @param string|null $message
     * @param string      $type (info, success, warning, danger, message, ...)
     * @param string|null $link
     * @param array|null  $metadata -> disimpan ke kolom `data` (JSON)
     * @return bool
     */
    public function send($userIds, string $title, ?string $message = null, string $type = 'info', ?string $link = null, ?array $metadata = null)
    {
        if (!is_array($userIds)) {
            $userIds = [$userIds];
        }

        // Check table
        if (!$this->db->tableExists('notifications')) {
            return false;
        }

        $data = [];
        $timestamp = date('Y-m-d H:i:s');

        foreach ($userIds as $userId) {
            $data[] = [
                'user_id'    => (int) $userId,
                'title'      => $title,
                'message'    => $message,
                'type'       => $type,
                'link'       => $link,
                'data'       => $metadata ? json_encode($metadata, JSON_UNESCAPED_UNICODE) : null, // ← gunakan kolom `data`
                'is_read'    => 0,
                'created_at' => $timestamp,
            ];
        }

        return $this->db->table('notifications')->insertBatch($data);
    }

    /**
     * Send notification to all users with specific role
     */
    public function sendToRole(string $roleName, string $title, ?string $message = null, string $type = 'info', ?string $link = null)
    {
        $users = $this->db->table('users')
            ->select('users.id')
            ->join('roles', 'roles.id = users.role_id')
            ->where('roles.role_name', $roleName)
            ->where('users.is_active', 1)
            ->get()
            ->getResultArray();

        if (empty($users)) {
            return false;
        }

        $userIds = array_column($users, 'id');

        return $this->send($userIds, $title, $message, $type, $link);
    }

    /**
     * Send notification to all active users
     */
    public function sendToAll(string $title, ?string $message = null, string $type = 'info', ?string $link = null)
    {
        $users = $this->db->table('users')
            ->select('id')
            ->where('is_active', 1)
            ->get()
            ->getResultArray();

        if (empty($users)) {
            return false;
        }

        $userIds = array_column($users, 'id');

        return $this->send($userIds, $title, $message, $type, $link);
    }

    /**
     * Get unread notifications for user
     */
    public function getUnread(int $userId, int $limit = 10): array
    {
        if (!$this->db->tableExists('notifications')) {
            return [];
        }

        return $this->db->table('notifications')
            ->where('user_id', $userId)
            ->where('is_read', 0)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    /**
     * Get all notifications for user
     */
    public function getAll(int $userId, int $limit = 20, int $offset = 0): array
    {
        if (!$this->db->tableExists('notifications')) {
            return [];
        }

        return $this->db->table('notifications')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'DESC')
            ->limit($limit, $offset)
            ->get()
            ->getResultArray();
    }

    /**
     * Get unread count for user
     */
    public function getUnreadCount(int $userId): int
    {
        if (!$this->db->tableExists('notifications')) {
            return 0;
        }

        return $this->db->table('notifications')
            ->where('user_id', $userId)
            ->where('is_read', 0)
            ->countAllResults();
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(int $notificationId): bool
    {
        if (!$this->db->tableExists('notifications')) {
            return false;
        }

        return $this->db->table('notifications')
            ->where('id', $notificationId)
            ->update(['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')]);
    }

    /**
     * Mark all notifications as read for user
     */
    public function markAllAsRead(int $userId): bool
    {
        if (!$this->db->tableExists('notifications')) {
            return false;
        }

        return $this->db->table('notifications')
            ->where('user_id', $userId)
            ->where('is_read', 0)
            ->update(['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')]);
    }

    /**
     * Delete notification (hard delete)
     */
    public function delete(int $notificationId): bool
    {
        if (!$this->db->tableExists('notifications')) {
            return false;
        }

        return $this->db->table('notifications')
            ->where('id', $notificationId)
            ->delete();
    }

    /**
     * Delete all notifications for user (hard delete)
     */
    public function deleteAll(int $userId): bool
    {
        if (!$this->db->tableExists('notifications')) {
            return false;
        }

        return $this->db->table('notifications')
            ->where('user_id', $userId)
            ->delete();
    }

    /**
     * Clean old notifications (older than X days)
     */
    public function cleanOldNotifications(int $days = 30): bool
    {
        if (!$this->db->tableExists('notifications')) {
            return false;
        }

        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        return $this->db->table('notifications')
            ->where('created_at <', $cutoffDate)
            ->where('is_read', 1)
            ->delete();
    }

    /**
     * Send session reminder notification
     */
    public function sendSessionReminder(int $studentId, array $sessionData): bool
    {
        $title   = 'Pengingat Sesi Konseling';
        $message = "Anda memiliki sesi konseling pada {$sessionData['session_date']} pukul {$sessionData['session_time']}";
        $link    = '/student/schedule';

        return $this->send($studentId, $title, $message, 'info', $link, $sessionData);
    }

    /**
     * Send violation notification
     */
    public function sendViolationNotification(int $studentId, array $violationData): bool
    {
        $title   = 'Pelanggaran Tercatat';
        $message = "Pelanggaran baru telah dicatat: {$violationData['violation_type']} (+{$violationData['points']} poin)";
        $link    = '/student/violations';

        // Send to student
        $this->send($studentId, $title, $message, 'warning', $link, $violationData);

        // Send to parent if exists
        $student = $this->db->table('students')
            ->where('user_id', $studentId)
            ->get()
            ->getRowArray();

        if ($student && !empty($student['parent_id'])) {
            $parentMessage = "Anak Anda telah melakukan pelanggaran: {$violationData['violation_type']} (+{$violationData['points']} poin)";
            $this->send((int)$student['parent_id'], $title, $parentMessage, 'warning', '/parent/violations', $violationData);
        }

        return true;
    }

    /**
     * Send assessment notification
     */
    public function sendAssessmentNotification(int $studentId, array $assessmentData): bool
    {
        $title   = 'Asesmen Baru';
        $message = "Asesmen baru tersedia: {$assessmentData['assessment_title']}. Deadline: {$assessmentData['deadline']}";
        $link    = '/student/assessments';

        return $this->send($studentId, $title, $message, 'info', $link, $assessmentData);
    }

    /**
     * Send message notification
     * @param int         $recipientId
     * @param string      $senderName
     * @param string      $messagePreview
     * @param int|null    $messageId (opsional, untuk link langsung ke detail)
     */
    public function sendMessageNotification(int $recipientId, string $senderName, string $messagePreview, ?int $messageId = null): bool
    {
        $title   = 'Pesan Baru';
        $message = "Pesan baru dari {$senderName}: {$messagePreview}";
        $link    = $messageId ? '/messages/detail/'.$messageId : '/messages';

        return $this->send($recipientId, $title, $message, 'info', $link);
    }

    /**
     * Get notification statistics for user
     */
    public function getStatistics(int $userId): array
    {
        if (!$this->db->tableExists('notifications')) {
            return [
                'total'  => 0,
                'unread' => 0,
                'read'   => 0,
            ];
        }

        $total = $this->db->table('notifications')
            ->where('user_id', $userId)
            ->countAllResults();

        $unread = $this->db->table('notifications')
            ->where('user_id', $userId)
            ->where('is_read', 0)
            ->countAllResults();

        return [
            'total'  => $total,
            'unread' => $unread,
            'read'   => $total - $unread,
        ];
    }
}
