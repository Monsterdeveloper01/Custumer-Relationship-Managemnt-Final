<?php
// functions.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function require_login()
{
    if (!isset($_SESSION['user'])) {
        header('Location: login.php');
        exit;
    }
}

function current_marketing_id()
{
    return $_SESSION['user']['marketing_id'] ?? null;
}

// Fungsi helper untuk escape HTML
function h($s)
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

// Fungsi helper untuk normalisasi status
function normStatus($status)
{
    return strtolower(trim($status));
}

function initcap(string $text): string
{
    return ucwords(strtolower(trim($text)));
}

// Email builder (ID)
function build_message_id(array $c, string $mkt, string $promo): array
{
    $perusahaan = initcap($c['nama_perusahaan']);
    $nama       = initcap($c['nama'] ?: $perusahaan);
    $jabatan    = $c['jabatan_lengkap'] ? "<br>" . initcap($c['jabatan_lengkap']) : '';

    $subject = "Penawaran Solusi Software, IT & Konsultasi 100% Tanpa Risiko";

    $body  = "Kepada Yth. Bapak/Ibu {$nama}{$jabatan},<br><br>";
    $body .= "Perkenalkan, saya {$mkt} dari PT Rayterton Indonesia.<br><br>";
    $body .= "Kami menawarkan solusi software IT dan bisnis dengan prinsip 100% Tanpa Risiko. "
        . "Software kami bersifat bestfit yang sepenuhnya bisa disesuaikan dengan proses dan kebutuhan bisnis Anda. "
        . "Tim kami dapat melakukan kustomisasi tanpa batas hingga benar-benar sesuai, semua tanpa biaya atau perjanjian di muka. "
        . "Proposal baru akan dikirim setelah software kami lolos uji (User Acceptance Testing / UAT) dan siap untuk go live di perusahaan Anda. 100% Risk Free.<br><br>"
        . "Tersedia untuk berbagai industri seperti Banking, Multifinance, Insurance, Manufacturing, Retail, Distribution, Government/Ministry/BUMN/BUMD, "
        . "Oil and Gas, Transportation & Logistics, Hotels and Hospitality, Travel, Property, dan lainnya.<br><br>";

    $body .= '<a href="https://www.rayterton.com" style="color:#2563eb;text-decoration:underline;">https://www.rayterton.com</a><br><br>';
    $body .= "Kami juga menyediakan jasa IT Consulting dan Management Consulting 100% Risk Free. "
        . "Dokumen konsultasi di awal (sebelum eksekusi) dapat Anda peroleh tanpa biaya di muka dan tanpa perjanjian terlebih dahulu.<br><br>";
    $body .= '<a href="https://www.rayterton.com/it-consulting.php" style="color:#2563eb;text-decoration:underline;">https://www.rayterton.com/it-consulting.php</a><br>';
    $body .= '<a href="https://www.rayterton.com/management-consulting.php" style="color:#2563eb;text-decoration:underline;">https://www.rayterton.com/management-consulting.php</a><br><br>';
    $body .= "Selain itu, kami menyediakan Rayterton Academy, program training pelatihan praktis IT, Business and Finance, Entrepreneurship, Leadership, serta Management dan Career, "
        . "yang membantu meningkatkan kompetensi tim Anda.<br><br>";
    $body .= '<a href="https://www.raytertonacademy.com" style="color:#2563eb;text-decoration:underline;">https://www.raytertonacademy.com</a><br><br>';
    $body .= "Sebagai apresiasi, kami berikan kode promo berikut:<br>";
    $body .= "<b>Kode Promo: {$promo}</b><br>";
    $body .= "Gunakan kode ini untuk mendapatkan penawaran khusus dari kami.<br><br>";
    $body .= "Hormat kami,<br>{$mkt}<br>Marketing Consultant/Partner<br>PT Rayterton Indonesia<br>";

    return [$subject, $body];
}

// Email builder (EN)
function build_message_en(array $c, string $mkt, string $promo): array
{
    $perusahaan = initcap($c['nama_perusahaan']);
    $nama       = initcap($c['nama'] ?: $perusahaan);
    $jabatan    = $c['jabatan_lengkap'] ? "<br>" . initcap($c['jabatan_lengkap']) : '';

    $subject = "100% Risk-Free Offer - Software, IT & Management Consulting";

    $body  = "Dear Mr./Ms. {$nama}{$jabatan},<br><br>";
    $body .= "My name is {$mkt} from PT Rayterton Indonesia.<br><br>";
    $body .= "We provide software solutions, IT consulting, and management consulting under a 100% Risk-Free principle. "
        . "Our best-fit software can be fully customized to your business processes and requirements. "
        . "Unlimited customization until it perfectly fits—no upfront cost or agreement. "
        . "A proposal will only be sent after our software passes User Acceptance Testing (UAT) and is ready to go live. "
        . "This service is available for various industries such as Banking, Multifinance, Insurance, Manufacturing, Retail, Distribution, Government/Ministry/State-Owned Enterprises, "
        . "Oil and Gas, Transportation & Logistics, Hotels and Hospitality, Travel, Property, and more.<br><br>";
    $body .= "https://www.rayterton.com<br><br>";
    $body .= "We also provide IT Consulting and Management Consulting 100% Risk Free. "
        . "Initial consulting documents (before execution) are available without any upfront cost or agreement.<br><br>";
    $body .= "https://www.rayterton.com/it-consulting.php<br>";
    $body .= "https://www.rayterton.com/management-consulting.php<br><br>";
    $body .= "Additionally, Rayterton Academy offers practical training in IT, Business and Finance, Entrepreneurship, Leadership, as well as Management and Career development, "
        . "helping to enhance your team's competence in today's evolving digital business environment. "
        . "This program also accelerates career growth for you and your colleagues.<br><br>";
    $body .= "https://www.raytertonacademy.com<br><br>";
    $body .= "Promo Code: {$promo}<br>";
    $body .= "Use this code to access our special offer.<br><br>";
    $body .= "Best regards,<br>{$mkt}<br>Marketing Consultant/Partner<br>PT Rayterton Indonesia<br>";

    return [$subject, $body];
}
