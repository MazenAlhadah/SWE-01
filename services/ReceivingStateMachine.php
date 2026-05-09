<?php
require_once __DIR__ . '/../models/Shipment.php';

class ReceivingStateMachine {

    public function initReceivingStateMachine($shipmentId) {
        $shipmentModel = new Shipment();
        $currentState = $shipmentModel->getShipmentById($shipmentId);
        $state = $currentState ? $currentState['state'] : '';
        $allowedStates = ['EXPECTED', 'AT_DOCK', 'BEING_INSPECTED', 'STORED'];

        if ($state === '' || !in_array($state, $allowedStates)) {
            $shipmentModel->setShipmentState($shipmentId, 'EXPECTED');
            return [
                'valid' => true,
                'state' => 'EXPECTED'
            ];
        }

        return [
            'valid' => true,
            'state' => $state
        ];
    }

    public function validateTransition($currentState, $nextState) {
        $allowed = [
            'EXPECTED' => 'AT_DOCK',
            'AT_DOCK' => 'BEING_INSPECTED',
            'BEING_INSPECTED' => 'STORED',
            'STORED' => ''
        ];

        if ($currentState === $nextState) {
            return true;
        }

        if (!isset($allowed[$currentState])) {
            return false;
        }

        return $allowed[$currentState] === $nextState;
    }
}
