<?php
header("Content-Type: application/json");

function fetchJobs() {
    $url = "https://jooble.org/api/";
    $apiKey = "a310d581-6f0b-4406-adbc-2e84f657f26c";

    $data = json_encode(["keywords" => "IT", "location" => "Bern"]);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url . $apiKey);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    $jobsData = json_decode($response, true);

    if (!isset($jobsData["jobs"]) || empty($jobsData["jobs"])) {
        echo json_encode(["error" => "No jobs found"]);
        exit;
    }

    $formattedJobs = [];
    foreach ($jobsData["jobs"] as $job) {
        $formattedJobs[] = [
            "id" => (string) $job["id"], // Convert ID to string
            "job_title" => $job["title"],
            "job_description" => $job["snippet"],
            "salary" => $job["salary"] ?: "Not Specified",
            "location" => $job["location"],
            "experience" => "N/A", // Jooble API doesn't provide experience data
            "source" => $job["source"],
            "link" => $job["link"]
        ];
    }

    echo json_encode($formattedJobs);
}

fetchJobs();
?>
