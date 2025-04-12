<?php
// Hubungkan ke database
include 'config.php';

// Pastikan metode request adalah POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ambil data dari Arduino
    $suhu = $_POST['suhu'];
    $kelembapan = $_POST['kelembapan'];
    $konsentrasi_co = $_POST['konsentrasi_co'];
    $pm10 = $_POST['pm10'];

        // Simpan data ke database
        $sql = "INSERT INTO monitoring_udara (Suhu, Kelembapan, `Konsentrasi CO`, PM10)
                VALUES ('$suhu', '$kelembapan', '$konsentrasi_co', '$pm10')";

        if ($conn->query($sql) === TRUE) {
            echo "Data berhasil disimpan.";
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
} else {
    echo "Metode request tidak valid!";
}
?>
