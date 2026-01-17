<?php

namespace App\Controllers;

class Test extends BaseController
{
    public function index()
    {
        $session = session();
        echo "<h1>Session Data:</h1>";
        echo "<pre>";
        print_r($session->get());
        echo "</pre>";

        echo "<h2>Auth Helper Test:</h2>";
        echo "Logged in: " . (is_logged_in() ? 'YES' : 'NO') . "<br>";
        echo "User ID: " . auth_id() . "<br>";
        echo "Role: " . auth_role() . "<br>";
        echo "Name: " . auth_name() . "<br>";
    }
}
