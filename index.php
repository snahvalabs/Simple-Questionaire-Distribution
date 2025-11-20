<?php
// config.php - Database Configuration
$host = 'localhost';
$dbname = 'dbname';
$username = 'user';
$password = 'password';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $informan_id = $_POST['informan_id'];
    $instansi = $_POST['instansi'];
    $pengalaman = $_POST['pengalaman'];
    $pendidikan = $_POST['pendidikan'];
    $jabatan = $_POST['jabatan'];
    
    // Get URL from informan
    $stmt = $pdo->prepare("SELECT url FROM informan WHERE id = ?");
    $stmt->execute([$informan_id]);
    $informan = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($informan) {
        // Save data diri to database
        $stmt = $pdo->prepare("INSERT INTO data_peserta (informan_id, instansi, lama_pengalaman, pendidikan, jabatan, tanggal_akses) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$informan_id, $instansi, $pengalaman, $pendidikan, $jabatan]);
        
        $redirect_url = $informan['url'];
        $success = true;
    }
}

// Get list of informan
$stmt = $pdo->query("SELECT id, nama FROM informan ORDER BY nama ASC");
$informan_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Metode Q untuk Menilai Persepsi Pemanfaatan Chatbot AI</title>
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
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 600px;
            width: 100%;
        }

        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
            font-size: 24px;
            line-height: 1.4;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            color: #555;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }

        label .required {
            color: #e74c3c;
        }

        select, input[type="text"], input[type="number"] {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            color: #333;
            background-color: #f9f9f9;
            transition: all 0.3s ease;
        }

        select {
            cursor: pointer;
        }

        select:hover, input:hover {
            border-color: #667eea;
        }

        select:focus, input:focus {
            outline: none;
            border-color: #667eea;
            background-color: white;
        }

        button {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 10px;
        }

        button:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
        }

        button:active:not(:disabled) {
            transform: translateY(0);
        }

        button:disabled {
            background: #cccccc;
            cursor: not-allowed;
            transform: none;
        }

        .info {
            background: #e8f4f8;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            color: #2c3e50;
            font-size: 13px;
            border-left: 4px solid #3498db;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: white;
            margin: 15% auto;
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 400px;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }

        .modal-content h2 {
            color: #27ae60;
            margin-bottom: 15px;
        }

        .modal-content p {
            margin-bottom: 20px;
            color: #555;
        }

        .modal-content a {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .modal-content a:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Metode Q untuk Pemanfaatan Chatbot AI</h1>
        
        <div class="info">
            📋 Mohon memilih nama yang sudah disiapkan kemudian silakan melengkapi data informan di bawah ini untuk mengakses form kuesioner Q-Sorting. Data informan hanya untuk digunakan kebutuhan riset.
        </div>

        <form method="POST" id="mainForm">
            <div class="form-group">
                <label for="informan_id">Pilih Informan: <span class="required">*</span></label>
                <select name="informan_id" id="informan_id" required>
                    <option value="">-- Pilih Nama Informan --</option>
                    <?php foreach ($informan_list as $informan): ?>
                        <option value="<?php echo $informan['id']; ?>">
                            <?php echo htmlspecialchars($informan['nama']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="instansi">Instansi/Tempat Kerja: <span class="required">*</span></label>
                <input type="text" name="instansi" id="instansi" placeholder="Contoh: Universitas Indonesia" required>
            </div>

            <div class="form-group">
                <label for="pengalaman">Lama Pengalaman Kerja (tahun): <span class="required">*</span></label>
                <input type="number" name="pengalaman" id="pengalaman" placeholder="Contoh: 5" min="0" required>
            </div>

            <div class="form-group">
                <label for="pendidikan">Pendidikan Terakhir: <span class="required">*</span></label>
                <select name="pendidikan" id="pendidikan" required>
                    <option value="">-- Pilih Pendidikan --</option>
                    <option value="SMA/SMK">SMA/SMK</option>
                    <option value="D3">D3</option>
                    <option value="S1">S1</option>
                    <option value="S2">S2</option>
                    <option value="S3">S3</option>
                </select>
            </div>

            <div class="form-group">
                <label for="jabatan">Posisi/Jabatan: <span class="required">*</span></label>
                <input type="text" name="jabatan" id="jabatan" placeholder="Contoh: Manager HRD" required>
            </div>

            <button type="submit" name="submit" id="submitBtn" disabled>Akses URL</button>
        </form>
    </div>

    <!-- Modal Success -->
    <div id="successModal" class="modal">
        <div class="modal-content">
            <h2>✅ Data Berhasil Disimpan!</h2>
            <p>Anda akan diarahkan ke halaman form Q-Sorting.</p>
            <a href="<?php echo isset($redirect_url) ? htmlspecialchars($redirect_url) : '#'; ?>" target="_blank">Buka Form</a>
        </div>
    </div>

    <script>
        const form = document.getElementById('mainForm');
        const submitBtn = document.getElementById('submitBtn');
        const informanSelect = document.getElementById('informan_id');
        const instansiInput = document.getElementById('instansi');
        const pengalamanInput = document.getElementById('pengalaman');
        const pendidikanSelect = document.getElementById('pendidikan');
        const jabatanSelect = document.getElementById('jabatan');

        // Check if all fields are filled
        function checkFormValidity() {
            const informan = informanSelect.value.trim();
            const instansi = instansiInput.value.trim();
            const pengalaman = pengalamanInput.value.trim();
            const pendidikan = pendidikanSelect.value.trim();
            const jabatan = jabatanSelect.value.trim();

            if (informan && instansi && pengalaman && pendidikan && jabatan) {
                submitBtn.disabled = false;
            } else {
                submitBtn.disabled = true;
            }
        }

        // Add event listeners to all form fields
        informanSelect.addEventListener('change', checkFormValidity);
        instansiInput.addEventListener('input', checkFormValidity);
        pengalamanInput.addEventListener('input', checkFormValidity);
        pendidikanSelect.addEventListener('change', checkFormValidity);
        jabatanSelect.addEventListener('change', checkFormValidity);

        // Show modal on success
        <?php if (isset($success) && $success): ?>
        document.getElementById('successModal').style.display = 'block';
        
        // Auto redirect after 3 seconds
        setTimeout(function() {
            window.open('<?php echo htmlspecialchars($redirect_url); ?>', '_blank');
        }, 2000);
        <?php endif; ?>
    </script>
</body>
</html>