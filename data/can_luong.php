<?php
// data/can_luong.php - Bảng tra cứu Cân lường theo dữ liệu chính xác

return [
    // 2.1. Theo năm sinh (Can Chi)
    'TheoNamSinh' => [
        'Giáp Tý' => 1.2,   'Bính Tý' => 1.6,   'Mậu Tý' => 1.5,    'Canh Tý' => 0.7,   'Nhâm Tý' => 0.5,
        'Ất Sửu' => 0.9,    'Đinh Sửu' => 0.8,  'Kỷ Sửu' => 0.8,    'Tân Sửu' => 0.7,   'Quý Sửu' => 0.5,
        'Bính Dần' => 0.6,  'Mậu Dần' => 0.8,   'Canh Dần' => 0.9,   'Nhâm Dần' => 0.9,  'Giáp Dần' => 1.2,
        'Đinh Mão' => 0.7,  'Kỷ Mão' => 1.9,    'Tân Mão' => 1.2,    'Quý Mão' => 1.2,   'Ất Mão' => 0.8,
        'Mậu Thìn' => 1.2,  'Canh Thìn' => 1.2, 'Nhâm Thìn' => 1.0,  'Giáp Thìn' => 0.8, 'Bính Thìn' => 0.8,
        'Kỷ Tỵ' => 0.5,     'Tân Tỵ' => 0.6,    'Quý Tỵ' => 0.7,     'Ất Tỵ' => 0.7,     'Đinh Tỵ' => 0.6,
        'Canh Ngọ' => 0.9,  'Nhâm Ngọ' => 0.8,  'Giáp Ngọ' => 1.5,   'Bính Ngọ' => 1.3,  'Mậu Ngọ' => 1.9,
        'Tân Mùi' => 0.8,   'Quý Mùi' => 0.7,   'Ất Mùi' => 0.6,     'Đinh Mùi' => 0.5,  'Kỷ Mùi' => 0.6,
        'Nhâm Thân' => 0.7, 'Giáp Thân' => 0.5, 'Bính Thân' => 0.5,  'Mậu Thân' => 1.4,  'Canh Thân' => 0.8,
        'Quý Dậu' => 0.8,   'Ất Dậu' => 1.5,    'Đinh Dậu' => 1.4,   'Kỷ Dậu' => 0.5,    'Tân Dậu' => 1.6,
        'Giáp Tuất' => 0.5, 'Bính Tuất' => 0.6, 'Mậu Tuất' => 1.4,   'Canh Tuất' => 0.9, 'Nhâm Tuất' => 1.0,
        'Ất Hợi' => 0.9,    'Đinh Hợi' => 1.6,  'Kỷ Hợi' => 0.9,     'Tân Hợi' => 1.7,   'Quý Hợi' => 0.7,
    ],

    // 2.2. Theo tháng sinh
    'TheoThangSinh' => [
        1 => 0.6,   // Tháng Một
        2 => 0.7,   // Tháng Hai
        3 => 1.8,   // Tháng Ba
        4 => 0.9,   // Tháng Tư
        5 => 0.5,   // Tháng Năm
        6 => 1.6,   // Tháng Sáu
        7 => 0.9,   // Tháng Bảy
        8 => 1.5,   // Tháng Tám
        9 => 1.8,   // Tháng Chín
        10 => 1.8,  // Tháng Mười
        11 => 0.9,  // Tháng Mười Một
        12 => 0.5,  // Tháng Mười Hai
    ],

    // 2.3. Theo ngày sinh
    'TheoNgaySinh' => [
        1 => 0.5,   2 => 1.0,   3 => 0.8,   4 => 1.5,   5 => 1.5,
        6 => 1.5,   7 => 0.8,   8 => 1.6,   9 => 0.8,   10 => 1.6,
        11 => 0.9,  12 => 1.7,  13 => 0.8,  14 => 1.7,  15 => 1.0,
        16 => 0.8,  17 => 0.9,  18 => 1.8,  19 => 0.5,  20 => 1.5,
        21 => 1.0,  22 => 0.9,  23 => 0.8,  24 => 0.9,  25 => 1.5,
        26 => 1.8,  27 => 0.7,  28 => 0.8,  29 => 1.6,  30 => 0.6,
    ],

    // 2.4. Theo giờ sinh (địa chi)
    'TheoGioSinh' => [
        'Tý' => 1.6,    'Sửu' => 0.6,   'Dần' => 0.7,   'Mão' => 1.0,
        'Thìn' => 0.9,  'Tỵ' => 1.6,    'Ngọ' => 1.0,   'Mùi' => 0.8,
        'Thân' => 0.8,  'Dậu' => 0.9,   'Tuất' => 0.6,  'Hợi' => 0.6,
    ],

    // Bảng giải thích trọng lượng (theo dữ liệu chính thống)
    'GiaiThichTrongLuong' => [
        // 7 lượng
        '7.1' => 'Sinh ra với số mệnh phi thường, được ban cho nhiều khanh tướng công hầu, sống trong sự tiêu diêu khoái lạc của phúc báo, cực phẩm hưng long vô song.',
        '7.0' => 'Phúc lớn như biển, không phải lo lắng gì cả, y lộc do trời ban cho không thể thay đổi, một đời vinh quang phú quý không ai sánh bằng.',

        // 6 lượng 9 chỉ - 6 lượng 0 chỉ
        '6.9' => 'Một ngôi sao may mắn trên trần gian, một thân giàu sang phú quý, mọi người đều tôn trọng. nói chung, là phước lộc do trời ban tặng, sống trong hạnh phúc vinh hiển suốt cuộc đời.',
        '6.8' => 'Phú quý do trời phú cho, không cần vất vả, gia sản có đầy ắp; nhưng sau mười năm lại không còn như xưa kia, phước lộc tổ tiên tan biến như chiếc thuyền giữa biển cả giông tố.',
        '6.7' => 'Sinh ra đã được trời ban phước báo, ruộng đất gia sản thật thịnh vượng, suốt đời giàu sang vinh diệu, mọi sự an lành viên mãn.',
        '6.6' => 'Phú quý do trời sắp đặt sẵn rồi, phước lộc vượt qua mọi người, quan vị cao sang uy quyền, châu báu ngập tràn khắp nơi, sung sướng cùng vợ con.',
        '6.5' => 'Nhìn ra thì phước lộc không hề ít ỏi, tài năng giúp ích cho nước nhà, công lao an bình cho dân chúng; chức tước cao quý trong triều đình, giàu sang không thiếu thứ gì cả, danh tiếng lan tỏa khắp thiên hạ.',
        '6.4' => 'Giàu sang vinh diệu không ai sánh được; uy quyền quyền lực không ai bằng phẳng. Mặc áo tím đeo đai vàng, ngồi ngôi cao nhất trong triều, suốt đời sung sướng vui vẻ.',
        '6.3' => 'Thi đỗ cao cấp, làm quan trọng, giàu sang vô cùng, được khen ngợi khắp nơi; phước lộc vô biên, gia đình hưng thịnh.',
        '6.2' => 'Phước lộc không tận, học hành thành tài, làm cho cha mẹ tự hào, mặc áo gấm đeo đai vàng, giàu sang vinh diệu, mọi sự sung túc.',
        '6.1' => 'Trí tuệ sáng suốt, học tập nhiều mặt, tự thân vinh quang, tên thi vào bảng danh dự. Dù không làm quan cao cấp, cũng chắc chắn là một nhà giàu có.',
        '6.0' => 'Tên thi vào bảng cao nhất, gây dựng công danh to lớn, mang lại vinh hiển cho gia tộc, ruộng đất gia sản thịnh vượng, sức khỏe dồi dào.',

        // 5 lượng 9 chỉ - 5 lượng 0 chỉ
        '5.9' => 'Người có số này là người tài hoa xuất chúng, thân thể mềm mại nhưng linh hồn thanh khiết. Họ có phận trời ban, học vấn cao siêu, đậu đạt các kỳ thi danh giá, được phong quan tước chức cao sang.',
        '5.8' => 'Người giàu sang phú quý, quyền thế uy nghi, được trời ưu ái ban cho phước lộc suốt đời. Họ có cuộc sống an nhàn sung túc, danh vọng kiêu ngạo, tài lộc dồi dào, phú thọ viên mãn.',
        '5.7' => 'Hưởng phước trọn vẹn, mọi việc đều thuận lợi, quang vinh tổ tiên, oai hùng tự tại. Họ được mọi người kính nể yêu mến, riêng mình thưởng thức bầu trời xanh.',
        '5.6' => 'Hiếu đạo thông minh, cuộc đời an khang phước đức; trải qua nhiều thăng trầm, nguồn tài lợi thì vô biên, bình an và hậu duệ.',
        '5.5' => 'Lúc trẻ luôn bôn ba khổ sở trên con đường danh vọng, nhưng công lao không bằng sự may mắn. Đến một ngày kia, phước lộc sẽ ùa về như nước triều dâng, rồi tự nhiên giàu có vinh quang.',
        '5.4' => 'Tính cách chính trực và cao thượng, học tập chăm chỉ, ăn mặc thanh lịch, tự nhiên an bình, chính là người có phúc khí trên đời.',
        '5.3' => 'Xem ra tính tình chân thành, công việc gia đình mà thành công cũng nhờ vào đó. Phước lộc suốt đời có số mệnh sắp đặt sẵn hoa lệ phú quý.',
        '5.2' => 'Cuộc đời hạnh phúc, việc gì cũng tốt lành, chẳng cần vất vả mà tự nhiên yên vui. Họ hàng thân thuộc thảy đều ủng hộ; sự nghiệp thăng tiến.',
        '5.1' => 'Cuộc đời rực rỡ, mọi việc thảy đều thuận buồm xuôi gió, chẳng cần gắng sức mà tự nhiên hạnh phúc. Anh em bạn bè đều hòa thuận như ý, gia sản và phước lộc đặng trọn vẹn.',
        '5.0' => 'Ngày ngày chỉ lao tâm khổ tứ về mặt công danh tài lợi. Lúc nửa đời cũng có nhiều lần gặp phước lộc; đến già có vì sao Tài Tinh chiếu sáng sẽ đặng sống nhàn hạ.',

        // 4 lượng 9 chỉ - 4 lượng 0 chỉ
        '4.9' => 'Phúc lộc vô biên, do chính tay mình gầy dựng nên sự nghiệp vinh quang cho gia đình. Người giàu sang đều kính nể. Cuộc đời sung túc hạnh phúc.',
        '4.8' => 'Khó khăn cả đời, từ khi còn trẻ cho đến khi già, chẳng có gì thịnh vượng. Anh em họ hàng không giúp đỡ được gì. Chỉ khi về già mới có chút an ổn.',
        '4.7' => 'Giàu sang khi tuổi xế chiều, vợ con phú quý, nhờ có phước báu tích lũy như nước chảy về.',
        '4.6' => 'May mắn mọi nơi, nhất là khi thay đổi họ hoặc dời nhà thì càng thêm thịnh vượng. Ăn mặc no đủ do số trời ban. Từ nửa đời trở đi cho đến khi già đều ổn định bình an.',
        '4.5' => 'Gian nan về công danh lợi lộc, trước phải chịu nhiều khổ cực, sau này cũng lang thang; số ít con cái vì khó nuôi dưỡng; anh em ruột thịt cũng không giúp ích được nhiều.',
        '4.4' => 'Do trời ban phước lộc, không cần lo lắng gì nhiều. Phúc lộc sau này sẽ hơn nhiều so với trước. Dù rằng khi trẻ khó có được tài lộc và sự sung sướng, nhưng khi già sẽ được yên bình.',
        '4.3' => 'Thông minh tài giỏi, tự tin trước người sang quý, phúc lộc do trời ban, không cần vất vả nhưng mọi việc đều suôn sẻ.',
        '4.2' => 'Được nhiều điều mong muốn. Từ nửa đời trở đi thì vận mệnh sẽ tốt hơn, lúc đó tài lộc công danh sẽ phát triển mạnh mẽ.',
        '4.1' => 'Tài ba nhưng không ổn định, công việc không có gì giống nhau; từ nửa đời bắt đầu suy thoái phước tiêu diêu, không còn như xưa kia chưa thành công.',
        '4.0' => 'Phúc lộc bền vững, nhưng trước trải qua nhiều sóng gió khó khăn. Sau này sẽ được hưởng thụ cuộc sống an nhàn giàu có.',

        // 3 lượng 9 chỉ - 3 lượng 0 chỉ
        '3.9' => 'Đường đời gian nan trắc trở, dù có cố gắng vẫn không thành công. Bao nhiêu công sức và tâm huyết xây dựng nên sự nghiệp nhưng cuối cùng lại tan thành mây khói.',
        '3.8' => 'Tính tình cao thượng, từ 36 tuổi trở đi sẽ gặp nhiều may mắn. Sẽ giàu sang phú quý, được người ngưỡng mộ và kính trọng.',
        '3.7' => 'Không có duyên với việc làm, anh em thân thuộc không giúp đỡ. Chỉ sống nhờ vào gia sản của tổ tiên, nhưng cũng không bền lâu. Khi đi xa thì không biết khi nào mới trở về.',
        '3.6' => 'Cả đời không cần vất vả quá nhiều, chỉ cần một tay làm chủ được cơ đồ. Có phúc khí to lớn, dù có gặp khó khăn cũng sẽ vượt qua được. Sẽ có tài lộc dồi dào và hạnh phúc.',
        '3.5' => 'Phúc đức trong đời không hoàn thiện, không được hưởng trọn vẹn phúc lộc do tổ tiên để lại. Phải chờ đợi thời cơ mới no đủ hơn xưa.',
        '3.4' => 'Có phúc khí tu tập, xa quê và cha mẹ tìm đến chỗ Phật, hàng ngày niệm Phật mới mong được an lành và viên mãn.',
        '3.3' => 'Đầu đời việc làm khó thành công, mưu tính cũng không hiệu quả. Từ nửa đời trở đi mới có vận may tốt hơn, sẽ có tài lộc phát triển nhiều hơn.',
        '3.2' => 'Năm xưa gặp nhiều rủi ro, khó khăn trong việc làm. Sau này tài lợi sẽ chảy về như nước. Nửa sau cuộc đời sẽ sung túc, công danh lợi lộc thuận buồm xuôi gió.',
        '3.1' => 'Sinh kế gian khổ vất vả, khó có thể dựa vào gia sản của tổ tiên để xây dựng nhà cửa. Nửa sau cuộc đời mới có đủ ăn đủ mặc.',
        '3.0' => 'Lao lực suốt đời, khổ sở, chăm chỉ kiếm tiền nhưng đến già cũng chỉ Giảm bớt chút ít phiền muộn mà thôi.',

        // 2 lượng 9 chỉ - 2 lượng 2 chỉ
        '2.9' => 'Ngày xưa vất vả với cuộc đời, chưa có duyên nợ để thành công sớm, công danh chậm chạp, phải đến 40 tuổi mới được yên bình, thay đổi nơi ở hoặc họ tên mới có may mắn.',
        '2.8' => 'Làm ăn bừa bãi không có tổ chức, sản nghiệp của tổ tiên như một giấc mơ xa vời. Nếu không nhận làm con nuôi hoặc không đổi họ tên thì chắc chắn di cư đi nhiều lần trong đời.',
        '2.7' => 'Tự mình lo toan suốt đời, khó gặp được người giúp đỡ, không thể dựa vào phúc đức của tổ tiên để vững vàng. Quanh năm tự lực cánh sinh, từ nhỏ đến già cũng không có gì đáng nhớ.',
        '2.6' => 'Số phận khốn khổ, một mình vật lộn với cuộc sống. Rời xa quê hương đất nước mới kiếm được miếng ăn, có lẽ chỉ khi già mới được sống an nhàn một chút.',
        '2.5' => 'Do tổ nghiệp suy yếu, khó xây dựng được gia đình hạnh phúc, họ hàng thân thích gặp nhiều phiền toái, cả đời khổ cực, chỉ biết tự lo cho bản thân.',
        '2.4' => 'Không có phúc lộc trong gia đình, khó mà thành công trong sự nghiệp, không có sự giúp đỡ của họ hàng thân thuộc, lang thang khắp nơi để kiếm sống tới khi tuổi già.',
        '2.3' => 'Dù có cố gắng làm việc gì cũng khó mà thành công, không có sự ủng hộ của anh em họ hàng, cuối cùng chỉ biết chịu số phận đi xa quê hương để tìm kiếm miếng cơm manh áo.',
        '2.2' => 'Do thân hàn cốt lạnh, khổ não tận tâm can, quanh năm lo toan kiếm ăn trong nghèo khó, nếu không cẩn thận trở thành kẻ lang bạt do số mệnh quyết định.'
    ]
];