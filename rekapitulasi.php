<?php
// rekapitulasi.php - Halaman Rekapitulasi Data Peserta
$host = 'localhost';
$dbname = 'dbname';
$username = 'username';
$password = 'password';


try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}

// Get filter parameter
$filter_informan = isset($_GET['informan_id']) ? $_GET['informan_id'] : '';

// Query data peserta dengan join ke tabel informan
$sql = "SELECT 
            dp.id,
            i.nama as nama_informan,
            dp.instansi,
            dp.jabatan,
            dp.lama_pengalaman,
            dp.pendidikan,
            dp.tanggal_pengisian,
            dp.tanggal_akses
        FROM data_peserta dp
        JOIN informan i ON dp.informan_id = i.id";

if ($filter_informan) {
    $sql .= " WHERE dp.informan_id = :informan_id";
}

$sql .= " ORDER BY dp.tanggal_pengisian DESC";

$stmt = $pdo->prepare($sql);
if ($filter_informan) {
    $stmt->bindParam(':informan_id', $filter_informan, PDO::PARAM_INT);
}
$stmt->execute();
$data_peserta = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get list informan untuk filter
$stmt_informan = $pdo->query("SELECT id, nama FROM informan ORDER BY nama ASC");
$informan_list = $stmt_informan->fetchAll(PDO::FETCH_ASSOC);

// Get statistik
$stmt_stats = $pdo->query("SELECT 
    COUNT(*) as total_peserta,
    COUNT(DISTINCT informan_id) as total_informan_digunakan,
    AVG(lama_pengalaman) as rata_pengalaman
FROM data_peserta");
$stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);

// Get data untuk chart pendidikan
$stmt_pendidikan = $pdo->query("SELECT 
    pendidikan, 
    COUNT(*) as jumlah 
FROM data_peserta 
GROUP BY pendidikan 
ORDER BY jumlah DESC");
$data_pendidikan = $stmt_pendidikan->fetchAll(PDO::FETCH_ASSOC);

// Get data untuk chart pengalaman kerja
$stmt_pengalaman = $pdo->query("SELECT 
    CASE 
        WHEN lama_pengalaman BETWEEN 1 AND 5 THEN '1-5 tahun'
        WHEN lama_pengalaman BETWEEN 6 AND 10 THEN '6-10 tahun'
        WHEN lama_pengalaman BETWEEN 11 AND 15 THEN '11-15 tahun'
        WHEN lama_pengalaman >= 16 AND lama_pengalaman <= 20 THEN '16-20 tahun'
        ELSE '20+ tahun'
    END as kategori,
    COUNT(*) as jumlah
FROM data_peserta
GROUP BY kategori
ORDER BY 
    CASE 
        WHEN kategori = '1-5 tahun' THEN 1
        WHEN kategori = '6-10 tahun' THEN 2
        WHEN kategori = '11-15 tahun' THEN 3
        WHEN kategori = '16-20 tahun' THEN 4
        ELSE 5
    END");
$data_pengalaman = $stmt_pengalaman->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekapitulasi Data Peserta - Metode Q</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 10px;
            font-size: 28px;
        }

        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
        }

        .stat-card h3 {
            font-size: 32px;
            margin-bottom: 5px;
        }

        .stat-card p {
            font-size: 14px;
            opacity: 0.9;
        }

        .filter-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            align-items: end;
            flex-wrap: wrap;
        }

        .filter-group {
            flex: 1;
            min-width: 200px;
        }

        .filter-group label {
            display: block;
            color: #555;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }

        select {
            width: 100%;
            padding: 10px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
            color: #333;
            background-color: white;
            cursor: pointer;
        }

        .btn {
            padding: 10px 25px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #6c757d;
        }

        .btn-export {
            background: #28a745;
            margin-left: 10px;
        }

        .table-container {
            overflow-x: auto;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
        }

        td {
            padding: 12px 15px;
            border-bottom: 1px solid #e0e0e0;
            font-size: 14px;
            color: #555;
        }

        tbody tr:hover {
            background-color: #f8f9fa;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
            font-style: italic;
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-primary {
            background: #e3f2fd;
            color: #1976d2;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: white;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .back-link:hover {
            transform: translateX(-5px);
        }

        .charts-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }

        .chart-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .chart-card h2 {
            color: #333;
            font-size: 18px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 600;
        }

        .chart-wrapper {
            position: relative;
            height: 300px;
        }

        @media (max-width: 768px) {
            .charts-container {
                grid-template-columns: 1fr;
            }

            .chart-wrapper {
                height: 250px;
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }

            .filter-section {
                flex-direction: column;
            }

            .table-container {
                font-size: 12px;
            }

            th, td {
                padding: 8px;
            }
        }
    </style>
</head>
<body>
    <a href="index.php" class="back-link">← Kembali ke Form</a>
    
    <div class="container">
        <h1>📊 Rekapitulasi Data Peserta</h1>
        <p class="subtitle">Metode Q untuk Pemanfaatan Chatbot AI</p>

        <!-- Statistik Cards -->
        <div class="stats-container">
            <div class="stat-card">
                <h3><?php echo number_format($stats['total_peserta']); ?></h3>
                <p>Total Peserta</p>
            </div>
            <div class="stat-card">
                <h3><?php echo number_format($stats['total_informan_digunakan']); ?></h3>
                <p>Informan Digunakan</p>
            </div>
            <div class="stat-card">
                <h3><?php echo number_format($stats['rata_pengalaman'], 1); ?></h3>
                <p>Rata-rata Pengalaman (Tahun)</p>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <div class="filter-group">
                <label for="filter_informan">Filter berdasarkan Informan:</label>
                <select id="filter_informan" onchange="applyFilter()">
                    <option value="">-- Semua Informan --</option>
                    <?php foreach ($informan_list as $informan): ?>
                        <option value="<?php echo $informan['id']; ?>" 
                            <?php echo ($filter_informan == $informan['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($informan['nama']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <button class="btn" onclick="applyFilter()">Terapkan Filter</button>
                <button class="btn btn-secondary" onclick="resetFilter()">Reset</button>
                <button class="btn btn-export" onclick="exportToCSV()">Export CSV</button>
            </div>
        </div>

        <!-- Tabel Data -->
        <div class="table-container">
            <table id="dataTable">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Informan</th>
                        <th>Instansi</th>
                        <th>Jabatan</th>
                        <th>Pengalaman</th>
                        <th>Pendidikan</th>
                        <th>Tanggal Pengisian</th>
                        <th>Tanggal Akses</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($data_peserta) > 0): ?>
                        <?php foreach ($data_peserta as $index => $peserta): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td>
                                    <span class="badge badge-primary">
                                        <?php echo htmlspecialchars($peserta['nama_informan']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($peserta['instansi']); ?></td>
                                <td><?php echo htmlspecialchars($peserta['jabatan']); ?></td>
                                <td><?php echo $peserta['lama_pengalaman']; ?> tahun</td>
                                <td><?php echo htmlspecialchars($peserta['pendidikan']); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($peserta['tanggal_pengisian'])); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($peserta['tanggal_akses'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="no-data">Belum ada data peserta</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Section Grafik - DITAMBAHKAN -->
        <div class="charts-container">
            <div class="chart-card">
                <h2>📚 Distribusi Pendidikan Peserta</h2>
                <div class="chart-wrapper">
                    <canvas id="chartPendidikan"></canvas>
                </div>
            </div>
            <div class="chart-card">
                <h2>⏳ Distribusi Pengalaman Kerja</h2>
                <div class="chart-wrapper">
                    <canvas id="chartPengalaman"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Data untuk Chart Pendidikan
        const dataPendidikan = {
            labels: [<?php echo implode(',', array_map(function($item) { return "'" . $item['pendidikan'] . "'"; }, $data_pendidikan)); ?>],
            datasets: [{
                data: [<?php echo implode(',', array_map(function($item) { return $item['jumlah']; }, $data_pendidikan)); ?>],
                backgroundColor: [
                    '#667eea',
                    '#764ba2',
                    '#f093fb',
                    '#4facfe',
                    '#43e97b',
                    '#fa709a'
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        };

        // Data untuk Chart Pengalaman
        const dataPengalaman = {
            labels: [<?php echo implode(',', array_map(function($item) { return "'" . $item['kategori'] . "'"; }, $data_pengalaman)); ?>],
            datasets: [{
                data: [<?php echo implode(',', array_map(function($item) { return $item['jumlah']; }, $data_pengalaman)); ?>],
                backgroundColor: [
                    '#667eea',
                    '#764ba2',
                    '#f093fb',
                    '#4facfe',
                    '#43e97b'
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        };

        // Config untuk Chart
        const chartConfig = {
            type: 'pie',
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            font: {
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(1);
                                return label + ': ' + value + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        };

        // Create Charts
        window.addEventListener('load', function() {
            const ctxPendidikan = document.getElementById('chartPendidikan').getContext('2d');
            new Chart(ctxPendidikan, {
                ...chartConfig,
                data: dataPendidikan
            });

            const ctxPengalaman = document.getElementById('chartPengalaman').getContext('2d');
            new Chart(ctxPengalaman, {
                ...chartConfig,
                data: dataPengalaman
            });
        });

        function applyFilter() {
            const informanId = document.getElementById('filter_informan').value;
            if (informanId) {
                window.location.href = '?informan_id=' + informanId;
            } else {
                window.location.href = 'rekapitulasi.php';
            }
        }

        function resetFilter() {
            window.location.href = 'rekapitulasi.php';
        }

        function exportToCSV() {
            const table = document.getElementById('dataTable');
            let csv = [];
            
            // Get headers
            const headers = [];
            for (let th of table.querySelectorAll('thead th')) {
                headers.push(th.textContent);
            }
            csv.push(headers.join(','));
            
            // Get data
            for (let row of table.querySelectorAll('tbody tr')) {
                const rowData = [];
                for (let cell of row.querySelectorAll('td')) {
                    let text = cell.textContent.trim();
                    // Remove badge if exists
                    const badge = cell.querySelector('.badge');
                    if (badge) {
                        text = badge.textContent.trim();
                    }
                    rowData.push('"' + text + '"');
                }
                if (rowData.length > 0 && rowData[0] !== '"Belum ada data peserta"') {
                    csv.push(rowData.join(','));
                }
            }
            
            // Download
            const csvContent = csv.join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', 'rekapitulasi_metode_q_' + new Date().getTime() + '.csv');
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>
</body>
</html>