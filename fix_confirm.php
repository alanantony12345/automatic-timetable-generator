<?php
$file = 'c:/xampp/htdocs/autotimetable/admin_dashboard.php';
$content = file_get_contents($file);

// Fix broken confirm string
// Look for "FORCE this assignment" followed by newline and spaces and "anyway?"
$pattern = '/FORCE this assignment\s*[\r\n]+\s*anyway\?"\)\)/';
$replacement = 'FORCE this assignment anyway?"))';

$new_content = preg_replace($pattern, $replacement, $content);

if ($new_content !== $content) {
    file_put_contents($file, $new_content);
    echo "Fixed confirm dialog string.\n";
} else {
    echo "Pattern not found.\n";
}
?>