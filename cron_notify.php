<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Content-Type: text/html; charset=UTF-8");

date_default_timezone_set('Asia/Ho_Chi_Minh');

include 'db.php';

// 1. CẤU HÌNH BOT TELEGRAM
$default_telegram_token   = '8987907075:AAEJFwpZoiH65oO0wh6lP2fNBbNhtv3-cHE'; // Điền token của bạn
$default_telegram_chat_id = '1733868980';       // Điền chat_id của bạn

$settings = [
    'kid1_name'         => 'Trâm Anh',
    'kid1_class'        => '6A3',
    'kid2_name'         => 'Thành Phát',
    'kid2_class'        => '10A4',
    'telegram_token'    => $default_telegram_token,
    'telegram_chat_id'  => $default_telegram_chat_id,
    'semester'          => 'HK1',
    'school_year'       => '2026-2027'
];

try {
    $st_set = $conn->query("SELECT setting_key, setting_val FROM app_settings");
    if ($st_set) {
        $db_settings = $st_set->fetchAll(PDO::FETCH_KEY_PAIR);
        $settings = array_merge($settings, $db_settings);
    }
} catch (Exception $e) {}

$bot_token = !empty($settings['telegram_token']) ? $settings['telegram_token'] : $default_telegram_token;
$chat_id   = !empty($settings['telegram_chat_id']) ? $settings['telegram_chat_id'] : $default_telegram_chat_id;

function sendTelegram($text, $token, $chat_id) {
    $url = "https://api.telegram.org/bot{$token}/sendMessage";
    $post_fields = [
        'chat_id'                  => $chat_id,
        'text'                     => $text,
        'parse_mode'               => 'HTML',
        'disable_web_page_preview' => true
    ];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_fields));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $res = curl_exec($ch);
    curl_close($ch);
    return $res;
}

// Lấy dự báo thời tiết Ninh Hòa lúc tan học
function getWeatherForecastNinhHoa($target_hour) {
    try {
        $url = 'https://api.open-meteo.com/v1/forecast?latitude=12.5000&longitude=109.1333&hourly=temperature_2m,precipitation,precipitation_probability&timezone=Asia%2FBangkok';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 8);
        $res = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($res, true);
        if (!$data || !isset($data['hourly']['time'])) return null;
        
        $today_str = date('Y-m-d');
        foreach ($data['hourly']['time'] as $i => $time_str) {
            if (strpos($time_str, $today_str) === 0) {
                $f_hour = (int)date('H', strtotime($time_str));
                if ($f_hour == $target_hour) {
                    $temp = round($data['hourly']['temperature_2m'][$i] ?? 30);
                    $rain_prob = $data['hourly']['precipitation_probability'][$i] ?? 0;
                    $precip = $data['hourly']['precipitation'][$i] ?? 0;
                    
                    if ($precip > 0.1 || $rain_prob >= 50) {
                        return "🌧️ <b>Thời tiết lúc tan học:</b> Có mưa ({$temp}°C, xác suất mưa {$rain_prob}%). Ba mẹ nhớ chuẩn bị áo mưa đón con nhé!";
                    } elseif ($temp >= 33) {
                        return "☀️ <b>Thời tiết lúc tan học:</b> Nắng gắt ({$temp}°C). Ba mẹ nhớ mang áo khoác, nón mũ che nắng cho con!";
                    } else {
                        return "⛅ <b>Thời tiết lúc tan học:</b> Dịu mát, tạnh ráo ({$temp}°C), thuận lợi đón con.";
                    }
                }
            }
        }
    } catch (Exception $e) {}
    return null;
}

// Tính phút kết thúc tiết học
function parseSlotMinutes($buoi, $tiet, $note, $subject) {
    $customText = $note . ' ' . $subject . ' ' . $tiet;
    if (preg_match('/(\d{1,2})[:hH](\d{2})\s*[~-]\s*(\d{1,2})[:hH](\d{2})/', $customText, $m)) {
        return ['start' => (int)$m[1] * 60 + (int)$m[2], 'end' => (int)$m[3] * 60 + (int)$m[4]];
    }
    $num = (int)preg_replace('/\D/', '', $tiet) ?: 1;
    $b = strtoupper($buoi);
    if (strpos($b, 'SÁNG') !== false || strpos($b, 'SANG') !== false) {
        $times = [[435, 480], [485, 530], [545, 590], [600, 645], [650, 695]];
        return $times[$num - 1] ?? [435, 675];
    } elseif (strpos($b, 'CHIỀU') !== false || strpos($b, 'CHIEU') !== false) {
        $times = [[810, 855], [860, 905], [920, 965], [970, 1015], [1020, 1065]];
        return $times[$num - 1] ?? [810, 1020];
    }
    return [1080, 1170];
}

$current_hour   = (int)date('H');
$current_minute = (int)date('i');
$current_total_min = $current_hour * 60 + $current_minute;

$current_w  = (int)date('w');
$today_col  = ($current_w == 0) ? 'cn' : 'thu' . ($current_w + 1);

// =========================================================================
// 1. GỬI LỊCH HỌC NGÀY MAI LÚC 19H (HOẶC KHI GỌI ?test=1)
// =========================================================================
if (($current_hour >= 19 && $current_hour < 20) || isset($_GET['test'])) {
    $tomorrow_ts = strtotime('+1 day');
    $tomorrow_w  = (int)date('w', $tomorrow_ts);
    $days_map = [
        1 => ['col' => 'thu2', 'name' => 'Thứ Hai'],
        2 => ['col' => 'thu3', 'name' => 'Thứ Ba'],
        3 => ['col' => 'thu4', 'name' => 'Thứ Tư'],
        4 => ['col' => 'thu5', 'name' => 'Thứ Năm'],
        5 => ['col' => 'thu6', 'name' => 'Thứ Sáu'],
        6 => ['col' => 'thu7', 'name' => 'Thứ Bảy'],
        0 => ['col' => 'cn',   'name' => 'Chủ Nhật']
    ];
    $tomorrow_info = $days_map[$tomorrow_w];
    $col_name = $tomorrow_info['col'];
    $day_title = $tomorrow_info['name'] . ', ngày ' . date('d/m/Y', $tomorrow_ts);

    $teachers_map = [];
    $stmt_tc = $conn->query("SELECT * FROM teachers");
    if ($stmt_tc) {
        foreach ($stmt_tc->fetchAll(PDO::FETCH_ASSOC) as $tc) {
            $teachers_map[$tc['id']] = $tc;
        }
    }

    $stmt = $conn->prepare("SELECT * FROM schedule ORDER BY FIELD(buoi, 'SÁNG', 'CHIỀU', 'TỐI'), id ASC");
    $stmt->execute();
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $kids_schedule = [
        '1' => ['name' => $settings['kid1_name'], 'class' => $settings['kid1_class'], 'items' => []],
        '2' => ['name' => $settings['kid2_name'], 'class' => $settings['kid2_class'], 'items' => []]
    ];
    $total_slots = 0;

    foreach ($schedules as $row) {
        $be_id = (string)$row['be_name'];
        $raw_content = trim($row[$col_name] ?? '');
        if (!empty($raw_content) && $raw_content !== '-') {
            $lines = array_values(array_filter(explode("\n", str_replace("\r", "", $raw_content))));
            $subject = trim($lines[0] ?? '');
            $note = isset($lines[1]) ? trim(implode(' ', array_slice($lines, 1))) : '';
            $teacher_id = $row['t_' . $col_name] ?? null;

            if (isset($kids_schedule[$be_id])) {
                $kids_schedule[$be_id]['items'][] = [
                    'buoi' => $row['buoi'], 'tiet' => $row['tiet'], 'subject' => $subject,
                    'note' => $note, 'teacher' => $teachers_map[$teacher_id] ?? null
                ];
                $total_slots++;
            }
        }
    }

    $msg = "📅 <b>LỊCH HỌC NGÀY MAI ({$day_title})</b>\n";
    $msg .= "<i>" . htmlspecialchars($settings['semester']) . " • " . htmlspecialchars($settings['school_year']) . "</i>\n";
    $msg .= "━━━━━━━━━━━━━━━━━━\n\n";

    if ($total_slots === 0) {
        $msg .= "🎉 <b>Ngày mai cả 2 bé đều được nghỉ học!</b>\n";
    } else {
        foreach ($kids_schedule as $k_id => $data) {
            $icon = ($k_id == '1') ? '👧' : '👦';
            $msg .= "{$icon} <b>Bé " . htmlspecialchars($data['name']) . " (Lớp " . htmlspecialchars($data['class']) . ")</b>\n";
            if (empty($data['items'])) {
                $msg .= "<i>Ngày mai bé không có tiết học nào.</i>\n\n";
                continue;
            }
            $current_buoi = '';
            foreach ($data['items'] as $item) {
                if ($item['buoi'] !== $current_buoi) {
                    $current_buoi = $item['buoi'];
                    $msg .= "  ☀️ <u>Buổi {$current_buoi}</u>:\n";
                }
                $clean_tiet = trim(explode('(', $item['tiet'])[0]);
                $msg .= "  • <b>{$clean_tiet}</b>: <b>" . htmlspecialchars($item['subject']) . "</b>";
                if (!empty($item['teacher'])) {
                    $msg .= " <i>(GV: " . htmlspecialchars($item['teacher']['teacher_name']) . ")</i>";
                }
                $msg .= "\n";
                if (!empty($item['note'])) {
                    $msg .= "    💬 <i>Dặn dò: " . htmlspecialchars($item['note']) . "</i>\n";
                }
            }
            $msg .= "\n";
        }
    }
    $msg .= "⏰ <i>Tin nhắn gửi tự động lúc 19:00 hàng ngày.</i>";
    echo sendTelegram($msg, $bot_token, $chat_id);
    exit;
}

// =========================================================================
// 2. BÁO GIỜ ĐÓN BÉ TRƯỚC 30 PHÚT KÈM THỜI TIẾT (HOẶC KHI GỌI ?test_pickup=1)
// =========================================================================
// =========================================================================
// 2. BÁO GIỜ ĐÓN TỪNG BÉ TRƯỚC 30 PHÚT KÈM THỜI TIẾT (SÁNG / CHIỀU)
// =========================================================================
$stmt = $conn->prepare("SELECT * FROM schedule WHERE be_name IN ('1','2')");
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lưu giờ tan học theo từng bé: [kid_id][buoi] => end_minutes
$kid_dismiss = [
    '1' => ['name' => $settings['kid1_name'], 'SANG' => 0, 'CHIEU' => 0],
    '2' => ['name' => $settings['kid2_name'], 'SANG' => 0, 'CHIEU' => 0]
];

foreach ($rows as $row) {
    $raw_content = trim($row[$today_col] ?? '');
    if (!empty($raw_content) && $raw_content !== '-') {
        $lines = array_values(array_filter(explode("\n", str_replace("\r", "", $raw_content))));
        $time_info = parseSlotMinutes($row['buoi'], $row['tiet'], $lines[1] ?? '', $lines[0] ?? '');
        $b = strtoupper($row['buoi']);
        
        $session = (strpos($b, 'SÁNG') !== false || strpos($b, 'SANG') !== false) ? 'SANG' : ((strpos($b, 'CHIỀU') !== false || strpos($b, 'CHIEU') !== false) ? 'CHIEU' : '');
        $be_id = (string)$row['be_name'];
        
        if ($session && isset($kid_dismiss[$be_id])) {
            if ($time_info['end'] > $kid_dismiss[$be_id][$session]) {
                $kid_dismiss[$be_id][$session] = $time_info['end'];
            }
        }
    }
}

$today_stamp = date('Ymd');
$is_test_pickup = isset($_GET['test_pickup']);

// Gom các bé có cùng khung giờ tan học (lệch nhau dưới 15 phút sẽ gộp 1 tin)
foreach (['SANG' => 'Buổi Sáng', 'CHIEU' => 'Buổi Chiều'] as $sess_key => $sess_name) {
    $handled_kids = [];

    foreach (['1', '2'] as $k_id) {
        if (in_array($k_id, $handled_kids)) continue;

        $d_min = $kid_dismiss[$k_id][$sess_key];
        
        if ($d_min > 0 || $is_test_pickup) {
            $diff = $d_min - $current_total_min;
            
            // Nhắc trước 30 phút (cửa sổ 20 đến 35 phút)
            if (($diff >= 20 && $diff <= 35) || $is_test_pickup) {
                $kids_to_pick = [$kid_dismiss[$k_id]['name']];
                $handled_kids[] = $k_id;

                // Kiểm tra xem bé kia có tan cùng lúc hoặc lệch dưới 15p không
                $other_id = ($k_id === '1') ? '2' : '1';
                $other_d_min = $kid_dismiss[$other_id][$sess_key];
                if ($other_d_min > 0 && abs($other_d_min - $d_min) <= 15) {
                    $kids_to_pick[] = $kid_dismiss[$other_id]['name'];
                    $handled_kids[] = $other_id;
                    $d_min = max($d_min, $other_d_min);
                }

                $lock_key = implode('_', $handled_kids);
                $lock_file = sys_get_temp_dir() . "/pickup_{$sess_key}_{$lock_key}_{$today_stamp}.lock";

                if (!file_exists($lock_file) || $is_test_pickup) {
                    if (!$is_test_pickup) {
                        file_put_contents($lock_file, time());
                    }

                    if ($d_min <= 0) {
                        $d_min = ($sess_key === 'SANG') ? (11 * 60 + 15) : (17 * 60);
                        $diff = 30;
                    }

                    $d_h = str_pad(floor($d_min / 60), 2, '0', STR_PAD_LEFT);
                    $d_m = str_pad($d_min % 60, 2, '0', STR_PAD_LEFT);
                    $time_str = "{$d_h}:{$d_m}";

                    $weather_text = getWeatherForecastNinhHoa((int)$d_h);
                    $kids_str = implode(' và ', $kids_to_pick);

                    $alert_msg = ($is_test_pickup ? "🧪 [TEST] " : "🚗 ") . "<b>NHẮC ĐÓN CON: {$sess_name}</b>\n";
                    $alert_msg .= "━━━━━━━━━━━━━━━━━━\n";
                    $alert_msg .= "⏰ Giờ tan học: <b>{$time_str}</b> (còn khoảng {$diff} phút nữa)\n";
                    $alert_msg .= "🎒 Bé cần đón: <b>{$kids_str}</b>\n\n";
                    if ($weather_text) {
                        $alert_msg .= "{$weather_text}\n\n";
                    }
                    $alert_msg .= "👉 Ba mẹ chuẩn bị đi đón con nhé!";

                    echo sendTelegram($alert_msg, $bot_token, $chat_id);
                    exit;
                }
            }
        }
    }
}

echo "Hệ thống đang chạy ngầm: Không có lịch trùng khớp tại thời điểm hiện tại (" . date('H:i') . ").";
