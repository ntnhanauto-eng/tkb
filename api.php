<?php
// api.php
include 'db.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

date_default_timezone_set('Asia/Ho_Chi_Minh');

$action = $_GET['action'] ?? '';

// ==============================================================================
// XỬ LÝ ĐỒNG BỘ HUY HIỆU / BÀI TẬP LÊN ĐÁM MÂY (api.php?action=sync_todo)
// ==============================================================================
if ($action === 'sync_todo') {
    $input = json_decode(file_get_contents('php://input'), true);
    $tags = $input['todo_tags'] ?? null;

    if (is_array($tags)) {
        try {
            $conn->exec("CREATE TABLE IF NOT EXISTS `schedule_todos` (
                `slot_key` VARCHAR(100) NOT NULL,
                `type` VARCHAR(20) NOT NULL,
                `content` VARCHAR(255) NOT NULL,
                `expire_at` BIGINT(20) NOT NULL,
                `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`slot_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            $conn->exec("TRUNCATE TABLE `schedule_todos`");

            if (!empty($tags)) {
                $stmt = $conn->prepare("INSERT INTO `schedule_todos` (`slot_key`, `type`, `content`, `expire_at`) VALUES (?, ?, ?, ?)");
                foreach ($tags as $key => $val) {
                    if (!empty($val['content'])) {
                        $stmt->execute([
                            (string)$key,
                            (string)($val['type'] ?? 'btvn'),
                            (string)$val['content'],
                            (int)($val['expireAt'] ?? 0)
                        ]);
                    }
                }
            }
            echo json_encode(['success' => true, 'message' => 'Đồng bộ huy hiệu thành công']);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    }
    echo json_encode(['success' => false, 'error' => 'Dữ liệu không hợp lệ']);
    exit;
}

// ==============================================================================
// XỬ LÝ ĐỒNG BỘ GHI CHÚ THEO NGÀY LÊN ĐÁM MÂY (api.php?action=sync_daily_notes)
// ==============================================================================
if ($action === 'sync_daily_notes') {
    $input = json_decode(file_get_contents('php://input'), true);
    $notes = $input['daily_notes'] ?? null;

    if (is_array($notes)) {
        try {
            $conn->exec("CREATE TABLE IF NOT EXISTS `daily_notes` (
                `note_key` VARCHAR(50) NOT NULL,
                `content` TEXT NOT NULL,
                `day_key` VARCHAR(20) NOT NULL,
                `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`note_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            $conn->exec("TRUNCATE TABLE `daily_notes`");

            if (!empty($notes)) {
                $stmt = $conn->prepare("INSERT INTO `daily_notes` (`note_key`, `content`, `day_key`) VALUES (?, ?, ?)");
                foreach ($notes as $key => $val) {
                    if (!empty($val['content'])) {
                        $stmt->execute([
                            (string)$key,
                            (string)$val['content'],
                            (string)($val['day_key'] ?? '')
                        ]);
                    }
                }
            }
            echo json_encode(['success' => true, 'message' => 'Đồng bộ ghi chú ngày thành công']);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    }
    echo json_encode(['success' => false, 'error' => 'Dữ liệu ghi chú không hợp lệ']);
    exit;
}

// ==============================================================================
// XỬ LÝ ĐỒNG BỘ LỊCH THI LÊN ĐÁM MÂY (api.php?action=sync_exams)
// ==============================================================================
if ($action === 'sync_exams') {
    $input = json_decode(file_get_contents('php://input'), true);
    $exams = $input['exams'] ?? null;

    if (is_array($exams)) {
        try {
            $conn->exec("CREATE TABLE IF NOT EXISTS `exam_schedules` (
                `id` VARCHAR(50) NOT NULL,
                `be_id` VARCHAR(10) NOT NULL,
                `exam_type` VARCHAR(20) NOT NULL,
                `subject` VARCHAR(100) NOT NULL,
                `exam_date` DATE NOT NULL,
                `start_time` VARCHAR(10) NOT NULL,
                `duration` INT(11) DEFAULT 60,
                `room` VARCHAR(50) DEFAULT NULL,
                `sbd` VARCHAR(50) DEFAULT NULL,
                `notes` VARCHAR(255) DEFAULT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            $conn->exec("TRUNCATE TABLE `exam_schedules`");

            if (!empty($exams)) {
                $stmt = $conn->prepare("INSERT INTO `exam_schedules` (`id`, `be_id`, `exam_type`, `subject`, `exam_date`, `start_time`, `duration`, `room`, `sbd`, `notes`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                foreach ($exams as $ex) {
                    if (!empty($ex['subject']) && !empty($ex['exam_date'])) {
                        $stmt->execute([
                            (string)($ex['id'] ?? 'exam_' . time()),
                            (string)($ex['be_id'] ?? '1'),
                            (string)($ex['exam_type'] ?? 'gk'),
                            (string)$ex['subject'],
                            (string)$ex['exam_date'],
                            (string)($ex['start_time'] ?? '07:30'),
                            (int)($ex['duration'] ?? 60),
                            (string)($ex['room'] ?? ''),
                            (string)($ex['sbd'] ?? ''),
                            (string)($ex['notes'] ?? '')
                        ]);
                    }
                }
            }
            echo json_encode(['success' => true, 'message' => 'Đồng bộ lịch thi thành công']);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    }
    echo json_encode(['success' => false, 'error' => 'Dữ liệu lịch thi không hợp lệ']);
    exit;
}

// 1. Cấu hình
$settings = [
    'kid1_name'   => 'Trâm Anh',
    'kid1_class'  => '6A3',
    'kid2_name'   => 'Thành Phát',
    'kid2_class'  => '10A4',
    'semester'    => 'HK1',
    'school_year' => '2026-2027'
];
try {
    $st_set = $conn->query("SELECT setting_key, setting_val FROM app_settings");
    if ($st_set) {
        $db_settings = $st_set->fetchAll(PDO::FETCH_KEY_PAIR);
        $settings = array_merge($settings, $db_settings);
    }
} catch (Exception $e) {}

// 2. Giáo viên
$teachers_map = [];
try {
    $stmt_tc = $conn->query("SELECT * FROM teachers ORDER BY be_name ASC, subject_name ASC");
    $all_teachers = $stmt_tc->fetchAll(PDO::FETCH_ASSOC);
    foreach ($all_teachers as $tc) {
        $teachers_map[$tc['id']] = $tc;
    }
} catch (Exception $e) {}

// 3. Thời khóa biểu
$stmt = $conn->query("SELECT * FROM schedule ORDER BY FIELD(buoi, 'SÁNG', 'CHIỀU', 'TỐI'), id ASC");
$raw_schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

$days_keys = ['thu2', 'thu3', 'thu4', 'thu5', 'thu6', 'thu7', 'cn'];
$app_data = [];

foreach ($raw_schedules as $row) {
    $be_id = (string)$row['be_name'];
    foreach ($days_keys as $dk) {
        $raw_content = trim($row[$dk] ?? '');
        if (!empty($raw_content) && $raw_content !== '-') {
            $lines = array_values(array_filter(explode("\n", str_replace("\r", "", $raw_content))));
            $subject = trim($lines[0] ?? '');
            $note = isset($lines[1]) ? trim(implode(' ', array_slice($lines, 1))) : '';

            $teacher_id = $row['t_' . $dk] ?? null;
            $teacher_info = ($teacher_id && isset($teachers_map[$teacher_id])) ? $teachers_map[$teacher_id] : null;

            $app_data[] = [
                'id'          => $row['id'],
                'be_id'       => $be_id,
                'day'         => $dk,
                'buoi'        => $row['buoi'],
                'tiet'        => $row['tiet'],
                'subject'     => $subject,
                'note'        => $note,
                'teacher'     => $teacher_info
            ];
        }
    }
}

// 4. Lấy danh sách ghi chú / huy hiệu từ đám mây (schedule_todos)
$cloud_todos = [];
try {
    $now = round(microtime(true) * 1000);
    $conn->exec("DELETE FROM `schedule_todos` WHERE `expire_at` > 0 AND `expire_at` < $now");

    $st_todo = $conn->query("SELECT * FROM `schedule_todos`");
    if ($st_todo) {
        while ($r = $st_todo->fetch(PDO::FETCH_ASSOC)) {
            $cloud_todos[$r['slot_key']] = [
                'type'     => $r['type'],
                'content'  => $r['content'],
                'expireAt' => (float)$r['expire_at']
            ];
        }
    }
} catch (Exception $e) {
    $cloud_todos = [];
}

// 5. Lấy danh sách lịch thi từ đám mây (exam_schedules)
$cloud_exams = [];
try {
    $st_ex = $conn->query("SELECT * FROM `exam_schedules`");
    if ($st_ex) {
        while ($r = $st_ex->fetch(PDO::FETCH_ASSOC)) {
            $cloud_exams[] = [
                'id'         => $r['id'],
                'be_id'      => $r['be_id'],
                'exam_type'  => $r['exam_type'],
                'subject'    => $r['subject'],
                'exam_date'  => $r['exam_date'],
                'start_time' => $r['start_time'],
                'duration'   => (int)$r['duration'],
                'room'       => $r['room'],
                'sbd'        => $r['sbd'],
                'notes'      => $r['notes']
            ];
        }
    }
} catch (Exception $e) {
    $cloud_exams = [];
}

// 6. Lấy danh sách ghi chú theo ngày từ đám mây (daily_notes)
$cloud_daily_notes = [];
try {
    $st_dn = $conn->query("SELECT * FROM `daily_notes`");
    if ($st_dn) {
        while ($r = $st_dn->fetch(PDO::FETCH_ASSOC)) {
            $cloud_daily_notes[$r['note_key']] = [
                'content'   => $r['content'],
                'day_key'   => $r['day_key'],
                'updatedAt' => isset($r['updated_at']) ? strtotime($r['updated_at']) * 1000 : 0
            ];
        }
    }
} catch (Exception $e) {
    $cloud_daily_notes = [];
}

echo json_encode([
    'settings'      => $settings,
    'teachers_map'  => $teachers_map,
    'raw_schedules' => $raw_schedules,
    'schedule_data' => $app_data,
    'todo_tags'     => $cloud_todos,
    'exam_schedules'=> $cloud_exams,
    'daily_notes'   => $cloud_daily_notes,
    'server_time'   => time()
], JSON_UNESCAPED_UNICODE);
exit;
