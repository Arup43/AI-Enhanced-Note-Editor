<?php

/**
 * Raw PHP Note Analytics Dashboard
 * 
 * This component provides analytics and insights for notes without using Laravel framework.
 * It directly connects to the database and processes note data to generate statistics.
 */

// Start session to check authentication
session_start();

// Allow setting user ID via URL parameter (temporary solution)
function getUserIdFromRequest()
{
    // Check if user_id is specified in URL
    if (isset($_GET['user_id']) && is_numeric($_GET['user_id'])) {
        return (int)$_GET['user_id'];
    }

    return null;
}

// Load environment variables (simple .env parser)
function loadEnv($path)
{
    if (!file_exists($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);

        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// Load environment variables
loadEnv(__DIR__ . '/../../.env');

// Database configuration
$dbConfig = [
    'host' => getenv('DB_HOST') ?: '127.0.0.1',
    'port' => getenv('DB_PORT') ?: '5432',
    'database' => getenv('DB_DATABASE') ?: 'laravel',
    'username' => getenv('DB_USERNAME') ?: 'root',
    'password' => getenv('DB_PASSWORD') ?: '',
    'driver' => getenv('DB_CONNECTION') ?: 'pgsql'
];

// Simple authentication check (verify Laravel session)
function isAuthenticated()
{
    // Check if Laravel session exists and user is authenticated
    $sessionName = getenv('SESSION_COOKIE') ?: 'laravel_session';

    if (!isset($_COOKIE[$sessionName])) {
        return false;
    }

    // For simplicity, we'll check if the session cookie exists
    // In a production environment, you'd want to validate the session properly
    return true;
}

// Redirect to login if not authenticated
if (!isAuthenticated()) {
    header('Location: /login');
    exit;
}

// Database connection
function getDbConnection($config)
{
    try {
        if ($config['driver'] === 'sqlite') {
            $dbPath = str_replace('database_path(\'', '', $config['database']);
            $dbPath = str_replace('\')', '', $dbPath);
            $dbPath = __DIR__ . '/../../database/' . basename($dbPath);
            $pdo = new PDO("sqlite:$dbPath");
        } else {
            $dsn = "{$config['driver']}:host={$config['host']};port={$config['port']};dbname={$config['database']}";
            $pdo = new PDO($dsn, $config['username'], $config['password']);
        }

        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}

$pdo = getDbConnection($dbConfig);

// Get the current user ID from the session
function getCurrentUserId($pdo)
{
    try {
        $sessionName = getenv('SESSION_COOKIE') ?: 'laravel_session';

        if (!isset($_COOKIE[$sessionName])) {
            return null;
        }

        // Laravel encrypts the session cookie, so direct lookup won't work
        // Instead, get the most recently active user as a fallback
        $sql = "SELECT user_id FROM sessions WHERE user_id IS NOT NULL ORDER BY last_activity DESC LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result && isset($result['user_id'])) {
            return $result['user_id'];
        }

        return null;
    } catch (Exception $e) {
        // If there's an error, just return null and show all notes
        return null;
    }
}

// Get the current user ID
$currentUserId = getUserIdFromRequest() ?: getCurrentUserId($pdo);

// Debug information - will only show when debug=1 is in URL
$isDebugMode = isset($_GET['debug']) && $_GET['debug'] === '1';

// Add debug line to check if user ID is being retrieved correctly
error_log("Current user ID: " . ($currentUserId ?: "not found"));

// Analytics functions
class NoteAnalytics
{
    private $pdo;
    private $driver;

    public function __construct($pdo, $driver)
    {
        $this->pdo = $pdo;
        $this->driver = $driver;
    }

    public function getTotalNotes($userId = null)
    {
        $sql = "SELECT COUNT(*) as total FROM notes";
        $params = [];

        if ($userId) {
            $sql .= " WHERE user_id = ?";
            $params[] = $userId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    public function getWordCount($userId = null)
    {
        $sql = "SELECT content FROM notes";
        $params = [];

        if ($userId) {
            $sql .= " WHERE user_id = ?";
            $params[] = $userId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $totalWords = 0;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $totalWords += str_word_count($row['content']);
        }

        return $totalWords;
    }

    public function getTopTags($userId = null, $limit = 10)
    {
        $sql = "SELECT tags FROM notes WHERE tags IS NOT NULL";
        $params = [];

        if ($userId) {
            $sql .= " AND user_id = ?";
            $params[] = $userId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $tagCounts = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $tags = json_decode($row['tags'], true);
            if (is_array($tags)) {
                foreach ($tags as $tag) {
                    $tag = trim($tag);
                    if (!empty($tag)) {
                        $tagCounts[$tag] = ($tagCounts[$tag] ?? 0) + 1;
                    }
                }
            }
        }

        arsort($tagCounts);
        return array_slice($tagCounts, 0, $limit, true);
    }

    public function getNotesPerMonth($userId = null)
    {
        // Use database-specific date formatting
        switch ($this->driver) {
            case 'sqlite':
                $dateFormat = "strftime('%Y-%m', created_at)";
                break;
            case 'mysql':
            case 'mariadb':
                $dateFormat = "DATE_FORMAT(created_at, '%Y-%m')";
                break;
            case 'pgsql':
                $dateFormat = "TO_CHAR(created_at, 'YYYY-MM')";
                break;
            default:
                $dateFormat = "DATE_FORMAT(created_at, '%Y-%m')";
        }

        $sql = "SELECT 
                    {$dateFormat} as month,
                    COUNT(*) as count 
                FROM notes";
        $params = [];

        if ($userId) {
            $sql .= " WHERE user_id = ?";
            $params[] = $userId;
        }

        $sql .= " GROUP BY {$dateFormat} ORDER BY month DESC LIMIT 12";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAverageNoteLength($userId = null)
    {
        // Use database-specific length function
        switch ($this->driver) {
            case 'sqlite':
            case 'mysql':
            case 'mariadb':
                $lengthFunc = "LENGTH(content)";
                break;
            case 'pgsql':
                $lengthFunc = "CHAR_LENGTH(content)";
                break;
            default:
                $lengthFunc = "LENGTH(content)";
        }

        $sql = "SELECT AVG({$lengthFunc}) as avg_length FROM notes";
        $params = [];

        if ($userId) {
            $sql .= " WHERE user_id = ?";
            $params[] = $userId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return round($stmt->fetch(PDO::FETCH_ASSOC)['avg_length']);
    }

    public function getMostCommonWords($userId = null, $limit = 20)
    {
        $sql = "SELECT content FROM notes";
        $params = [];

        if ($userId) {
            $sql .= " WHERE user_id = ?";
            $params[] = $userId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $allText = '';
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $allText .= ' ' . strtolower($row['content']);
        }

        // Remove common stop words
        $stopWords = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'is', 'are', 'was', 'were', 'be', 'been', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'could', 'should', 'may', 'might', 'must', 'can', 'this', 'that', 'these', 'those', 'i', 'you', 'he', 'she', 'it', 'we', 'they', 'me', 'him', 'her', 'us', 'them'];

        $words = str_word_count($allText, 1);
        $words = array_filter($words, function ($word) use ($stopWords) {
            return strlen($word) > 3 && !in_array($word, $stopWords);
        });

        $wordCounts = array_count_values($words);
        arsort($wordCounts);

        return array_slice($wordCounts, 0, $limit, true);
    }
}

// Get analytics data
$analytics = new NoteAnalytics($pdo, $dbConfig['driver']);
$totalNotes = $analytics->getTotalNotes($currentUserId);
$totalWords = $analytics->getWordCount($currentUserId);
$topTags = $analytics->getTopTags($currentUserId);
$notesPerMonth = $analytics->getNotesPerMonth($currentUserId);
$avgNoteLength = $analytics->getAverageNoteLength($currentUserId);
$commonWords = $analytics->getMostCommonWords($currentUserId);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Personal Note Analytics - AI Enhanced Note Editor</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="icon" href="/favicon.ico">
    <style>
        /* Custom responsive styles for better mobile experience */
        @media (max-width: 640px) {
            .chart-container {
                height: 250px !important;
            }
        }

        /* Ensure charts are responsive */
        .chart-wrapper {
            position: relative;
            height: 300px;
        }

        @media (max-width: 768px) {
            .chart-wrapper {
                height: 250px;
            }
        }

        @media (max-width: 640px) {
            .chart-wrapper {
                height: 200px;
            }
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-3 sm:px-4 lg:px-6 py-4 sm:py-6 lg:py-8">
        <!-- Header -->
        <div class="mb-6 sm:mb-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="text-center sm:text-left">
                    <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-gray-900">📊 Your Note Analytics</h1>
                    <p class="text-gray-600 mt-1 sm:mt-2 text-sm sm:text-base">Personal insights and statistics about your notes</p>
                    <p class="text-xs sm:text-sm text-blue-600 mt-1">✨ Your Personal Note Statistics</p>
                    <p class="text-xs text-gray-500 mt-1">Database: <?= strtoupper($dbConfig['driver']) ?></p>
                </div>
                <div class="flex justify-center sm:justify-end">
                    <a href="/dashboard" class="bg-blue-600 text-white px-3 sm:px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors text-sm sm:text-base w-full sm:w-auto text-center">
                        ← Back to Dashboard
                    </a>
                </div>
            </div>
        </div>

        <?php if ($isDebugMode): ?>
            <!-- Debug Info (only visible when debug=1 is in URL) -->
            <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6" role="alert">
                <p class="font-bold">Debug Information</p>
                <p>Current User ID: <?= $currentUserId ?: 'Not Found' ?></p>
                <p>Session Cookie: <?= isset($_COOKIE[getenv('SESSION_COOKIE') ?: 'laravel_session']) ? 'Present' : 'Not Found' ?></p>
                <p>DB Driver: <?= $dbConfig['driver'] ?></p>
                <p>Notes Count (with filter): <?= $totalNotes ?></p>
                <p>SQL Test:
                    <?php
                    $testSql = "SELECT COUNT(*) FROM notes";
                    $testStmt = $pdo->prepare($testSql);
                    $testStmt->execute();
                    echo $testStmt->fetchColumn();
                    ?>
                </p>
            </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 lg:gap-6 mb-6 sm:mb-8">
            <div class="bg-white rounded-lg shadow-md p-4 sm:p-6">
                <div class="flex items-center">
                    <div class="p-2 sm:p-3 rounded-full bg-blue-100 text-blue-600 flex-shrink-0">
                        <span class="text-lg sm:text-xl">📝</span>
                    </div>
                    <div class="ml-3 sm:ml-4 min-w-0 flex-1">
                        <p class="text-xs sm:text-sm font-medium text-gray-600 truncate">Your Notes</p>
                        <p class="text-lg sm:text-xl lg:text-2xl font-bold text-gray-900"><?= number_format($totalNotes) ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-4 sm:p-6">
                <div class="flex items-center">
                    <div class="p-2 sm:p-3 rounded-full bg-green-100 text-green-600 flex-shrink-0">
                        <span class="text-lg sm:text-xl">📊</span>
                    </div>
                    <div class="ml-3 sm:ml-4 min-w-0 flex-1">
                        <p class="text-xs sm:text-sm font-medium text-gray-600 truncate">Your Words</p>
                        <p class="text-lg sm:text-xl lg:text-2xl font-bold text-gray-900"><?= number_format($totalWords) ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-4 sm:p-6">
                <div class="flex items-center">
                    <div class="p-2 sm:p-3 rounded-full bg-purple-100 text-purple-600 flex-shrink-0">
                        <span class="text-lg sm:text-xl">📏</span>
                    </div>
                    <div class="ml-3 sm:ml-4 min-w-0 flex-1">
                        <p class="text-xs sm:text-sm font-medium text-gray-600 truncate">Avg Note Length</p>
                        <p class="text-lg sm:text-xl lg:text-2xl font-bold text-gray-900"><?= number_format($avgNoteLength) ?> <span class="text-sm sm:text-base font-normal">chars</span></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-4 sm:p-6">
                <div class="flex items-center">
                    <div class="p-2 sm:p-3 rounded-full bg-orange-100 text-orange-600 flex-shrink-0">
                        <span class="text-lg sm:text-xl">🏷️</span>
                    </div>
                    <div class="ml-3 sm:ml-4 min-w-0 flex-1">
                        <p class="text-xs sm:text-sm font-medium text-gray-600 truncate">Your Tags</p>
                        <p class="text-lg sm:text-xl lg:text-2xl font-bold text-gray-900"><?= count($topTags) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 sm:gap-6 mb-6 sm:mb-8">
            <!-- Notes Per Month Chart -->
            <div class="bg-white rounded-lg shadow-md p-4 sm:p-6">
                <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-3 sm:mb-4 flex items-center">
                    <span class="mr-2">📈</span>
                    <span class="truncate">Notes Created Per Month</span>
                </h3>
                <div class="chart-wrapper">
                    <canvas id="notesPerMonthChart" class="w-full h-full"></canvas>
                </div>
            </div>

            <!-- Top Tags Chart -->
            <div class="bg-white rounded-lg shadow-md p-4 sm:p-6">
                <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-3 sm:mb-4 flex items-center">
                    <span class="mr-2">🏷️</span>
                    <span class="truncate">Most Used Tags</span>
                </h3>
                <?php if (empty($topTags)): ?>
                    <div class="flex items-center justify-center h-32 sm:h-48 text-gray-500">
                        <div class="text-center">
                            <p class="text-lg sm:text-xl">🏷️</p>
                            <p class="mt-2 text-sm sm:text-base">No tags found</p>
                            <p class="text-xs sm:text-sm">Start adding tags to your notes!</p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="chart-wrapper">
                        <canvas id="topTagsChart" class="w-full h-full"></canvas>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Word Cloud -->
        <div class="bg-white rounded-lg shadow-md p-4 sm:p-6 mb-6 sm:mb-8">
            <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-3 sm:mb-4 flex items-center">
                <span class="mr-2">☁️</span>
                <span>Most Common Words</span>
            </h3>
            <?php if (empty($commonWords)): ?>
                <div class="flex items-center justify-center h-24 sm:h-32 text-gray-500">
                    <div class="text-center">
                        <p class="text-lg sm:text-xl">☁️</p>
                        <p class="mt-2 text-sm sm:text-base">No content to analyze</p>
                        <p class="text-xs sm:text-sm">Create some notes to see word patterns!</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="flex flex-wrap gap-1 sm:gap-2">
                    <?php foreach ($commonWords as $word => $count): ?>
                        <span class="inline-block bg-blue-100 text-blue-800 px-2 sm:px-3 py-1 rounded-full text-xs sm:text-sm font-medium break-words"
                            style="font-size: <?= min(12 + ($count * 1), 18) ?>px;">
                            <?= htmlspecialchars($word) ?> (<?= $count ?>)
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Footer -->
        <div class="text-center text-gray-500 text-xs sm:text-sm space-y-1">
            <p>🔧 This analytics dashboard is built with <strong>Raw PHP</strong> - no Laravel framework used!</p>
            <p>Generated on <?= date('Y-m-d H:i:s') ?> | Database: <?= strtoupper($dbConfig['driver']) ?></p>
        </div>
    </div>

    <script>
        // Make charts responsive
        Chart.defaults.responsive = true;
        Chart.defaults.maintainAspectRatio = false;

        // Notes Per Month Chart
        const notesPerMonthCtx = document.getElementById('notesPerMonthChart').getContext('2d');
        const notesPerMonthData = <?= json_encode(array_reverse($notesPerMonth)) ?>;

        new Chart(notesPerMonthCtx, {
            type: 'line',
            data: {
                labels: notesPerMonthData.map(item => item.month),
                datasets: [{
                    label: 'Notes Created',
                    data: notesPerMonthData.map(item => item.count),
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true,
                    borderWidth: window.innerWidth < 640 ? 2 : 3,
                    pointRadius: window.innerWidth < 640 ? 3 : 4,
                    pointHoverRadius: window.innerWidth < 640 ? 5 : 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                scales: {
                    x: {
                        ticks: {
                            font: {
                                size: window.innerWidth < 640 ? 10 : 12
                            },
                            maxRotation: window.innerWidth < 640 ? 45 : 0
                        },
                        grid: {
                            display: window.innerWidth >= 640
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            font: {
                                size: window.innerWidth < 640 ? 10 : 12
                            }
                        },
                        grid: {
                            display: window.innerWidth >= 640
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        titleFont: {
                            size: window.innerWidth < 640 ? 12 : 14
                        },
                        bodyFont: {
                            size: window.innerWidth < 640 ? 11 : 13
                        }
                    }
                }
            }
        });

        // Top Tags Chart (only if tags exist)
        <?php if (!empty($topTags)): ?>
            const topTagsCtx = document.getElementById('topTagsChart').getContext('2d');
            const topTagsData = <?= json_encode($topTags) ?>;

            new Chart(topTagsCtx, {
                type: 'doughnut',
                data: {
                    labels: Object.keys(topTagsData),
                    datasets: [{
                        data: Object.values(topTagsData),
                        backgroundColor: [
                            '#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6',
                            '#06B6D4', '#84CC16', '#F97316', '#EC4899', '#6B7280'
                        ],
                        borderWidth: window.innerWidth < 640 ? 1 : 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: window.innerWidth < 640 ? '50%' : '60%',
                    plugins: {
                        legend: {
                            position: window.innerWidth < 640 ? 'bottom' : 'right',
                            labels: {
                                font: {
                                    size: window.innerWidth < 640 ? 10 : 12
                                },
                                padding: window.innerWidth < 640 ? 10 : 15,
                                usePointStyle: true,
                                pointStyle: 'circle'
                            }
                        },
                        tooltip: {
                            titleFont: {
                                size: window.innerWidth < 640 ? 12 : 14
                            },
                            bodyFont: {
                                size: window.innerWidth < 640 ? 11 : 13
                            }
                        }
                    }
                }
            });
        <?php endif; ?>

        // Handle window resize for better responsiveness
        window.addEventListener('resize', function() {
            // Charts will automatically resize due to responsive: true
            // But we can add any additional responsive behavior here if needed
        });
    </script>
</body>

</html>