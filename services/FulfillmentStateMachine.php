<?php
require_once __DIR__ . '/../models/Order.php';

class FulfillmentStateMachine {
    private $order;
    private const TRANSITIONS = [
        'PROCESSING' => 'PICKING',
        'PICKING' => 'PACKING',
        'PACKING' => 'SHIPPED',
        'SHIPPED' => 'DELIVERED',
        'DELIVERED' => ''
    ];

    public function __construct() {
        $this->order = new Order();
    }

    public function getCurrentState($orderId) {
        $currentState = $this->fetchOrderState($orderId);
        return [
            'currentState' => $currentState,
            'nextAllowedState' => $this->getNextAllowedState($currentState)
        ];
    }

    public function fetchOrderState($orderId) {
        return $this->order->fetchOrderState($orderId);
    }

    public function validateTransition($currentState, $nextState) {
        $allowed = $this->getNextAllowedState($currentState);

        if ($currentState === '') {
            return [
                'valid' => false,
                'reason' => 'Invalid state transition'
            ];
        }

        if ($allowed === '') {
            return [
                'valid' => false,
                'reason' => 'Invalid state transition'
            ];
        }

        if ($allowed !== $nextState) {
            return [
                'valid' => false,
                'reason' => 'Invalid state transition'
            ];
        }

        return [
            'valid' => true,
            'reason' => ''
        ];
    }

    public function updateOrderState($orderId, $nextState) {
        return $this->order->updateOrderState($orderId, $nextState);
    }

    private function getNextAllowedState($currentState) {
        return self::TRANSITIONS[$currentState] ?? '';
    }
}
