<?php
require_once('../config/db.php');

class EventController {
    public static function createEvent($data) {
        global $pdo;
        $stmt = $pdo->prepare("INSERT INTO events (title, description, start_time, end_time, venue_id, user_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $data['title'],
            $data['description'],
            $data['start_time'],
            $data['end_time'],
            $data['venue_id'],
            $data['user_id']
        ]);
        return ['status' => 'success', 'message' => 'Event created'];
    }

    public static function getEvents() {
        global $pdo;
        $stmt = $pdo->query("SELECT * FROM events");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
