<?php
require_once __DIR__ . '/../models/Order.php';

class FulfillmentStateMachine {
    private $order;

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
                'reason' => 'Order state could not be found.'
            ];
        }

        if ($allowed === '') {
            return [
                'valid' => false,
                'reason' => 'This order is already in its final state.'
            ];
        }

        if ($allowed !== $nextState) {
            return [
                'valid' => false,
                'reason' => "Invalid transition. Next allowed state is {$allowed}."
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
        $map = [
            'PROCESSING' => 'PICKING',
            'PICKING' => 'PACKING',
            'PACKING' => 'SHIPPED',
            'SHIPPED' => 'DELIVERED',
            'DELIVERED' => ''
        ];

        return $map[$currentState] ?? '';
    }
}
