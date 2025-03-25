<?php
// Dummy job data with 100+ entries
$jobs = [
    ["title" => "Software Engineer", "company" => "Google", "location" => "California, USA", "salary" => "$120,000", "type" => "Full-Time", "experience" => "3+ Years"],
    ["title" => "Software Engineer", "company" => "Microsoft", "location" => "New York, USA", "salary" => "$110,000", "type" => "Full-Time", "experience" => "3+ Years"],
    ["title" => "Software Engineer", "company" => "Amazon", "location" => "Seattle, USA", "salary" => "$115,000", "type" => "Full-Time", "experience" => "3+ Years"],
    ["title" => "Data Analyst", "company" => "Google", "location" => "California, USA", "salary" => "$100,000", "type" => "Full-Time", "experience" => "2+ Years"],
    ["title" => "Data Analyst", "company" => "Microsoft", "location" => "New York, USA", "salary" => "$95,000", "type" => "Full-Time", "experience" => "2+ Years"],
    ["title" => "Web Developer", "company" => "Facebook", "location" => "London, UK", "salary" => "$90,000", "type" => "Full-Time", "experience" => "2+ Years"],
    ["title" => "Project Manager", "company" => "Tesla", "location" => "California, USA", "salary" => "$130,000", "type" => "Full-Time", "experience" => "5+ Years"],
    ["title" => "DevOps Engineer", "company" => "Apple", "location" => "Texas, USA", "salary" => "$125,000", "type" => "Full-Time", "experience" => "4+ Years"],
    ["title" => "Cyber Security Analyst", "company" => "IBM", "location" => "Washington, USA", "salary" => "$105,000", "type" => "Full-Time", "experience" => "3+ Years"],
    ["title" => "Cloud Engineer", "company" => "Oracle", "location" => "California, USA", "salary" => "$118,000", "type" => "Full-Time", "experience" => "4+ Years"],
    ["title" => "Product Manager", "company" => "Adobe", "location" => "San Francisco, USA", "salary" => "$140,000", "type" => "Full-Time", "experience" => "6+ Years"],
];

// Arrays for randomization
$titles = ["Software Engineer", "Data Analyst", "Web Developer", "Project Manager", "DevOps Engineer", "Cyber Security Analyst", "Cloud Engineer", "Product Manager", "UI/UX Designer", "Mobile App Developer"];
$companies = ["Google", "Microsoft", "Amazon", "Facebook", "Tesla", "Apple", "IBM", "Oracle", "Adobe", "Netflix", "Spotify", "Uber", "Lyft", "Twitter", "Snapchat"];
$locations = ["California, USA", "New York, USA", "Seattle, USA", "London, UK", "Texas, USA", "Washington, USA", "San Francisco, USA", "Berlin, Germany", "Paris, France", "Tokyo, Japan"];
$types = ["Full-Time", "Part-Time", "Internship"];
$experienceLevels = ["0+ Years", "1+ Years", "2+ Years", "3+ Years", "4+ Years", "5+ Years", "6+ Years"];

// Add more jobs dynamically
for ($i = 0; $i < 90; $i++) {
    $title = $titles[array_rand($titles)];
    $company = $companies[array_rand($companies)];
    $location = $locations[array_rand($locations)];
    $type = $types[array_rand($types)];
    $experience = $experienceLevels[array_rand($experienceLevels)];

    // Generate a random salary based on the title and experience
    $baseSalary = [
        "Software Engineer" => 100000,
        "Data Analyst" => 90000,
        "Web Developer" => 85000,
        "Project Manager" => 120000,
        "DevOps Engineer" => 110000,
        "Cyber Security Analyst" => 95000,
        "Cloud Engineer" => 105000,
        "Product Manager" => 130000,
        "UI/UX Designer" => 80000,
        "Mobile App Developer" => 95000,
    ][$title];

    $salary = $baseSalary + (rand(0, 5) * 5000);

    $jobs[] = [
        "title" => $title,
        "company" => $company,
        "location" => $location,
        "salary" => "$" . number_format($salary),
        "type" => $type,
        "experience" => $experience,
    ];
}

// Return jobs as JSON
header('Content-Type: application/json');
echo json_encode($jobs, JSON_PRETTY_PRINT);
?>