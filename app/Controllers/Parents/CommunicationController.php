<?php

namespace App\Controllers\Parents;

class CommunicationController extends BaseParentController
{
    public function index()
    {
        return redirect()->to(site_url('messages/inbox'));
    }

    public function sendMessage()
    {
        return redirect()->to(site_url('messages/compose'))
            ->with('info', 'Silakan tulis pesan melalui halaman pesan internal.');
    }
}
