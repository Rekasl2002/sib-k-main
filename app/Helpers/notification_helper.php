<?php

/**
 * File Path: app/Helpers/notification_helper.php
 * 
 * Notification Helper Functions
 * Menyediakan fungsi-fungsi untuk mengirim notifikasi dengan mudah
 * 
 * Load this helper: helper('notification');
 * 
 * @package    SIB-K
 * @subpackage Helpers
 * @category   Utilities
 * @author     Development Team
 * @created    2025-01-07
 */

if (!function_exists('send_notification')) {
    /**
     * Send notification to a single user
     * 
     * @param int         $user_id User ID
     * @param string      $title   Notification title
     * @param string|null $message Notification message
     * @param string      $type    Notification type (info, success, warning, danger, message, ...)
     * @param array       $data    Additional data (optional) -> disimpan ke kolom `data` (JSON)
     * @param string|null $link    Action link (opsional) -> disimpan ke kolom `link`
     * @return bool
     */
    function send_notification(
        int $user_id,
        string $title,
        ?string $message = null,
        string $type = 'info',
        array $data = [],
        ?string $link = null
    ): bool {
        try {
            $db = \Config\Database::connect();

            // Prepare notification data
            $notificationData = [
                'user_id'    => $user_id,
                'title'      => $title,
                'message'    => $message,
                'type'       => $type,
                'link'       => $link,
                'data'       => !empty($data) ? json_encode($data, JSON_UNESCAPED_UNICODE) : null,
                'is_read'    => 0,
                'created_at' => date('Y-m-d H:i:s'),
            ];

            // Insert notification
            return $db->table('notifications')->insert($notificationData);
        } catch (\Exception $e) {
            log_message('error', '[NOTIFICATION] Failed to send: ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('notify_users')) {
    /**
     * Send notification to multiple users
     * 
     * @param array       $user_ids Array of user IDs
     * @param string      $title    Notification title
     * @param string|null $message  Notification message
     * @param string      $type     Notification type
     * @param array       $data     Additional data (optional)
     * @param string|null $link     Action link (optional)
     * @return int Number of successful notifications
     */
    function notify_users(
        array $user_ids,
        string $title,
        ?string $message = null,
        string $type = 'info',
        array $data = [],
        ?string $link = null
    ): int {
        $success = 0;

        foreach ($user_ids as $user_id) {
            if (send_notification((int)$user_id, $title, $message, $type, $data, $link)) {
                $success++;
            }
        }

        return $success;
    }
}

if (!function_exists('notify_role')) {
    function notify_role(
        string $role_name,
        string $title,
        string $message,
        string $type = 'info',
        array $data = [],
        ?string $link = null
    ): int {
        try {
            $db = \Config\Database::connect();

            $users = $db->table('users')
                ->select('users.id')
                ->join('roles', 'roles.id = users.role_id')
                ->where('roles.role_name', $role_name)
                ->where('users.is_active', 1)
                ->where('users.deleted_at', null)
                ->get()
                ->getResultArray();

            $user_ids = array_column($users, 'id');

            return notify_users($user_ids, $title, $message, $type, $data, $link);
        } catch (\Exception $e) {
            log_message('error', '[NOTIFICATION] Failed to notify role: ' . $e->getMessage());
            return 0;
        }
    }
}

if (!function_exists('notify_class')) {
    function notify_class(
        int $class_id,
        string $title,
        string $message,
        string $type = 'info',
        array $data = [],
        ?string $link = null
    ): int {
        try {
            $db = \Config\Database::connect();

            $students = $db->table('students')
                ->select('user_id')
                ->where('class_id', $class_id)
                ->where('deleted_at', null)
                ->get()
                ->getResultArray();

            $user_ids = array_column($students, 'user_id');

            return notify_users($user_ids, $title, $message, $type, $data, $link);
        } catch (\Exception $e) {
            log_message('error', '[NOTIFICATION] Failed to notify class: ' . $e->getMessage());
            return 0;
        }
    }
}

if (!function_exists('notify_parents')) {
    function notify_parents(
        array $student_ids,
        string $title,
        string $message,
        string $type = 'info',
        array $data = [],
        ?string $link = null
    ): int {
        try {
            $db = \Config\Database::connect();

            $parents = $db->table('students')
                ->select('parent_id')
                ->whereIn('id', $student_ids)
                ->where('parent_id IS NOT NULL')
                ->where('deleted_at', null)
                ->get()
                ->getResultArray();

            $parent_ids = array_unique(array_column($parents, 'parent_id'));

            return notify_users($parent_ids, $title, $message, $type, $data, $link);
        } catch (\Exception $e) {
            log_message('error', '[NOTIFICATION] Failed to notify parents: ' . $e->getMessage());
            return 0;
        }
    }
}

if (!function_exists('get_user_notifications')) {
    function get_user_notifications(int $user_id, bool $unread_only = false, int $limit = 10): array
    {
        try {
            $db = \Config\Database::connect();

            $builder = $db->table('notifications')
                ->where('user_id', $user_id)
                ->where('deleted_at', null);

            if ($unread_only) {
                $builder->where('is_read', 0);
            }

            return $builder->orderBy('created_at', 'DESC')
                ->limit($limit)
                ->get()
                ->getResultArray();
        } catch (\Exception $e) {
            log_message('error', '[NOTIFICATION] Failed to get notifications: ' . $e->getMessage());
            return [];
        }
    }
}

if (!function_exists('get_unread_count')) {
    function get_unread_count(int $user_id): int
    {
        try {
            $db = \Config\Database::connect();

            return $db->table('notifications')
                ->where('user_id', $user_id)
                ->where('is_read', 0)
                ->where('deleted_at', null)
                ->countAllResults();
        } catch (\Exception $e) {
            log_message('error', '[NOTIFICATION] Failed to get unread count: ' . $e->getMessage());
            return 0;
        }
    }
}

if (!function_exists('mark_as_read')) {
    function mark_as_read($notification_ids, ?int $user_id = null): bool
    {
        try {
            $db = \Config\Database::connect();

            $builder = $db->table('notifications');

            if (is_array($notification_ids)) {
                $builder->whereIn('id', $notification_ids);
            } else {
                $builder->where('id', $notification_ids);
            }

            if ($user_id !== null) {
                $builder->where('user_id', $user_id);
            }

            return $builder->update([
                'is_read' => 1,
                'read_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            log_message('error', '[NOTIFICATION] Failed to mark as read: ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('mark_all_as_read')) {
    function mark_all_as_read(int $user_id): bool
    {
        try {
            $db = \Config\Database::connect();

            return $db->table('notifications')
                ->where('user_id', $user_id)
                ->where('is_read', 0)
                ->update([
                    'is_read' => 1,
                    'read_at' => date('Y-m-d H:i:s'),
                ]);
        } catch (\Exception $e) {
            log_message('error', '[NOTIFICATION] Failed to mark all as read: ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('delete_notification')) {
    function delete_notification(int $notification_id, ?int $user_id = null): bool
    {
        try {
            $db = \Config\Database::connect();

            $builder = $db->table('notifications')
                ->where('id', $notification_id);

            if ($user_id !== null) {
                $builder->where('user_id', $user_id);
            }

            return $builder->update([
                'deleted_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            log_message('error', '[NOTIFICATION] Failed to delete: ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('delete_all_notifications')) {
    function delete_all_notifications(int $user_id): bool
    {
        try {
            $db = \Config\Database::connect();

            return $db->table('notifications')
                ->where('user_id', $user_id)
                ->update([
                    'deleted_at' => date('Y-m-d H:i:s'),
                ]);
        } catch (\Exception $e) {
            log_message('error', '[NOTIFICATION] Failed to delete all: ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('notification_icon')) {
    function notification_icon(string $type): string
    {
        $icons = [
            'info'       => 'mdi mdi-information',
            'success'    => 'mdi mdi-check-circle',
            'warning'    => 'mdi mdi-alert',
            'danger'     => 'mdi mdi-alert-circle',
            'session'    => 'mdi mdi-calendar-clock',
            'assessment' => 'mdi mdi-clipboard-text',
            'violation'  => 'mdi mdi-alert-octagon',
            'message'    => 'mdi mdi-message-text',
        ];
        return $icons[$type] ?? 'mdi mdi-bell';
    }
}

if (!function_exists('notification_color')) {
    function notification_color(string $type): string
    {
        $colors = [
            'info'       => 'primary',
            'success'    => 'success',
            'warning'    => 'warning',
            'danger'     => 'danger',
            'session'    => 'info',
            'assessment' => 'purple',
            'violation'  => 'danger',
            'message'    => 'success',
        ];
        return $colors[$type] ?? 'secondary';
    }
}

if (!function_exists('format_notification_time')) {
    function format_notification_time(string $datetime): string
    {
        helper('date');
        return relative_time($datetime);
    }
}

if (!function_exists('render_notification_badge')) {
    function render_notification_badge(?int $user_id = null): string
    {
        helper('permission');

        if ($user_id === null) {
            $user_id = current_user_id();
        }

        if (!$user_id) {
            return '';
        }

        $count = get_unread_count($user_id);

        if ($count > 0) {
            $display = $count > 99 ? '99+' : $count;
            return '<span class="badge bg-danger rounded-pill notification-badge">' . $display . '</span>';
        }

        return '';
    }
}

if (!function_exists('broadcast_notification')) {
    function broadcast_notification(
        string $title,
        string $message,
        string $type = 'info',
        array $data = [],
        ?string $link = null
    ): int {
        try {
            $db = \Config\Database::connect();

            $users = $db->table('users')
                ->select('id')
                ->where('is_active', 1)
                ->where('deleted_at', null)
                ->get()
                ->getResultArray();

            $user_ids = array_column($users, 'id');

            return notify_users($user_ids, $title, $message, $type, $data, $link);
        } catch (\Exception $e) {
            log_message('error', '[NOTIFICATION] Failed to broadcast: ' . $e->getMessage());
            return 0;
        }
    }
}

if (!function_exists('notify_counselors')) {
    function notify_counselors(
        string $title,
        string $message,
        string $type = 'info',
        array $data = [],
        ?string $link = null
    ): int {
        try {
            $db = \Config\Database::connect();

            $users = $db->table('users')
                ->select('users.id')
                ->join('roles', 'roles.id = users.role_id')
                ->whereIn('roles.role_name', ['Guru BK', 'Koordinator BK'])
                ->where('users.is_active', 1)
                ->where('users.deleted_at', null)
                ->get()
                ->getResultArray();

            $user_ids = array_column($users, 'id');

            return notify_users($user_ids, $title, $message, $type, $data, $link);
        } catch (\Exception $e) {
            log_message('error', '[NOTIFICATION] Failed to notify counselors: ' . $e->getMessage());
            return 0;
        }
    }
}

if (!function_exists('notify_with_link')) {
    function notify_with_link(
        int $user_id,
        string $title,
        string $message,
        string $link,
        string $type = 'info'
    ): bool {
        $data = [
            'action_url'  => $link,
            'action_text' => 'Lihat Detail',
        ];

        return send_notification($user_id, $title, $message, $type, $data, $link);
    }
}

if (!function_exists('clean_old_notifications')) {
    function clean_old_notifications(int $days = 30): int
    {
        try {
            $db = \Config\Database::connect();

            $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));

            return $db->table('notifications')
                ->where('created_at <', $cutoff_date)
                ->where('is_read', 1)
                ->delete();
        } catch (\Exception $e) {
            log_message('error', '[NOTIFICATION] Failed to clean old notifications: ' . $e->getMessage());
            return 0;
        }
    }
}
