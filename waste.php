<?php
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 1. Besuchen Sie die Website der Abfallwirtschaft Schaumburg unter https://aws-shg.de.
// 2. Klicken Sie auf "Abfuhrtermine" und geben Sie Ihre Adresse ein.
// 3. Rechts wird die Option "iCal-Kalenderabo" angezeigt. Klicken Sie darauf.
// 4. Kopieren Sie die angezeigte URL und fügen Sie sie in das Script ein.
//
// Hier ist ein Beispiel für eine typische URL-Struktur:
// https://kundenlogin.aws-shg.de/WasteManagementSchaumburg/WasteManagementServiceServlet?ApplicationName=Calendar&SubmitAction=sync&StandortID=XXXXXX&AboID=XXXXXX&Fra=P;R;B;S;V
//
// Beachten Sie:
// - Ersetzen Sie XXXXXX mit Ihren spezifischen Standort ID und Abo ID.
// - Die Parameter (P;R;B;S;V) repräsentieren verschiedene Müllarten und können je nach Ihren Abonnements variieren.
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Config-Bereich
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Kalenderabo-URL
$icalUrl = "";

// Ort Einstellungen - nur für die Anzeige in der E-Mail
$street = "Str";
$hno = "1";
$zip = "12345";
$city = "Musterstadt";

// E-Mail-Einstellungen
$smtpHost = 'mail.musterserver.tld';
$smtpUsername = 'waste@abfallnotify.tld';
$smtpPassword = 'Password';
$smtpPort = 465;
$emailFrom = 'waste@abfallnotify.de';
$emailFromName = 'Absendername';
$emailTo = ['waste@user.tld'];
$emailBcc = ['wastebcc1@user.tld', 'wastebcc2@user.tld', 'wastebcc3@user.tld'];
$emailSubject = 'Betreffzeile';

// Hinweistext am Ende der E-Mail
$footerText = 'Diese E-Mail wurde automatisch erstellt. Bitte antworten Sie nicht auf diese E-Mail';

// Anzahl der Tage, die in der E-Mail angezeigt werden sollen
$numberOfDays = 4;

// Wochentage übersetzen?
$translateWeekdays = true;

// Kalenderwoche anzeigen?
$showCalendarWeek = true;

// E-Mail-Inhalte
$emailTitle = "Anstehende Müllabholungen";
$emailHeading = "Anstehende Müllabholungen";
$emailIntro = "Hier sind die nächsten Müll-Abholtermine für die";

// Abfallarten und deren Farben
$wasteTypes = [
    'restabfall' => ['backgroundColor' => '#2c3e50', 'textColor' => '#ffffff'],
    'bioabfall' => ['backgroundColor' => '#27ae60', 'textColor' => '#ffffff'],
    'sommerbiotonne' => ['backgroundColor' => '#27ae60', 'textColor' => '#ffffff'],
    'leichtverpackungen' => ['backgroundColor' => '#f1c40f', 'textColor' => '#000000'],
    'altpapier' => ['backgroundColor' => '#2980b9', 'textColor' => '#ffffff'],
    'default' => ['backgroundColor' => '#f8f9fa', 'textColor' => '#333333']
];

// Funktion zur Konvertierung englischer Wochentage in deutsche
function getDeutscherWochentag($englischerTag) {
    if (!$GLOBALS['translateWeekdays']) {
        return $englischerTag;
    }
    switch ($englischerTag) {
        case 'Monday':    return 'Montag';
        case 'Tuesday':   return 'Dienstag';
        case 'Wednesday': return 'Mittwoch';
        case 'Thursday':  return 'Donnerstag';
        case 'Friday':    return 'Freitag';
        case 'Saturday':  return 'Samstag';
        case 'Sunday':    return 'Sonntag';
        default:          return $englischerTag;
    }
}

// Funktion zum Abrufen und Überprüfen des iCal-Inhalts
function fetchIcalContent($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        die("Error fetching iCal content: " . curl_error($ch));
    }
    
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode != 200) {
        die("Failed to fetch iCal content. HTTP Code: " . $httpCode);
    }
    
    // Check if the response is a valid iCal content
    if (strpos($response, 'BEGIN:VCALENDAR') === false) {
        die("The response does not contain valid iCal content.");
    }
    
    return $response;
}

// Fetch iCal content
$icalContent = fetchIcalContent($icalUrl);

// Extrahiere alle SUMMARY- und DTSTART-Einträge
preg_match_all('/DTSTART;VALUE=DATE:(.*?)(?:\r\n|\r|\n)DTEND.*?(?:\r\n|\r|\n).*?SUMMARY;LANGUAGE=de:(.*?)(?:\r\n|\r|\n)/s', $icalContent, $matches, PREG_SET_ORDER);

$events = [];
foreach ($matches as $match) {
    $date = DateTime::createFromFormat('Ymd', $match[1]);
    $summary = trim($match[2]);
    $events[] = [
        "date" => $date,
        "summary" => $summary
    ];
}

// Sortiere Events nach Datum
usort($events, function($a, $b) {
    return $a['date'] <=> $b['date'];
});

// Filtere zukünftige Events
$futureEvents = array_filter($events, function($event) {
    return $event['date'] >= new DateTime();
});

// Wähle die nächsten Events aus, einschließlich aller Events am letzten Tag
$nextEvents = [];
$lastDate = null;
foreach ($futureEvents as $event) {
    if (count($nextEvents) < $numberOfDays || ($lastDate && $event['date'] == $lastDate)) {
        $nextEvents[] = $event;
        $lastDate = $event['date'];
    } else {
        break;
    }
}

// Gruppiere Events nach Kalenderwoche
$groupedEvents = [];
foreach ($nextEvents as $event) {
    $kw = $event['date']->format('W');
    $year = $event['date']->format('Y');
    if (!isset($groupedEvents[$year][$kw])) {
        $groupedEvents[$year][$kw] = [];
    }
    $groupedEvents[$year][$kw][] = $event;
}

// E-Mail HTML-Inhalt erstellen
$emailContent = "
<!DOCTYPE html>
<html lang='de'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>$emailTitle</title>
    <style>
        /* Grundlegende Stile */
        body, table, td {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333333;
        }
        /* Dark mode Anpassungen */
        @media (prefers-color-scheme: dark) {
            body, table, td {
                background-color: #333333 !important;
                color: #ffffff !important;
            }
        }
    </style>
</head>

<body style='margin: 0; padding: 0; background-color: #ffffff;'>
    <table role='presentation' style='width: 100%; border-collapse: collapse;'>
        <tr>
            <td align='center' style='padding: 20px;'>
                <table role='presentation' style='width: 100%; max-width: 600px; border-collapse: collapse;'>
                    <tr>
                        <td align='center' style='padding: 20px; background-color: #ffffff;'>
                            <h1 style='color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px;'>$emailHeading</h1>
                            <p>$emailIntro<b>
                            <br><br>".$street." ".$hno."<br>".$zip." ".$city."</b></p>
                        </td>
                    </tr>";

foreach ($groupedEvents as $year => $weeks) {
    foreach ($weeks as $kw => $events) {
        $monday = new DateTime();
        $monday->setISODate($year, $kw);
        $sunday = clone $monday;
        $sunday->modify('+6 days');

        if ($showCalendarWeek) {
            $emailContent .= "
                    <tr>
                        <td align='center' style='padding: 10px 0 5px 0;'>
                            <h2 style='color: #2c3e50; font-size: 1.2em; text-align: center; margin: 0;'>KW $kw ($year) - " . $monday->format('d.m.') . " bis " . $sunday->format('d.m.') . "</h2>
                        </td>
                    </tr>";
        }

        foreach ($events as $event) {
            $englishDayName = $event['date']->format('l');
            $germanDayName = getDeutscherWochentag($englishDayName);
            $dateFormatted = $germanDayName . ', ' . $event['date']->format('d.m.Y');
            $summary = htmlspecialchars($event['summary']);

            // Farben basierend auf der Abholung
            $wasteType = strtolower($summary);
            $colors = isset($wasteTypes[$wasteType]) ? $wasteTypes[$wasteType] : $wasteTypes['default'];
            $backgroundColor = $colors['backgroundColor'];
            $textColor = $colors['textColor'];

            $emailContent .= "
            <tr>
                <td style='padding: 5px 10px;'>
                    <table role='presentation' style='width: 100%; border-collapse: collapse; border-radius: 5px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.1);'>
                        <tr>
                            <td style='background-color: $backgroundColor; color: $textColor; padding: 15px;'>
                                <div style='font-weight: bold; margin-bottom: 5px;'>$dateFormatted</div>
                                <div style='margin-top: 5px;'>$summary</div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>";
        }
    }
}

$emailContent .= "
        <tr>
            <td align='center' style='padding: 20px;'>
                <p style='font-size: 8pt; color: #999999;'>" . htmlspecialchars($footerText) . "</p>
            </td>
        </tr>
    </table>
</td>
</tr>
</table>
</body>
</html>";

// E-Mail senden mit PHPMailer
$mail = new PHPMailer(true);
// SMTP-Methode basierend auf Port - Diesen Abschnitt nicht ändern (nur oben den gewünschten Port angeben)
$useStartTLS = $smtpPort == 587; // Wenn der Port 587 ist, wird STARTTLS verwendet

try {
    // Server-Einstellungen
    $mail->isSMTP();
    $mail->Host       = $smtpHost;
    $mail->SMTPAuth   = true;
    $mail->Username   = $smtpUsername;
    $mail->Password   = $smtpPassword;
    $mail->SMTPSecure = $useStartTLS ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = $smtpPort;
    $mail->setLanguage("de");
    $mail->CharSet = "UTF-8";

    // Empfänger
    $mail->setFrom($emailFrom, $emailFromName);
    foreach ($emailTo as $recipient) {
        $mail->addAddress($recipient);
    }
    foreach ($emailBcc as $bccRecipient) {
        $mail->addBCC($bccRecipient);
    }

    // Inhalt
    $mail->isHTML(true);
    $mail->Subject = $emailSubject;
    $mail->Body    = $emailContent;

    $mail->send();
    echo "E-Mail wurde erfolgreich gesendet.\n";
} catch (Exception $e) {
    echo "E-Mail konnte nicht gesendet werden. Fehler: {$mail->ErrorInfo}\n";
}
