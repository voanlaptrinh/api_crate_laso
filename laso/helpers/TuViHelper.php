<?php
// file: helpers/TuViHelper.php

class TuViHelper
{
    // --- HẰNG SỐ CƠ BẢN ---
    private const THIEN_CAN = ['Giáp', 'Ất', 'Bính', 'Đinh', 'Mậu', 'Kỷ', 'Canh', 'Tân', 'Nhâm', 'Quý'];
    private const DIA_CHI = ['Tý', 'Sửu', 'Dần', 'Mão', 'Thìn', 'Tỵ', 'Ngọ', 'Mùi', 'Thân', 'Dậu', 'Tuất', 'Hợi'];
    private const CUNG_CHUC_NANG = ['MỆNH', 'PHỤ MẪU', 'PHÚC ĐỨC', 'ĐIỀN TRẠCH', 'QUAN LỘC', 'NÔ BỘC', 'THIÊN DI', 'TẬT ÁCH', 'TÀI BẠCH', 'TỬ TỨC', 'PHU THÊ', 'HUYNH ĐỆ'];
    private static $cungChucNangAbbr = ['MỆNH' => 'MỆNH', 'PHỤ MẪU' => 'PHỤ', 'PHÚC ĐỨC' => 'PHÚC', 'ĐIỀN TRẠCH' => 'ĐIỀN', 'QUAN LỘC' => 'QUAN', 'NÔ BỘC' => 'NÔ', 'THIÊN DI' => 'DI', 'TẬT ÁCH' => 'TẬT', 'TÀI BẠCH' => 'TÀI', 'TỬ TỨC' => 'TỬ', 'PHU THÊ' => 'THÊ', 'HUYNH ĐỆ' => 'HUYNH',];
    private const NGU_HANH_RELATIONS = [
        'Kim' => ['sinh' => 'Thủy', 'khac' => 'Mộc'],
        'Mộc' => ['sinh' => 'Hỏa', 'khac' => 'Thổ'],
        'Thủy' => ['sinh' => 'Mộc', 'khac' => 'Hỏa'],
        'Hỏa' => ['sinh' => 'Thổ', 'khac' => 'Kim'],
        'Thổ' => ['sinh' => 'Kim', 'khac' => 'Thủy'],
    ];
    // --- BẢNG TRA CỨU TỪ HÌNH ẢNH (CÔNG THỨC CỐT LÕI) ---

    /**
     * Bảng 1: Tra cứu vị trí sao Tử Vi dựa vào Cục và Ngày sinh.
     */
    private $brightnessTable; // Biến để cache bảng độ sáng
    protected $saoGroupClasses = [];
    //   private $data_sao_brightness;
    private $data_loc_ton;
    private $data_theo_chi_nam;
    private $data_vong_trang_sinh;
    private $data_chu_menh_than;
    private $data_thien_tru;
    private $data_thien_quan_phuc;
    private $data_triet;
    private $data_tuan_khong_vong;
    private $data_nap_am;
    private $data_sao_ngu_hanh;
    public function __construct()
    {
        // === KHỞI TẠO CÁC CLASS NHÓM (GROUP) CHO SAO (TÙY CHỌN) ===
        // Dùng để nhóm các sao có tính chất tương tự, giúp style CSS hàng loạt dễ hơn.
        $this->saoGroupClasses = [
            'Hóa Lộc' => 'sao-nhom-tu-hoa',
            'Hóa Quyền' => 'sao-nhom-tu-hoa',
            'Hóa Khoa' => 'sao-nhom-tu-hoa',
            'Hóa Kỵ' => 'sao-nhom-tu-hoa',

            'Tả Phù' => 'sao-nhom-luc-cat',
            'Hữu Bật' => 'sao-nhom-luc-cat',
            'Văn Xương' => 'sao-nhom-luc-cat',
            'Văn Khúc' => 'sao-nhom-luc-cat',
            'Thiên Khôi' => 'sao-nhom-luc-cat',
            'Thiên Việt' => 'sao-nhom-luc-cat',

            'Kình Dương' => 'sao-nhom-luc-sat',
            'Đà La' => 'sao-nhom-luc-sat',
            'Hỏa Tinh' => 'sao-nhom-luc-sat',
            'Linh Tinh' => 'sao-nhom-luc-sat',
            'Địa Không' => 'sao-nhom-luc-sat',
            'Địa Kiếp' => 'sao-nhom-luc-sat',
        ];

        // Cache bảng độ sáng để dùng nhiều lần
        // $this->brightnessTable = config('tuvi_data.sao_brightness');
        $this->brightnessTable = require ROOT_PATH . '/data/sao_brightness.php';
        $this->data_nap_am = require ROOT_PATH . '/data/nap_am.php';
        $this->data_theo_chi_nam = require ROOT_PATH . '/data/theo_chi_nam.php';
        $this->data_loc_ton = require ROOT_PATH . '/data/loc_ton.php';
        $this->data_vong_trang_sinh = require ROOT_PATH . '/data/vong_trang_sinh.php';
        $this->data_chu_menh_than = require ROOT_PATH . '/data/chu_menh_than.php';
        $this->data_thien_tru = require ROOT_PATH . '/data/thien_tru.php';
        $this->data_thien_quan_phuc = require ROOT_PATH . '/data/thien_quan_phuc.php';
        $this->data_triet = require ROOT_PATH . '/data/triet.php';
        $this->data_tuan_khong_vong = require ROOT_PATH . '/data/tuan_khong_vong.php';
        $this->data_sao_ngu_hanh = require ROOT_PATH . '/data/sao_ngu_hanh.php';
    }


    private $input, $laSo, $cungMenhViTri, $cungThanViTri, $cucSo, $amDuong;
    private $viTriTuVi = null;

    public function generate(array $normalizedData): array
    {
        $this->input = $normalizedData;
        $this->initializeLaSo();
        $this->input['lunar']['year_can'] = CustomLunarConverterHelper::getCan($this->input['lunar']['year']);
        $this->input['lunar']['year_chi'] = CustomLunarConverterHelper::getChi($this->input['lunar']['year']);
        $this->lapThongTinCoBan();
        $this->anCungMenhThan();
        $this->anCanCung();
        $this->tinhCuc();
        $this->anCungChucNang();
        $this->anChinhTinh();
        $this->anPhuTinh();
        $this->anCacSaoCoDinhVaTheoChucNang();
        $this->anVongTrangSinh();
        $this->anTuanTriet();
        $this->anDaiVan();
        $this->anSaoLuu();
        $this->anTieuVanCung();
        $this->anTieuHan();
        $this->anTieuVanThang();
        $this->anTenCacVanHan();
        $this->tinhChuMenhThan();
        $this->tinhLuanGiaiCoBan();
        $this->tinhCucMenhRelation();
        // $this->anDoSangSao();
        $this->anTuHoa();

        return $this->laSo;
    }
    private function tinhCucMenhRelation()
    {
        // Lấy hành của Cục và Mệnh đã được tính toán trước đó
        $hanhCuc = $this->laSo['info']['hanh_cuc'] ?? null;
        $hanhMenh = $this->laSo['info']['hanh_menh'] ?? null;

        if (!$hanhCuc || !$hanhMenh) {
            $this->laSo['info']['cuc_menh_relation'] = 'Chưa rõ';
            return;
        }

        // Trường hợp 1: Bình hòa
        if ($hanhCuc === $hanhMenh) {
            $this->laSo['info']['cuc_menh_relation'] = 'Cục Hòa Bản Mệnh';
            return;
        }

        // Trường hợp 2: Cục sinh Mệnh (Tốt nhất)
        if (self::NGU_HANH_RELATIONS[$hanhCuc]['sinh'] === $hanhMenh) {
            $this->laSo['info']['cuc_menh_relation'] = 'Cục Sinh Bản Mệnh';
            return;
        }

        // Trường hợp 3: Mệnh sinh Cục (Phải nỗ lực)
        if (self::NGU_HANH_RELATIONS[$hanhMenh]['sinh'] === $hanhCuc) {
            $this->laSo['info']['cuc_menh_relation'] = 'Bản Mệnh Sinh Cục';
            return;
        }

        // Trường hợp 4: Cục khắc Mệnh (Xấu nhất)
        if (self::NGU_HANH_RELATIONS[$hanhCuc]['khac'] === $hanhMenh) {
            $this->laSo['info']['cuc_menh_relation'] = 'Cục Khắc Bản Mệnh';
            return;
        }

        // Trường hợp 5: Mệnh khắc Cục (Vượt khó)
        if (self::NGU_HANH_RELATIONS[$hanhMenh]['khac'] === $hanhCuc) {
            $this->laSo['info']['cuc_menh_relation'] = 'Bản Mệnh Khắc Cục';
            return;
        }

        // Trường hợp dự phòng
        $this->laSo['info']['cuc_menh_relation'] = 'Không xác định';
    }
    private function anTieuHan()
    {
        // === BƯỚC 1: LẤY DỮ LIỆU CẦN THIẾT ===
        $chiNamSinh = $this->input['lunar']['chi'];
        // ===================================================================
        $amDuongNamSinh = $this->laSo['info']['am_duong_nam_sinh'];
        $gioiTinh = $this->input['gioi_tinh'];

        // === BƯỚC 2: TÌM CUNG KHỞI ĐẦU (DỰA TRÊN TAM HỢP CHI NĂM SINH) ===
        $cungKhoiTieuHanIndex = -1;
        $chiNamSinhIndex = $this->getIndex(self::DIA_CHI, $chiNamSinh);

        if ($chiNamSinhIndex === false) {
            return; // Không tìm thấy chi năm sinh
        }

        // Xác định nhóm tam hợp
        $tamHopGroup = $chiNamSinhIndex % 4; // 0: Thân Tý Thìn, 1: Tỵ Dậu Sửu, 2: Dần Ngọ Tuất, 3: Hợi Mão Mùi

        switch ($tamHopGroup) {
            case 2: // Nhóm Dần, Ngọ, Tuất
                $cungKhoiTieuHanIndex = $this->getIndex(self::DIA_CHI, 'Thìn');
                break;
            case 0: // Nhóm Thân, Tý, Thìn
                $cungKhoiTieuHanIndex = $this->getIndex(self::DIA_CHI, 'Tuất');
                break;
            case 3: // Nhóm Hợi, Mão, Mùi
                $cungKhoiTieuHanIndex = $this->getIndex(self::DIA_CHI, 'Sửu');
                break;
            case 1: // Nhóm Tỵ, Dậu, Sửu
                $cungKhoiTieuHanIndex = $this->getIndex(self::DIA_CHI, 'Mùi');
                break;
        }

        if ($cungKhoiTieuHanIndex === -1) {
            return; // Lỗi logic
        }

        // === BƯỚC 3: XÁC ĐỊNH CHIỀU ĐẾM (THUẬN/NGHỊCH) ===
        $isThuan = ($gioiTinh == 'Nam') || ($gioiTinh == 'Nữ');

        // === BƯỚC 4: AN VÒNG TIỂU HẠN VÀO 12 CUNG ===
        // Vòng lặp từ 1 đến 12 để an đủ 1 vòng Giáp
        for ($i = 0; $i < 12; $i++) {
            $currentCungIndex = -1;

            if ($isThuan) {
                // Đếm thuận
                $currentCungIndex = ($cungKhoiTieuHanIndex + $i) % 12;
            } else {
                // Đếm nghịch, thêm 12*N để đảm bảo kết quả dương
                $currentCungIndex = ($cungKhoiTieuHanIndex - $i + 12 * ($i + 1)) % 12;
            }

            $chiCung = self::DIA_CHI[$currentCungIndex];

            // Chi của năm Tiểu Hạn cũng là một vòng lặp từ chi năm sinh
            $chiTieuHan = self::DIA_CHI[($chiNamSinhIndex + $i) % 12];

            // Gán chi của năm Tiểu Hạn vào cung tương ứng
            if (isset($this->laSo['palaces'][$chiCung])) {
                $this->laSo['palaces'][$chiCung]['vong_tuoi_chi'] = $chiTieuHan;
            }
        }
    }
    private function initializeLaSo()
    {
        $this->laSo = ['palaces' => [], 'info' => []];
        foreach (self::DIA_CHI as $chi) {
            $this->laSo['palaces'][$chi] = ['ten_cung' => $chi, 'dv_chuc_nang' => '', 'vong_tuoi_chi' => '', 'ln_chuc_nang' => '', 'thang_am' => '', 'can_chi_cung' => '', 'cung_chuc_nang' => '', 'dai_van' => '', 'chinh_tinh' => [], 'phu_tinh_cat' => [], 'phu_tinh_sat' => [], 'phu_tinh' => [], 'vong_trang_sinh' => '', 'special' => [], 'luu' => [], 'do_sang_sao' => []];
        }
    }

    /**
     * VIẾT LẠI HOÀN TOÀN: An Chính Tinh dựa trên 2 bảng tra cứu trong ảnh.
     */
    private function anChinhTinh()
    {
        // === Phần 1: An sao Tử Vi (Logic mới theo yêu cầu của bạn) ===
        if (!$this->cucSo || empty($this->input['lunar']['day'])) {
            throw new Exception('Không thể an sao Tử Vi: Thiếu Cục hoặc ngày sinh Âm lịch.');
        }
        $ngaySinh = $this->input['lunar']['day'];
        $cucSo = $this->cucSo;
        $danIndex = $this->getIndex(self::DIA_CHI, 'Dần');

        $viTriTuViIndex = 0;

        // Trường hợp 1: Ngày sinh chia hết cho Cục số (giữ nguyên)
        if ($ngaySinh % $cucSo == 0) {
            $thuongSo = $ngaySinh / $cucSo;
            $soBuoc = $thuongSo - 1;
            $viTriTuViIndex = ($danIndex + $soBuoc) % 12;
        }
        // Trường hợp 2: Ngày sinh KHÔNG chia hết (ÁP DỤNG LOGIC MỚI CỦA BẠN)
        else {
            // Bước A: Tìm "số mượn" và "thương số mới"
            $soMuon = 1;
            while (($ngaySinh + $soMuon) % $cucSo != 0) {
                $soMuon++;
            }
            $thuongSoMoi = ($ngaySinh + $soMuon) / $cucSo;
            $soBuocThuan = $thuongSoMoi - 1;

            // Bước B: Tìm Vị Trí Trung Gian bằng cách đi THUẬN từ Dần
            $viTriTrungGianIndex = ($danIndex + $soBuocThuan) % 12;

            // Bước C: Điều chỉnh từ Vị Trí Trung Gian
            // Dùng chính $soMuon làm số bước điều chỉnh
            $soBuocDieuChinh = $soMuon;

            if ($soMuon % 2 == 0) {
                // Nếu "số mượn" là chẵn, TIẾN $soBuocDieuChinh bước
                $viTriTuViIndex = ($viTriTrungGianIndex + $soBuocDieuChinh) % 12;
            } else {
                // Nếu "số mượn" là lẻ, LÙI $soBuocDieuChinh bước
                $viTriTuViIndex = ($viTriTrungGianIndex - $soBuocDieuChinh + (12 * $soBuocDieuChinh)) % 12; // Cộng 12*N để đảm bảo luôn dương
            }
        }

        // Gán sao Tử Vi vào cung đã tìm được
        $cungAnTuVi = self::DIA_CHI[$viTriTuViIndex];
        $this->addSaoToCung($cungAnTuVi, 'Tử Vi', 'chinh_tinh');
        $this->viTriTuVi = $cungAnTuVi;
        // === PHẦN MỚI: AN SAO THIÊN PHỦ ===
        // Khẩu quyết: Thiên Phủ đối xứng với Tử Vi qua trục Dần-Thân.
        // Công thức: index_Thiên_Phủ = (4 - index_Tử_Vi + 12) % 12

        // Sử dụng chỉ số của Tử Vi đã tính ở trên.
        $viTriThienPhuIndex = (4 - $viTriTuViIndex + 12) % 12;

        // Lấy tên cung và an sao Thiên Phủ.
        $cungAnThienPhu = self::DIA_CHI[$viTriThienPhuIndex];
        $this->addSaoToCung($cungAnThienPhu, 'Thiên Phủ', 'chinh_tinh');
        // === PHẦN MỚI: AN SAO THIÊN CƠ ===
        // Khẩu quyết: Từ Tử Vi, đếm nghịch 1 cung là Thiên Cơ.

        // Sử dụng lại chỉ số của Tử Vi đã tính.
        $viTriThienCoIndex = ($viTriTuViIndex - 1 + 12) % 12;

        // Lấy tên cung và an sao Thiên Cơ.
        $cungAnThienCo = self::DIA_CHI[$viTriThienCoIndex];
        $this->addSaoToCung($cungAnThienCo, 'Thiên Cơ', 'chinh_tinh');
        // === PHẦN MỚI: AN SAO THÁI DƯƠNG ===
        // Khẩu quyết: Từ Thiên Cơ, đếm nghịch, bỏ cách 1 cung (tức là -2).

        // Sử dụng chỉ số của Thiên Cơ đã tính.
        $viTriThaiDuongIndex = ($viTriThienCoIndex - 2 + 12) % 12;

        // Lấy tên cung và an sao Thái Dương.
        $cungAnThaiDuong = self::DIA_CHI[$viTriThaiDuongIndex];
        $this->addSaoToCung($cungAnThaiDuong, 'Thái Dương', 'chinh_tinh');

        // === PHẦN MỚI: AN SAO VŨ KHÚC ===
        // Khẩu quyết: Từ Thái Dương, đếm nghịch 1 cung là Vũ Khúc.

        // Sử dụng chỉ số của Thái Dương đã tính.
        $viTriVuKhucIndex = ($viTriThaiDuongIndex - 1 + 12) % 12;

        // Lấy tên cung và an sao Vũ Khúc.
        $cungAnVuKhuc = self::DIA_CHI[$viTriVuKhucIndex];
        $this->addSaoToCung($cungAnVuKhuc, 'Vũ Khúc', 'chinh_tinh');
        // Khẩu quyết: Từ Vũ Khúc, đếm nghịch 1 cung là Thiên Đồng.

        // Sử dụng chỉ số của Vũ Khúc đã tính.
        $viTriThienDongIndex = ($viTriVuKhucIndex - 1 + 12) % 12;

        // Lấy tên cung và an sao Thiên Đồng.
        $cungAnThienDong = self::DIA_CHI[$viTriThienDongIndex];
        $this->addSaoToCung($cungAnThienDong, 'Thiên Đồng', 'chinh_tinh');
        // Khẩu quyết: Từ Thiên Đồng, đếm nghịch, bỏ cách 2 cung (tức là -3).

        // Sử dụng chỉ số của Thiên Đồng đã tính.
        $viTriLiemTrinhIndex = ($viTriThienDongIndex - 3 + 12) % 12;

        // Lấy tên cung và an sao Liêm Trinh.
        $cungAnLiemTrinh = self::DIA_CHI[$viTriLiemTrinhIndex];
        $this->addSaoToCung($cungAnLiemTrinh, 'Liêm Trinh', 'chinh_tinh');
        // Khẩu quyết: Từ Thiên Phủ, đếm thuận 1 cung là Thái Âm.

        // Ta cần lấy lại chỉ số của Thiên Phủ đã tính ở đầu hàm.
        // (Đảm bảo biến $viTriThienPhuIndex vẫn có sẵn)
        $viTriThaiAmIndex = ($viTriThienPhuIndex + 1) % 12;

        // Lấy tên cung và an sao Thái Âm.
        $cungAnThaiAm = self::DIA_CHI[$viTriThaiAmIndex];
        $this->addSaoToCung($cungAnThaiAm, 'Thái Âm', 'chinh_tinh');
        // Khẩu quyết: Từ Thái Âm, đếm thuận 1 cung là Tham Lang.

        // Sử dụng chỉ số của Thái Âm đã tính.
        $viTriThamLangIndex = ($viTriThaiAmIndex + 1) % 12;

        // Lấy tên cung và an sao Tham Lang.
        $cungAnThamLang = self::DIA_CHI[$viTriThamLangIndex];
        $this->addSaoToCung($cungAnThamLang, 'Tham Lang', 'chinh_tinh');
        // Khẩu quyết: Từ Tham Lang, đếm thuận 1 cung là Cự Môn.

        // Sử dụng chỉ số của Tham Lang đã tính.
        $viTriCuMonIndex = ($viTriThamLangIndex + 1) % 12;

        // Lấy tên cung và an sao Cự Môn.
        $cungAnCuMon = self::DIA_CHI[$viTriCuMonIndex];
        $this->addSaoToCung($cungAnCuMon, 'Cự Môn', 'chinh_tinh');
        // Khẩu quyết: Từ Cự Môn, đếm thuận 1 cung là Thiên Tướng.

        // Sử dụng chỉ số của Cự Môn đã tính.
        $viTriThienTuongIndex = ($viTriCuMonIndex + 1) % 12;

        // Lấy tên cung và an sao Thiên Tướng.
        $cungAnThienTuong = self::DIA_CHI[$viTriThienTuongIndex];
        $this->addSaoToCung($cungAnThienTuong, 'Thiên Tướng', 'chinh_tinh');
        // Khẩu quyết: Từ Thiên Tướng, đếm thuận 1 cung là Thiên Lương.

        // Sử dụng chỉ số của Thiên Tướng đã tính.
        $viTriThienLuongIndex = ($viTriThienTuongIndex + 1) % 12;

        // Lấy tên cung và an sao Thiên Lương.
        $cungAnThienLuong = self::DIA_CHI[$viTriThienLuongIndex];
        $this->addSaoToCung($cungAnThienLuong, 'Thiên Lương', 'chinh_tinh');
        // Khẩu quyết: Từ Thiên Lương, đếm thuận 1 cung là Thất Sát.

        // Sử dụng chỉ số của Thiên Lương đã tính.
        $viTriThatSatIndex = ($viTriThienLuongIndex + 1) % 12;

        // Lấy tên cung và an sao Thất Sát.
        $cungAnThatSat = self::DIA_CHI[$viTriThatSatIndex];
        $this->addSaoToCung($cungAnThatSat, 'Thất Sát', 'chinh_tinh');
        // Khẩu quyết: Từ Thất Sát, đếm thuận, bỏ qua 3 cung (tức là +4).

        // Sử dụng chỉ số của Thất Sát đã tính.
        $viTriPhaQuanIndex = ($viTriThatSatIndex + 4) % 12;

        // Lấy tên cung và an sao Phá Quân.
        $cungAnPhaQuan = self::DIA_CHI[$viTriPhaQuanIndex];
        $this->addSaoToCung($cungAnPhaQuan, 'Phá Quân', 'chinh_tinh');
    }
    /**
     * SỬA LẠI: Sửa lỗi logic map Hành và Cục.
     */
    private function tinhCuc()
    {
        if (!$this->cungMenhViTri || empty($this->laSo['palaces'][$this->cungMenhViTri]['can_chi_cung'])) {
            $this->laSo['info']['cuc'] = 'Thiếu Cung Mệnh hoặc Can Chi Cung';
            return;
        }
        $canChiCungMenhStr = $this->laSo['palaces'][$this->cungMenhViTri]['can_chi_cung'];
        $hoaGiapMenh = str_replace('.', ' ', trim($canChiCungMenhStr));
        // $menhData = config('tuvi_data.nap_am.' . $hoaGiapMenh);
        $menhData = $this->data_nap_am[$hoaGiapMenh] ?? null;
        if (!is_array($menhData) || empty($menhData['hanh'])) {
            $this->laSo['info']['cuc'] = 'Lỗi tra cứu Nạp Âm hoặc thiếu hành';
            return;
        }
        $hanhCuc = $menhData['hanh'];
        $cucMap = ['Thủy' => 2, 'Mộc'  => 3, 'Kim'  => 4, 'Thổ'  => 5, 'Hỏa'  => 6,];
        $cucTextMap = [2 => 'Thủy Nhị Cục', 3 => 'Mộc Tam Cục', 4 => 'Kim Tứ Cục', 5 => 'Thổ Ngũ Cục', 6 => 'Hỏa Lục Cục',];

        // ===================================================================
        // XÓA 2 DÒNG TÍNH CAN/CHI Ở ĐÂY ĐI
        // $this->input['lunar']['year_can'] = CustomLunarConverterHelper::getCan($this->input['lunar']['year']);
        // $this->input['lunar']['year_chi'] = CustomLunarConverterHelper::getChi($this->input['lunar']['year']);

        // SỬA LẠI ĐỂ DÙNG KEY 'can' ĐÃ ĐƯỢC TÍNH TỪ TRƯỚC
        $canNamSinh = $this->input['lunar']['can'];
        // ===================================================================

        $canIndex = $this->getIndex(self::THIEN_CAN, $canNamSinh);

        if ($canIndex !== false) {
            $this->laSo['info']['am_duong_nam_sinh'] = ($canIndex % 2 === 0) ? 'Dương' : 'Âm';
        } else {
            $this->laSo['info']['am_duong_nam_sinh'] = '';
        }
        $this->cucSo = $cucMap[$hanhCuc] ?? null;
        if ($this->cucSo) {
            $this->laSo['info']['cuc'] = $cucTextMap[$this->cucSo];
            $this->laSo['info']['hanh_cuc'] = $hanhCuc;
        } else {
            $this->laSo['info']['cuc'] = 'Không xác định được Cục';
        }
    }


    private function anTuHoa()
    {
        // Lấy Can của năm sinh
        $canNam = $this->input['lunar']['can'];
        if (!$canNam) return;

        // Mảng tra cứu cho Tứ Hóa
        $tuHoaConfig = [
            'Giáp' => ['Lộc' => 'Liêm Trinh', 'Quyền' => 'Phá Quân', 'Khoa' => 'Vũ Khúc', 'Kỵ' => 'Thái Dương'],
            'Ất'   => ['Lộc' => 'Thiên Cơ',  'Quyền' => 'Thiên Lương', 'Khoa' => 'Tử Vi',    'Kỵ' => 'Thái Âm'],
            'Bính' => ['Lộc' => 'Thiên Đồng', 'Quyền' => 'Thiên Cơ',  'Khoa' => 'Văn Xương', 'Kỵ' => 'Liêm Trinh'],
            'Đinh' => ['Lộc' => 'Thái Âm',   'Quyền' => 'Thiên Đồng', 'Khoa' => 'Thiên Cơ',  'Kỵ' => 'Cự Môn'],
            'Mậu'  => ['Lộc' => 'Tham Lang', 'Quyền' => 'Thái Âm',   'Khoa' => 'Hữu Bật',  'Kỵ' => 'Thiên Cơ'],
            'Kỷ'   => ['Lộc' => 'Vũ Khúc',  'Quyền' => 'Tham Lang', 'Khoa' => 'Thiên Lương', 'Kỵ' => 'Văn Khúc'],
            'Canh' => ['Lộc' => 'Thái Dương', 'Quyền' => 'Vũ Khúc',  'Khoa' => 'Thái Âm', 'Kỵ' => 'Thiên Đồng'],
            'Tân'  => ['Lộc' => 'Cự Môn',   'Quyền' => 'Thái Dương', 'Khoa' => 'Văn Khúc',  'Kỵ' => 'Văn Xương'],
            'Nhâm' => ['Lộc' => 'Thiên Lương', 'Quyền' => 'Tử Vi',    'Khoa' => 'Tả Phụ', 'Kỵ' => 'Vũ Khúc'],
            'Quý'  => ['Lộc' => 'Phá Quân', 'Quyền' => 'Cự Môn',   'Khoa' => 'Thái Âm',   'Kỵ' => 'Tham Lang'],
        ];

        // Lấy cấu hình cho Can Năm hiện tại
        $currentConfig = $tuHoaConfig[$canNam] ?? null;
        if (!$currentConfig) return;

        // Duyệt qua Lộc, Quyền, Khoa, Kỵ và an vào lá số
        foreach ($currentConfig as $hoaType => $saoName) {
            // Tìm vị trí của sao gốc (ví dụ: tìm cung có sao Thái Dương)
            $viTriSaoGoc = $this->findSao($saoName);

            if ($viTriSaoGoc) {
                // Tạo tên sao Hóa (ví dụ: "Hóa Lộc", "Hóa Kỵ")
                $saoHoaName = 'Hóa ' . $hoaType;

                // An sao Hóa vào cùng cung với sao gốc.
                // Dùng loại 'special' để có màu khác biệt.
                $this->addSaoToCung($viTriSaoGoc, $saoHoaName, 'special');
            }
        }
    }
    // --- CÁC HÀM CÒN LẠI GIỮ NGUYÊN (ĐÃ RÚT GỌN ĐỂ DỄ NHÌN) ---
    private function anCungChucNang()
    {
        if (!$this->cungMenhViTri) return;

        $menhCungIndex = $this->getIndex(self::DIA_CHI, $this->cungMenhViTri);
        $isThuan = in_array($this->amDuong, ['Dương Nam', 'Âm Nữ']);

        for ($i = 0; $i < 12; $i++) {
            // Vị trí chức năng = xoay mảng theo hướng
            $funcIndex = $isThuan ? $i : (12 - $i) % 12;

            // Vị trí cung thực tế
            $cungIndex = ($menhCungIndex + $i) % 12;

            $this->laSo['palaces'][self::DIA_CHI[$cungIndex]]['cung_chuc_nang'] = self::CUNG_CHUC_NANG[$i];
        }

        if ($this->cungThanViTri) {
            $this->laSo['palaces'][$this->cungThanViTri]['cung_chuc_nang'] .= ' (THÂN)';
        }
    }

    private function anPhuTinh()
    {
        $canNam = $this->input['lunar']['can'];
        $chiNam = $this->input['lunar']['chi'];
        $thang = $this->input['lunar']['month'];
        $ngay = $this->input['lunar']['day'];
        $gioChi = $this->input['lunar']['hour_chi'];
        $chiNamIndex = $this->getIndex(self::DIA_CHI, $chiNam);
        $gioIndex = $this->getIndex(self::DIA_CHI, $gioChi);
        $isThuan = in_array($this->amDuong, ['Dương Nam', 'Âm Nữ']);

        $thinIndex = $this->getIndex(self::DIA_CHI, 'Thìn');
        if ($canNam) {
            // $locTonViTri = config("tuvi_data.loc_ton.{$canNam}");
            $locTonViTri = $this->data_loc_ton[$canNam] ?? null;
            if ($locTonViTri) {
                $this->addSaoToCung($locTonViTri, 'Lộc Tồn', 'phu_tinh_cat');
                $locTonIndex = $this->getIndex(self::DIA_CHI, $locTonViTri);
                $this->addSaoToCung(self::DIA_CHI[($locTonIndex + 1) % 12], 'Kình Dương', 'phu_tinh_sat');
                $this->addSaoToCung(self::DIA_CHI[($locTonIndex - 1 + 12) % 12], 'Đà La', 'phu_tinh_sat');
                if ($isThuan) {
                    // Đếm thuận
                    $viTriVawnTinhIndex = ($locTonIndex + 3) % 12;
                    $tuongPhuIndex = ($viTriVawnTinhIndex + 2) % 12;
                    $quocAnIndex = ($tuongPhuIndex + 3) % 12;
                } else {
                    // Đếm ngược
                    $viTriVawnTinhIndex = ($locTonIndex - 3 + 12) % 12;
                    $tuongPhuIndex = ($viTriVawnTinhIndex - 2 + 12) % 12;
                    $quocAnIndex = ($tuongPhuIndex - 3 + 12) % 12;
                }

                // $cungAnTuongPhu = self::DIA_CHI[$tuongPhuIndex];
                // $this->addSaoToCung($cungAnTuongPhu, 'Đường Phù', 'phu_tinh_cat');
                // $cungAnQuocAn = self::DIA_CHI[$quocAnIndex];
                // $this->addSaoToCung($cungAnQuocAn, 'Quốc Ấn', 'phu_tinh_cat');
                // Lấy tên cung và an sao. Giả định đây là một cát tinh.
                $cungAnVawnTinh = self::DIA_CHI[$viTriVawnTinhIndex];
                $this->addSaoToCung($cungAnVawnTinh, 'L.N.Văn Tinh', 'phu_tinh_cat');


                $viTriQuocAn = null;
                switch ($canNam) {
                    case 'Giáp':
                        $viTriQuocAn = 'Tuất';
                        break;
                    case 'Ất':
                        $viTriQuocAn = 'Hợi';
                        break;
                    case 'Bính':
                    case 'Mậu':
                        $viTriQuocAn = 'Sửu';
                        break;
                    case 'Đinh':
                    case 'Kỷ':
                        $viTriQuocAn = 'Dần';
                        break;
                    case 'Canh':
                        $viTriQuocAn = 'Thìn';
                        break;
                    case 'Tân':
                        $viTriQuocAn = 'Tỵ';
                        break;
                    case 'Nhâm':
                        $viTriQuocAn = 'Mùi';
                        break;
                    case 'Quý':
                        $viTriQuocAn = 'Thân';
                        break;
                }
                if ($viTriQuocAn) {
                    $this->addSaoToCung($viTriQuocAn, 'Quốc Ấn', 'phu_tinh_cat');
                }
                $viTriDuongPhu = null;
                switch ($canNam) {
                    case 'Giáp':
                        $viTriDuongPhu = 'Mùi';
                        break;
                    case 'Ất':
                        $viTriDuongPhu = 'Thân';
                        break;
                    case 'Bính':
                    case 'Mậu':
                        $viTriDuongPhu = 'Tuất';
                        break;
                    case 'Đinh':
                    case 'Kỷ':
                        $viTriDuongPhu = 'Hợi';
                        break;
                    case 'Canh':
                        $viTriDuongPhu = 'Sửu';
                        break;
                    case 'Tân':
                        $viTriDuongPhu = 'Dần';
                        break;
                    case 'Nhâm':
                        $viTriDuongPhu = 'Thìn';
                        break;
                    case 'Quý':
                        $viTriDuongPhu = 'Tỵ';
                        break;
                }
                if ($viTriDuongPhu) {
                    $this->addSaoToCung($viTriDuongPhu, 'Đường Phù', 'phu_tinh_cat');
                }

                $vongBacSiData = [
                    ['name' => 'Bác Sĩ',     'type' => 'phu_tinh_cat'],
                    ['name' => 'Lực Sỹ',     'type' => 'phu_tinh_cat'],
                    ['name' => 'Thanh Long',  'type' => 'phu_tinh_cat'],
                    ['name' => 'Tiểu Hao',    'type' => 'phu_tinh_sat'], // Bại tinh
                    ['name' => 'Tướng Quân',  'type' => 'phu_tinh_sat'],
                    ['name' => 'Tấu Thư',     'type' => 'phu_tinh_cat'],
                    ['name' => 'Phi Liêm',    'type' => 'phu_tinh_sat'],
                    ['name' => 'Hỷ Thần',     'type' => 'phu_tinh_cat'],
                    ['name' => 'Bệnh Phù',    'type' => 'phu_tinh_sat'], // Sát tinh
                    ['name' => 'Đại Hao',     'type' => 'phu_tinh_sat'], // Bại tinh
                    ['name' => 'Phục Binh',   'type' => 'phu_tinh_sat'], // Sát tinh/Ám tinh
                    ['name' => 'Quan Phủ',    'type' => 'phu_tinh_sat'], // Sát tinh
                ];
                for ($i = 0; $i < 12; $i++) {
                    // Xác định vị trí cung cần an sao
                    $offset = $isThuan ? $i : -$i;
                    $cungIndex = ($locTonIndex + $offset + 12) % 12;
                    $cungAnSao = self::DIA_CHI[$cungIndex];

                    // Lấy thông tin sao từ mảng dữ liệu đã cấu trúc
                    $sao = $vongBacSiData[$i];

                    // An sao vào cung với đúng tên và đúng loại
                    $this->addSaoToCung($cungAnSao, $sao['name'], $sao['type']);
                }
            }
            $viTriKhoi = null;
            $viTriViet = null;
            $viTriLuuHa = null;

            switch ($canNam) {
                case 'Giáp':
                case 'Mậu':
                    $viTriKhoi = 'Sửu';
                    $viTriViet = 'Mùi';
                    break;
                case 'Ất':
                case 'Kỷ': // Sửa lại từ "Sửu" thành "Kỷ" cho đúng quy tắc
                    $viTriKhoi = 'Tý';
                    $viTriViet = 'Thân';
                    break;
                case 'Bính':
                case 'Đinh':
                    $viTriKhoi = 'Hợi';
                    $viTriViet = 'Dậu';
                    break;
                case 'Canh':
                case 'Tân':
                    $viTriKhoi = 'Ngọ';
                    $viTriViet = 'Dần';
                    break;
                case 'Nhâm':
                case 'Quý':
                    $viTriKhoi = 'Mão';
                    $viTriViet = 'Tỵ';
                    break;
            }

            if ($viTriKhoi && $viTriViet) {
                $this->addSaoToCung($viTriKhoi, 'Thiên Khôi', 'phu_tinh_cat');
                $this->addSaoToCung($viTriViet, 'Thiên Việt', 'phu_tinh_cat');
            }
            switch ($canNam) {
                case 'Giáp':
                    $viTriLuuHa = 'Dậu';
                    break;
                case 'Ất':
                    $viTriLuuHa = 'Tuất';
                    break;
                case 'Bính':
                    $viTriLuuHa = 'Mùi';
                    break;
                case 'Đinh':
                    $viTriLuuHa = 'Thân';
                    break;
                case 'Mậu':
                    $viTriLuuHa = 'Tỵ';
                    break;
                case 'Kỷ':
                    $viTriLuuHa = 'Ngọ';
                    break;
                case 'Canh':
                    $viTriLuuHa = 'Mão';
                    break;
                case 'Tân':
                    $viTriLuuHa = 'Thìn';
                    break;
                case 'Nhâm':
                    $viTriLuuHa = 'Hợi';
                    break;
                case 'Quý':
                    $viTriLuuHa = 'Dần';
                    break;
            }
            if ($viTriLuuHa) {
                $this->addSaoToCung($viTriLuuHa, 'Lưu Hà', 'phu_tinh_sat');
            }

            // $this->addSaoToCung(config("tuvi_data.thien_tru.{$canNam}"), 'Thiên Trù', 'phu_tinh_cat');
            $thienTruViTri = $this->data_thien_tru[$canNam] ?? null;
            if ($thienTruViTri) {
                $this->addSaoToCung($thienTruViTri, 'Thiên Trù', 'phu_tinh_cat');
            }
            // if ($qp = config("tuvi_data.thien_quan_phuc.{$canNam}")) {
            //     $this->addSaoToCung($qp['ThienQuan'], 'Thiên Quan', 'phu_tinh_cat');
            //     $this->addSaoToCung($qp['ThienPhuc'], 'Thiên Phúc', 'phu_tinh_cat');
            // }
            $qp = $this->data_thien_quan_phuc[$canNam] ?? null;
            if ($qp) {
                $this->addSaoToCung($qp['ThienQuan'], 'Thiên Quan', 'phu_tinh_cat');
                $this->addSaoToCung($qp['ThienPhuc'], 'Thiên Phúc', 'phu_tinh_cat');
            }
            // if ($tuHoa = config("tuvi_data.tu_hoa.{$canNam}")) {
            //     foreach ($tuHoa as $type => $sao) {
            //         if ($vt = $this->findSao($sao)) {
            //             $this->addSaoToCung($vt, $type, 'special');
            //         }
            //     }
            // }
        }
        if ($chiNamIndex !== false) {
            $vongThaiTue = [
                ['name' => 'Thái Tuế', 'type' => 'phu_tinh_sat'],
                ['name' => 'Thiếu Dương', 'type' => 'phu_tinh_cat'],
                ['name' => 'Tang Môn', 'type' => 'phu_tinh_sat'],
                ['name' => 'Thiếu Âm', 'type' => 'phu_tinh_cat'],
                ['name' => 'Quan Phù', 'type' => 'phu_tinh_sat'],
                ['name' => 'Tử Phù', 'type' => 'phu_tinh_sat'],
                ['name' => 'Tuế Phá', 'type' => 'phu_tinh_sat'],
                ['name' => 'Long Đức', 'type' => 'phu_tinh_cat'],
                ['name' => 'Bạch Hổ', 'type' => 'phu_tinh_sat'],
                ['name' => 'Phúc Đức', 'type' => 'phu_tinh_cat'],
                ['name' => 'Điếu Khách', 'type' => 'phu_tinh_sat'],
                ['name' => 'Trực Phù', 'type' => 'phu_tinh_sat'],

            ];
            for ($i = 0; $i < 12; $i++) {
                // Xác định cung cần an sao
                $cungIndex = ($chiNamIndex + $i) % 12;
                $cungAnSao = self::DIA_CHI[$cungIndex];

                // Lấy thông tin của sao hiện tại từ mảng
                $saoData = $vongThaiTue[$i];

                // An sao trong vòng Thái Tuế vào cung
                $this->addSaoToCung($cungAnSao, $saoData['name'], $saoData['type']);

                // QUY TẮC MỚI: Nếu sao vừa an là Thiếu Dương, an Thiên Không vào cùng cung
                if ($saoData['name'] === 'Thiếu Dương') {
                    $this->addSaoToCung($cungAnSao, 'Thiên Không', 'phu_tinh_sat');
                }
                if ($saoData['name'] === 'Phúc Đức') {
                    $this->addSaoToCung($cungAnSao, 'Thiên Đức', 'phu_tinh_cat');
                }
            }
            // --- BẮT ĐẦU CODE MỚI ĐỂ AN SAO LONG TRÌ ---
            // Quy tắc: Từ cung Thìn, coi là Tý, đếm thuận đến năm sinh để an Long Trì.
            // Công thức: Vị trí = (index của Thìn + index của Năm Sinh)
            $thinIndex = $this->getIndex(self::DIA_CHI, 'Thìn');
            if ($thinIndex !== false) {
                $longTriIndex = ($thinIndex + $chiNamIndex) % 12;
                $cungAnLongTri = self::DIA_CHI[$longTriIndex];
                $this->addSaoToCung($cungAnLongTri, 'Long Trì', 'phu_tinh_cat');
            }
            // --- KẾT THÚC CODE MỚI ---

            // --- BẮT ĐẦU CODE MỚI ĐỂ AN SAO PHƯỢNG CÁC VÀ GIẢI THẦN ---
            // Quy tắc: Từ cung Tuất, đếm nghịch đến năm sinh để an Phượng Các và Giải Thần.
            // Công thức: Vị trí = (index của Tuất - index của Năm Sinh)
            $tuatIndex = $this->getIndex(self::DIA_CHI, 'Tuất');
            if ($tuatIndex !== false) {
                $phuongCacGiaiThanIndex = ($tuatIndex - $chiNamIndex + 12) % 12;
                $cungAnSao = self::DIA_CHI[$phuongCacGiaiThanIndex];

                // An cả hai sao vào cùng một cung
                $this->addSaoToCung($cungAnSao, 'Phượng Các', 'phu_tinh_cat');
                $this->addSaoToCung($cungAnSao, 'Giải Thần', 'phu_tinh_cat');
            }
            // --- BẮT ĐẦU CODE MỚI ĐỂ AN SAO NGUYỆT ĐỨC ---
            // Quy tắc: Từ cung Tỵ, coi là Tý, đếm thuận đến năm sinh để an Nguyệt Đức.
            // Công thức: Vị trí = (index của Tỵ + index của Năm Sinh)
            $tyIndex = $this->getIndex(self::DIA_CHI, 'Tỵ');
            if ($tyIndex !== false) {
                $nguyetDucIndex = ($tyIndex + $chiNamIndex) % 12;
                $cungAnNguyetDuc = self::DIA_CHI[$nguyetDucIndex];
                $this->addSaoToCung($cungAnNguyetDuc, 'Nguyệt Đức', 'phu_tinh_cat');
            }
            // --- KẾT THÚC CODE MỚI ---

            // --- BẮT ĐẦU CODE MỚI ĐỂ AN SAO THIÊN KHỐC VÀ THIÊN HƯ ---
            // Lấy chỉ số của cung Ngọ làm mốc
            $ngoIndex = $this->getIndex(self::DIA_CHI, 'Ngọ');
            if ($ngoIndex !== false) {
                // An Thiên Khốc: Từ Ngọ, đếm nghịch đến năm sinh
                $thienKhocIndex = ($ngoIndex - $chiNamIndex + 12) % 12;
                $cungAnKhoc = self::DIA_CHI[$thienKhocIndex];
                $this->addSaoToCung($cungAnKhoc, 'Thiên Khốc', 'phu_tinh_sat');

                // An Thiên Hư: Từ Ngọ, đếm thuận đến năm sinh
                $thienHuIndex = ($ngoIndex + $chiNamIndex) % 12;
                $cungAnHu = self::DIA_CHI[$thienHuIndex];
                $this->addSaoToCung($cungAnHu, 'Thiên Hư', 'phu_tinh_sat');
            }
            // --- KẾT THÚC CODE MỚI ---
            if ($groupKey = $this->getChiGroupKey($chiNam)) {
                // $saoData = config("tuvi_data.theo_chi_nam.{$groupKey}");
                $saoData = $this->data_theo_chi_nam[$groupKey] ?? null;
                // Quy tắc: An tại cung kế tiếp của chi đầu tam hợp tuổi.
                $viTriDaoHoa = null;
                switch ($groupKey) {
                    case 'DanNgoTuat': // Chi đầu là Dần, cung kế tiếp là Mão
                        $viTriDaoHoa = 'Mão';
                        break;
                    case 'ThanTyThin': // Chi đầu là Thân, cung kế tiếp là Dậu
                        $viTriDaoHoa = 'Dậu';
                        break;
                    case 'TiDauSuu':  // Chi đầu là Tỵ, cung kế tiếp là Ngọ
                        $viTriDaoHoa = 'Ngọ';
                        break;
                    case 'HoiMaoMui':  // Chi đầu là Hợi, cung kế tiếp là Tý
                        $viTriDaoHoa = 'Tý';
                        break;
                }
                if ($viTriDaoHoa) {
                    $this->addSaoToCung($viTriDaoHoa, 'Đào Hoa', 'phu_tinh_cat');
                }
                // --- KẾT THÚC CODE MỚI ---

                // --- BẮT ĐẦU CODE MỚI ĐỂ AN SAO THIÊN MÃ THEO QUY TẮC ---
                // Quy tắc: Lấy chi đầu của tam hợp tuổi, xung chiếu sang để an Thiên Mã.
                $viTriThienMa = null;
                switch ($groupKey) {
                    case 'DanNgoTuat': // Nhóm Dần-Ngọ-Tuất có chi đầu là Dần, xung chiếu là Thân
                        $viTriThienMa = 'Thân';
                        break;
                    case 'ThanTyThin': // Nhóm Thân-Tý-Thìn có chi đầu là Thân, xung chiếu là Dần
                        $viTriThienMa = 'Dần';
                        break;
                    case 'TiDauSuu':  // Nhóm Tỵ-Dậu-Sửu có chi đầu là Tỵ, xung chiếu là Hợi
                        $viTriThienMa = 'Hợi';
                        break;
                    case 'HoiMaoMui':  // Nhóm Hợi-Mão-Mùi có chi đầu là Hợi, xung chiếu là Tỵ
                        $viTriThienMa = 'Tỵ';
                        break;
                }
                if ($viTriThienMa) {
                    $this->addSaoToCung($viTriThienMa, 'Thiên Mã', 'phu_tinh_cat');
                }
                // --- KẾT THÚC CODE MỚI ---
                // --- BẮT ĐẦU CODE MỚI ĐỂ AN SAO HOA CÁI ---
                // Quy tắc: Từ Thiên Mã, đếm thuận, bỏ qua 1 cung (tức là +2) để an Hoa Cái.
                $thienMaIndex = $this->getIndex(self::DIA_CHI, $viTriThienMa);
                if ($thienMaIndex !== false) {
                    $hoaCaiIndex = ($thienMaIndex + 2) % 12;
                    $cungAnHoaCai = self::DIA_CHI[$hoaCaiIndex];
                    $this->addSaoToCung($cungAnHoaCai, 'Hoa Cái', 'phu_tinh_cat');
                    // --- BẮT ĐẦU CODE MỚI ĐỂ AN SAO KIẾP SÁT ---
                    // Quy tắc: Từ Hoa Cái, đến cung kế tiếp (+1) để an Kiếp Sát
                    $kiepSatIndex = ($hoaCaiIndex + 1) % 12;
                    $cungAnKiepSat = self::DIA_CHI[$kiepSatIndex];
                    $this->addSaoToCung($cungAnKiepSat, 'Kiếp Sát', 'phu_tinh_sat');
                }
                // --- KẾT THÚC CODE MỚI ---


                // --- KẾT THÚC CODE MỚI ---

            }


            // --- BẮT ĐẦU CODE MỚI ĐỂ AN SAO HỒNG LOAN VÀ THIÊN HỶ ---
            // Quy tắc: Từ Mão, đếm nghịch đến năm sinh để an Hồng Loan. Thiên Hỷ xung chiếu.
            $maoIndex = $this->getIndex(self::DIA_CHI, 'Mão');
            if ($maoIndex !== false) {
                // An Hồng Loan
                $hongLoanIndex = ($maoIndex - $chiNamIndex + 12) % 12;
                $cungAnHongLoan = self::DIA_CHI[$hongLoanIndex];
                $this->addSaoToCung($cungAnHongLoan, 'Hồng Loan', 'phu_tinh_cat');
                $thienHyIndex = ($hongLoanIndex + 6) % 12;
                $cungAnThienHy = self::DIA_CHI[$thienHyIndex];
                $this->addSaoToCung($cungAnThienHy, 'Thiên Hỷ', 'phu_tinh_cat');
            }
            $viTriCoThan = null;
            $viTriPhaToai = null;
            switch ($chiNam) {
                case 'Hợi':
                case 'Tý':
                case 'Sửu':
                    $viTriCoThan = 'Dần';
                    break;
                case 'Dần':
                case 'Mão':
                case 'Thìn':
                    $viTriCoThan = 'Tỵ';
                    break;
                case 'Tỵ':
                case 'Ngọ':
                case 'Mùi':
                    $viTriCoThan = 'Thân';
                    break;
                case 'Thân':
                case 'Dậu':
                case 'Tuất':
                    $viTriCoThan = 'Hợi';
                    break;
            }

            if ($viTriCoThan) {
                $this->addSaoToCung($viTriCoThan, 'Cô Thần', 'phu_tinh_sat');
                // --- BẮT ĐẦU CODE MỚI ĐỂ AN SAO QUẢ TÚ ---
                // Quy tắc: Từ Cô Thần, đếm ngược về 4 cung để an Quả Tú.
                $coThanIndex = $this->getIndex(self::DIA_CHI, $viTriCoThan);
                if ($coThanIndex !== false) {
                    $quaTuIndex = ($coThanIndex - 4 + 12) % 12;
                    $cungAnQuaTu = self::DIA_CHI[$quaTuIndex];
                    $this->addSaoToCung($cungAnQuaTu, 'Quả Tú', 'phu_tinh_sat');
                }
                // --- KẾT THÚC CODE MỚI ---
            }
            switch ($chiNam) {
                case 'Dần':
                case 'Thân':
                case 'Tỵ':
                case 'Hợi':
                    $viTriPhaToai = 'Dậu';
                    break;
                case 'Tý':
                case 'Ngọ':
                case 'Mão':
                case 'Dậu':
                    $viTriPhaToai = 'Tỵ';
                    break;
                case 'Thìn':
                case 'Tuất':
                case 'Sửu':
                case 'Mùi':
                    $viTriPhaToai = 'Sửu';
                    break;
            }
            if ($viTriPhaToai) {
                $this->addSaoToCung($viTriPhaToai, 'Phá Toái', 'phu_tinh_sat');
            }
            if ($this->cungMenhViTri) {
                $menhCungIndex = $this->getIndex(self::DIA_CHI, $this->cungMenhViTri);

                // Công thức: (index Mệnh + index Năm Sinh) % 12
                $thienTaiIndex = ($menhCungIndex + $chiNamIndex) % 12;
                $cungAnThienTai = self::DIA_CHI[$thienTaiIndex];

                // An sao Thiên Tài (một cát tinh)
                $this->addSaoToCung($cungAnThienTai, 'Thiên Tài', 'phu_tinh_cat');
            }
            if ($this->cungThanViTri) {
                $thanCungIndex = $this->getIndex(self::DIA_CHI, $this->cungThanViTri);

                // Công thức: (index Thân + index Năm Sinh) % 12
                $thienTaiIndex = ($thanCungIndex + $chiNamIndex) % 12;
                $cungAnThienTho = self::DIA_CHI[$thienTaiIndex];

                // An sao Thiên Tài (một cát tinh)
                $this->addSaoToCung($cungAnThienTho, 'Thiên Thọ', 'phu_tinh_cat');
            }
            if ($thang && $gioIndex !== false) {
                // Bước 1: Tìm cung khởi điểm (Cung X)
                // Công thức: (Index Năm Sinh - (Tháng Sinh - 1))
                $khoiDiemIndex = ($chiNamIndex - ($thang - 1));

                // Bước 2: Tìm cung an Đẩu Quân
                // Công thức: (Index Khởi Điểm + Index Giờ Sinh)
                $dauQuanIndex = ($khoiDiemIndex + $gioIndex);

                // Xử lý quay vòng cho lá số 12 cung (đảm bảo kết quả từ 0-11)
                // Dùng ($dauQuanIndex % 12 + 12) % 12 để xử lý cả số âm và số dương
                $finalDauQuanIndex = ($dauQuanIndex % 12 + 12) % 12;

                // Lấy tên cung và an sao
                $cungAnDauQuan = self::DIA_CHI[$finalDauQuanIndex];
                $this->addSaoToCung($cungAnDauQuan, 'Đẩu Quân', 'phu_tinh_sat');
            }
        }
        if ($thang) {
            $dauIndex = $this->getIndex(self::DIA_CHI, 'Dậu');
            if ($dauIndex !== false) {
                // Công thức: (index của Dậu + tháng sinh) % 12
                $thienHinhIndex = ($dauIndex + ($thang - 1)) % 12;
                $cungAnThienHinh = self::DIA_CHI[$thienHinhIndex];

                // An sao Thiên Hình (một sát tinh)
                $this->addSaoToCung($cungAnThienHinh, 'Thiên Hình', 'phu_tinh_sat');
            }
            $suuIndex = $this->getIndex(self::DIA_CHI, 'Sửu');
            if ($suuIndex !== false) {
                // Công thức: (index của Sửu + (tháng sinh - 1)) % 12
                $thienYDieuIndex = ($suuIndex + ($thang - 1)) % 12;
                $cungAnSao = self::DIA_CHI[$thienYDieuIndex];

                // An cả hai sao vào cùng một cung với đúng loại
                $this->addSaoToCung($cungAnSao, 'Thiên Y', 'phu_tinh_cat');
                $this->addSaoToCung($cungAnSao, 'Thiên Diêu', 'phu_tinh_sat');
            }
            $than_Index = $this->getIndex(self::DIA_CHI, 'Thân');
            if ($than_Index !== false) {
                // Công thức: (index của Thân + (tháng sinh - 1)) % 12
                $thienKhongIndex = ($than_Index + ($thang - 1)) % 12;
                $cungAnThienKhong = self::DIA_CHI[$thienKhongIndex];

                // An sao Thiên Không (một sát tinh)
                $this->addSaoToCung($cungAnThienKhong, 'Thiên Giải', 'phu_tinh_cat');
            }
            $mui_Index = $this->getIndex(self::DIA_CHI, 'Mùi');
            if ($mui_Index !== false) {
                // Công thức: (index của Mùi + (tháng sinh - 1)) % 12
                $diaGiaiIndex = ($mui_Index + ($thang - 1)) % 12;
                $cungAndiaGiai = self::DIA_CHI[$diaGiaiIndex];

                // An sao Địa giải (một sát tinh)
                $this->addSaoToCung($cungAndiaGiai, 'Địa Giải', 'phu_tinh_cat');
            }
            $thin_Index = $this->getIndex(self::DIA_CHI, 'Thìn');
            if ($thin_Index !== false) {
                // Công thức: (index của Thìn + (tháng sinh - 1)) % 12
                $taPhuIndex = ($thin_Index + ($thang - 1)) % 12;
                $cungAntaPhu = self::DIA_CHI[$taPhuIndex];

                // An sao Tả Phụ (một sát tinh)
                $this->addSaoToCung($cungAntaPhu, 'Tả Phù', 'phu_tinh_cat');
            }
            // $tuat_Index = $this->getIndex(self::DIA_CHI, 'Tuất');
            // if ($tuat_Index !== false) {
            //     // Công thức: (index của Tuất + (tháng sinh - 1)) % 12
            //     $huuBatIndex = ($tuat_Index + ($thang - 1)) % 12;
            //     $cungAnHuuBat = self::DIA_CHI[$huuBatIndex];

            //     // An sao Hữu Bật (một sát tinh)
            //     $this->addSaoToCung($cungAnHuuBat, 'Hữu Bật', 'phu_tinh_cat');
            // }
            $this->anSaoFromStart('Tuất', $thang - 1, false, 'Hữu Bật', 'phu_tinh_cat');
            if ($tp = $this->findSao('Tả Phù')) {
                $this->anSaoFromStart($tp, $ngay - 1, true, 'Tam Thai', 'phu_tinh_cat');
            }

            //  if ($hb = $this->findSao('Hữu Bật')) $this->anSaoFromStart($hb, $ngay - 1, false, 'Bát Tọa', 'phu_tinh_cat');
            // --- AN SAO BÁT TỌA THEO QUY TẮC MỚI ---
            if ($viTriHuuBat = $this->findSao('Hữu Bật')) {
                $huuBatIndex = $this->getIndex(self::DIA_CHI, $viTriHuuBat);
                if ($huuBatIndex !== false) {
                    $soBuocDem = $ngay - 1;
                    $batToaIndex = (($huuBatIndex - $soBuocDem) % 12 + 12) % 12;
                    $cungAnBatToa = self::DIA_CHI[$batToaIndex];
                    $this->addSaoToCung($cungAnBatToa, 'Bát Tọa', 'phu_tinh_cat');
                }
            }
            if ($tuatIndex = $this->getIndex(self::DIA_CHI, 'Tuất')) {
                // Bước 1: Tìm cung trung gian (từ Tuất đếm ngược đến giờ sinh)
                $cungTrungGianIndex = ($tuatIndex - $gioIndex + 12) % 12;

                // Bước 2: Tìm cung cuối cùng
                // Từ cung trung gian coi là ngày 1, đếm thuận đến ngày sinh, rồi lùi 1
                $soBuocDem = $ngay - 1; // "coi là ngày 1, đếm đến ngày sinh"
                $luiLaiMotCung = -1;    // "rồi lùi lại 1"

                $anQuangIndex_raw = $cungTrungGianIndex + $soBuocDem + $luiLaiMotCung;

                // Chuẩn hóa chỉ số để đảm bảo an toàn
                $anQuangIndex = ($anQuangIndex_raw % 12 + 12) % 12;

                $cungAnAnQuang = self::DIA_CHI[$anQuangIndex];
                $this->addSaoToCung($cungAnAnQuang, 'Ân Quang', 'phu_tinh_cat');
            }


            // 1. Tìm chỉ số của cung Thìn
            // --- BẮT ĐẦU CODE MỚI: AN SAO THIÊN QUÝ THEO CÔNG THỨC CHÍNH XÁC ---
            if ($ngay && $gioIndex !== false) {
                // Khẩu quyết: Lấy Thìn làm Tý, đếm thuận đến giờ sinh.
                // Từ cung đó làm ngày 1, đếm nghịch đến ngày sinh.

                // Bước 1: Khởi Tý tại cung Thìn.
                // -> 4

                // Bước 2: Đếm thuận đến giờ sinh để tìm "cung giờ sinh".
                $cungGioSinhIndex = ($thinIndex + $gioIndex) % 12; // Với giờ Tý ($gioIndex=0) -> (4+0)%12 = 4 (Cung Thìn)

                // Bước 3 & 4: Từ "cung giờ sinh", đếm nghịch đến ngày sinh để tìm "cung ngày sinh".
                $soBuocNghich = $ngay - 1; // Với ngày 23 -> 22 bước
                $cungNgaySinhIndex_raw = $cungGioSinhIndex - $soBuocNghich; // -> 4 - 22 = -18
                $cungNgaySinhIndex_final = ($cungNgaySinhIndex_raw % 12 + 12) % 12; // -> 6 (Cung Ngọ)

                // Bước 5: THAY ĐỔI LOGIC TẠI ĐÂY
                // THEO KHẨU QUYẾT GỐC: Lùi lại 1 cung (-1) -> ra Tỵ
                // THEO GIẢ THUYẾT MỚI ĐỂ RA MÙI: Tiến lên 1 cung (+1)
                $thienQuyIndex_final = ($cungNgaySinhIndex_final + 1) % 12; // -> (6 + 1) % 12 = 7 (Cung Mùi)

                // An sao Thiên Quý vào cung đã tìm được.
                $cungAnThienQuy = self::DIA_CHI[$thienQuyIndex_final];
                $this->addSaoToCung($cungAnThienQuy, 'Thiên Quý', 'phu_tinh_cat');
            }
            // --- KẾT THÚC CODE MỚI ---
            // --- BẮT ĐẦU CODE MỚI: AN SAO VĂN XƯƠNG THEO GIỜ SINH ---
            // Khẩu quyết: Từ cung Tuất, đếm nghịch đến giờ sinh để an Văn Xương.
            if ($gioIndex !== false) {
                // 1. Lấy chỉ số của cung khởi đầu (Tuất).
                $tuatIndex = $this->getIndex(self::DIA_CHI, 'Tuất'); // Giá trị là 10

                // 2. Số bước đếm nghịch chính là chỉ số của giờ sinh.
                $soBuocNghich = $gioIndex;

                // 3. Tính toán vị trí cuối cùng của Văn Xương.
                // Công thức: (vị trí bắt đầu - số bước nghịch % 12 + 12) % 12 để xử lý số âm.
                $vanXuongIndex = ($tuatIndex - $soBuocNghich + 12) % 12;

                // 4. Lấy tên cung và an sao.
                $cungAnVanXuong = self::DIA_CHI[$vanXuongIndex];
                $this->addSaoToCung($cungAnVanXuong, 'Văn Xương', 'phu_tinh_cat');



                // 2. Số bước đếm nghịch chính là chỉ số của giờ sinh.
                $soBuocNghich = $gioIndex;

                // 3. Tính toán vị trí cuối cùng của Văn Khúc.
                // Công thức: (vị trí bắt đầu - số bước nghịch % 12 + 12) % 12 để xử lý số âm.
                $vanKhucIndex = ($thinIndex + $soBuocNghich + 12) % 12;
                // 4. Lấy tên cung và an sao.
                $cungAnVanKhuc = self::DIA_CHI[$vanKhucIndex];
                $this->addSaoToCung($cungAnVanKhuc, 'Văn Khúc', 'phu_tinh_cat');


                // Lấy chỉ số của cung Hợi làm mốc
                $hoiIndex = $this->getIndex(self::DIA_CHI, 'Hợi'); // Giá trị là 11

                // Số bước đếm (thuận hoặc nghịch) chính là chỉ số của giờ sinh
                $soBuocDem = $gioIndex;

                // 1. An sao Địa Không (đếm nghịch)
                $diaKhongIndex = ($hoiIndex - $soBuocDem + 12) % 12;
                $cungAnDiaKhong = self::DIA_CHI[$diaKhongIndex];
                $this->addSaoToCung($cungAnDiaKhong, 'Địa Không', 'phu_tinh_sat');

                // 2. An sao Địa Kiếp (đếm thuận)
                $diaKiepIndex = ($hoiIndex + $soBuocDem) % 12;
                $cungAnDiaKiep = self::DIA_CHI[$diaKiepIndex];
                $this->addSaoToCung($cungAnDiaKiep, 'Địa Kiếp', 'phu_tinh_sat');
            }
            // --- KẾT THÚC CODE MỚI ---
            if ($viTriVanKhuc = $this->findSao('Văn Khúc')) {
                // 1. Lấy chỉ số (index) của cung an sao Văn Khúc.
                $vanKhucIndex = $this->getIndex(self::DIA_CHI, $viTriVanKhuc);

                // Đảm bảo rằng chỉ số được tìm thấy để tránh lỗi.
                if ($vanKhucIndex !== false) {
                    // 2. An sao Thai Phụ (đếm thuận, bỏ qua 1 cung, tức là +2)
                    $thaiPhuIndex = ($vanKhucIndex + 2) % 12;
                    $cungAnThaiPhu = self::DIA_CHI[$thaiPhuIndex];
                    $this->addSaoToCung($cungAnThaiPhu, 'Thai Phụ', 'phu_tinh_cat');

                    // 3. An sao Phong Cáo (đếm nghịch, bỏ qua 1 cung, tức là -2)
                    $phongCaoIndex = ($vanKhucIndex - 2 + 12) % 12;
                    $cungAnPhongCao = self::DIA_CHI[$phongCaoIndex];
                    $this->addSaoToCung($cungAnPhongCao, 'Phong Cáo', 'phu_tinh_cat');
                }
            }
        }
        if ($chiNam) {
            // 1. Xác định nhóm Tam Hợp của năm sinh.
            $groupKey = $this->getChiGroupKey($chiNam);
            $hoaTinhStartCung = null;
            $linhTinhStartCung = null;

            // 2. Lấy cung khởi đầu dựa vào nhóm Tam Hợp.
            switch ($groupKey) {
                case 'DanNgoTuat':
                    $hoaTinhStartCung = 'Sửu';
                    $linhTinhStartCung = 'Mão';
                    break;
                case 'ThanTyThin':
                    $hoaTinhStartCung = 'Dần';
                    $linhTinhStartCung = 'Mão'; // Theo yêu cầu của bạn.
                    break;
                case 'TiDauSuu':
                    $hoaTinhStartCung = 'Mão';
                    $linhTinhStartCung = 'Tuất';
                    break;
                case 'HoiMaoMui':
                    $hoaTinhStartCung = 'Dậu';
                    $linhTinhStartCung = 'Tuất';
                    break;
            }

            // 3. Tiến hành an sao nếu đã xác định được cung khởi đầu.
            if ($hoaTinhStartCung && $linhTinhStartCung) {
                $hoaTinhStartIndex = $this->getIndex(self::DIA_CHI, $hoaTinhStartCung);
                $linhTinhStartIndex = $this->getIndex(self::DIA_CHI, $linhTinhStartCung);
                $soBuocDem = $gioIndex;

                // 4. Xác định vị trí cuối cùng dựa trên chiều đếm.
                $hoaTinhFinalIndex = 0;
                $linhTinhFinalIndex = 0;

                // $isThuan là true cho Dương Nam / Âm Nữ.
                if ($isThuan) {
                    // Hỏa Tinh đếm NGHỊCH
                    $hoaTinhFinalIndex = ($hoaTinhStartIndex - $soBuocDem + 12) % 12;
                    // Linh Tinh đếm THUẬN
                    $linhTinhFinalIndex = ($linhTinhStartIndex + $soBuocDem) % 12;
                } else { // Âm Nam / Dương Nữ
                    // Hỏa Tinh đếm THUẬN
                    $hoaTinhFinalIndex = ($hoaTinhStartIndex + $soBuocDem) % 12;
                    // Linh Tinh đếm NGHỊCH
                    $linhTinhFinalIndex = ($linhTinhStartIndex - $soBuocDem + 12) % 12;
                }

                // 5. An sao Hỏa Tinh và Linh Tinh vào cung.
                $this->addSaoToCung(self::DIA_CHI[$hoaTinhFinalIndex], 'Hỏa Tinh', 'phu_tinh_sat');
                $this->addSaoToCung(self::DIA_CHI[$linhTinhFinalIndex], 'Linh Tinh', 'phu_tinh_sat');
            }
        }
        $viTriTuongTinh = null;
        switch ($groupKey) {
            case 'DanNgoTuat':
                $viTriTuongTinh = 'Ngọ';
                break;
            case 'ThanTyThin':
                $viTriTuongTinh = 'Tý';
                break;
            case 'TiDauSuu':
                $viTriTuongTinh = 'Dậu';
                break;
            case 'HoiMaoMui':
                $viTriTuongTinh = 'Mão';
                break;
        }
        if ($viTriTuongTinh) {
            // Tướng Tinh (Tướng Quân) là một sao về quyền uy, mạnh mẽ, thường được xếp vào nhóm sao võ (sát).
            // Phân loại 'phu_tinh_sat' để đồng bộ với Tướng Quân trong vòng Bác Sĩ.
            $this->addSaoToCung($viTriTuongTinh, 'Tướng Tinh', 'phu_tinh_cat');
        }
        // if ($gioIndex !== false) {
        //     $this->addSaoToCung(config("tuvi_data.van_xuong.{$gioIndex}"), 'Văn Xương', 'phu_tinh_cat');
        //     $this->addSaoToCung(config("tuvi_data.van_khuc.{$gioIndex}"), 'Văn Khúc', 'phu_tinh_cat');
        //     if ($khongKiep = config("tuvi_data.dia_khong_kiep.{$gioIndex}")) {
        //         $this->addSaoToCung($khongKiep['Địa Không'], 'Địa Không', 'phu_tinh_sat');
        //         $this->addSaoToCung($khongKiep['Địa Kiếp'], 'Địa Kiếp', 'phu_tinh_sat');
        //     }
        //     if ($groupKey = $this->getChiGroupKey($chiNam)) {
        //         if ($hoaLinh = config("tuvi_data.hoa_linh.{$groupKey}.{$gioIndex}")) {
        //             $this->addSaoToCung($hoaLinh['Hỏa Tinh'], 'Hỏa Tinh', 'phu_tinh_sat');
        //             $this->addSaoToCung($hoaLinh['Linh Tinh'], 'Linh Tinh', 'phu_tinh_sat');
        //         }
        //     }
        //     if ($gioSao = config("tuvi_data.gio.{$gioIndex}")) {
        //         $this->addSaoToCung($gioSao['Phá Toái'], 'Phá Toái', 'phu_tinh_sat');
        //     }
        // }
        // if ($ngay) {
        //     if ($tp = $this->findSao('Tả Phụ')) $this->anSaoFromStart($tp, $ngay, true, 'Tam Thai', 'phu_tinh_cat');
        //     if ($hb = $this->findSao('Hữu Bật')) $this->anSaoFromStart($hb, $ngay, false, 'Bát Tọa', 'phu_tinh_cat');
        //     if ($vx = $this->findSao('Văn Xương')) $this->anSaoFromStart($vx, $ngay - 1, true, 'Ân Quang', 'phu_tinh_cat');
        //     if ($vk = $this->findSao('Văn Khúc')) $this->anSaoFromStart($vk, $ngay - 1, false, 'Thiên Quý', 'phu_tinh_cat');
        //     if ($ngaySao = config("tuvi_data.tang_tu.{$ngay}")) {
        //         $this->addSaoToCung($ngaySao['Táng Hổ'], 'Táng Hổ', 'phu_tinh_sat');
        //     }
        // }
    }
   private function anTenCacVanHan()
    {
        $tuoi = $this->input['tuoi'];
    
        $cungDaiVanMenh = null;
        foreach ($this->laSo['palaces'] as $chi => $cung) {
            if (!empty($cung['dai_van']) && $tuoi >= $cung['dai_van'] && $tuoi < ($cung['dai_van'] + 10)) {
                $cungDaiVanMenh = $chi;
                break;
            }
        }
    
        // If no match is found (e.g., age is less than the first dai_van), default to the Menh palace.
        if ($cungDaiVanMenh === null) {
            $cungDaiVanMenh = $this->cungMenhViTri;
        }
    
        $cungTieuVanMenh = $this->laSo['info']['tieu_van_cung'] ?? null;
        if (!$cungDaiVanMenh || !$cungTieuVanMenh) {
            return;
        }
    
        $indexDaiVanMenh = $this->getIndex(self::DIA_CHI, $cungDaiVanMenh);
        $indexTieuVanMenh = $this->getIndex(self::DIA_CHI, $cungTieuVanMenh);
    
        for ($i = 0; $i < 12; $i++) {
            $currentChi = self::DIA_CHI[$i];
            $dvChucNangIndex = ($i - $indexDaiVanMenh + 12) % 12;
            $dvChucNangName = self::CUNG_CHUC_NANG[$dvChucNangIndex];
    
            $this->laSo['palaces'][$currentChi]['dv_chuc_nang'] = 'ĐV.' . self::$cungChucNangAbbr[$dvChucNangName];
    
            $lnChucNangIndex = ($i - $indexTieuVanMenh + 12) % 12;
            $lnChucNangName = self::CUNG_CHUC_NANG[$lnChucNangIndex];
            $this->laSo['palaces'][$currentChi]['ln_chuc_nang'] = 'LN.' . self::$cungChucNangAbbr[$lnChucNangName];
        }
    }
    private function anCanCung()
    {
        $canNam = $this->input['lunar']['can'];
        $canDanMap = ['Giáp' => 'Bính', 'Kỷ' => 'Bính', 'Ất' => 'Mậu', 'Canh' => 'Mậu', 'Bính' => 'Canh', 'Tân' => 'Canh', 'Đinh' => 'Nhâm', 'Nhâm' => 'Nhâm', 'Mậu' => 'Giáp', 'Quý' => 'Giáp'];
        $canDan = $canDanMap[$canNam] ?? 'Giáp';
        $startCanIndex = $this->getIndex(self::THIEN_CAN, $canDan);
        $startChiIndex = $this->getIndex(self::DIA_CHI, 'Dần');
        for ($i = 0; $i < 12; $i++) {
            $currentChiIndex = ($startChiIndex + $i) % 12;
            $currentCanIndex = ($startCanIndex + $i) % 10;
            $chi = self::DIA_CHI[$currentChiIndex];
            $can = self::THIEN_CAN[$currentCanIndex];
            $this->laSo['palaces'][$chi]['can_chi_cung'] = "{$can}.{$chi}";
        }
    }
    private function anCacSaoCoDinhVaTheoChucNang()
    {
        $this->addSaoToCung('Thìn', 'Thiên La', 'phu_tinh_sat');
        $this->addSaoToCung('Tuất', 'Địa Võng', 'phu_tinh_sat');
        if ($n = $this->findCungChucNang('NÔ BỘC')) $this->addSaoToCung($n, 'Thiên Thương', 'phu_tinh_sat');
        if ($t = $this->findCungChucNang('TẬT ÁCH')) $this->addSaoToCung($t, 'Thiên Sứ', 'phu_tinh_sat');
    }
    private function anVongTrangSinh()
    {
        if (!$this->cucSo) return;
        $isThuan = in_array($this->amDuong, ['Dương Nam', 'Âm Nữ']);
        $vong = ['Trường Sinh', 'Mộc Dục', 'Quan Đới', 'Lâm Quan', 'Đế Vượng', 'Suy', 'Bệnh', 'Tử', 'Mộ', 'Tuyệt', 'Thai', 'Dưỡng'];
        // $startChi = config("tuvi_data.vong_trang_sinh.{$this->cucSo}");
        $startChi =  $this->data_vong_trang_sinh[$this->cucSo] ?? null;;
        $startIndex = $this->getIndex(self::DIA_CHI, $startChi);
        if ($startIndex === false) return;
        for ($i = 0; $i < 12; $i++) {
            $offset = $isThuan ? $i : -$i;
            $cungIndex = ($startIndex + $offset + 12) % 12;
            $this->laSo['palaces'][self::DIA_CHI[$cungIndex]]['vong_trang_sinh'] = $vong[$i];
        }
    }
    private function tinhChuMenhThan()
    {
        if (empty($this->input['lunar']['chi'])) return;
        $diaChiNamSinh = $this->input['lunar']['chi'];
        // $this->laSo['info']['chu_menh'] = config("tuvi_data.chu_menh_than.ChuMenh.{$diaChiNamSinh}");
        // $this->laSo['info']['chu_than'] = config("tuvi_data.chu_menh_than.ChuThan.{$diaChiNamSinh}");
        $this->laSo['info']['chu_menh'] = $this->data_chu_menh_than['ChuMenh'][$diaChiNamSinh] ?? null;
        $this->laSo['info']['chu_than'] = $this->data_chu_menh_than['ChuThan'][$diaChiNamSinh] ?? null;
    }
    private function anCungMenhThan()
    {
        $thangAm = $this->input['lunar']['month'];
        $gioSinhChi = $this->input['lunar']['hour_chi'];

        // --- Kiểm tra đầu vào ---
        if (!is_numeric($thangAm) || $thangAm < 1 || $thangAm > 12) {
            throw new Exception("Tháng âm lịch không hợp lệ: $thangAm");
        }
        $gioIndex = $this->getIndex(self::DIA_CHI, $gioSinhChi);
        if ($gioIndex === false) {
            throw new Exception("Giờ sinh không hợp lệ: $gioSinhChi");
        }

        // --- ÁP DỤNG CÔNG THỨC CHUẨN ---

        // Bước 1 & 2: Từ cung Dần (index=2) khởi tháng 1, đếm thuận đến tháng sinh.
        // Ví dụ: Tháng 1 -> ở Dần (index 2). Tháng 2 -> ở Mão (index 3).
        $cungThangSinhIndex = (2 + ($thangAm - 1) + 12) % 12;

        // Bước 3 & 4: Từ cung tháng sinh, đếm nghịch đến giờ sinh để an MỆNH.
        // Phép toán ($a - $b + 12) % 12 là cách an toàn để trừ trong vòng tròn 12 cung.
        $menhCungIndex = ($cungThangSinhIndex - $gioIndex + 12) % 12;

        // Bước 5: Từ cung tháng sinh, đếm thuận đến giờ sinh để an THÂN.
        $thanCungIndex = ($cungThangSinhIndex + $gioIndex + 12) % 12;

        // --- Lưu kết quả ---
        $this->cungMenhViTri = self::DIA_CHI[$menhCungIndex];
        $this->cungThanViTri = self::DIA_CHI[$thanCungIndex];
    }
    private function anTieuVanCung()
    {
        $gioiTinh = $this->input['gioi_tinh'];
        $chiNamSinh = $this->input['lunar']['chi'];
        $namXemChi = CustomLunarConverterHelper::getChi($this->input['nam_xem']);
        $groupKey = $this->getChiGroupKey($chiNamSinh);
        $khoiCungMap = ['DanNgoTuat' => 'Thìn', 'ThanTyThin' => 'Tuất', 'TiDauSuu' => 'Mùi', 'HoiMaoMui' => 'Sửu',];
        $cungKhoi = $khoiCungMap[$groupKey] ?? null;
        if (!$cungKhoi) return;
        $startIndexCung = $this->getIndex(self::DIA_CHI, $cungKhoi);
        $startIndexChi = $this->getIndex(self::DIA_CHI, $chiNamSinh);
        $endIndexChi = $this->getIndex(self::DIA_CHI, $namXemChi);
        $distance = ($endIndexChi - $startIndexChi + 12) % 12;
        $isThuan = ($gioiTinh == 'Nam');
        $finalIndex = $isThuan ? (($startIndexCung + $distance) % 12) : (($startIndexCung - $distance + 12) % 12);
        $this->laSo['info']['tieu_van_cung'] = self::DIA_CHI[$finalIndex];
    }
    // private function anTieuVanThang()
    // {
    //     $cungTieuVan = $this->laSo['info']['tieu_van_cung'] ?? null;
    //     if (!$cungTieuVan) return;
    //     $gioSinhChi = $this->input['lunar']['hour_chi'];
    //     $tieuVanIndex = $this->getIndex(self::DIA_CHI, $cungTieuVan);
    //     $gioIndex = $this->getIndex(self::DIA_CHI, $gioSinhChi);
    //     $viTriThang1Index = ($tieuVanIndex + $gioIndex) % 12;
    //     for ($i = 0; $i < 12; $i++) {
    //         $currentMonth = $i + 1;
    //         $currentCungIndex = ($viTriThang1Index + $i) % 12;
    //         $currentCungChi = self::DIA_CHI[$currentCungIndex];
    //         $this->laSo['palaces'][$currentCungChi]['thang_am'] = $currentMonth;
    //     }
    // }
    private function anTieuVanThang()
    {
        // === BƯỚC 0: KIỂM TRA VÀ LẤY DỮ LIỆU CẦN THIẾT ===
        $namXemChi = CustomLunarConverterHelper::getChi($this->input['nam_xem']);
        $thangSinh = $this->input['lunar']['month'];
        $gioSinhChi = $this->input['lunar']['hour_chi'];
        $gioIndex = $this->getIndex(self::DIA_CHI, $gioSinhChi);

        // Kiểm tra đầu vào
        if (!$namXemChi || !$thangSinh || $gioIndex === false) {
            // Không đủ thông tin để tính
            return;
        }

        // === BƯỚC 1: TÌM CUNG KHỞI ĐIỂM ===
        // Tìm cung có 'nguyet_han_chi' trùng với chi của năm xem.
        $startCungIndex = -1;
        foreach ($this->laSo['palaces'] as $chiCung => $cungData) {
            if (isset($cungData['vong_tuoi_chi']) && $cungData['vong_tuoi_chi'] === $namXemChi) {

                $startCungIndex = $this->getIndex(self::DIA_CHI, $chiCung);
                break;
            }
        }

        // Nếu không tìm thấy, có thể vòng Nguyệt Hạn chưa được an, nên dừng lại.
        if ($startCungIndex === -1) {
            return;
        }

        // === BƯỚC 2: TÌM CUNG TRUNG GIAN ===
        // Từ Cung Khởi Điểm, đếm nghịch (tháng sinh - 1) bước.
        $soBuocNghich = $thangSinh - 1;
        $intermediateCungIndex = ($startCungIndex - $soBuocNghich + 12 * $thangSinh) % 12; // Thêm 12*N để đảm bảo dương

        // === BƯỚC 3: TÌM CUNG AN THÁNG 1 ===
        // Từ Cung Trung Gian (coi là Tý), đếm thuận đến giờ sinh.
        $soBuocThuan = $gioIndex;
        $viTriThang1Index = ($intermediateCungIndex + $soBuocThuan) % 12;

        // === BƯỚC 4: AN 12 THÁNG VÀO 12 CUNG ===
        // Từ cung Tháng 1, đếm thuận để an các tháng còn lại.
        for ($i = 0; $i < 12; $i++) {
            $currentMonth = $i + 1; // Tháng 1, 2, 3...

            // Vị trí cung cần an tháng hiện tại
            $currentCungIndex = ($viTriThang1Index + $i) % 12;
            $currentCungChi = self::DIA_CHI[$currentCungIndex];

            // Gán số tháng vào dữ liệu của cung tương ứng
            $this->laSo['palaces'][$currentCungChi]['thang_am'] = $currentMonth;
        }
    }
    private function lapThongTinCoBan()
    {
        $lunarData = $this->input['lunar'];

        $duongCan = in_array($lunarData['can'], ['Giáp', 'Bính', 'Mậu', 'Canh', 'Nhâm']);
        $this->amDuong = ($this->input['gioi_tinh'] == 'Nam') ? ($duongCan ? 'Dương Nam' : 'Âm Nam') : ($duongCan ? 'Dương Nữ' : 'Âm Nữ');

        $hoaGiap = $lunarData['can'] . ' ' . $lunarData['chi'];
        // $menhData = config('tuvi_data.nap_am.' . $hoaGiap);
        $menhData = $this->data_nap_am[$hoaGiap] ?? null;
        $hanhMenh = $menhData['hanh'] ?? null;

        $this->laSo['info'] = ['ho_ten' => $this->input['ho_ten'], 'gioi_tinh' => $this->input['gioi_tinh'], 'duong_lich_str' => $this->input['duong_lich_str'], 'am_lich_str' => $this->input['am_lich_str'], 'nam_xem' => $this->input['nam_xem'], 'tuoi' => $this->input['tuoi'], 'am_duong' => $this->amDuong, 'menh' => $menhData['napAm'] ?? 'Không rõ', 'hanh_menh' => $hanhMenh,];
    }
    private function anDaiVan()
    {
        if (!$this->cungMenhViTri || !$this->cucSo) return;
        $menhIndex = $this->getIndex(self::DIA_CHI, $this->cungMenhViTri);
        if ($menhIndex === false) return;
        $isThuan = in_array($this->amDuong, ['Dương Nam', 'Âm Nữ']);
        for ($i = 0; $i < 12; $i++) {
            $offset = $isThuan ? $i : -$i;
            $cungIndex = ($menhIndex + $offset + 12) % 12;
            $tuoi = $this->cucSo + ($i * 10);
            $this->laSo['palaces'][self::DIA_CHI[$cungIndex]]['dai_van'] = $tuoi;
        }
    }
    private function anTuanTriet()
    {
        $canNam = $this->input['lunar']['can'];
        $chiNam = $this->input['lunar']['chi'];
        $canMap = ['Giáp' => 'Kỷ', 'Ất' => 'Canh', 'Bính' => 'Tân', 'Đinh' => 'Nhâm', 'Mậu' => 'Quý'];
        foreach ($canMap as $c1 => $c2) {
            if ($canNam === $c1 || $canNam === $c2) {
                // $cungTriet = config("tuvi_data.triet.{$c1} {$c2}");
                $cungTriet = $this->data_triet["{$c1} {$c2}"] ?? null;
                $this->addSaoToCung($cungTriet[0], 'Triệt', 'special');
                $this->addSaoToCung($cungTriet[1], 'Triệt', 'special');
                break;
            }
        }
        $canIndex = $this->getIndex(self::THIEN_CAN, $canNam);
        $chiIndex = $this->getIndex(self::DIA_CHI, $chiNam);
        $giapTyChiIndex = ($chiIndex - $canIndex + 12) % 12;
        $giapTyCanChi = "Giáp " . self::DIA_CHI[$giapTyChiIndex];
        // $cungTuan = config("tuvi_data.tuan_khong_vong.{$giapTyCanChi}");
        $cungTuan = $this->data_tuan_khong_vong[$giapTyCanChi] ?? null;

        if ($cungTuan) {
            $this->addSaoToCung($cungTuan[0], 'Tuần', 'special');
            $this->addSaoToCung($cungTuan[1], 'Tuần', 'special');
        }
    }
    private function anSaoLuu()
    {
        $namXemCan = CustomLunarConverterHelper::getCan($this->input['nam_xem']);
        $namXemChi = CustomLunarConverterHelper::getChi($this->input['nam_xem']);
        $nxIndex = $this->getIndex(self::DIA_CHI, $namXemChi);
        $cungAnLuuThaiTue = $namXemChi;
        // An sao vào cung. 
        // Tôi đề xuất dùng một loại sao mới là 'luu_tinh' để phân biệt với 'phu_tinh'.
        $this->addSaoToCung($cungAnLuuThaiTue, 'L.Thái Tuế', 'phu_tinh_sat');
        // Sử dụng chỉ số của L.Thái Tuế ($nxIndex) làm mốc.
        $viTriTangMonIndex = ($nxIndex + 2) % 12;

        // Lấy tên cung và an sao.
        $cungAnTangMon = self::DIA_CHI[$viTriTangMonIndex];
        $this->addSaoToCung($cungAnTangMon, 'L.Tang Môn', 'phu_tinh_sat');
        // Khẩu quyết: L.Bạch Hổ ở cung đối diện (xung chiếu) với L.Tang Môn.
        // Công thức: +6 vào chỉ số của L.Tang Môn.

        // Sử dụng chỉ số của L.Tang Môn đã tính ở bước trước.
        $viTriBachHoIndex = ($viTriTangMonIndex + 6) % 12;

        // Lấy tên cung và an sao.
        $cungAnBachHo = self::DIA_CHI[$viTriBachHoIndex];
        $this->addSaoToCung($cungAnBachHo, 'L.Bạch Hổ', 'phu_tinh_sat');
        // Khẩu quyết: Từ Ngọ coi là Tý, đếm thuận đến Chi năm xem an L.Thiên Hư,
        // đếm nghịch đến Chi năm xem an L.Thiên Khốc.

        // Lấy chỉ số của cung Ngọ làm mốc.
        $ngoIndex = $this->getIndex(self::DIA_CHI, 'Ngọ');
        if ($ngoIndex === false) {
            return;
        } // An toàn

        // Số bước đếm chính là chỉ số của Chi năm xem ($nxIndex).
        $soBuocDem = $nxIndex;

        // 1. An L.Thiên Hư (đếm thuận)
        $thienHuIndex = ($ngoIndex + $soBuocDem) % 12;
        $this->addSaoToCung(self::DIA_CHI[$thienHuIndex], 'L.Thiên Hư', 'phu_tinh_sat');

        // 2. An L.Thiên Khốc (đếm nghịch)
        $thienKhocIndex = ($ngoIndex - $soBuocDem + 12) % 12;
        $this->addSaoToCung(self::DIA_CHI[$thienKhocIndex], 'L.Thiên Khốc', 'phu_tinh_sat');
        // Khẩu quyết: Dựa vào tam hợp của năm xem hạn.
        $cungAnLuuThienMa = null;

        switch ($namXemChi) {
            case 'Dần':
            case 'Ngọ':
            case 'Tuất':
                // Nhóm Dần-Ngọ-Tuất. Chi đầu là Dần. Xung chiếu Dần là Thân.
                $cungAnLuuThienMa = 'Thân';
                break;

            case 'Thân':
            case 'Tý':
            case 'Thìn':
                // Nhóm Thân-Tý-Thìn. Chi đầu là Thân. Xung chiếu Thân là Dần.
                $cungAnLuuThienMa = 'Dần';
                break;

            case 'Tỵ':
            case 'Dậu':
            case 'Sửu':
                // Nhóm Tỵ-Dậu-Sửu. Chi đầu là Tỵ. Xung chiếu Tỵ là Hợi.
                $cungAnLuuThienMa = 'Hợi';
                break;

            case 'Hợi':
            case 'Mão':
            case 'Mùi':
                // Nhóm Hợi-Mão-Mùi. Chi đầu là Hợi. Xung chiếu Hợi là Tỵ.
                $cungAnLuuThienMa = 'Tỵ';
                break;
        }

        // An sao nếu đã tìm được cung
        if ($cungAnLuuThienMa) {
            $this->addSaoToCung($cungAnLuuThienMa, 'L.Thiên Mã', 'phu_tinh_cat');
        }

        // --- PHẦN MỚI: AN SAO LƯU LỘC TỒN, L.KÌNH DƯƠNG, L.ĐÀ LA ---
        // Khẩu quyết: Dựa vào Can của năm xem hạn.
        $cungAnLuuLocTon = null;

        switch ($namXemCan) {
            case 'Giáp':
                $cungAnLuuLocTon = 'Dần';
                break;
            case 'Ất':
                $cungAnLuuLocTon = 'Mão';
                break;
            case 'Bính':
            case 'Mậu':
                $cungAnLuuLocTon = 'Tỵ';
                break;
            case 'Đinh':
            case 'Kỷ':
                $cungAnLuuLocTon = 'Ngọ';
                break;
            case 'Canh':
                $cungAnLuuLocTon = 'Thân';
                break;
            case 'Tân':
                $cungAnLuuLocTon = 'Dậu';
                break;
            case 'Nhâm':
                $cungAnLuuLocTon = 'Hợi';
                break;
            case 'Quý':
                $cungAnLuuLocTon = 'Tý';
                break;
        }

        // Nếu đã tìm được vị trí của L.Lộc Tồn, an luôn bộ ba
        if ($cungAnLuuLocTon) {
            // L.Lộc Tồn (Cát tinh)
            $this->addSaoToCung($cungAnLuuLocTon, 'L.Lộc Tồn', 'phu_tinh_cat');

            // An luôn 2 sao hộ vệ của nó
            $locTonIndex = $this->getIndex(self::DIA_CHI, $cungAnLuuLocTon);
            if ($locTonIndex !== false) {
                // L.Kình Dương (Sát tinh) ở cung kế tiếp
                $kinhDuongIndex = ($locTonIndex + 1) % 12;
                $this->addSaoToCung(self::DIA_CHI[$kinhDuongIndex], 'L.Kình Dương', 'phu_tinh_sat');

                // L.Đà La (Sát tinh) ở cung trước đó
                $daLaIndex = ($locTonIndex - 1 + 12) % 12;
                $this->addSaoToCung(self::DIA_CHI[$daLaIndex], 'L.Đà La', 'phu_tinh_sat');
            }
        }

        // 1. Lấy thông tin cần thiết
        $chiNam = $this->input['lunar']['chi'];
        $chiNamIndex = $this->getIndex(self::DIA_CHI, $chiNam);
        $isMale = ($this->input['gioi_tinh'] === 'Nam');

        // 2. Xác định "Cung Khởi Điểm"
        $groupKey = $this->getChiGroupKey($chiNam);
        $cungGoc = null;
        switch ($groupKey) {
            case 'DanNgoTuat':
                $cungGoc = 'Tuất';
                break;
            case 'ThanTyThin':
                $cungGoc = 'Thìn';
                break;
            case 'TiDauSuu':
                $cungGoc = 'Sửu';
                break;
            case 'HoiMaoMui':
                $cungGoc = 'Mùi';
                break;
        }

        if ($cungGoc && $chiNamIndex !== false) {
            $cungGocIndex = $this->getIndex(self::DIA_CHI, $cungGoc);
            $cungKhoiDiemIndex = ($cungGocIndex + 6) % 12;

            // 3. Xác định chiều đếm
            $direction = $isMale ? 1 : -1; // 1 for thuận, -1 for nghịch

            // 4. Vòng lặp để điền 12 địa chi mới vào 12 cung
            for ($i = 0; $i < 12; $i++) {
                // Tính vị trí của cung đích trên lá số (vị trí đặt)
                $targetCungIndex_raw = $cungKhoiDiemIndex + ($i * $direction);
                $targetCungIndex = ($targetCungIndex_raw % 12 + 12) % 12;
                $tenCungDich = self::DIA_CHI[$targetCungIndex];

                // Tính Chi mới sẽ được đặt vào cung đó (giá trị đặt)
                // Giá trị này luôn đi thuận từ chi năm sinh
                $chiMoiIndex = ($chiNamIndex + $i) % 12;
                $tenChiMoi = self::DIA_CHI[$chiMoiIndex];

                // 5. Gán Chi mới vào dữ liệu của cung.
                // Chúng ta dùng key mới là 'vong_tuoi_chi'
                $this->laSo['palaces'][$tenCungDich]['vong_tuoi_chi'] = $tenChiMoi;
            }
        }
    }
    private function tinhLuanGiaiCoBan()
    {
        if (!$this->cungMenhViTri) return;
        $ketLuan = [];
        $duongAmDuong = strpos($this->amDuong, 'Dương') !== false;
        $duongCungMenh = in_array($this->cungMenhViTri, ['Tý', 'Dần', 'Thìn', 'Ngọ', 'Thân', 'Tuất']);
        $ketLuan[] = $duongAmDuong == $duongCungMenh ? 'Âm Dương thuận lý' : 'Âm Dương nghịch lý';
        $this->laSo['info']['ket_luan'] = $ketLuan;
    }
    private function getIndex(array $haystack, $needle): bool|int|string
    {
        return array_search($needle, $haystack);
    }
    // === THAY ĐỔI 1: THÊM HÀM HELPER ĐỂ CHUYỂN TÊN SAO THÀNH TÊN CLASS ===
    /**
     * Chuyển đổi một chuỗi tiếng Việt có dấu thành một chuỗi "slug" an toàn cho CSS class.
     * Ví dụ: "Thiên Đồng" -> "thien-dong", "L.Thái Tuế" -> "l-thai-tue"
     * @param string $text
     * @return string
     */
    private function slugifySaoName(string $text): string
    {
        $text = strtolower($text);
        // Bảng thay thế các ký tự có dấu
        $patterns = [
            '/[àáảãạăằắẳẵặâầấẩẫậ]/u' => 'a',
            '/[èéẻẽẹêềếểễệ]/u' => 'e',
            '/[ìíỉĩị]/u' => 'i',
            '/[òóỏõọôồốổỗộơờớởỡợ]/u' => 'o',
            '/[ùúủũụưừứửữự]/u' => 'u',
            '/[ỳýỷỹỵ]/u' => 'y',
            '/[đ]/u' => 'd',
            '/\./' => '', // Xóa dấu chấm (trong L.Thái Tuế)
            '/\s+/' => '-', // Thay khoảng trắng bằng gạch ngang
        ];
        $text = preg_replace(array_keys($patterns), array_values($patterns), $text);
        // Xóa các ký tự không hợp lệ còn lại
        $text = preg_replace('/[^a-z0-9-]/', '', $text);
        return trim($text, '-');
    }
    // protected function addSaoToCung($tenCung, $tenSao, $type)
    // {
    //     if (!isset($this->laSo['palaces'][$tenCung])) {
    //         throw new Exception("Cung $tenCung không tồn tại");
    //     }
    //     if (!isset($this->laSo['palaces'][$tenCung][$type])) {
    //         $this->laSo['palaces'][$tenCung][$type] = [];
    //     }
    //     $this->laSo['palaces'][$tenCung][$type][] = $tenSao;
    // }
    // === THAY ĐỔI 2: HÀM addSaoToCung ĐƯỢC NÂNG CẤP TOÀN DIỆN ===
    protected function addSaoToCung($tenCung, $tenSao, $type)
    {
        if (!isset($this->laSo['palaces'][$tenCung])) {
            return;
        }
        if (!isset($this->laSo['palaces'][$tenCung][$type])) {
            $this->laSo['palaces'][$tenCung][$type] = [];
        }

        // Tách tên sao gốc để tra cứu độ sáng
        // Loại bỏ các prefix như 'L.', 'L.N.', 'TV.' để lấy tên sao gốc
        $saoNameForLookup = preg_replace('/^(L\.N\.|L\.|TV\.)/', '', $tenSao);
        $brightnessCode = $this->brightnessTable[$saoNameForLookup][$tenCung] ?? null;
        // if ($brightnessCode === 'B') {
        //     $brightnessCode = null;
        // }

        // --- Tự động tạo các lớp CSS ---
        $classes = [];

        // 1. Class chung theo loại (ví dụ: 'la-phu-tinh-cat')
        $classes[] = 'la-' . str_replace('_', '-', $type);

        // 2. Class RIÊNG BIỆT cho từng sao (ví dụ: 'sao-thien-hinh', 'sao-l-thai-tue')
        $classes[] = 'sao-' . $this->slugifySaoName($tenSao);

        // 3. Class nhóm (nếu có định nghĩa trong $saoGroupClasses)
        if (isset($this->saoGroupClasses[$tenSao])) {
            $classes[] = $this->saoGroupClasses[$tenSao];
        }

        // Tạo chuỗi class cuối cùng, loại bỏ các giá trị rỗng và trùng lặp
        $finalClass = implode(' ', array_unique(array_filter($classes)));

        // Lấy ngũ hành của sao
        $nguHanh = $this->data_sao_ngu_hanh[$saoNameForLookup] ?? null;

        // Tạo cấu trúc dữ liệu chuẩn và thêm vào lá số
        $this->laSo['palaces'][$tenCung][$type][] = [
            'name'   => $tenSao,
            'bright' => $brightnessCode,
            'class'  => $finalClass,
            'cung'   => $tenCung,  // Tên cung (hệ zodiac) cho sao
            'hanh'   => $nguHanh   // Ngũ hành (Kim, Mộc, Thủy, Hỏa, Thổ) của sao
        ];
    }
    private function findCungChucNang($tenCungChucNang)
    {
        foreach ($this->laSo['palaces'] as $chi => $cung) {
            if (isset($cung['cung_chuc_nang']) && strpos($cung['cung_chuc_nang'], $tenCungChucNang) !== false) {
                return $chi;
            }
        }
        return null;
    }
    // private function findSao($tenSao): mixed
    // {
    //     $saoNameOnly = preg_replace('/\(.+\)/', '', $tenSao);
    //     foreach ($this->laSo['palaces'] as $chi => $cung) {
    //         foreach (['chinh_tinh', 'phu_tinh_cat', 'phu_tinh_sat', 'phu_tinh', 'special'] as $type) {
    //             if (isset($cung[$type])) {
    //                 foreach ($cung[$type] as $saoInCung) {
    //                     if (str_starts_with($saoInCung, $saoNameOnly)) {
    //                         return $chi;
    //                     }
    //                 }
    //             }
    //         }
    //     }
    //     return null;
    // }
    private function findSao($tenSao): mixed
    {
        foreach ($this->laSo['palaces'] as $chi => $cung) {
            foreach (['chinh_tinh', 'phu_tinh_cat', 'phu_tinh_sat', 'phu_tinh', 'special', 'luu'] as $type) {
                if (!empty($cung[$type])) {
                    foreach ($cung[$type] as $saoInCung) {
                        if (isset($saoInCung['name']) && $saoInCung['name'] === $tenSao) {
                            return $chi;
                        }
                    }
                }
            }
        }
        return null;
    }
    private function anSaoFromStart($startChi, $count, $isThuan, $tenSao, $loaiSao = 'phu_tinh_cat')
    {
        // SỬA LỖI 1: Cho phép số bước đếm ($count) bằng 0.
        // Điều kiện đúng phải là $count < 0.
        if (!$startChi || $count < 0) {
            return;
        }

        $startIndex = $this->getIndex(self::DIA_CHI, $startChi);
        if ($startIndex === false) {
            return;
        }

        // SỬA LỖI 2: Loại bỏ việc trừ đi 1 một cách thừa thãi.
        // Tham số $count giờ đây chính là số bước cần di chuyển.
        $offset = $isThuan ? $count : -$count;

        $cungIndex = ($startIndex + $offset + 12) % 12;
        if (isset(self::DIA_CHI[$cungIndex])) {
            $this->addSaoToCung(self::DIA_CHI[$cungIndex], $tenSao, $loaiSao);
        }
    }
    private function getChiGroupKey($chi, $isPhaToai = false, $isCoThan = false)
    {
        if ($isCoThan) {
            if (in_array($chi, ['Hợi', 'Tý', 'Sửu'])) return 'HoiTySuu';
            if (in_array($chi, ['Dần', 'Mão', 'Thìn'])) return 'DanMaoThin';
            if (in_array($chi, ['Tỵ', 'Ngọ', 'Mùi'])) return 'TiNgoMui';
            if (in_array($chi, ['Thân', 'Dậu', 'Tuất'])) return 'ThanDauTuat';
        }
        if ($isPhaToai) {
            if (in_array($chi, ['Dần', 'Thân', 'Tỵ', 'Hợi'])) return 'DanThanTiHoi';
            if (in_array($chi, ['Tý', 'Ngọ', 'Mão', 'Dậu'])) return 'TyNgoMaoDau';
            if (in_array($chi, ['Thìn', 'Tuất', 'Sửu', 'Mùi'])) return 'ThinTuatSuuMui';
        }
        if (in_array($chi, ['Dần', 'Ngọ', 'Tuất'])) return 'DanNgoTuat';
        if (in_array($chi, ['Thân', 'Tý', 'Thìn'])) return 'ThanTyThin';
        if (in_array($chi, ['Tỵ', 'Dậu', 'Sửu'])) return 'TiDauSuu';
        if (in_array($chi, ['Hợi', 'Mão', 'Mùi'])) return 'HoiMaoMui';
        return null;
    }
    static function jdFromLunarDate($lunarDay, $lunarMonth, $lunarYear, $lunarLeap)
    {
        $a11 = self::getLunarMonth11($lunarYear);
        $b11 = self::getLunarMonth11($lunarYear + 1);

        $off = $lunarMonth - 11;
        if ($off < 0) {
            $b11 = $a11;
            $a11 = self::getLunarMonth11($lunarYear - 1);
            $off = $lunarMonth + 12 - 11;
        }

        if ($lunarLeap != 0) {
            $leapMonth = self::getLeapMonthOffset($a11);
            if ($leapMonth != $lunarMonth) {
                // Không đúng tháng nhuận
                return 0;
            }
            $off++;
        }

        $k = self::getNewMoonIndex($a11);
        $monthStart = self::getNewMoonDay($k + $off);

        return $monthStart + $lunarDay - 1;
    }
    static function getNewMoonIndex($jd)
    {
        // Mốc thời gian là ngày Sóc (new moon) gần ngày 1/1/1900
        $T0 = 2415021.076998695;
        $synodicMonth = 29.530588853; // Độ dài trung bình của 1 chu kỳ trăng (synodic month)

        // Tính chỉ số sóc gần ngày $jd nhất
        return floor(($jd - $T0) / $synodicMonth);
    }

    static function getLeapMonthOffset($a11, $timeZone = 7.0)
    {
        $k = floor(($a11 - 2415021.076998695) / 29.530588853 + 0.5);
        $last = 0;
        $i = 1; // We start with the month following lunar month 11
        $arc = self::getSunLongitude(self::getNewMoonDay($k + $i, $timeZone), $timeZone);
        do {
            $last = $arc;
            $i = $i + 1;
            $arc = self::getSunLongitude(self::getNewMoonDay($k + $i, $timeZone), $timeZone);
        } while ($arc != $last && $i < 14);
        return $i - 1;
    }

    static function getLunarMonth11($yy, $timeZone = 7.0)
    {
        $off = self::jdFromDate(31, 12, $yy) - 2415021;
        $k = floor($off / 29.530588853);
        $nm = self::getNewMoonDay($k, $timeZone);
        $sunLong = self::getSunLongitude($nm, $timeZone); // sun longitude at local midnight
        if ($sunLong >= 9) {
            $nm = self::getNewMoonDay($k - 1, $timeZone);
        }
        return $nm;
    }
    static function getSunLongitude($jdn, $timeZone = 7.0)
    {
        $T = ($jdn - 2451545.5 - $timeZone / 24) / 36525; // Time in Julian centuries from 2000-01-01 12:00:00 GMT
        $T2 = $T * $T;
        $dr = M_PI / 180; // degree to radian
        $M = 357.52910 + 35999.05030 * $T - 0.0001559 * $T2 - 0.00000048 * $T * $T2; // mean anomaly, degree
        $L0 = 280.46645 + 36000.76983 * $T + 0.0003032 * $T2; // mean longitude, degree
        $DL = (1.914600 - 0.004817 * $T - 0.000014 * $T2) * sin($dr * $M);
        $DL = $DL + (0.019993 - 0.000101 * $T) * sin($dr * 2 * $M) + 0.000290 * sin($dr * 3 * $M);
        $L = $L0 + $DL; // true longitude, degree
        //echo "\ndr = $dr, M = $M, T = $T, DL = $DL, L = $L, L0 = $L0\n";
        // obtain apparent longitude by correcting for nutation and aberration
        $omega = 125.04 - 1934.136 * $T;
        $L = $L - 0.00569 - 0.00478 * sin($omega * $dr);
        $L = $L * $dr;
        $L = $L - M_PI * 2 * (floor($L / (M_PI * 2))); // Normalize to (0, 2*PI)
        return floor($L / M_PI * 6);
    }
    static function getNewMoonDay($k, $timeZone = 7.0)
    {
        $T = $k / 1236.85; // Time in Julian centuries from 1900 January 0.5
        $T2 = $T * $T;
        $T3 = $T2 * $T;
        $dr = M_PI / 180;
        $Jd1 = 2415020.75933 + 29.53058868 * $k + 0.0001178 * $T2 - 0.000000155 * $T3;
        $Jd1 = $Jd1 + 0.00033 * sin((166.56 + 132.87 * $T - 0.009173 * $T2) * $dr); // Mean new moon
        $M = 359.2242 + 29.10535608 * $k - 0.0000333 * $T2 - 0.00000347 * $T3; // Sun's mean anomaly
        $Mpr = 306.0253 + 385.81691806 * $k + 0.0107306 * $T2 + 0.00001236 * $T3; // Moon's mean anomaly
        $F = 21.2964 + 390.67050646 * $k - 0.0016528 * $T2 - 0.00000239 * $T3; // Moon's argument of latitude
        $C1 = (0.1734 - 0.000393 * $T) * sin($M * $dr) + 0.0021 * sin(2 * $dr * $M);
        $C1 = $C1 - 0.4068 * sin($Mpr * $dr) + 0.0161 * sin($dr * 2 * $Mpr);
        $C1 = $C1 - 0.0004 * sin($dr * 3 * $Mpr);
        $C1 = $C1 + 0.0104 * sin($dr * 2 * $F) - 0.0051 * sin($dr * ($M + $Mpr));
        $C1 = $C1 - 0.0074 * sin($dr * ($M - $Mpr)) + 0.0004 * sin($dr * (2 * $F + $M));
        $C1 = $C1 - 0.0004 * sin($dr * (2 * $F - $M)) - 0.0006 * sin($dr * (2 * $F + $Mpr));
        $C1 = $C1 + 0.0010 * sin($dr * (2 * $F - $Mpr)) + 0.0005 * sin($dr * (2 * $Mpr + $M));
        if ($T < -11) {
            $deltat = 0.001 + 0.000839 * $T + 0.0002261 * $T2 - 0.00000845 * $T3 - 0.000000081 * $T * $T3;
        } else {
            $deltat = -0.000278 + 0.000265 * $T + 0.000262 * $T2;
        };
        $JdNew = $Jd1 + $C1 - $deltat;
        //echo "JdNew = $JdNew\n";
        return floor($JdNew + 0.5 + $timeZone / 24);
    }
    static function jdFromDate($dd, $mm, $yy)
    {
        // Xác định xem tháng có nhỏ hơn hoặc bằng 2 hay không.
        // Nếu tháng <= 2, thì dịch tháng về cuối năm trước (tháng 13, 14)
        // Điều này giúp việc tính toán chính xác hơn khi xử lý ngày trong năm nhuận.
        $a = floor((14 - $mm) / 12);

        // Điều chỉnh năm theo cách tính của lịch Julian & Gregorian.
        // Nếu tháng < 3, thì coi như thuộc về năm trước.
        $y = $yy + 4800 - $a;

        // Điều chỉnh tháng (chuyển tháng 1 & 2 thành tháng 13 & 14 của năm trước)
        $m = $mm + 12 * $a - 3;

        // Công thức tính số ngày Julian (JD) dựa trên lịch Gregory
        $jd = $dd
            + floor((153 * $m + 2) / 5)  // Tính số ngày đã trôi qua trong năm dựa trên số tháng
            + 365 * $y                   // Cộng số ngày của tất cả các năm đã qua
            + floor($y / 4)              // Thêm ngày nhuận (cứ 4 năm thêm 1 ngày)
            - floor($y / 100)            // Trừ đi năm không nhuận (cứ 100 năm không nhuận 1 lần)
            + floor($y / 400)            // Cộng lại những năm nhuận bị loại trừ ở bước trên (cứ 400 năm có 1 năm nhuận)
            - 32045;                     // Điều chỉnh để phù hợp với hệ Julian Date

        // Nếu ngày cần tính là trước 15/10/1582 (trước khi lịch Gregory được áp dụng)
        // thì sử dụng công thức Julian cũ (không có điều chỉnh năm nhuận đặc biệt)
        if ($jd < 2299161) {
            $jd = $dd
                + floor((153 * $m + 2) / 5)
                + 365 * $y
                + floor($y / 4)  // Chỉ áp dụng quy tắc năm nhuận Julian (cứ 4 năm là năm nhuận)
                - 32083;         // Điều chỉnh cho hệ thống Julian Date cũ
        }

        return $jd;  // Trả về kết quả là Julian Day tương ứng với ngày đã nhập
    }
    private  static $hang_can = [
        0 => 'Giáp',
        1 => 'Ất',
        2 => 'Bính',
        3 => 'Đinh',
        4 => 'Mậu',
        5 => 'Kỷ',
        6 => 'Canh',
        7 => 'Tân',
        8 => 'Nhâm',
        9 => 'Quý',
    ];
    private  static $hang_chi = [
        0 => 'Tý',
        1 => 'Sửu',
        2 => 'Dần',
        3 => 'Mão',
        4 => 'Thìn',
        5 => 'Tỵ',
        6 => 'Ngọ',
        7 => 'Mùi',
        8 => 'Thân',
        9 => 'Dậu',
        10 => 'Tuất',
        11 => 'Hợi',
    ];
    static function canchiNgayByJD($jd)
    {
        //Cho N là số ngày Julius của ngày dd/mm/yyyy. Ta chia N+9 cho 10. Số dư 0 là Giáp, 1 là Ất v.v. Để tìm Chi, chia N+1 cho 12; số dư 0 là Tý, 1 là Sửu v.v.
        return self::$hang_can[($jd + 9) % 10] . ' ' . self::$hang_chi[($jd + 1) % 12];
    }
    static function canchiNgay($yy, $mm, $dd)
    {
        $dl = CustomLunarConverterHelper::convertSolarToLunar($dd, $mm, $yy, 0);
        $jd = self::jdFromDate($dl[0], $dl[1], $dl[2]);
        return self::$hang_can[($jd + 9) % 10] . ' ' . self::$hang_chi[($jd + 1) % 12];
    }

    static function canchiThang($yy, $mm)
    {
        //Trong một năm âm lịch, tháng 11 là tháng Tý, tháng 12 là Sửu, tháng Giêng là tháng Dần v.v. Can của tháng M năm Y âm lịch được tính theo công thức sau: chia Y*12+M+3 cho 10. Số dư 0 là Giáp, 1 là Ất v.v.
        $thang = $mm < 11 ? $mm + 1 : $mm - 11;
        return self::$hang_can[($yy * 12 + $mm + 3) % 10] . ' ' . self::$hang_chi[$thang];
    }

    static function canchiNam($yy)
    {
        //Để tính Can của năm Y, tìm số dư của Y+6 chia cho 10. Số dư 0 là Giáp, 1 là Ất v.v. Để tính Chi của năm, chia Y+8 cho 12. Số dư 0 là Tý, 1 là Sửu, 2 là Dần v.v.
        return self::$hang_can[($yy + 6) % 10] . ' ' . self::$hang_chi[($yy + 8) % 12];
    }
}
