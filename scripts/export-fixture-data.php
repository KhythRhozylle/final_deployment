<?php

declare(strict_types=1);

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

(new Dotenv())->bootEnv(dirname(__DIR__) . '/.env');

$kernel = new Kernel($_SERVER['APP_ENV'] ?? 'dev', (bool) ($_SERVER['APP_DEBUG'] ?? true));
$kernel->boot();

$conn = $kernel->getContainer()->get('doctrine.dbal.default_connection');

$outDir = dirname(__DIR__) . '/src/DataFixtures/data';
if (!is_dir($outDir)) {
    mkdir($outDir, 0777, true);
}

$tables = [
    'users' => 'SELECT * FROM `user` ORDER BY id',
    'categories' => 'SELECT * FROM category ORDER BY id',
    'customers' => 'SELECT * FROM customer ORDER BY id',
    'products' => 'SELECT * FROM product ORDER BY id',
    'orders' => 'SELECT * FROM `order` ORDER BY id',
    'activity_logs' => 'SELECT * FROM activity_log ORDER BY id',
];

foreach ($tables as $name => $sql) {
    $rows = $conn->fetchAllAssociative($sql);
    foreach ($rows as &$row) {
        if (isset($row['roles']) && is_string($row['roles'])) {
            $row['roles'] = json_decode($row['roles'], true, 512, JSON_THROW_ON_ERROR);
        }
        foreach ($row as $key => $value) {
            if ($value instanceof \DateTimeInterface) {
                $row[$key] = $value->format('Y-m-d H:i:s');
            }
        }
    }
    unset($row);

    $export = var_export($rows, true);
    $content = "<?php\n\ndeclare(strict_types=1);\n\n// Auto-exported from local database — regenerate with: php scripts/export-fixture-data.php\n\nreturn {$export};\n";
    file_put_contents($outDir . '/' . $name . '.php', $content);
    echo sprintf("Exported %d rows to %s.php\n", count($rows), $name);
}

echo "Done.\n";
