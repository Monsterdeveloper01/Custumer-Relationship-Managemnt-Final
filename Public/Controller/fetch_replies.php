<?php
require 'db.php';
require 'functions.php';

$mid = current_marketing_id();

// Setting IMAP
$hostname = '{mail.rayterton.com:993/imap/ssl}INBOX';
$username = 'marketing@rayterton.com';
$password = 'RTNmainServer';

// Koneksi IMAP
$inbox = imap_open($hostname, $username, $password) 
    or die('❌ Tidak bisa konek ke IMAP: ' . imap_last_error());

// Ambil email sejak hari ini
$today  = date("d-M-Y");
$emails = imap_search($inbox, 'SINCE "' . $today . '"');

if ($emails) {
    rsort($emails); // terbaru duluan

    foreach ($emails as $email_number) {
        $overview = imap_fetch_overview($inbox, $email_number, 0)[0];

        // Ambil Message-ID (unik tiap email)
        $message_id = $overview->message_id ?? null;

        // Cek apakah email ini sudah ada di DB
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM email_replies WHERE message_id = :mid");
        $stmt->execute(['mid' => $message_id]);
        if ($stmt->fetchColumn() > 0) {
            continue; // sudah ada → skip
        }

        // Ambil struktur email
        $structure = imap_fetchstructure($inbox, $email_number);
        $message   = '';

        if (isset($structure->parts) && count($structure->parts)) {
            for ($i = 0; $i < count($structure->parts); $i++) {
                $part = $structure->parts[$i];
                $body = imap_fetchbody($inbox, $email_number, $i+1);

                // Decode sesuai encoding
                if ($part->encoding == 3) { // BASE64
                    $body = base64_decode($body);
                } elseif ($part->encoding == 4) { // QUOTED-PRINTABLE
                    $body = quoted_printable_decode($body);
                }

                if ($part->subtype == 'HTML') {
                    $message = $body;
                    break;
                } elseif ($part->subtype == 'PLAIN' && $message == '') {
                    $message = nl2br($body);
                }
            }
        } else {
            $message = imap_fetchbody($inbox, $email_number, 1);
        }

        // Ambil info header
        $subject = isset($overview->subject) ? imap_utf8($overview->subject) : '(No Subject)';
        $from    = isset($overview->from) ? $overview->from : '';
        $date    = isset($overview->date) ? date("Y-m-d H:i:s", strtotime($overview->date)) : date("Y-m-d H:i:s");

        // Ambil email pengirim
        $from_email = '';
        if (preg_match('/<(.+?)>/', $from, $matches)) {
            $from_email = $matches[1];
        } else {
            $from_email = $from;
        }

        // Simpan ke database
        $stmt = $pdo->prepare("INSERT INTO email_replies 
            (company_email, marketing_id, subject, body, from_email, received_at, message_id) 
            VALUES (:company_email, :marketing_id, :subject, :body, :from_email, :received_at, :message_id)");
        $stmt->execute([
            'company_email' => $username,
            'marketing_id'  => $mid,
            'subject'       => $subject,
            'body'          => $message,
            'from_email'    => $from_email,
            'received_at'   => $date,
            'message_id'    => $message_id
        ]);

        // Tandai sebagai sudah dibaca
        imap_setflag_full($inbox, $email_number, "\\Seen");
    }
}

imap_close($inbox);

// Redirect otomatis ke view_replies.php
header("Location: view_replies.php");
exit;
