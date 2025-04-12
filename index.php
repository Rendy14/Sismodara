<?php
include 'config.php'; // Hubungkan ke database
include 'aes_dekript.php'; // Pastikan ini adalah nama file yang benar

// Tentukan jumlah data per halaman
$limit = 10; // Mengambil 10 data per halaman

// Ambil halaman yang diminta
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Ambil data terbaru dengan pagination
$sql = "SELECT * FROM `monitoring_udara` ORDER BY timestamp DESC LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);

// Inisialisasi variabel untuk menyimpan hasil
$rows = [];

if ($result->num_rows > 0) {
    while ($data = $result->fetch_assoc()) {
        // Kunci enkripsi
        $key = '2b7e151628aed2a6abf7cf8919a08d3c';
        // Dekripsi data
        $row = [];
        $row['Id'] = $data['Id'] ;
        $row['suhu'] = (float) decrypt(hex2bin($data['Suhu']), $key);
        $row['kelembapan'] = (float) decrypt(hex2bin($data['Kelembapan']), $key);
        $row['co'] = (float) decrypt(hex2bin($data['Konsentrasi CO']), $key);
        $row['pm10'] = (float) decrypt(hex2bin($data['PM10']), $key);
        $rows[] = $row; // Menambahkan data ke array $rows
    }
}

// Hitung total jumlah data
$sql_count = "SELECT COUNT(*) as total FROM `monitoring_udara`";
$count_result = $conn->query($sql_count);
$total_row = $count_result->fetch_assoc();
$total_data = $total_row['total'];

// Hitung jumlah total halaman jika ada data
$total_pages = ($total_data > 0) ? ceil($total_data / $limit) : 1;

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring Udara - Data dan Grafik</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f7f7f7;
            height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            flex: 1;
        }

        .navbar {
            background-color: #28a745;
        }

        .navbar-brand {
            color: white;
            font-size: 20px;
            font-weight: bold;
        }

        .table-container {
            margin-top: 30px;
            width: 100%;
            display: flex;
        }

        .table-box {
            width: 65%;
            padding-right: 20px;
        }

        .table-box table {
            width: 100%;
            border-collapse: collapse;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .table-box th, .table-box td {
            padding: 12px;
            text-align: center;
            border: 1px solid #ddd;
        }

        .table-box th {
            background-color: #28a745;
            color: white;
        }

        .table-box td {
            background-color: #f9f9f9;
        }

        /* Pagination style */
        .pagination-container {
            margin-top: 20px;
            text-align: center;
        }

        .chart-container {
            width: 35%;
            display: flex;
            flex-direction: column;
        }

        .chart-box {
            width: 100%;
            margin-bottom: 20px;
        }

        .chart-box h5 {
            margin-bottom: 10px;
        }

        .chart-box canvas {
            width: 100%;
            height: 200px;
        }

        .footer {
            text-align: center;
            padding: 10px;
            background-color: #28a745;
            color: white;
            position: fixed;
            width: 100%;
            bottom: 0;
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="#">Sistem Monitoring Kualitas Udara</a>
            <a href="realtimeOke.php" class="btn btn-light">Data Realtime</a>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <div class="table-container">
            <!-- Table Data -->
            <div class="table-box">
                <h4>Tabel Data Udara</h4>
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Suhu (°C)</th>
                            <th>Kelembapan (%)</th>
                            <th>CO (ppm)</th>
                            <th>PM10 (µg/m³)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $startNumber = ($page - 1) * $limit + 1; ?>
                        <?php foreach ($rows as $index => $row): ?>
                            <tr>
                                <td><?= $startNumber + $index; ?></td>
                                <td><?= number_format($row['suhu'], 2); ?></td>
                                <td><?= number_format($row['kelembapan'], 2); ?></td>
                                <td><?= number_format($row['co'], 2); ?></td>
                                <td><?= number_format($row['pm10'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
           

            <!-- Pagination -->
            <div class="pagination-container">
                <nav aria-label="Page navigation example">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item"><a class="page-link" href="?page=1">First</a></li>
                            <li class="page-item"><a class="page-link" href="?page=<?= $page - 1; ?>">Previous</a></li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= ($i == $page) ? 'active' : ''; ?>"><a class="page-link" href="?page=<?= $i; ?>"><?= $i; ?></a></li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item"><a class="page-link" href="?page=<?= $page + 1; ?>">Next</a></li>
                            <li class="page-item"><a class="page-link" href="?page=<?= $total_pages; ?>">Last</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
            </div>
            <!-- Charts -->
            <div class="chart-container">
                <div class="chart-box">
                    <h5>Grafik Suhu (°C)</h5>
                    <canvas id="suhuChart"></canvas>
                </div>
                <div class="chart-box">
                    <h5>Grafik Kelembapan (%)</h5>
                    <canvas id="kelembapanChart"></canvas>
                </div>
                <div class="chart-box">
                    <h5>Grafik CO (ppm)</h5>
                    <canvas id="coChart"></canvas>
                </div>
                <div class="chart-box">
                    <h5>Grafik PM10 (µg/m³)</h5>
                    <canvas id="pm10Chart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div>@ Sistem Monitoring Kualitas Udara By Rendy</div>
    </footer>

    <script>
        // Data untuk chart
        var labels = [];
        var suhuData = [];
        var kelembapanData = [];
        var coData = [];
        var pm10Data = [];

        <?php foreach ($rows as $row): ?>
            labels.push('<?= $row['Id']; ?>');
            suhuData.push(<?= $row['suhu']; ?>);
            kelembapanData.push(<?= $row['kelembapan']; ?>);
            coData.push(<?= $row['co']; ?>);
            pm10Data.push(<?= $row['pm10']; ?>);
        <?php endforeach; ?>

        // Inisialisasi chart suhu
        var suhuCtx = document.getElementById('suhuChart').getContext('2d');
        var suhuChart = new Chart(suhuCtx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Suhu (°C)',
                    data: suhuData,
                    borderColor: 'rgba(255, 99, 132, 1)',
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    fill: false,
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value, index, values) {
                                return value.toFixed(2);
                            }
                        }
                    }
                }
            }
        });

        // Inisialisasi chart kelembapan
        var kelembapanCtx = document.getElementById('kelembapanChart').getContext('2d');
        var kelembapanChart = new Chart(kelembapanCtx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Kelembapan (%)',
                    data: kelembapanData,
                    borderColor: 'rgba(54, 162, 235, 1)',
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    fill: false,
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value, index, values) {
                                return value.toFixed(2);
                            }
                        }
                    }
                }
            }
        });

        // Inisialisasi chart CO
        var coCtx = document.getElementById('coChart').getContext('2d');
        var coChart = new Chart(coCtx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'CO (ppm)',
                    data: coData,
                    borderColor: 'rgba(255, 206, 86, 1)',
                    backgroundColor: 'rgba(255, 206, 86, 0.2)',
                    fill: false,
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value, index, values) {
                                return value.toFixed(2);
                            }
                        }
                    }
                }
            }
        });

        // Inisialisasi chart PM10
        var pm10Ctx = document.getElementById('pm10Chart').getContext('2d');
        var pm10Chart = new Chart(pm10Ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'PM10 (µg/m³)',
                    data: pm10Data,
                    borderColor: 'rgba(75, 192, 192, 1)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    fill: false,
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value, index, values) {
                                return value.toFixed(2);
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
