<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\NotificationModel;

class NotificationApiController extends BaseController
{
    private NotificationModel $notifications;

    public function __construct()
    {
        $this->notifications = new NotificationModel();
    }

    public function getLatest()
    {
        $items = $this->notifications
            ->where('user_id', (int) session('user_id'))
            ->orderBy('created_at', 'DESC')
            ->findAll(10);

        return $this->response->setJSON($items);
    }

    public function getUnreadCount()
    {
        $count = $this->notifications
            ->where('user_id', (int) session('user_id'))
            ->where('is_read', 0)
            ->countAllResults();

        return $this->response->setJSON(['count' => (int) $count]);
    }

    public function markAsRead($id)
    {
        $updated = $this->notifications
            ->where('id', (int) $id)
            ->where('user_id', (int) session('user_id'))
            ->set(['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')])
            ->update();

        return $this->response->setJSON(['status' => $updated ? 'ok' : 'not_found']);
    }
}
