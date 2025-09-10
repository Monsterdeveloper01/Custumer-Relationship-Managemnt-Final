<?php
if (function_exists('imap_open')) {
    echo "<h2 style='color:green;'>✅ Ekstensi IMAP sudah aktif di server ini.</h2>";
} else {
    echo "<h2 style='color:red;'>❌ Ekstensi IMAP TIDAK aktif di server ini.</h2>";
    echo "<p>Silakan aktifkan dulu ekstensi <b>imap</b> di php.ini atau cPanel → PHP Extensions.</p>";
}

echo "<h3>Daftar ekstensi PHP yang aktif:</h3>";
echo "<pre>";
print_r(get_loaded_extensions());
echo "</pre>";
?>
