<?php
include 'config.php'; // Hubungkan ke database
include 'aes_dekript.php'; // Pastikan ini adalah nama file yang benar

// Tentukan jumlah data per halaman
$limit = 15; // Mengambil 15 data per halaman

// Ambil halaman yang diminta
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Ambil data terbaru dengan pagination
$sql = "SELECT * FROM `monitoring_udara` ORDER BY timestamp DESC LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);

// Inisialisasi variabel untuk menyimpan hasil
$rows = [];
$i = 1;

if ($result->num_rows > 0) {
    while ($data = $result->fetch_assoc()) {
        // Kunci enkripsi
        $key = '2b7e151628aed2a6abf7cf8919a08d3c';
        // Dekripsi data
        $row = [];
        $row['no'] = $i;
        $row['ciphertextKelembapan'] = $data['Kelembapan'] ;
        $start_time = microtime(true);
        $row['kelembapan'] = (float) decrypt(hex2bin($data['Kelembapan']), $key);
        $end_time = microtime(true);
        $execution_time = ($end_time - $start_time)*1000000;
        $row['execution_time'] = $execution_time;
        $rows[] = $row; // Menambahkan data ke array $rows
        $i++;
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
        }

        .table-container table {
            width: 100%;
            border-collapse: collapse;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .table-container th, .table-container td {
            padding: 12px;
            text-align: center;
            border: 1px solid #ddd;
        }

        .table-container th {
            background-color: #28a745;
            color: white;
        }

        .table-container td {
            background-color: #f9f9f9;
        }

        /* Pagination style */
        .pagination-container {
            margin-top: 20px;
            text-align: center;
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a href="dataOke_suhu.php" class="btn btn-light">suhu</a>
            <a href="dataOke_kelembapan.php" class="btn btn-light">kelembapan</a>
            <a href="dataOke_co.php" class="btn btn-light">co</a>
            <a href="dataOke_debu.php" class="btn btn-light">debu</a>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <!-- Table Data -->
        <div class="table-box">
            <h4>Tabel Data Udara</h4>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>ciphertext Kelembapan</th>
                            <th>Kelembapan (%)</th>
                            <th>waktu dekripsi (µs)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td><?= $row['no']; ?></td>
                                <td><?= $row['ciphertextKelembapan']; ?></td>
                                <td><?= $row['kelembapan']; ?></td>
                                <td><?= $row['execution_time']; ?></td>

                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

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

    <!-- Footer -->
    <footer class="footer">
        <div>@ Sistem Monitoring Kualitas Udara By Rendy</div>
    </footer>

</body>
</html>
