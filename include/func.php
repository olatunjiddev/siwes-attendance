<?php

function redirect($url){
    header('Location: '.$url);
    exit();
}

function addNumber($num1 , $num2){

    return $num1 + $num2;

}

/* ================= AUTO-MARK PAST UNMARKED ATTENDANCE ================= */
function autoMarkAbsent($conn, $cutoffTime = '10:00:00') {
    $today = date('Y-m-d');

    // Get all attendance dates up to today
    $stmt = $conn->prepare("SELECT id, date FROM attendance_dates WHERE date <= ?");
    $stmt->execute([$today]);
    $dates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($dates as $date) {
        $attendance_date_id = $date['id'];
        $attendance_date = $date['date'];

        // Skip if auto-absent already ran
        $stmtCheck = $conn->prepare("
            SELECT COUNT(*) FROM attendance_records
            WHERE attendance_date_id = ?
            AND status = 'absent'
        ");
        $stmtCheck->execute([$attendance_date_id]);
        $alreadyMarked = $stmtCheck->fetchColumn() > 0;

        if (!$alreadyMarked) {
            $currentDate = date('Y-m-d');
            $currentTime = date('H:i:s');
            
            // Only mark if past date or today after cutoff
            if ($attendance_date < $currentDate || ($attendance_date == $currentDate && $currentTime >= $cutoffTime)) {
                $insert = $conn->prepare("
                    INSERT INTO attendance_records (student_id, attendance_date_id, status, marked_at, attendance_score)
                    SELECT s.id, ?, 'absent', NULL, 0
                    FROM students s
                    WHERE NOT EXISTS (
                        SELECT 1 FROM attendance_records ar
                        WHERE ar.student_id = s.id
                        AND ar.attendance_date_id = ?
                    )
                ");
                $insert->execute([$attendance_date_id, $attendance_date_id]);
            }
        }
    }
}

// Run auto-absent at the top of dashboard
autoMarkAbsent($conn);

?>