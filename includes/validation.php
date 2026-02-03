<?php
function validate_register($data) {
    $errors = [];
    if (empty($data['nama'])) $errors[] = "Nama wajib diisi";
    if (empty($data['email'])) $errors[] = "Email wajib diisi";
    if (empty($data['password'])) $errors[] = "Password wajib diisi";
    if ($data['password'] !== $data['confirm_password']) $errors[] = "Konfirmasi password tidak cocok";
    return $errors;
}

function validate_login($data) {
    $errors = [];
    if (empty($data['email'])) $errors[] = "Email wajib diisi";
    if (empty($data['password'])) $errors[] = "Password wajib diisi";
    return $errors;
}

function validate_transaksi($data) {
    $errors = [];
    foreach (['tanggal','mid','tid','tipe_id','jenis_bbm','amount'] as $field) {
        if (empty($data[$field])) $errors[] = ucfirst($field) . " wajib diisi";
    }
    return $errors;
}
?> 