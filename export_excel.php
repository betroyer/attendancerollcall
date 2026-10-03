<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/report_query.php';
require_login();

$pdo = db();
$teacher = current_teacher();

$searchType = $_GET['search_type'] ?? 'student';
$studentId = trim((string) ($_GET['student_id'] ?? ''));
$classId = (int) ($_GET['class_id'] ?? 0);

[$records, $label] = fetch_attendance_report($pdo, $searchType, $studentId, $classId);

if ($records === []) {
    header('Location: report.php?' . report_query_string($searchType, $studentId, $classId));
    exit;
}

$safeLabel = preg_replace('/[^A-Za-z0-9_-]+/', '_', $label) ?: 'attendance';
$filename = 'attendance_' . $safeLabel . '_' . date('Y-m-d') . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$out = fopen('php://output', 'w');
if ($out === false) {
    http_response_code(500);
    exit('Could not create export file.');
}

// UTF-8 BOM so Excel opens accented names correctly
fwrite($out, "\xEF\xBB\xBF");

fputcsv($out, [
    'SFIT Student\'s Attendance System',
]);
fputcsv($out, ['Report', $label]);
fputcsv($out, ['Exported by', $teacher['full_name'] ?? '']);
fputcsv($out, ['Exported on', date('Y-m-d H:i')]);
fputcsv($out, []);
fputcsv($out, [
    'Date',
    'Student ID',
    'Student Name',
    'Class',
    'Subject',
    'Status',
    'Marked by',
]);

foreach ($records as $row) {
    fputcsv($out, [
        $row['attendance_date'],
        $row['student_id'],
        $row['full_name'],
        $row['class_name'],
        $row['subject_name'],
        $row['status'],
        $row['teacher_name'],
    ]);
}

fclose($out);
exit;
