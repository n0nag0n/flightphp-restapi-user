<?php
// AuthMiddleware.php

class AuthMiddleware {

    public function before() {
        $headers = Flight::request()->getHeaders();
        if (isset($headers['Authorization']) === true) {
            $token = $headers['Authorization'];
            // Normally, you would validate the token here. For simplicity, we'll just check if it's "secret"
            if ($token == 'secret') {
                return true;
            }
        }
        Flight::jsonHalt([ 'message' => 'Unauthorized' ], 401);
    }

}