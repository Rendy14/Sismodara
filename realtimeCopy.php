<?php
include 'config.php'; // Hubungkan ke database
include 'aes_dekript.php';

// Ambil data terbaru dari tabel monitoring_udara
$sql = "SELECT * FROM `monitoring_udara` ORDER BY timestamp DESC LIMIT 1";
$result = $conn->query($sql);

// Inisialisasi variabel default jika data tidak tersedia
$suhu = $kelembapan = $co = $pm10 = "--";
$indikator = "Menunggu Data";
$warna = "secondary"; // Warna default indikator
$progress = 0; // Nilai progress bar default

if ($result->num_rows > 0) {
    $data = $result->fetch_assoc();

    // Kunci enkripsi
    $key = '2b7e151628aed2a6abf7cf8919a08d3c';
    // Dekripsi data
    $suhu = (float) decrypt(hex2bin($data['Suhu']), $key);
    $kelembapan = (float) decrypt(hex2bin($data['Kelembapan']), $key);
    $co = (float) decrypt(hex2bin($data['Konsentrasi CO']), $key);
    $pm10 = (float) decrypt(hex2bin($data['PM10']), $key);

    // Menentukan indikator kualitas udara
    if ($pm10 <= 50 && $co <= 2) {
        $indikator = "Baik";
        $warna = "success";
        $progress = 100; // Progress bar penuh jika udara bersih
    } elseif ($pm10 <= 100 && $co <= 6) {
        $indikator = "Sedang";
        $warna = "primary";
        $progress = 75;
    } elseif ($pm10 <= 150 || $co <= 9) {
        $indikator = "Tidak Sehat";
        $warna = "warning";
        $progress = 50;
    } else {
        $indikator = "Berbahaya";
        $warna = "danger";
        $progress = 25;
    }
}

// Ambil data historis untuk grafik
$sql_history = "SELECT * FROM `monitoring_udara` ORDER BY timestamp DESC LIMIT 100";
$result_history = $conn->query($sql_history);

$timestamps = [];
$suhu_data = [];
$kelembapan_data = [];
$co_data = [];
$pm10_data = [];

if ($result_history->num_rows > 0) {
    while ($row = $result_history->fetch_assoc()) {
        $timestamps[] = date('H:i', strtotime($row['timestamp']));
        $suhu_data[] = (float) decrypt(hex2bin($row['Suhu']), $key);
        $kelembapan_data[] = (float) decrypt(hex2bin($row['Kelembapan']), $key);
        $co_data[] = (float) decrypt(hex2bin($row['Konsentrasi CO']), $key);
        $pm10_data[] = (float) decrypt(hex2bin($row['PM10']), $key);
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring Udara - Real Time</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f8f9fa;
            height: 100vh;
            display: flex;
            flex-direction: column;
            margin: 0;
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
            font-size: 24px;
            font-weight: bold;
        }

        .time {
            text-align: center;
            font-size: 28px;
            margin: 20px 0;
            color: #343a40;
        }

        .data-box {
            display: flex;
            justify-content: space-between;
            margin: 20px 0;
        }

        .data-box div {
            width: 23%;
            padding: 20px;
            text-align: center;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .data-box div:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }

        .data-box div h5 {
            margin-bottom: 10px;
            font-size: 18px;
            color: #343a40;
        }

        .data-box div p {
            font-size: 24px;
            font-weight: bold;
        }

        .indicator-container {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 300px;
            text-align: center;
            margin: 20px 0;
        }

        .indicator-circle {
            width: 180px;
            height: 180px;
            border-radius: 50%;
            display: inline-block;
            line-height: 180px;
            text-align: center;
            font-size: 28px;
            color: white;
            background-color: #28a745;
            margin-top: 20px;
            transition: background-color 0.3s;
        }

        .progress-container {
            width: 100%;
            margin-top: 20px;
        }

        .progress {
            height: 30px;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .progress-bar {
            height: 100%;
            border-radius: 15px;
            transition: width 0.5s;
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

        .tooltip {
            position: relative;
            display: inline-block;
        }

        .tooltip .tooltiptext {
            visibility: hidden;
            width: 120px;
            background-color: black;
            color: #fff;
            text-align: center;
            border-radius: 5px;
            padding: 5px 0;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            margin-left: -60px;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .tooltip:hover .tooltiptext {
            visibility: visible;
            opacity: 1;
        }

        .chart-container {
            margin: 20px 0;
        }

        .chart-container canvas {
            max-width: 100%;
            height: auto;
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="#">Sistem Monitoring Kualitas Udara</a>
            <a href="dataOke.php" class="btn btn-light">Lihat Data & Grafik</a>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <!-- Jam Otomatis -->
        <div class="time">
            <span id="clock"></span>
        </div>

        <h3 class="text-center">Data Udara Real-Time</h3>

        <!-- Data Boxes -->
        <div class="data-box">
            <div class="tooltip">
                <h5>Suhu</h5>
                <p class="text-danger"><?= $suhu; ?>°C</p>
                <span class="tooltiptext">Suhu udara saat ini</span>
            </div>
            <div class="tooltip">
                <h5>Kelembapan</h5>
                <p class="text-primary"><?= $kelembapan; ?>%</p>
                <span class="tooltiptext">Kelembapan udara saat ini</span>
            </div>
            <div class="tooltip">
                <h5>CO</h5>
                <p class="text-secondary"><?= $co; ?> ppm</p>
                <span class="tooltiptext">Konsentrasi CO saat ini</span>
            </div>
            <div class="tooltip">
                <h5>PM10</h5>
                <p class="text-warning"><?= $pm10; ?> µg/m³</p>
                <span class="tooltiptext">Konsentrasi PM10 saat ini</span>
            </div>
        </div>
    
        <!-- Air Quality Indicator -->
        <div class="indicator-container">
            <h4 class="text-center">Kualitas Udara Saat Ini</h4>
            <div class="indicator-circle bg-<?= $warna; ?>">
                <?= $indikator; ?>
            </div>
            <div class="progress-container">
                <div class="progress">
                    <div class="progress-bar bg-<?= $warna; ?>" role="progressbar" style="width: <?= $progress; ?>%;" aria-valuenow="<?= $progress; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="chart-container">
            <canvas id="suhuChart"></canvas>
        </div>
        <div class="chart-container">
            <canvas id="kelembapanChart"></canvas>
        </div>
        <div class="chart-container">
            <canvas id="coChart"></canvas>
        </div>
        <div class="chart-container">
            <canvas id="pm10Chart"></canvas>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div>@ Sistem Monitoring Kualitas Udara By Rendy</div>
    </footer>

    <!-- JavaScript for Real-time Clock -->
    <script>
        function updateClock() {
            const now = new Date();
            let hours = now.getHours().toString().padStart(2, '0');
            let minutes = now.getMinutes().toString().padStart(2, '0');
            let seconds = now.getSeconds().toString().padStart(2, '0');
            document.getElementById('clock').textContent = `${hours} : ${minutes} : ${seconds}`;
        }

        setInterval(updateClock, 1000); // Update the clock every second
        updateClock(); // Initial call to display the clock right away
    </script>

    <!-- JavaScript for Charts -->
    <script>
        const timestamps = <?php echo json_encode(array_reverse($timestamps)); ?>;
        const suhuData = <?php echo json_encode(array_reverse($suhu_data)); ?>;
        const kelembapanData = <?php echo json_encode(array_reverse($kelembapan_data)); ?>;
        const coData = <?php echo json_encode(array_reverse($co_data)); ?>;
        const pm10Data = <?php echo json_encode(array_reverse($pm10_data)); ?>;

        const suhuChart = new Chart(document.getElementById('suhuChart'), {
            type: 'line',
            data: {
                labels: timestamps,
                datasets: [{
                    label: 'Suhu (°C)',
                    data: suhuData,
                    borderColor: 'rgba(220, 53, 69, 1)',
                    backgroundColor: 'rgba(220, 53, 69, 0.2)',
                    fill: true,
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Grafik Suhu Udara'
                    }
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Waktu'
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Suhu (°C)'
                        }
                    }
                }
            }
        });

        const kelembapanChart = new Chart(document.getElementById('kelembapanChart'), {
            type: 'line',
            data: {
                labels: timestamps,
                datasets: [{
                    label: 'Kelembapan (%)',
                    data: kelembapanData,
                    borderColor: 'rgba(54, 162, 235, 1)',
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    fill: true,
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Grafik Kelembapan Udara'
                    }
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Waktu'
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Kelembapan (%)'
                        }
                    }
                }
            }
        });

        const coChart = new Chart(document.getElementById('coChart'), {
            type: 'line',
            data: {
                labels: timestamps,
                datasets: [{
                    label: 'Konsentrasi CO (ppm)',
                    data: coData,
                    borderColor: 'rgba(153, 102, 255, 1)',
                    backgroundColor: 'rgba(153, 102, 255, 0.2)',
                    fill: true,
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Grafik Konsentrasi CO'
                    }
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Waktu'
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Konsentrasi CO (ppm)'
                        }
                    }
                }
            }
        });

        const pm10Chart = new Chart(document.getElementById('pm10Chart'), {
            type: 'line',
            data: {
                labels: timestamps,
                datasets: [{
                    label: 'Konsentrasi PM10 (µg/m³)',
                    data: pm10Data,
                    borderColor: 'rgba(255, 193, 7, 1)',
                    backgroundColor: 'rgba(255, 193, 7, 0.2)',
                    fill: true,
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Grafik Konsentrasi PM10'
                    }
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Waktu'
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Konsentrasi PM10 (µg/m³)'
                        }
                    }
                }
            }
        });
    </script>

</body>
</html>
