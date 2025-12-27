<?php

/* =========================
   BASIC HELPERS
========================= */

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function addNumber(int|float $num1, int|float $num2): int|float
{
    return $num1 + $num2;
}

/* =========================
   AUTO-MARK ABSENT FUNCTION
========================= */

/**
 * Automatically marks absent students
 * AFTER the attendance window has closed.
 *
 * RULES:
 * - Attendance must be hosted
 * - Attendance must NOT be closed
 * - Time window must have passed
 * - Runs only once per attendance date
 *
 * @param PDO $conn
 * @param int $closeAfterMinutes Default: 60 minutes
 */
function autoMarkAbsent(PDO $conn, int $closeAfterMinutes = 60): void
{
    $now = time();

    // Fetch ONLY open attendance sessions
    $stmt = $conn->prepare("
        SELECT id, hosted_at
        FROM attendance_dates
        WHERE hosted_at IS NOT NULL
        AND closed = 0
    ");
    $stmt->execute();

    $attendanceDates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$attendanceDates) {
        return; // Nothing to process
    }

    foreach ($attendanceDates as $date) {
        $attendanceDateId = (int)$date['id'];
        $hostedAt = strtotime($date['hosted_at']);

        // Safety check
        if (!$hostedAt) {
            continue;
        }

        // Check if attendance window is still open
        if (($now - $hostedAt) < ($closeAfterMinutes * 60)) {
            continue;
        }

        /* =========================
           MARK ABSENT STUDENTS
        ========================= */

        $insert = $conn->prepare("
            INSERT INTO attendance_records
                (student_id, attendance_date_id, status, marked_at, attendance_score)
            SELECT
                s.id, ?, 'absent', NOW(), 0
            FROM students s
            WHERE NOT EXISTS (
                SELECT 1
                FROM attendance_records ar
                WHERE ar.student_id = s.id
                AND ar.attendance_date_id = ?
            )
        ");
        $insert->execute([$attendanceDateId, $attendanceDateId]);

        /* =========================
           CLOSE ATTENDANCE SESSION
        ========================= */

        $close = $conn->prepare("
            UPDATE attendance_dates
            SET closed = 1
            WHERE id = ?
        ");
        $close->execute([$attendanceDateId]);
    }
}
