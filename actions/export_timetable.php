<?php
session_start();
require '../config/db.php';

// Check if user is logged in and is Admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || strcasecmp($_SESSION['role'], 'Admin') !== 0) {
    // If not admin, redirect or unauthorized
    header("Location: ../admin_login.php");
    exit();
}

// 1. Determine Format
$format = $_GET['format'] ?? 'excel';

// 2. Fetch Latest Timetable Version
// We look for the latest entry in timetable_versions
$stmt = $conn->prepare("SELECT data_json, version_name, created_at FROM timetable_versions ORDER BY created_at DESC LIMIT 1");
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    die("No generated timetable found. Please generate a timetable first.");
}

$row = $res->fetch_assoc();
$timetableData = json_decode($row['data_json'], true);
$versionName = $row['version_name'];
$createdAt = $row['created_at'];

if (!$timetableData) {
    die("Error decoding timetable data.");
}

// 3. Fetch Helper Data to Map IDs to Names
// Faculties
$faculties = [];
$res = $conn->query("SELECT id, name FROM faculties");
if ($res) {
    while ($r = $res->fetch_assoc())
        $faculties[$r['id']] = $r['name'];
}

// Subjects
$subjects = [];
$res = $conn->query("SELECT id, name, code FROM subjects");
if ($res) {
    while ($r = $res->fetch_assoc())
        $subjects[$r['id']] = $r;
}

// Rooms
$rooms = [];
// Based on grep, columns are name, type, capacity
$res = $conn->query("SELECT id, name FROM classrooms");
if ($res) {
    while ($r = $res->fetch_assoc())
        $rooms[$r['id']] = $r['name'];
}

// Sections
$sections = [];
$sec_query = "SELECT s.id, s.section_name, d.name as dept_name, s.year, s.semester 
              FROM sections s 
              LEFT JOIN departments d ON s.department_id = d.id";
$res = $conn->query($sec_query);
if ($res) {
    while ($r = $res->fetch_assoc())
        $sections[$r['id']] = $r;
}

// 4. Generate Output based on Format
if ($format === 'excel') {
    $filename = "Timetable_Export_" . date('Y-m-d_H-i') . ".xls";

    // Headers for Excel Download
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Pragma: no-cache");
    header("Expires: 0");

    // Start Output
    echo "<!DOCTYPE html>";
    echo "<html>";
    echo "<head><meta charset='UTF-8'></head>";
    echo "<body>";

    echo "<h3>Timetable Export: $versionName</h3>";
    echo "<p>Generated on: $createdAt</p>";

    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<thead style='background-color: #f2f2f2;'>";
    echo "<tr>
            <th style='padding: 8px; text-align: left;'>Day</th>
            <th style='padding: 8px; text-align: left;'>Period</th>
            <th style='padding: 8px; text-align: left;'>Department</th>
            <th style='padding: 8px; text-align: left;'>Year / Sem</th>
            <th style='padding: 8px; text-align: left;'>Section</th>
            <th style='padding: 8px; text-align: left;'>Subject Code</th>
            <th style='padding: 8px; text-align: left;'>Subject Name</th>
            <th style='padding: 8px; text-align: left;'>Faculty</th>
            <th style='padding: 8px; text-align: left;'>Room</th>
            <th style='padding: 8px; text-align: left;'>Type</th>
          </tr>";
    echo "</thead>";
    echo "<tbody>";

    // Iterate through the structure
    // $timetable[day][period][section_id] = ['subject_id'=>..., 'faculty_id'=>..., 'room_id'=>..., 'type'=>...]
    if (is_array($timetableData)) {
        foreach ($timetableData as $day => $periods) {
            // Sort periods if needed, assuming keys are 1, 2, 3...
            ksort($periods);

            foreach ($periods as $period => $sectionEntries) {
                foreach ($sectionEntries as $sectionId => $entry) {
                    $secInfo = $sections[$sectionId] ?? null;
                    if (!$secInfo)
                        continue; // Should not happen if data is consistent

                    $deptName = $secInfo['dept_name'] ?? '-';
                    $yearSem = "Yr {$secInfo['year']} Sem {$secInfo['semester']}";
                    $secName = $secInfo['section_name'] ?? '-';

                    $subId = $entry['subject_id'] ?? null;
                    $facId = $entry['faculty_id'] ?? null;
                    $roomId = $entry['room_id'] ?? null;
                    $type = $entry['type'] ?? 'Theory';

                    $sCode = $subjects[$subId]['code'] ?? '-';
                    $sName = $subjects[$subId]['name'] ?? '-';
                    $fName = $faculties[$facId] ?? 'Unassigned';
                    $rName = $rooms[$roomId] ?? 'Unassigned';

                    echo "<tr>";
                    echo "<td style='padding: 8px;'>$day</td>";
                    echo "<td style='padding: 8px;'>$period</td>";
                    echo "<td style='padding: 8px;'>$deptName</td>";
                    echo "<td style='padding: 8px;'>$yearSem</td>";
                    echo "<td style='padding: 8px;'>$secName</td>";
                    echo "<td style='padding: 8px;'>$sCode</td>";
                    echo "<td style='padding: 8px;'>$sName</td>";
                    echo "<td style='padding: 8px;'>$fName</td>";
                    echo "<td style='padding: 8px;'>$rName</td>";
                    echo "<td style='padding: 8px;'>$type</td>";
                    echo "</tr>";
                }
            }
        }
    }

    echo "</tbody>";
    echo "</table>";
    echo "</body>";
    echo "</html>";

} elseif ($format === 'pdf') {
    // Placeholder for PDF - currently we handle that via frontend jsPDF mostly, 
    // or we could implement a backend TCPDF/MPDF solution here.
    // For now, let's just stick to the requested Excel format or show a message.
    echo "PDF generation is handled on the client-side for this version.";
} else {
    echo "Invalid format specified.";
}
?>