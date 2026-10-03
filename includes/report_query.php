<?php
declare(strict_types=1);

/**
 * @return array{0: list<array<string, mixed>>, 1: string}
 */
function fetch_attendance_report(PDO $pdo, string $searchType, string $studentId, int $classId): array
{
    $records = [];
    $label = '';

    if ($searchType === 'student' && $studentId !== '') {
        $stmt = $pdo->prepare(
            'SELECT a.attendance_date, a.status, s.student_id, s.full_name,
                    c.class_name, sub.subject_name, t.full_name AS teacher_name
             FROM attendance a
             JOIN students s ON s.id = a.student_id
             JOIN classes c ON c.id = a.class_id
             JOIN subjects sub ON sub.id = a.subject_id
             JOIN teachers t ON t.id = a.teacher_id
             WHERE s.student_id = ?
             ORDER BY a.attendance_date DESC, sub.subject_name'
        );
        $stmt->execute([$studentId]);
        $records = $stmt->fetchAll();
        $label = 'Student ' . $studentId;
    } elseif ($searchType === 'class' && $classId > 0) {
        $stmt = $pdo->prepare(
            'SELECT a.attendance_date, a.status, s.student_id, s.full_name,
                    c.class_name, sub.subject_name, t.full_name AS teacher_name
             FROM attendance a
             JOIN students s ON s.id = a.student_id
             JOIN classes c ON c.id = a.class_id
             JOIN subjects sub ON sub.id = a.subject_id
             JOIN teachers t ON t.id = a.teacher_id
             WHERE a.class_id = ?
             ORDER BY a.attendance_date DESC, s.full_name, sub.subject_name'
        );
        $stmt->execute([$classId]);
        $records = $stmt->fetchAll();
        $label = $records[0]['class_name'] ?? ('Class #' . $classId);
    }

    return [$records, $label];
}

function report_query_string(string $searchType, string $studentId, int $classId): string
{
    $params = [
        'search' => '1',
        'search_type' => $searchType,
    ];

    if ($searchType === 'student') {
        $params['student_id'] = $studentId;
    } else {
        $params['class_id'] = (string) $classId;
    }

    return http_build_query($params);
}
