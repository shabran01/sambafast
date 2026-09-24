<?php
/**
 * SpeedRadius — Backfill voucher activation prices
 *
 * Voucher activations were written to tbl_transactions with price = 0, which is
 * why the Activation Report showed "GMD 0" in the Plan Price column and why
 * voucher takings never reached Income Today. New activations now store the plan
 * price (see Package::rechargeUser). This script repairs the rows created before
 * that change by looking the price up from the plan the voucher belongs to.
 *
 * Nothing is written unless you ask for it, twice.
 *
 * Dry run — shows exactly what would change:
 *   php fix_voucher_prices.php
 *   https://yourdomain.com/fix_voucher_prices.php
 *
 * Apply:
 *   php fix_voucher_prices.php --apply
 *   https://yourdomain.com/fix_voucher_prices.php?apply=1&confirm=yes
 *
 * Delete this file once the backfill is done.
 */

require_once __DIR__ . '/config.php';

// config.php may define either name, depending on the version.
if (empty($db_pass) && !empty($db_password)) {
    $db_pass = $db_password;
}
if (empty($db_user) && !empty($db_username)) {
    $db_user = $db_username;
}

$isCli = (PHP_SAPI === 'cli');

if ($isCli) {
    $apply = in_array('--apply', $argv, true);
} else {
    // Two deliberate flags, so a stray visit or a crawler cannot mutate anything.
    $apply = (isset($_GET['apply']) && $_GET['apply'] === '1')
        && (isset($_GET['confirm']) && $_GET['confirm'] === 'yes');
}

$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error . "\n");
}
$mysqli->set_charset('utf8mb4');

if (!$isCli) {
    header('Content-Type: text/plain; charset=utf-8');
}

echo "SpeedRadius - Voucher Price Backfill\n";
echo str_repeat('=', 52) . "\n";
echo $apply
    ? "Mode: APPLY - matching rows will be updated\n\n"
    : "Mode: DRY RUN - nothing will be changed\n\n";

/**
 * Look up the price of the plan a voucher belongs to.
 * Prefers an exact plan on the same router, then falls back to the name alone
 * for cases where the router name on the transaction has since changed.
 */
$planCache = [];
$resolvePrice = function ($planName, $routerName) use ($mysqli, &$planCache) {
    $key = $planName . '|' . $routerName;
    if (array_key_exists($key, $planCache)) {
        return $planCache[$key];
    }

    $price = null;

    $stmt = $mysqli->prepare("SELECT price FROM tbl_plans WHERE name_plan = ? AND routers = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('ss', $planName, $routerName);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row) {
            $price = $row['price'];
        }
    }

    if ($price === null) {
        $stmt = $mysqli->prepare("SELECT price FROM tbl_plans WHERE name_plan = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('s', $planName);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($row) {
                $price = $row['price'];
            }
        }
    }

    return $planCache[$key] = $price;
};

// Every voucher activation that was stored without a price.
$result = $mysqli->query(
    "SELECT id, invoice, username, plan_name, routers, recharged_on, method
     FROM tbl_transactions
     WHERE method LIKE 'Voucher%' AND price = 0
     ORDER BY id ASC"
);

if (!$result) {
    die("Query failed: " . $mysqli->error . "\n");
}

$rows = $result->fetch_all(MYSQLI_ASSOC);
$result->free();

$total = count($rows);
echo "Voucher activations with price 0 : {$total}\n";

if ($total === 0) {
    echo "\nNothing to do - every voucher activation already carries its price.\n";
    $mysqli->close();
    exit;
}

$planned = [];
$unmatched = [];
$sumBefore = 0.0;
$sumAfter = 0.0;

foreach ($rows as $row) {
    $price = $resolvePrice($row['plan_name'], $row['routers']);

    if ($price === null) {
        $unmatched[] = $row;
        continue;
    }

    $price = (float) $price;
    if ($price <= 0) {
        // A genuinely free plan: nothing to add.
        continue;
    }

    $planned[] = ['id' => (int) $row['id'], 'price' => $price, 'row' => $row];
    $sumBefore += 0.0;
    $sumAfter += $price;
}

echo "Repairable                        : " . count($planned) . "\n";
echo "Plan no longer found              : " . count($unmatched) . "\n";
echo "Value that would be added         : " . number_format($sumAfter, 2) . "\n";

if (!empty($planned)) {
    echo "\nFirst 20 rows:\n";
    foreach (array_slice($planned, 0, 20) as $item) {
        printf(
            "  #%-8d %-16s %-22s %s  %s\n",
            $item['id'],
            $item['row']['recharged_on'],
            $item['row']['plan_name'],
            $item['row']['invoice'],
            number_format($item['price'], 2)
        );
    }
    if (count($planned) > 20) {
        echo "  ... and " . (count($planned) - 20) . " more\n";
    }
}

if (!empty($unmatched)) {
    echo "\nRows whose plan could not be resolved (left untouched):\n";
    foreach (array_slice($unmatched, 0, 20) as $row) {
        printf("  #%-8d %-22s router: %s\n", $row['id'], $row['plan_name'], $row['routers']);
    }
    if (count($unmatched) > 20) {
        echo "  ... and " . (count($unmatched) - 20) . " more\n";
    }
}

if (!$apply) {
    echo "\nDRY RUN complete. Re-run with ";
    echo $isCli ? "--apply" : "?apply=1&confirm=yes";
    echo " to write these prices.\n";
    $mysqli->close();
    exit;
}

// ── Apply ────────────────────────────────────────────────────────────────────
echo "\nApplying...\n";

$updated = 0;
$failed = 0;
$stmt = $mysqli->prepare("UPDATE tbl_transactions SET price = ? WHERE id = ? AND price = 0");

if (!$stmt) {
    echo "Prepare failed: " . $mysqli->error . "\n";
    $mysqli->close();
    exit(1);
}

foreach ($planned as $item) {
    $stmt->bind_param('di', $item['price'], $item['id']);
    if ($stmt->execute()) {
        $updated += $stmt->affected_rows;
    } else {
        $failed++;
        echo "  Failed on #{$item['id']}: " . $stmt->error . "\n";
    }
}
$stmt->close();

echo "Rows updated                      : {$updated}\n";
if ($failed > 0) {
    echo "Rows failed                       : {$failed}\n";
}

// Confirm by recomputing what is still outstanding.
$left = $mysqli->query(
    "SELECT COUNT(*) AS c FROM tbl_transactions WHERE method LIKE 'Voucher%' AND price = 0"
);
if ($left) {
    $c = $left->fetch_assoc();
    echo "Still at price 0 afterwards       : " . $c['c'] . "\n";
    $left->free();
}

$mysqli->close();

echo "\nDone. Delete this file now that the backfill has run.\n";
