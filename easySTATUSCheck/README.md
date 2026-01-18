# easySTATUSCheck

![easySTATUSCheck Logo](https://img.shields.io/badge/WordPress-Plugin-blue?style=for-the-badge&logo=wordpress)
![Version](https://img.shields.io/badge/Version-1.1.3-green?style=for-the-badge)
![PHP](https://img.shields.io/badge/PHP-7.4+-purple?style=for-the-badge&logo=php)
![WordPress](https://img.shields.io/badge/WordPress-5.0+-blue?style=for-the-badge&logo=wordpress)

Ein professionelles WordPress-Plugin zur Überwachung von Cloud-Services, Hosting-Anbietern und benutzerdefinierten Services. Entwickelt speziell für IT-Administratoren und Systemmonitoring.

**Autor:** Andreas Hepp  
**Webseite:** [phinit.de](https://phinit.de)  
**Version:** 1.1.3  
**Letzte Aktualisierung:** 18.01.2026

## 🚀 Features

### ✨ Hauptfunktionen
- **Umfassende Service-Überwachung** - Überwachen Sie Cloud-Services, Hosting-Anbieter und eigene Webadressen
- **Moderne Admin-Oberfläche** - Komplett überarbeitetes Dashboard mit 3-Spalten Grid-Layout
- **40+ Vordefinierte Services** - IT-Services in 8 Kategorien: Cloud, Hosting, DevOps, Security, E-Mail, Datenbanken, DNS, Monitoring
- **Public Status Pages** - Öffentliche Seiten für Services, Incidents und History
- **CVE/Incident Tracking** - Integration von Sicherheitswarnungen und RSS-Feeds
- **Automatische Benachrichtigungen** - E-Mail-Alerts bei Statusänderungen
- **History & Analytics** - Detaillierte Uptime-Statistiken mit Chart.js Visualisierung
- **Template-System** - Schnelles Hinzufügen von Services aus vorgefertigten Templates

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

### Admin-Menü Struktur

#### 1. Dashboard
- **Service-Übersicht** - Gesamtanzahl, Online/Offline Services im 3-Spalten Grid
- **Schnellaktionen** - Service hinzufügen, Alle prüfen, Zu Public Pages
- **Kürzliche Änderungen** - Historie der letzten Statusänderungen

#### 2. Services
- **Service-Verwaltung** - Alle Services in 3-Spalten Grid-Layout
- **Schnellaktionen** - Aktivieren/Deaktivieren, Prüfen, Bearbeiten, Löschen
- **Service hinzufügen** - Manuell oder aus Templates

#### 3. Templates
- **8 Kategorien** - Microsoft 365, Monitoring/Analytics/CDN, Cloud-Anbieter, Hosting, DevOps, Security/SSL/DNS, E-Mail, Datenbanken
- **80+ IT-Services** - AWS, Azure, GCP, GitHub, GitLab, Docker, MongoDB, Hetzner, OVH, etc.
- **30+ Hosting-Anbieter** - Deutschland, Frankreich, Niederlande, Österreich, Schweiz, USA
- **Einzelne Templates** - Kein Bulk-Add, nur einzeln hinzufügen

#### 4. Incidents
- **CVE RSS Feeds** - Integration von Sicherheitswarnungen
- **Feed-Verwaltung** - Name und URL für RSS-Feeds
- **Max Items** - Anzahl der angezeigten CVE-Items (5-50)
- **Public Incidents Page** - Öffentliche Anzeige der Incidents

#### 5. Einstellungen (4 Tabs)
- **Allgemein** - Public Pages aktivieren, Basis-URL, Prüfintervall, Timeout
- **Benachrichtigungen** - E-Mail-Alerts aktivieren, Empfänger-Adresse
- **Design** - 6 Farbeinstellungen, Anzeigeoptionen, Auto-Refresh
- **Support** - System-Status, Datenbank-Tools, Cron-Status, Hilfe

## 🌐 Vordefinierte Service-Templates

Das Plugin enthält 40+ vordefinierte IT-Services in 8 professionellen Kategorien:

### 1. Microsoft 365
- Microsoft 365 Status, Teams, Exchange Online, SharePoint Online, OneDrive for Business

### 2. Cloud-Anbieter (AWS, Azure, GCP)
- AWS Status, Azure Status, Google Cloud Status, DigitalOcean, Linode, Vultr

### 3. Hosting-Anbieter (30 Services)
- **Deutschland:** IONOS, Hetzner, Mittwald, Netcup, All-Inkl, Strato, Host Europe, dogado, Alfahosting
- **Frankreich:** OVH, Scaleway, Online.net
- **Niederlande:** TransIP, Byte, Vimexx
- **Österreich:** World4You, easyname
- **Schweiz:** Hostpoint, Infomaniak, cyon
- **USA:** GoDaddy, Bluehost, HostGator, SiteGround, DreamHost, A2 Hosting, InMotion, Hostinger

### 4. Monitoring, Analytics & CDN (14 Services)
- **CDN:** Cloudflare, Fastly, KeyCDN, BunnyCDN, Amazon CloudFront, Akamai, StackPath
- **Monitoring:** Google Analytics, New Relic, Datadog, Pingdom, Sentry, Grafana, Splunk

### 5. DevOps & CI/CD (8 Services)
- GitHub, GitLab, Bitbucket, Docker Hub, Jenkins, CircleCI, Travis CI, Kubernetes

### 6. Security, SSL & DNS (6 Services)
- **Security:** Let's Encrypt, Cloudflare SSL, Sucuri
- **DNS:** Cloudflare DNS (1.1.1.1), Google DNS (8.8.8.8), Quad9

### 7. E-Mail Services (7 Services)
- Gmail, Mailgun, SendGrid, Postmark, Amazon SES, Mailchimp, SparkPost

### 8. Datenbanken & Storage (8 Services)
- MongoDB Atlas, Redis Cloud, Amazon S3, Backblaze B2, PostgreSQL, MySQL, Elasticsearch, Dropbox


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

## 📊 Public Status Pages

### Öffentliche Seiten
Das Plugin bietet 3 öffentliche Status-Seiten:

#### 1. Services Status Page
- **URL:** `yoursite.com/status/services`
- **Anzeige:** Alle aktiven Services im Grid-Layout
- **Echtzeit-Status:** Online/Offline mit Farbcodierung
- **Details:** Uptime, Antwortzeit, letzte Prüfung

#### 2. Incidents/CVE Page
- **URL:** `yoursite.com/status/incidents`
- **CVE RSS Feeds:** Integration von Sicherheitswarnungen
- **Anzeige:** Aktuelle Incidents und Sicherheitsmeldungen
- **Konfigurierbar:** Anzahl der Items pro Feed

#### 3. History Page
- **URL:** `yoursite.com/status/history/[service-id]`
- **Charts:** Visualisierung der Service-Historie
- **Statistiken:** Uptime, Durchschnittszeit, Anzahl Checks
- **Zeiträume:** 24h, 7d, 30d, 90d

### Status-Typen
- 🟢 **Online** - Service ist erreichbar und antwortet erwartungsgemäß
- 🔴 **Offline** - Service ist nicht erreichbar oder antwortet mit Fehlern
- 🟡 **Warnung** - Service antwortet, aber nicht mit erwarteten Codes
- ⚪ **Unbekannt** - Service wurde noch nicht geprüft

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

## 🛠️ Support-Tools

### Datenbank-Tools
- **Prüfen** - Überprüft ob alle Tabellen existieren
- **Erstellen** - Erstellt fehlende Datenbank-Tabellen
- **Optimieren** - Optimiert alle Plugin-Tabellen (OPTIMIZE TABLE)
- **Reparieren** - Repariert beschädigte Tabellen (REPAIR TABLE)

### Cron-Tools
- **Prüfen** - Überprüft Cron-Status und geplante Jobs
- **Manuell ausführen** - Führt alle Service-Checks sofort aus
- **Status-Anzeige** - WordPress Cron aktiv/deaktiviert, Anzahl geplanter Jobs

### System-Status
- WordPress Version, PHP Version, MySQL Version, cURL Status
- Plugin Version: 1.1.3

## 🔄 Changelog

### Version 1.1.3 (18.01.2026)
- 🌍 **80+ IT-Service Templates** - Massive Erweiterung auf 8 optimierte Kategorien
- 📊 **Template-Kategorien optimiert** - Zusammenführung auf 8 übersichtliche Kategorien
- 🌐 **30+ Hosting-Anbieter** - DE, FR, NL, AT, CH, US mit professionellen Status-APIs
- 📈 **History aus Admin entfernt** - Nur noch öffentliche History-Seite verfügbar
- 🔗 **History-Button bei Services** - Direkter Link zur öffentlichen History-Seite
- 🛠️ **Support-Tab erweitert** - Hilfetext, System-Status, Datenbank-Tools, Cron-Management
- 📊 **Template-Zähler korrigiert** - Korrekte Anzeige der Template-Anzahl
- 🔧 **Bug-Fixes** - Parse Errors behoben, Code-Optimierungen

### Version 1.0.0 (18.01.2026)
- ✨ **Initiale Veröffentlichung**
- 🎯 **Admin-Menü komplett überarbeitet** - Dashboard, Services, Templates, Incidents, Einstellungen
- 📊 **3-Spalten Grid-Layout** - Moderne Card-Darstellung für Services
- 🌐 **Public Status Pages** - 3 öffentliche Seiten (Services, Incidents, History)
- 🔔 **CVE/Incident Tracking** - RSS-Feed Integration für Sicherheitswarnungen
- 🎨 **4-Tab Einstellungen** - Allgemein, Benachrichtigungen, Design, Support
- 🛠️ **Support-Tools** - Datenbank-Tools und Cron-Management
- 🔌 **40+ IT-Service Templates** - 8 Kategorien mit professionellen Services
- 📧 **E-Mail-Benachrichtigungen** - Automatische Alerts bei Statusänderungen
- 🔒 **Security** - Nonce-Schutz, Prepared Statements, XSS-Prevention
- 🚀 **Performance** - Caching, Asynchrone Checks, Auto-Cleanup

---

**Entwickelt mit ❤️ von Andreas Hepp**

*easySTATUSCheck - Professionelles Service-Monitoring für WordPress*

**Webseite:** [phinit.de](https://phinit.de)  
**Version:** 1.1.3  
**Datum:** 18.01.2026
