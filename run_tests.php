<?php


require_once __DIR__ . '/tests/BookControllerTest.php';
require_once __DIR__ . '/tests/MoodAnalyzerTest.php';
require_once __DIR__ . '/tests/AISongGeneratorTest.php';

echo "Starting Unit Tests...\n";
echo "======================\n\n";

$tests = [

    new MoodAnalyzerTest(),
    new BookControllerTest(),
    new AISongGeneratorTest()
];

$totalPassed = 0;
$totalFailed = 0;

foreach ($tests as $test) {
    echo "Testing " . get_class($test) . ":\n";
    $test->run();
    $results = $test->getResults();
    $totalPassed += $results['passed'];
    $totalFailed += $results['failed'];
    echo "\n";
}

echo "======================\n";
echo "Total Passed: $totalPassed\n";
echo "Total Failed: $totalFailed\n";

if ($totalFailed > 0) {
    echo "\n\033[31mSOME TESTS FAILED\033[0m\n";
    exit(1);
} else {
    echo "\n\033[32mALL TESTS PASSED\033[0m\n";
    exit(0);
}
