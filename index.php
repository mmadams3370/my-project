<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}
<?php
function forecast_sales($sales, $steps = 5, $seasonality = 7) {
    $window = 3; // moving average window
    $forecasts = [];

    for ($i = 0; $i < $steps; $i++) {
        $last_values = array_slice($sales, -$window);
        $avg = array_sum($last_values) / count($last_values);

        // Seasonal adjustment: 5% fluctuation based on seasonality
        $season_adj = ($sales[count($sales) % $seasonality] ?? $avg) * 0.05;
        $forecast_value = $avg + $season_adj;

        $sales[] = $forecast_value;
        $forecasts[] = round($forecast_value, 2);
    }
    return $forecasts;
}

$results = "";
$chartData = [];
$products = [];
$selectedProduct = "";

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
        $forecast = forecast_sales($sales, 5);

        $results = "<h3>Forecast for <span style='color:#005A9C;'>$selectedProduct</span>:</h3><ul>";
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
<form method="POST" action="logout.php" style="text-align:right;">
  <button type="submit">Logout</button>
</form>
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