<?php
// Very simple debug without auth
echo "Simple Debug\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n";
echo "Method: " . $_SERVER['REQUEST_METHOD'] . "\n";

// Test if this is a session issue
echo "Testing session...\n";
session_start();
echo "Session ID: " . session_id() . "\n";
echo "Session data count: " . count($_SESSION) . "\n";

echo "Done.\n";
?>