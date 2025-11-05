<?php

namespace App\Services;

class WarehouseAssignmentService
{
    /**
     * Mapping tỉnh/thành phố với kho hàng
     * Dựa trên 3 cụm chính: TP.HCM, Gia Lai (Tây Nguyên), Hà Nội
     */
    private const WAREHOUSE_MAPPING = [
        // Cụm TP. Hồ Chí Minh (miền Nam) - Warehouse ID: 2
        'TP. Hồ Chí Minh' => 2,
        'Hồ Chí Minh' => 2,
        'TP.HCM' => 2,
        'An Giang' => 2,
        'Cà Mau' => 2,
        'Cần Thơ' => 2,
        'Đồng Tháp' => 2,
        'Vĩnh Long' => 2,
        'Đồng Nai' => 2,
        'Tây Ninh' => 2,
        'Lâm Đồng' => 2,
        'Bà Rịa - Vũng Tàu' => 2,
        'Bình Dương' => 2,
        'Bình Phước' => 2,
        'Bình Thuận' => 2,
        'Bạc Liêu' => 2,
        'Bến Tre' => 2,
        'Hậu Giang' => 2,
        'Kiên Giang' => 2,
        'Long An' => 2,
        'Ninh Thuận' => 2,
        'Sóc Trăng' => 2,
        'Tiền Giang' => 2,
        'Trà Vinh' => 2,

        // Cụm Gia Lai (Tây Nguyên & Trung Bộ gần Tây Nguyên) - Warehouse ID: 3
        'Gia Lai' => 3,
        'Đắk Lắk' => 3,
        'Đắk Nông' => 3,
        'Kon Tum' => 3,
        'Đà Nẵng' => 3,
        'Thừa Thiên Huế' => 3,
        'Huế' => 3,
        'Quảng Trị' => 3,
        'Quảng Ngãi' => 3,
        'Khánh Hòa' => 3,
        'Quảng Nam' => 3,
        'Quảng Bình' => 3,
        'Bình Định' => 3,
        'Phú Yên' => 3,

        // Cụm Hà Nội (miền Bắc & Bắc Trung Bộ gần Hà Nội) - Warehouse ID: 1
        'Hà Nội' => 1,
        'Bắc Ninh' => 1,
        'Hưng Yên' => 1,
        'Thái Nguyên' => 1,
        'Hải Phòng' => 1,
        'Quảng Ninh' => 1,
        'Cao Bằng' => 1,
        'Lạng Sơn' => 1,
        'Tuyên Quang' => 1,
        'Phú Thọ' => 1,
        'Ninh Bình' => 1,
        'Thanh Hóa' => 1,
        'Nghệ An' => 1,
        'Hà Tĩnh' => 1,
        'Lào Cai' => 1,
        'Lai Châu' => 1,
        'Sơn La' => 1,
        'Điện Biên' => 1,
        'Hà Giang' => 1,
        'Bắc Giang' => 1,
        'Bắc Kạn' => 1,
        'Vĩnh Phúc' => 1,
        'Hà Nam' => 1,
        'Thái Bình' => 1,
        'Nam Định' => 1,
        'Hòa Bình' => 1,
        'Yên Bái' => 1,
    ];

    /**
     * Lấy warehouse ID dựa trên tên tỉnh/thành phố
     *
     * @param string $provinceName
     * @return int Warehouse ID (mặc định là Kho Hà Nội nếu không tìm thấy)
     */
    public static function getWarehouseIdByProvince(?string $provinceName): int
    {
        // If province is not provided, default to Ha Noi warehouse (ID 1)
        if ($provinceName === null || trim($provinceName) === "") {
            return 1;
        }
        // Chuẩn hóa tên tỉnh (loại bỏ khoảng trắng thừa, chuyển về dạng chuẩn)
        $provinceName = trim($provinceName);

        // Tìm warehouse ID từ mapping
        if (isset(self::WAREHOUSE_MAPPING[$provinceName])) {
            return self::WAREHOUSE_MAPPING[$provinceName];
        }

        // Thử tìm kiếm gần đúng (case-insensitive)
        foreach (self::WAREHOUSE_MAPPING as $province => $warehouseId) {
            if (strcasecmp($province, $provinceName) === 0) {
                return $warehouseId;
            }
        }

        // Mặc định trả về Kho Hà Nội nếu không tìm thấy
        return 1;
    }

    /**
     * Lấy tên kho hàng dựa trên warehouse ID
     *
     * @param int $warehouseId
     * @return string
     */
    public static function getWarehouseName(int $warehouseId): string
    {
        $names = [
            1 => 'Kho Hà Nội',
            2 => 'Kho TP. Hồ Chí Minh',
            3 => 'Kho Bình Định (Tây Nguyên)',
        ];

        return $names[$warehouseId] ?? 'Kho Hà Nội';
    }

    /**
     * Lấy danh sách tất cả tỉnh/thành phố theo cụm
     *
     * @return array
     */
    public static function getProvincesByCluster(): array
    {
        return [
            'south' => [
                'name' => 'Cụm TP. Hồ Chí Minh (miền Nam)',
                'warehouse_id' => 2,
                'warehouse_name' => 'Kho TP. Hồ Chí Minh',
                'provinces' => [
                    'TP. Hồ Chí Minh',
                    'An Giang',
                    'Cà Mau',
                    'Cần Thơ',
                    'Đồng Tháp',
                    'Vĩnh Long',
                    'Đồng Nai',
                    'Tây Ninh',
                    'Lâm Đồng',
                    'Bà Rịa - Vũng Tàu',
                    'Bình Dương',
                    'Bình Phước',
                    'Bình Thuận',
                    'Bạc Liêu',
                    'Bến Tre',
                    'Hậu Giang',
                    'Kiên Giang',
                    'Long An',
                    'Ninh Thuận',
                    'Sóc Trăng',
                    'Tiền Giang',
                    'Trà Vinh',
                ]
            ],
            'central' => [
                'name' => 'Cụm Gia Lai (Tây Nguyên & Trung Bộ)',
                'warehouse_id' => 3,
                'warehouse_name' => 'Kho Bình Định',
                'provinces' => [
                    'Gia Lai',
                    'Đắk Lắk',
                    'Đắk Nông',
                    'Kon Tum',
                    'Đà Nẵng',
                    'Thừa Thiên Huế',
                    'Quảng Trị',
                    'Quảng Ngãi',
                    'Khánh Hòa',
                    'Quảng Nam',
                    'Quảng Bình',
                    'Bình Định',
                    'Phú Yên',
                ]
            ],
            'north' => [
                'name' => 'Cụm Hà Nội (miền Bắc)',
                'warehouse_id' => 1,
                'warehouse_name' => 'Kho Hà Nội',
                'provinces' => [
                    'Hà Nội',
                    'Bắc Ninh',
                    'Hưng Yên',
                    'Thái Nguyên',
                    'Hải Phòng',
                    'Quảng Ninh',
                    'Cao Bằng',
                    'Lạng Sơn',
                    'Tuyên Quang',
                    'Phú Thọ',
                    'Ninh Bình',
                    'Thanh Hóa',
                    'Nghệ An',
                    'Hà Tĩnh',
                    'Lào Cai',
                    'Lai Châu',
                    'Sơn La',
                    'Điện Biên',
                    'Hà Giang',
                    'Bắc Giang',
                    'Bắc Kạn',
                    'Vĩnh Phúc',
                    'Hà Nam',
                    'Thái Bình',
                    'Nam Định',
                    'Hòa Bình',
                    'Yên Bái',
                ]
            ]
        ];
    }

    /**
     * Lấy danh sách tất cả tỉnh/thành phố
     *
     * @return array
     */
    public static function getAllProvinces(): array
    {
        $clusters = self::getProvincesByCluster();
        $allProvinces = [];

        foreach ($clusters as $cluster) {
            foreach ($cluster['provinces'] as $province) {
                $allProvinces[] = [
                    'name' => $province,
                    'warehouse_id' => $cluster['warehouse_id'],
                    'warehouse_name' => $cluster['warehouse_name'],
                    'cluster' => $cluster['name'],
                ];
            }
        }

        return $allProvinces;
    }
}
