<?php
require_once '../includes/auth.php';
require_login();
require_once '../includes/db.php';
require_once '../includes/utils.php';
require_once '../includes/tid-list.php';

if (session_status() === PHP_SESSION_NONE) session_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Panduan Import Transaksi BBM</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        .pertamina-blue { color: #0099da; }
        .pertamina-green { color: #43b02a; }
        .pertamina-red { color: #ed1c24; }
        .bg-pertamina-blue { background-color: #0099da; }
        .bg-pertamina-green { background-color: #43b02a; }
        .bg-pertamina-light { background-color: #f6fbfd; }
        .border-pertamina-blue { border-color: #0099da; }
        .border-pertamina-light { border-color: #e3f1fa; }
        
        .hover-lift {
            transition: transform 0.2s ease-in-out;
        }
        .hover-lift:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body class="bg-pertamina-light min-h-screen">
    <div class="flex h-screen overflow-hidden">
        <?php include '../includes/sidebar.php'; ?>
        <div class="flex-1 flex flex-col h-screen overflow-hidden bg-pertamina-light">
            <!-- Navbar -->
            <nav class="bg-white shadow-md py-3 px-4 md:px-8 flex items-center justify-between border-b-2 border-pertamina-blue flex-shrink-0 z-10">
                <button class="md:hidden text-pertamina-blue focus:outline-none" onclick="toggleSidebar()">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                <div class="flex items-center gap-3 ml-auto">
                    <span class="font-semibold text-pertamina-blue">📖 Panduan Import Transaksi</span>
                </div>
            </nav>
            
            <main class="flex-1 p-4 md:p-8 overflow-auto">
                <div class="w-full max-w-6xl mx-auto">
                    
                    <!-- Header -->
                    <div class="text-center mb-8">
                        <h1 class="text-3xl md:text-4xl font-bold text-pertamina-blue mb-4">
                            Panduan Import Transaksi BBM
                        </h1>
                        <p class="text-lg text-gray-600">
                            Pelajari cara menggunakan fitur import transaksi untuk menghemat waktu dengan format data yang fleksibel
                        </p>
                        <div class="mt-4 bg-blue-50 p-4 rounded-lg inline-block">
                            <p class="text-sm text-blue-700"><strong>🆕 Update:</strong> Template sudah disesuaikan dengan format spreadsheet client!</p>
                        </div>
                    </div>

                    <!-- Keuntungan -->
                    <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
                        <h2 class="text-2xl font-bold text-pertamina-blue mb-6">⚡ Keuntungan Fitur Import</h2>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="bg-blue-50 p-6 rounded-lg border border-blue-200">
                                <h3 class="text-lg font-semibold text-blue-800 mb-2">Efisiensi Waktu</h3>
                                <p class="text-blue-700 text-sm">Import ratusan transaksi sekaligus dengan konversi format otomatis</p>
                            </div>
                            <div class="bg-green-50 p-6 rounded-lg border border-green-200">
                                <h3 class="text-lg font-semibold text-green-800 mb-2">Akurasi Data</h3>
                                <p class="text-green-700 text-sm">Validasi otomatis dan perhitungan amount yang akurat</p>
                            </div>
                            <div class="bg-purple-50 p-6 rounded-lg border border-purple-200">
                                <h3 class="text-lg font-semibold text-purple-800 mb-2">Fleksibilitas</h3>
                                <p class="text-purple-700 text-sm">Support format CSV dan Excel dengan konversi otomatis</p>
                            </div>
                        </div>
                    </div>

                    <!-- Langkah-langkah -->
                    <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
                        <h2 class="text-2xl font-bold text-pertamina-blue mb-6">📝 Langkah-langkah Import</h2>
                        <div class="space-y-6">
                            <div class="flex items-start gap-4">
                                <div class="w-8 h-8 bg-pertamina-blue rounded-full flex items-center justify-center text-white font-bold">1</div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Download Template</h3>
                                    <p class="text-gray-600">Download template CSV atau Excel dari halaman Import Transaksi</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-4">
                                <div class="w-8 h-8 bg-pertamina-green rounded-full flex items-center justify-center text-white font-bold">2</div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Isi Data Transaksi</h3>
                                    <p class="text-gray-600">Isi template dengan data transaksi sesuai format yang ditentukan. Sistem akan otomatis mengkonversi dan menghitung total amount.</p>
                                    <div class="mt-2 bg-yellow-50 p-3 rounded-lg">
                                        <p class="text-sm text-yellow-700"><strong>💡 Tips:</strong> Format data sudah disesuaikan dengan contoh spreadsheet client. Kolom BCA dan PERTALITE bisa dikosongkan.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-start gap-4">
                                <div class="w-8 h-8 bg-purple-500 rounded-full flex items-center justify-center text-white font-bold">3</div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Upload File</h3>
                                    <p class="text-gray-600">Upload file yang sudah diisi dan klik "Preview Data"</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-4">
                                <div class="w-8 h-8 bg-orange-500 rounded-full flex items-center justify-center text-white font-bold">4</div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Review & Konfirmasi</h3>
                                    <p class="text-gray-600">Periksa data dalam preview dan konfirmasi import</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Format Data -->
                    <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
                        <h2 class="text-2xl font-bold text-pertamina-blue mb-6">📊 Format Data</h2>
                        <div class="overflow-x-auto">
                            <table class="min-w-full bg-white border border-gray-200 rounded-lg">
                                <thead>
                                    <tr class="bg-pertamina-blue text-white">
                                        <th class="px-4 py-3 text-left font-semibold">Kolom</th>
                                        <th class="px-4 py-3 text-left font-semibold">Nama</th>
                                        <th class="px-4 py-3 text-left font-semibold">Format</th>
                                        <th class="px-4 py-3 text-left font-semibold">Contoh</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="border-b border-gray-100">
                                        <td class="px-4 py-3 font-mono">A</td>
                                        <td class="px-4 py-3 font-semibold">Tanggal SPBU</td>
                                        <td class="px-4 py-3">Sunday, August 10, 2025</td>
                                        <td class="px-4 py-3 font-mono">Sunday, August 10, 2025</td>
                                    </tr>
                                    <tr class="border-b border-gray-100">
                                        <td class="px-4 py-3 font-mono">B</td>
                                        <td class="px-4 py-3 font-semibold">Shift SPBU</td>
                                        <td class="px-4 py-3">1/2/3</td>
                                        <td class="px-4 py-3 font-mono">1</td>
                                    </tr>
                                    <tr class="border-b border-gray-100">
                                        <td class="px-4 py-3 font-mono">C</td>
                                        <td class="px-4 py-3 font-semibold">Tanggal Settle</td>
                                        <td class="px-4 py-3">10/1/2024</td>
                                        <td class="px-4 py-3 font-mono">10/1/2024</td>
                                    </tr>
                                    <tr class="border-b border-gray-100">
                                        <td class="px-4 py-3 font-mono">D</td>
                                        <td class="px-4 py-3 font-semibold">Waktu</td>
                                        <td class="px-4 py-3">HH:MM</td>
                                        <td class="px-4 py-3 font-mono">1:05</td>
                                    </tr>
                                    <tr class="border-b border-gray-100">
                                        <td class="px-4 py-3 font-mono">E</td>
                                        <td class="px-4 py-3 font-semibold">Jumlah Liter</td>
                                        <td class="px-4 py-3">Angka + L</td>
                                        <td class="px-4 py-3 font-mono">1.5 L</td>
                                    </tr>
                                    <tr class="border-b border-gray-100">
                                        <td class="px-4 py-3 font-mono">F</td>
                                        <td class="px-4 py-3 font-semibold">Harga</td>
                                        <td class="px-4 py-3">Angka dengan separator</td>
                                        <td class="px-4 py-3 font-mono">13,000</td>
                                    </tr>
                                    <tr class="border-b border-gray-100">
                                        <td class="px-4 py-3 font-mono">G</td>
                                        <td class="px-4 py-3 font-semibold">BCA</td>
                                        <td class="px-4 py-3">Kosong</td>
                                        <td class="px-4 py-3 font-mono">-</td>
                                    </tr>
                                    <tr class="border-b border-gray-100">
                                        <td class="px-4 py-3 font-mono">H</td>
                                        <td class="px-4 py-3 font-semibold">PERTALITE</td>
                                        <td class="px-4 py-3">Kosong</td>
                                        <td class="px-4 py-3 font-mono">-</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- TID List -->
                    <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
                        <h2 class="text-2xl font-bold text-pertamina-blue mb-6">🏷️ Daftar TID yang Tersedia</h2>
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
                            <?php foreach ($tid_list as $tid): ?>
                                <div class="bg-gray-100 px-4 py-3 rounded-lg text-center border border-gray-300">
                                    <span class="font-mono text-sm font-semibold text-gray-700"><?= $tid ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Informasi Pemrosesan Data -->
                    <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
                        <h2 class="text-2xl font-bold text-pertamina-blue mb-6">⚙️ Cara Sistem Memproses Data</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="bg-blue-50 p-6 rounded-lg border border-blue-200">
                                <h3 class="text-lg font-semibold text-blue-800 mb-4">📊 Konversi Data Otomatis</h3>
                                <ul class="space-y-2 text-sm text-blue-700">
                                    <li class="flex items-start gap-2">
                                        <span class="w-2 h-2 bg-blue-500 rounded-full mt-2 flex-shrink-0"></span>
                                        <span><strong>Tanggal:</strong> "Sunday, August 10, 2025" → "2025-08-10"</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="w-2 h-2 bg-blue-500 rounded-full mt-2 flex-shrink-0"></span>
                                        <span><strong>Shift:</strong> "1" → "Pagi", "2" → "Siang", "3" → "Malam"</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="w-2 h-2 bg-blue-500 rounded-full mt-2 flex-shrink-0"></span>
                                        <span><strong>Jumlah Liter:</strong> "1.5 L" → 1.5</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="w-2 h-2 bg-blue-500 rounded-full mt-2 flex-shrink-0"></span>
                                        <span><strong>Harga:</strong> "13,000" → 13000</span>
                                    </li>
                                </ul>
                            </div>
                            <div class="bg-green-50 p-6 rounded-lg border border-green-200">
                                <h3 class="text-lg font-semibold text-green-800 mb-4">🧮 Perhitungan Otomatis</h3>
                                <ul class="space-y-2 text-sm text-green-700">
                                    <li class="flex items-start gap-2">
                                        <span class="w-2 h-2 bg-green-500 rounded-full mt-2 flex-shrink-0"></span>
                                        <span><strong>Total Amount:</strong> Jumlah Liter × Harga per Liter</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="w-2 h-2 bg-green-500 rounded-full mt-2 flex-shrink-0"></span>
                                        <span><strong>Contoh:</strong> 1.5 L × Rp 13,000 = Rp 19,500</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="w-2 h-2 bg-green-500 rounded-full mt-2 flex-shrink-0"></span>
                                        <span><strong>Default Values:</strong> TID: D2DF8372, Jenis BBM: Pertalite</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="w-2 h-2 bg-green-500 rounded-full mt-2 flex-shrink-0"></span>
                                        <span><strong>Pembayaran:</strong> Default BCA</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Call to Action -->
                    <div class="bg-gradient-to-r from-pertamina-blue to-pertamina-green rounded-xl shadow-lg p-8 text-center text-white">
                        <h2 class="text-2xl font-bold mb-4">🚀 Siap untuk Import Transaksi?</h2>
                        <p class="text-lg mb-6">Mari mulai menghemat waktu dengan import data transaksi secara otomatis!</p>
                        <a href="transaksi-import.php" class="inline-flex items-center gap-2 px-6 py-3 bg-white text-pertamina-blue rounded-lg font-semibold hover:bg-gray-100 transition-colors hover-lift">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                            </svg>
                            Mulai Import Transaksi
                        </a>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
