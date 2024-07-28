# 🗑️ Müllabfuhr-Benachrichtigungssystem

Eine elegante Lösung zur automatischen Benachrichtigung über bevorstehende Müllabfuhrtermine.


## 🌟 Übersicht

Dieses PHP-Script lädt einen iCal-Kalender von einer angegebenen URL herunter, extrahiert die Müllabfuhrtermine und sendet eine formatierte E-Mail-Benachrichtigung mit den bevorstehenden Terminen. Es überbrückt die Lücke zwischen digitalen Abfallkalendern und persönlichen Erinnerungen.


## 🚀 Funktionen

- 📅 **iCal-Integration**: Lädt und verarbeitet iCal-Kalender von einer angegebenen URL
- 🗓️ **Intelligente Terminextraktion**: Extrahiert Müllabfuhrtermine für die nächsten Tage
- 📊 **Wochenweise Gruppierung**: Organisiert Termine übersichtlich nach Kalenderwoche
- 📧 **Formatierte E-Mail-Benachrichtigungen**: Sendet ansprechende HTML-E-Mails mit den bevorstehenden Terminen
- 🎨 **Farbcodierung**: Unterstützt verschiedene Müllarten mit individuellen Farbcodes
- 🌍 **Sprachunterstützung**: Optionale Übersetzung der Wochentage ins Deutsche
- ⚙️ **Anpassbare Konfiguration**: Flexibel einstellbare Anzahl der anzuzeigenden Tage
- 🏠 **Automatische Adressextraktion**: Extrahiert die Adresse aus der iCal-URL (falls verfügbar)


## 📋 Voraussetzungen

- 🖥️ PHP 7.0 oder höher
- 📦 Composer (für die Installation von PHPMailer)
- 📨 SMTP-Server für den E-Mail-Versand


## 🛠️ Installation

1. Klonen Sie dieses Repository oder laden Sie die Dateien herunter.
2. Führen Sie `composer require phpmailer/phpmailer` aus, um PHPMailer zu installieren.
3. Konfigurieren Sie die Einstellungen im Config-Bereich am Anfang des Scripts.


## ⚙️ Konfiguration

Bearbeiten Sie den Config-Bereich am Anfang des Scripts, um folgende Einstellungen anzupassen:

- 🔗 iCal-URL
- 📧 SMTP-Server-Einstellungen
- 📬 E-Mail-Adressen für Absender und Empfänger
- 🔢 Anzahl der anzuzeigenden Tage
- 🌐 Optionen für Wochentagsübersetzung und Kalenderwochenanzeige
- 🎨 Farbcodes für verschiedene Müllarten


### 🔗 iCal-URL Konfiguration

Die iCal-URL kann je nach Mülldienstleister variieren. Dieses Script ist auf die Abfallwirtschaft Schaumburg (aws) ausgelegt. Hier ist eine Anleitung, wie Sie die iCal-URL für Ihre Adresse erhalten:

1. Besuchen Sie die Website der Abfallwirtschaft Schaumburg unter [https://aws-shg.de](https://aws-shg.de).
2. Klicken Sie auf "Abfuhrtermine" und geben Sie Ihre Adresse ein.
3. Rechts wird die Option "iCal-Kalenderabo" angezeigt. Klicken Sie darauf.
4. Kopieren Sie die angezeigte URL und fügen Sie sie in das Script ein.

Hier ist ein Beispiel für eine typische URL-Struktur:

```
https://kundenlogin.aws-shg.de/WasteManagementSchaumburg/WasteManagementServiceServlet?ApplicationName=Calendar&SubmitAction=sync&StandortID=XXXXXX&AboID=XXXXXX&Fra=P;R;B;S;V
```

Beachten Sie:
- Ersetzen Sie XXXXXX mit Ihren spezifischen StandortID und AboID.
- Die Parameter (P;R;B;S;V) repräsentieren verschiedene Müllarten und können je nach Ihren Abonnements variieren.

⚠️ **Sicherheitshinweis**: Da die URL möglicherweise persönliche Informationen enthält, behandeln Sie sie vertraulich und teilen Sie sie nicht öffentlich.


### 🎨 Anpassung der Farbcodes

Im Config-Bereich können Sie die Farben für verschiedene Müllarten anpassen:

```php
$userDefinedColors = [
    'paper' => '#2980b9',
    'plastic' => '#f1c40f',
    'bio' => '#27ae60',
    'residual' => '#2c3e50',
    'default' => '#95a5a6'
];
```

Sie können diese Farben nach Ihren Wünschen ändern. Das Script versucht automatisch, die richtige Farbe für jede Müllart zu wählen, basierend auf den Bezeichnungen in der iCal-Datei.


## 🕒 Einrichtung eines Cronjobs

Um das Script automatisch jede Woche auszuführen und eine E-Mail zu versenden, können Sie einen Cronjob einrichten. Hier ist eine Anleitung, wie Sie einen Cronjob erstellen, der das Script jeden Sonntag um 18 Uhr ausführt:

1. Öffnen Sie das Terminal auf Ihrem Server.
2. Geben Sie den folgenden Befehl ein, um die Crontab zu bearbeiten:
   ```
   crontab -e
   ```
3. Fügen Sie die folgende Zeile am Ende der Datei hinzu:
   ```
   0 18 * * 0 /usr/bin/php /pfad/zu/ihrem/script/notify-aktuell.php
   ```
   Ersetzen Sie `/pfad/zu/ihrem/script/` mit dem tatsächlichen Pfad zu Ihrem Script.
4. Speichern und schließen Sie die Datei.

Erklärung der Cron-Syntax:
- `0`: Minute (0-59)
- `18`: Stunde (0-23)
- `*`: Tag des Monats (1-31)
- `*`: Monat (1-12)
- `0`: Tag der Woche (0-7, wobei 0 und 7 Sonntag sind)

Diese Einstellung führt das Script jeden Sonntag um 18:00 Uhr aus.


## 🚀 Verwendung

Führen Sie das Script manuell aus oder richten Sie einen Cron-Job ein, um es regelmäßig auszuführen:

```bash
php pfad/zu/notify-aktuell.php
```

## 🛠️ Anpassung

Sie können das Script weiter anpassen, indem Sie:

- ➕ Zusätzliche Müllarten und Farbcodes im `$wasteTypeColors`-Array hinzufügen
- 🎨 Das E-Mail-Template im HTML-Teil des Scripts ändern
- 🧠 Zusätzliche Logik für die Verarbeitung der Termine hinzufügen


## ⚠️ Vorsicht

Testen Sie das Script immer mit Testdaten, bevor Sie es in einer Produktionsumgebung einsetzen.


## 🆘 Unterstützung

Bei Problemen, Fragen oder Beiträgen öffnen Sie bitte ein Issue in diesem GitHub-Repository.


## 📄 Lizenz

Dieses Projekt steht unter der MIT-Lizenz. Weitere Details finden Sie in der [LICENSE](LICENSE) Datei.

---

Entwickelt mit ❤️ für eine sauberere Umwelt