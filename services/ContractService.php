<?php
require_once __DIR__ . '/../models/SupplierContract.php';

class ContractService {
    public function fetchSupplierContracts() {
        $model = new SupplierContract();
        return $model->fetchSupplierContracts();
    }

    public function getContractVersions() {
        $model = new SupplierContract();
        return $model->getContractVersions();
    }
}
