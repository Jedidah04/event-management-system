<?php
require_once('../config/db.php');

class UserController {
    public static function registerForEvent($userId, $eventId) {
        global $pdo;
        $stmt = $pdo->prepare("INSERT INTO attendees (user_id, event_id) VALUES (?, ?)");
        $stmt->execute([$userId, $eventId]);
        return ['status' => 'success', 'message' => 'User registered'];
    }
}
