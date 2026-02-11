<?php
require __DIR__ . '/../config/db.php';

if (isset($_GET['format'])) {
    $format = $_GET['format'];

    // Fetch Latest Timetable Version
    // We use the query seen in previous logs: ORDER BY created_at DESC LIMIT 1
    $stmt = $conn->prepare("SELECT id, data_json, version_name, created_at FROM timetable_versions ORDER BY created_at DESC LIMIT 1");
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $timetable = json_decode($row['data_json'], true);
        $version_name = $row['version_name'];
        $version_id = $row['id'];

        if ($format === 'excel') {
            // Headers for Excel download
            header("Content-Type: application/vnd.ms-excel");
            header("Content-Disposition: attachment; filename=timetable_{$version_name}.xls");
            header("Pragma: no-cache");
            header("Expires: 0");

            // Fetch Reference Data (Names) to resolve IDs
            $subjects = [];
            $faculties = [];
            $rooms = [];
            $sections = [];

            // Helper to fetch data into array
            function fetchData($conn, $table, &$array)
            {
                $res = $conn->query("SELECT * FROM $table");
                while ($r = $res->fetch_assoc())
                    $array[$r['id']] = $r;
            }
            fetchData($conn, 'subjects', $subjects);
            fetchData($conn, 'faculties', $faculties);
            fetchData($conn, 'classrooms', $rooms);
            fetchData($conn, 'sections', $sections);

            echo "<html>";
            echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">";
            echo "<body>";
            echo "<h2>Timetable: $version_name</h2>";
            echo "<p>Generated on: " . $row['created_at'] . "</p>";

            echo "<table border='1'>";
            echo "<tr style='background-color:#f0f0f0; font-weight:bold;'>
                <th>Day</th>
                <th>Period</th>
                <th>Section</th>
                <th>Subject Code</th>
                <th>Subject Name</th>
                <th>Faculty</th>
                <th>Room</th>
                <th>Type</th>
            </tr>";

            // Iterate and populate rows
            // Logic: The JSON structure is $timetable[$day][$period][$section_id] = $entry
            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

            foreach ($days as $day) {
                if (!isset($timetable[$day]))
                    continue;

                // Sort periods? Assuming numeric logic 1..N
                ksort($timetable[$day]);

                foreach ($timetable[$day] as $period => $sec_data) {
                    foreach ($sec_data as $sec_id => $entry) {
                        // Resolve Names
                        $sec_name = isset($sections[$entry['section_id']]) ? $sections[$entry['section_id']]['section_name'] : 'Unknown Section';
                        $sub_code = isset($subjects[$entry['subject_id']]) ? $subjects[$entry['subject_id']]['code'] : '-';
                        $sub_name = isset($subjects[$entry['subject_id']]) ? $subjects[$entry['subject_id']]['name'] : 'Unknown Subject';
                        $fac_name = isset($faculties[$entry['faculty_id']]) ? $faculties[$entry['faculty_id']]['name'] : 'TBA';
                        $room_name = isset($rooms[$entry['room_id']]) ? $rooms[$entry['room_id']]['room_number'] : 'TBA';
                        $type = $entry['type'] ?? 'Theory';

                        echo "<tr>";
                        echo "<td>$day</td>";
                        echo "<td>$period</td>";
                        echo "<td>$sec_name</td>";
                        echo "<td>$sub_code</td>";
                        echo "<td>$sub_name</td>";
                        echo "<td>$fac_name</td>";
                        echo "<td>$room_name</td>";
                        echo "<td>$type</td>";
                        echo "</tr>";
                    }
                }
            }
            echo "</table>";
            echo "</body></html>";
            exit();

        } elseif ($format === 'pdf') {
            // Basic Placeholder for PDF
            echo "<h1>PDF Export</h1>";
            echo "<p>PDF generation requires a library like TCPDF. Please install via composer or use the Excel export.</p>";
            echo "<p><a href='javascript:history.back()'>Go Back</a></p>";
        }
    } else {
        echo "<h1>No generated timetable found.</h1>";
        echo "<p>Please generate a timetable first from the Admin Dashboard.</p>";
    }
} else {
    echo "Invalid request.";
}
?>