<?php
interface PaymentProcessor {
    public function pay($userId, $eventId);
}
