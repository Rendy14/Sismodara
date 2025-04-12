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
        $indikator = "baik";
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
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring Udara - Real Time</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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

        .time {
            text-align: center;
            font-size: 24px;
            margin: 20px 0;
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
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .data-box div h5 {
            margin-bottom: 10px;
            font-size: 18px;
        }

        .indicator-container {
            display: flex;
            flex-direction: column; /* Membuat elemen tersusun vertikal */
            justify-content: center;
            align-items: center;
            height: 250px; /* Set height for vertical centering */
            text-align: center;
        }

        .indicator-circle {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            display: inline-block;
            line-height: 150px;
            text-align: center;
            font-size: 24px;
            color: white;
            background-color: #28a745;
            margin-top: 20px; /* Memberikan jarak antara teks dan lingkaran */
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
            <div>
                <h5>Suhu</h5>
                <p class="text-danger"><?= $suhu; ?>°C</p>
            </div>
            <div>
                <h5>Kelembapan</h5>
                <p class="text-primary"><?= $kelembapan; ?>%</p>
            </div>
            <div>
                <h5>CO</h5>
                <p class="text-secondary"><?= $co; ?> ppm</p>
            </div>
            <div>
                <h5>PM10</h5>
                <p class="text-warning"><?= $pm10; ?> µg/m³</p>
            </div>
        </div>
    
        <!-- Air Quality Indicator -->
        <div class="indicator-container">
            <h4 class="text-center">Kualitas udara saat ini</h4>
            <div class="indicator-circle bg-<?= $warna; ?>">
                <?= $indikator; ?>
            </div>
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

</body>
</html>
