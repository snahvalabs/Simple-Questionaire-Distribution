-- Buat Database
CREATE DATABASE metode_q_db;
USE metode_q_db;

-- Tabel Informan (daftar nama dan URL)
CREATE TABLE informan (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama VARCHAR(100) NOT NULL,
    url VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Data Peserta (menyimpan data diri)
CREATE TABLE data_peserta (
    id INT PRIMARY KEY AUTO_INCREMENT,
    informan_id INT NOT NULL,
    instansi VARCHAR(200) NOT NULL,
    lama_pengalaman INT NOT NULL,
    pendidikan VARCHAR(50) NOT NULL,
    jabatan VARCHAR(100) NOT NULL,
    tanggal_akses TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    tanggal_pengisian TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (informan_id) REFERENCES informan(id)
);

-- Insert data contoh
INSERT INTO informan (nama, url) VALUES 
('Agus', 'https://form.kuesioner.com/agus'),
('Budi', 'https://form.kuesioner.com/budi'),
('Citra', 'https://form.kuesioner.com/citra');

