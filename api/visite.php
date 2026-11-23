<?php

header("Content-Type: application/json; charset=utf-8");

// CONFIGURATION TELEGRAM
$botToken = "9003752696:AAH14D6x32yPqjTJFogbxzcuAPbJYy4FCeg";
$chatId = "8365539748";

// Informations générales de la visite
$page = basename(parse_url($_SERVER["REQUEST_URI"] ?? "/", PHP_URL_PATH));
$date = date("d/m/Y à H:i:s");

$ip = $_SERVER["REMOTE_ADDR"] ?? "Inconnue";

$country = "Inconnu";
$city = "Inconnue";

// Recherche du pays et de la ville à partir de l'IP
if (filter_var($ip, FILTER_VALIDATE_IP)) {

    $locationUrl = "https://ipapi.co/" . rawurlencode($ip) . "/json/";

    $context = stream_context_create([
        "http" => [
            "timeout" => 5
        ]
    ]);

    $locationResponse = @file_get_contents(
        $locationUrl,
        false,
        $context
    );

    if ($locationResponse !== false) {

        $locationData = json_decode(
            $locationResponse,
            true
        );

        if (is_array($locationData)) {

            $country = $locationData["country_name"] ?? "Inconnu";
            $city = $locationData["city"] ?? "Inconnue";
        }
    }
}

// Création du message Telegram
$message = "🔔 NOUVELLE VISITE\n\n";

$message .= "🌐 Site : "
    . ($_SERVER["HTTP_HOST"] ?? "Inconnu")
    . "\n";

$message .= "📄 Page : "
    . $page
    . "\n";

$message .= "🕒 Date : "
    . $date
    . "\n";

$message .= "🌍 Adresse IP : "
    . $ip
    . "\n";

$message .= "🇺🇳 Pays : "
    . $country
    . "\n";

$message .= "🏙️ Ville : "
    . $city;

// Envoi Telegram
$url =
    "https://api.telegram.org/bot"
    . $botToken
    . "/sendMessage";

$postData = [
    "chat_id" => $chatId,
    "text" => $message
];

$ch = curl_init($url);

curl_setopt($ch, CURLOPT_POST, true);

curl_setopt(
    $ch,
    CURLOPT_POSTFIELDS,
    http_build_query($postData)
);

curl_setopt(
    $ch,
    CURLOPT_RETURNTRANSFER,
    true
);

curl_setopt(
    $ch,
    CURLOPT_TIMEOUT,
    10
);

$result = curl_exec($ch);

if ($result === false) {

    curl_close($ch);

    http_response_code(500);

    echo json_encode([
        "ok" => false,
        "error" => "Erreur de connexion à Telegram"
    ]);

    exit;
}

$status = curl_getinfo(
    $ch,
    CURLINFO_HTTP_CODE
);

curl_close($ch);

if ($status !== 200) {

    http_response_code(500);

    echo json_encode([
        "ok" => false,
        "error" => "Telegram a retourné une erreur"
    ]);

    exit;
}

echo json_encode([
    "ok" => true
]);