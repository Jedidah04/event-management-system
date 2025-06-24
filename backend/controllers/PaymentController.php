<?php
require_once('../config/db.php');              // connects to DB
require_once('../interfaces/PaymentProcessor.php');  // include the interface

// DummyPayment class simulates a payment
class DummyPayment implements PaymentProcessor {
    public function pay($userId, $eventId) {
        global $pdo;

        // Update the attendees table: set has_paid = 1
        $stmt = $pdo->prepare("UPDATE attendees SET has_paid = 1 WHERE user_id = ? AND event_id = ?");
        $stmt->execute([$userId, $eventId]);

        return ['status' => 'success', 'message' => 'Payment simulated'];
    }
}
