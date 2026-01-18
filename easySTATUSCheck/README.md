# easySTATUSCheck

![easySTATUSCheck Logo](https://img.shields.io/badge/WordPress-Plugin-blue?style=for-the-badge&logo=wordpress)
![Version](https://img.shields.io/badge/Version-1.0.0-green?style=for-the-badge)
![PHP](https://img.shields.io/badge/PHP-7.4+-purple?style=for-the-badge&logo=php)
![WordPress](https://img.shields.io/badge/WordPress-5.0+-blue?style=for-the-badge&logo=wordpress)

Ein professionelles WordPress-Plugin zur Überwachung von Cloud-Services, Hosting-Anbietern und benutzerdefinierten Services. Entwickelt speziell für IT-Administratoren und Systemmonitoring.

## 🚀 Features

### ✨ Hauptfunktionen
- **Umfassende Service-Überwachung** - Überwachen Sie Cloud-Services, Hosting-Anbieter und eigene Webadressen
- **Moderne UX-Oberfläche** - Benutzerfreundliche und responsive Darstellung des Service-Status
- **Vordefinierte Services** - Über 50 vordefinierte Services für gängige Anbieter wie MS365, AWS, Google Cloud, IONOS, Hetzner, etc.
- **Flexible Konfiguration** - Vollständig anpassbare Service-Einstellungen im Admin-Bereich
- **Automatische Benachrichtigungen** - E-Mail-Alerts bei Statusänderungen
- **Echtzeit-Updates** - Automatische Aktualisierung der Status-Anzeige
- **Shortcode-Integration** - Einfache Einbindung in Seiten und Beiträge

### 🔧 Technische Features
- **HTTP/HTTPS Monitoring** - Unterstützung für GET, POST und HEAD Anfragen
- **JSON-API Integration** - Native Unterstützung für Status-APIs (statuspage.io Format)
- **RSS/XML Monitoring** - Parsing von RSS-Feeds für Service-Status (z.B. AWS)
- **Anpassbare Timeouts** - Konfigurierbare Timeout-Werte für jeden Service
- **Erwartete HTTP-Codes** - Flexible Definition erfolgreicher Status-Codes
- **Custom Headers** - Unterstützung für benutzerdefinierte HTTP-Header
- **JSON Path Support** - Flexible Pfad-Definition für JSON-Status-Werte
- **Incident Detection** - Automatische Erkennung aktiver Vorfälle in APIs
- **Uptime-Statistiken** - Detaillierte Verfügbarkeitsstatistiken für jeden Service
- **Response-Time Monitoring** - Überwachung der Antwortzeiten
- **Datenbank-Logging** - Vollständige Historie aller Status-Checks
- **Cron-basierte Checks** - Automatische Hintergrund-Überwachung

## 📦 Installation

### Automatische Installation
1. Laden Sie die Plugin-Dateien in das Verzeichnis `/wp-content/plugins/easySTATUSCheck/` hoch
2. Aktivieren Sie das Plugin über das 'Plugins' Menü in WordPress
3. Navigieren Sie zu 'Status Check' im Admin-Menü

### Manuelle Installation
1. Laden Sie das Plugin-Archiv herunter
2. Entpacken Sie es in `/wp-content/plugins/`
3. Aktivieren Sie das Plugin im WordPress Admin-Bereich

## 🎯 Verwendung

### Shortcode
Verwenden Sie den `[easy_status_display]` Shortcode, um die Status-Anzeige auf Ihren Seiten einzubinden:

```php
[easy_status_display]
```

#### Shortcode-Parameter
- `category` - Filtert nach Kategorie: `cloud`, `hosting`, `custom`, `all` (Standard: `all`)
- `layout` - Layout-Typ: `grid`, `list` (Standard: `grid`)
- `refresh` - Auto-Refresh Intervall in Sekunden (Standard: `300`)
- `show_uptime` - Zeigt Uptime-Statistiken: `true`, `false` (Standard: `true`)
- `show_response_time` - Zeigt Antwortzeiten: `true`, `false` (Standard: `true`)
- `columns` - Anzahl Spalten im Grid-Layout: `1`, `2`, `3`, `4` (Standard: `3`)

#### Beispiele
```php
// Nur Cloud Services anzeigen
[easy_status_display category="cloud"]

// List-Layout mit 1-minütiger Aktualisierung
[easy_status_display layout="list" refresh="60"]

// 4-spaltige Grid-Ansicht ohne Uptime-Anzeige
[easy_status_display columns="4" show_uptime="false"]
```

### Admin-Funktionen

#### Dashboard
- **Service-Übersicht** - Gesamtanzahl, Online/Offline Services
- **Kürzliche Änderungen** - Historie der letzten Statusänderungen
- **Sofortige Prüfung** - Manuelle Status-Prüfung aller Services

#### Service-Verwaltung
- **Services hinzufügen** - Eigene Services mit vollständiger Konfiguration
- **Vordefinierte Services** - Schnelles Hinzufügen bekannter Services
- **Bulk-Aktionen** - Mehrere Services gleichzeitig verwalten
- **Import/Export** - Services zwischen Installationen übertragen

#### Einstellungen
- **Standard-Intervalle** - Globale Prüfintervalle festlegen
- **E-Mail-Benachrichtigungen** - Notification-Einstellungen
- **Timeout-Konfiguration** - Standard-Timeout für neue Services

## 🌐 Vordefinierte Services

Das Plugin enthält über 50 vordefinierte Services in verschiedenen Kategorien:

### Cloud Services
- **Microsoft 365** - Office 365, Teams, OneDrive, Outlook
- **Amazon Web Services** - AWS Console, S3, CloudFront
- **Google Cloud** - GCP Console, Workspace, Gmail, Drive
- **Weitere** - GitHub, GitLab, Slack, Zoom, Dropbox

### Hosting-Anbieter
- **Deutsche Anbieter** - IONOS, Hetzner, Strato, All-Inkl, Mittwald, Netcup
- **Internationale Anbieter** - DigitalOcean, Linode, Vultr, OVH
- **CDN-Anbieter** - Cloudflare, KeyCDN, BunnyCDN

### IT-Services
- **Monitoring** - Pingdom, New Relic, Datadog
- **DNS** - Cloudflare DNS, Google DNS, Quad9
- **Security** - Let's Encrypt, Sucuri, Wordfence
- **Communication** - Discord, Telegram, WhatsApp Business

## ⚙️ Konfiguration

### Service-Konfiguration
Jeder Service kann individuell konfiguriert werden:

- **Name** - Anzeigename des Services
- **URL** - Zu überwachende Webadresse
- **Kategorie** - Cloud, Hosting oder Benutzerdefiniert
- **HTTP-Methode** - GET, POST oder HEAD
- **Timeout** - Maximale Wartezeit in Sekunden (1-60)
- **Erwartete Codes** - HTTP-Status-Codes für "Online" (z.B. "200,201,204")
- **Prüfintervall** - Wie oft der Service geprüft werden soll
- **E-Mail-Benachrichtigungen** - Aktivieren/Deaktivieren von Alerts
- **Response-Typ** - Standard HTTP, JSON-API oder RSS/XML
- **JSON-Pfad** - Pfad zum Status-Wert in JSON-APIs (z.B. "status.indicator")
- **Custom Headers** - Zusätzliche HTTP-Header (optional)

### JSON-API Integration
Das Plugin unterstützt nativ die Status-APIs der meisten Cloud-Anbieter:

#### Unterstützte API-Formate
- **StatusPage.io Format** - Standard-Format vieler Anbieter
- **Custom JSON-Pfade** - Flexible Pfad-Definition für beliebige APIs
- **RSS/XML Feeds** - Für Services wie AWS Status RSS

#### Beispiel JSON-Response
```json
{
  "status": {
    "indicator": "none|minor|major|critical",
    "description": "All Systems Operational"
  },
  "components": [...],
  "incidents": [...]
}
```

#### Status-Mapping
- `none`, `operational`, `ok` → 🟢 **Online**
- `minor`, `degraded`, `partial` → 🟡 **Warnung**
- `major`, `critical`, `down` → 🔴 **Offline**

### Beispiel Custom Headers
```
Authorization: Bearer your-token
User-Agent: MyCustomBot/1.0
X-API-Key: your-api-key
```

## 📊 Status-Anzeige

### Status-Typen
- 🟢 **Online** - Service ist erreichbar und antwortet erwartungsgemäß
- 🔴 **Offline** - Service ist nicht erreichbar oder antwortet mit Fehlern
- 🟡 **Warnung** - Service antwortet, aber nicht mit erwarteten Codes
- ⚪ **Unbekannt** - Service wurde noch nicht geprüft

### Anzeige-Elemente
- **Service-Name und URL**
- **Aktueller Status mit visueller Anzeige**
- **Uptime-Prozentsatz** (24h, 7d, 30d)
- **Antwortzeit** in Millisekunden
- **Letzte Prüfung** mit Zeitstempel
- **Fehlerdetails** bei Problemen
- **Erweiterte Details** (HTTP-Code, Methode, etc.)

## 🔔 Benachrichtigungen

### E-Mail-Alerts
- **Statusänderungen** - Automatische E-Mails bei Status-Wechseln
- **Persistente Ausfälle** - Benachrichtigung bei länger anhaltenden Problemen
- **Anpassbare Templates** - Konfigurierbare E-Mail-Inhalte

### Benachrichtigungs-Typen
- Service wird offline → E-Mail mit Details
- Service kommt wieder online → Bestätigungs-E-Mail
- Service offline für > 30 Minuten → Kritische Warnung

## 🎨 Anpassung

### CSS-Anpassungen
Das Plugin verwendet moderne CSS-Klassen für einfache Anpassungen:

```css
/* Status-Indikatoren anpassen */
.esc-status-online .esc-status-indicator {
    background: #your-green-color;
}

/* Service-Karten stylen */
.esc-service-item {
    border-radius: 8px;
    box-shadow: your-shadow;
}
```

### Hooks & Filter
```php
// Status-Check Ergebnis modifizieren
add_filter('esc_status_check_result', 'my_custom_status_logic', 10, 2);

// E-Mail-Template anpassen
add_filter('esc_notification_email_template', 'my_custom_email_template', 10, 3);

// Vordefinierte Services erweitern
add_filter('esc_predefined_services', 'my_additional_services');
```

## 📋 Systemanforderungen

- **WordPress** 5.0 oder höher
- **PHP** 7.4 oder höher
- **MySQL** 5.6 oder höher
- **cURL** PHP-Erweiterung (für HTTP-Requests)
- **JSON** PHP-Erweiterung

## 🔒 Sicherheit

- Alle AJAX-Requests sind mit WordPress Nonces geschützt
- SQL-Injections werden durch prepared Statements verhindert
- XSS-Schutz durch konsequente Daten-Escaping
- Capability-Checks für Admin-Funktionen

## 🐛 Debugging

### Debug-Modus aktivieren
```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

### Log-Dateien
- WordPress Debug-Log: `/wp-content/debug.log`
- Plugin-Logs: Admin → Status Check → Logs

## 📈 Performance

- **Caching** - Transients für API-Antworten (5 Minuten)
- **Asynchrone Checks** - Nicht-blockierende Status-Prüfungen
- **Batch-Processing** - Effiziente Bulk-Operationen
- **Database-Optimization** - Automatische Log-Bereinigung (30 Tage)

## 🤝 Support

### Dokumentation
- Vollständige Inline-Dokumentation
- PHPDoc-kompatible Code-Kommentare
- Beispiel-Implementierungen

### Community
- GitHub Issues für Bug-Reports
- Feature-Requests willkommen
- Pull-Requests erwünscht

## 📄 Lizenz

Dieses Plugin ist unter der GPL v2 oder höher lizenziert.

## 🏗️ Entwicklung

### Lokale Entwicklung
```bash
# Repository klonen
git clone https://github.com/your-repo/easySTATUSCheck.git

# In WordPress Plugin-Verzeichnis kopieren
cp -r easySTATUSCheck /path/to/wordpress/wp-content/plugins/

# Plugin aktivieren
wp plugin activate easySTATUSCheck
```

### Code-Standards
- WordPress Coding Standards
- PSR-4 Autoloading
- Semantic Versioning

## 🔄 Changelog

### Version 1.0.0
- ✨ Initiale Veröffentlichung
- 🎯 Service-Monitoring für Cloud und Hosting
- 📊 Moderne Status-Anzeige
- 🔔 E-Mail-Benachrichtigungen
- 📱 Responsive Design
- 🛠️ Admin-Interface
- 📈 Uptime-Statistiken
- 🔌 JSON-API Integration
- 📡 RSS/XML Feed Support
- 🎯 50+ Vordefinierte Status-APIs
- 🔍 Intelligente Incident-Erkennung
- 📊 Erweiterte Status-Parsing
- 🎨 API-Type Indikatoren im Admin
- 🚀 Automatische Datenbank-Migration

---

**Entwickelt mit ❤️ für die WordPress-Community**

*easySTATUSCheck - Ihr zuverlässiger Partner für Service-Monitoring*
