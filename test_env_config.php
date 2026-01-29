<?php
// Test script to verify .env is loaded correctly
echo "<h2>Environment Configuration Test</h2>";

require __DIR__ . '/config/google_auth.php';

echo "<h3>Testing .env file loading...</h3>";

// Check if constants are defined
$checks = [
    'GOOGLE_CLIENT_ID' => defined('GOOGLE_CLIENT_ID') ? GOOGLE_CLIENT_ID : 'NOT DEFINED',
    'GOOGLE_CLIENT_SECRET' => defined('GOOGLE_CLIENT_SECRET') ? GOOGLE_CLIENT_SECRET : 'NOT DEFINED',
    'GOOGLE_REDIRECT_URI' => defined('GOOGLE_REDIRECT_URI') ? GOOGLE_REDIRECT_URI : 'NOT DEFINED',
];

echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Variable</th><th>Status</th><th>Value (first 20 chars)</th></tr>";

foreach ($checks as $key => $value) {
    $status = ($value !== 'NOT DEFINED' && !empty($value)) ? '✅ Loaded' : '❌ Missing';
    $displayValue = ($value !== 'NOT DEFINED') ? substr($value, 0, 20) . '...' : $value;

    echo "<tr>";
    echo "<td><strong>$key</strong></td>";
    echo "<td>$status</td>";
    echo "<td>$displayValue</td>";
    echo "</tr>";
}

echo "</table>";

echo "<h3>Summary</h3>";
if (
    defined('GOOGLE_CLIENT_ID') && !empty(GOOGLE_CLIENT_ID) &&
    defined('GOOGLE_CLIENT_SECRET') && !empty(GOOGLE_CLIENT_SECRET)
) {
    echo "<p style='color: green; font-weight: bold;'>✅ All Google OAuth credentials loaded successfully from .env file!</p>";
    echo "<p>Your application is ready to use Google OAuth without hardcoded credentials.</p>";
} else {
    echo "<p style='color: red; font-weight: bold;'>❌ Some credentials are missing!</p>";
    echo "<p>Please check your .env file and make sure all values are filled in.</p>";
}

echo "<hr>";
echo "<p><em>This test page can be deleted after verification.</em></p>";
?>