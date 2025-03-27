<?php
session_start();
require __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $data['csrf_token'])) {
    echo json_encode(['success' => false, 'message' => 'CSRF token validation failed']);
    exit();
}

// Prepare resume data
$resume_data = [
    'template' => $data['resume_template'] ?? 'modern',
    'contact_info' => [
        'name' => trim($data['contact_name'] ?? ''),
        'email' => trim($data['contact_email'] ?? ''),
        'phone' => trim($data['contact_phone'] ?? ''),
        'location' => trim($data['contact_location'] ?? '')
    ],
    'summary' => trim($data['summary'] ?? ''),
    'experience' => [],
    'education' => [],
    'skills' => [],
    'custom_sections' => []
];

if (isset($data['experience_job_title'])) {
    foreach ($data['experience_job_title'] as $index => $job_title) {
        $resume_data['experience'][] = [
            'job_title' => trim($job_title),
            'company' => trim($data['experience_company'][$index] ?? ''),
            'dates' => trim($data['experience_dates'][$index] ?? ''),
            'responsibilities' => trim($data['experience_responsibilities'][$index] ?? '')
        ];
    }
}

if (isset($data['education_degree'])) {
    foreach ($data['education_degree'] as $index => $degree) {
        $resume_data['education'][] = [
            'degree' => trim($degree),
            'institution' => trim($data['education_institution'][$index] ?? ''),
            'graduation_year' => trim($data['education_graduation_year'][$index] ?? '')
        ];
    }
}

if (isset($data['skills']) && isset($data['skill_levels'])) {
    foreach ($data['skills'] as $index => $skill) {
        if (!empty(trim($skill))) {
            $resume_data['skills'][] = [
                'name' => trim($skill),
                'level' => min(5, max(1, (int)($data['skill_levels'][$index] ?? 3)))
            ];
        }
    }
}

if (isset($data['custom_section_title'])) {
    foreach ($data['custom_section_title'] as $index => $title) {
        if (!empty($title)) {
            $resume_data['custom_sections'][] = [
                'title' => trim($title),
                'content' => trim($data['custom_section_content'][$index] ?? '')
            ];
        }
    }
}

$resume_json = json_encode($resume_data);
$stmt = $conn->prepare("SELECT id FROM resumes WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $stmt = $conn->prepare("UPDATE resumes SET resume_data = ? WHERE user_id = ?");
    $stmt->bind_param("si", $resume_json, $user_id);
} else {
    $stmt = $conn->prepare("INSERT INTO resumes (user_id, resume_data) VALUES (?, ?)");
    $stmt->bind_param("is", $user_id, $resume_json);
}

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Resume saved']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save resume']);
}
$stmt->close();
?>