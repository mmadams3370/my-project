<?php
require __DIR__ . '/auth.php'; // enforce login session
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Forecast function now considers season-specific multipliers
function forecast_sales($sales, $steps = 5, $seasonality = 7, $season = 'general') {
    $window = 3; // moving average window
    $forecasts = [];

    // Seasonal multipliers (example: festive, summer, winter)
    $season_multipliers = [
        'general' => 1.0,
        'festive' => 1.2,
        'summer' => 1.1,
        'winter' => 0.9
    ];

    $multiplier = $season_multipliers[$season] ?? 1.0;

    for ($i = 0; $i < $steps; $i++) {
        $last_values = array_slice($sales, -$window);
        $avg = array_sum($last_values) / count($last_values);

        // Seasonal adjustment: small fluctuation based on seasonality
        $season_adj = ($sales[count($sales) % $seasonality] ?? $avg) * 0.05;
        $forecast_value = ($avg + $season_adj) * $multiplier;

        $sales[] = $forecast_value;
        $forecasts[] = round($forecast_value, 2);
    }
    return $forecasts;
}

$results = "";
$chartData = [];
$products = [];
$selectedProduct = "";
$selectedSeason = $_POST['season'] ?? 'general';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $filePath = $_FILES['csv_file']['tmp_name'];
    $data = array_map('str_getcsv', file($filePath));

    $header = array_shift($data); // expect: date,product,sales

    // Group sales by product
    foreach ($data as $row) {
        $date = $row[0];
        $product = $row[1];
        $sale = (float)$row[2];
        $products[$product]['dates'][] = $date;
        $products[$product]['sales'][] = $sale;
    }

    // Default: first product if none selected
    $selectedProduct = $_POST['product'] ?? array_key_first($products);

    if ($selectedProduct && isset($products[$selectedProduct])) {
        $dates = $products[$selectedProduct]['dates'];
        $sales = $products[$selectedProduct]['sales'];
        $forecast = forecast_sales($sales, 5, 7, $selectedSeason);

        $results = "<h3>Forecast for <span style='color:#005A9C;'>$selectedProduct</span> in <em>$selectedSeason</em> season:</h3><ul>";
        foreach ($forecast as $i => $val) {
            $results .= "<li>Day ".($i+1).": <strong>".$val." units</strong></li>";
        }
        $results .= "</ul>";

        $chartData = [
            'labels' => array_merge($dates, ["Forecast 1","Forecast 2","Forecast 3","Forecast 4","Forecast 5"]),
            'sales' => array_merge($sales, $forecast)
        ];
    }
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Smart Supermarket Forecasting</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f4f8fb;
      margin: 0;
      padding: 20px;
      color: #333;
    }
    header { background:#005A9C; color:white; padding:16px; margin:-20px -20px 20px -20px; }
    header .container { max-width:920px; margin:0 auto; display:flex; justify-content:space-between; align-items:center; }
    header a { color:white; text-decoration:none; font-weight:bold; }
    h1 {
      text-align: center;
      color: #005A9C;
    }
    form {
      text-align: center;
      margin-bottom: 20px;
      background: white;
      padding: 20px;
      border-radius: 12px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }
    select, input[type=file], button {
      margin: 10px;
      padding: 10px;
      border-radius: 8px;
      border: 1px solid #ccc;
      font-size: 16px;
    }
    button {
      background: #005A9C;
      color: white;
      border: none;
      cursor: pointer;
    }
    button:hover {
      background: #003d6b;
    }
    .results {
      background: white;
      padding: 20px;
      border-radius: 12px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      margin: auto;
      max-width: 700px;
    }
    canvas {
      margin-top: 20px;
    }
  </style>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
  <header>
    <div class="container">
      <div>Welcome, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></div>
      <a href="logout.php">Logout</a>
    </div>
  </header>

  <h1>📊 Smart Supermarket Forecasting System</h1>
  <form method="POST" enctype="multipart/form-data">
    <label><strong>Upload Sales CSV:</strong></label><br>
    <input type="file" name="csv_file" required><br>

    <?php if (!empty($products)) : ?>
      <label>Select Product:</label>
      <select name="product">
        <?php foreach (array_keys($products) as $product): ?>
          <option value="<?php echo $product; ?>" <?php if ($product === $selectedProduct) echo 'selected'; ?>>
            <?php echo $product; ?>
          </option>
        <?php endforeach; ?>
      </select>

      <label>Select Season:</label>
      <select name="season">
        <option value="general" <?php if ($selectedSeason==='general') echo 'selected'; ?>>General</option>
        <option value="festive" <?php if ($selectedSeason==='festive') echo 'selected'; ?>>Festive</option>
        <option value="summer" <?php if ($selectedSeason==='summer') echo 'selected'; ?>>Summer</option>
        <option value="winter" <?php if ($selectedSeason==='winter') echo 'selected'; ?>>Winter</option>
      </select>
    <?php endif; ?>

    <button type="submit">Generate Forecast</button>
  </form>

  <div class="results">
    <?php echo $results; ?>
    <?php if (!empty($chartData)) : ?>
      <canvas id="forecastChart"></canvas>
      <script>
        const ctx = document.getElementById('forecastChart');
        new Chart(ctx, {
          type: 'line',
          data: {
            labels: <?php echo json_encode($chartData['labels']); ?>,
            datasets: [{
              label: 'Sales & Forecast',
              data: <?php echo json_encode($chartData['sales']); ?>,
              borderColor: '#005A9C',
              backgroundColor: 'rgba(0,90,156,0.2)',
              tension: 0.3,
              fill: true,
              pointRadius: 5,
              pointBackgroundColor: '#003d6b'
            }]
          },
          options: {
            responsive: true,
            plugins: {
              legend: { display: true }
            },
            scales: {
              y: { beginAtZero: true }
            }
          }
        });
      </script>
    <?php endif; ?>
  </div>
</body>
</html>