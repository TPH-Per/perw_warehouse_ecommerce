<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WarehouseAssignmentService;
use Illuminate\Http\JsonResponse;

class ProvinceController extends Controller
{
    /**
     * Get all provinces grouped by cluster
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $provinces = WarehouseAssignmentService::getProvincesByCluster();

        return response()->json($provinces);
    }

    /**
     * Get all provinces as a flat list
     *
     * @return JsonResponse
     */
    public function list(): JsonResponse
    {
        $provinces = WarehouseAssignmentService::getAllProvinces();

        return response()->json($provinces);
    }

    /**
     * Get warehouse ID for a specific province
     *
     * @param string $province
     * @return JsonResponse
     */
    public function getWarehouse(string $province): JsonResponse
    {
        $warehouseId = WarehouseAssignmentService::getWarehouseIdByProvince($province);
        $warehouseName = WarehouseAssignmentService::getWarehouseName($warehouseId);

        return response()->json([
            'province' => $province,
            'warehouse_id' => $warehouseId,
            'warehouse_name' => $warehouseName,
        ]);
    }
}
