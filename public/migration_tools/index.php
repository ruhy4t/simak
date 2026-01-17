<?php
// Script Redirect ke Vercel (Simpan sebagai index.php di hosting lama)
$destination = "https://simak-xi.vercel.app" . ($_SERVER['REQUEST_URI'] ?? '/');
header("HTTP/1.1 301 Moved Permanently");
header("Location: " . $destination);
exit();
