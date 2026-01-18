# easyGlossary

**Professionelles Glossar-Plugin für WordPress mit Auto-Linking, Tooltips und WCAG 2.1 AA Barrierefreiheit**

[![Version](https://img.shields.io/badge/version-1.3.0-blue.svg)](https://github.com/phin-it/easyGlossary)
[![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/license-GPL%20v2%2B-green.svg)](LICENSE)
[![WCAG](https://img.shields.io/badge/WCAG-2.1%20AA-brightgreen.svg)](https://www.w3.org/WAI/WCAG21/quickref/)

---

## 📖 Übersicht

easyGlossary ist ein umfassendes WordPress-Plugin für professionelle Glossar-Verwaltung mit automatischer Begriffsverknüpfung, intelligenten Tooltips, Live-Suche und vollständiger WCAG 2.1 AA Barrierefreiheit.

**Ideal für:**
- 📰 Online-Magazine und News-Portals
- 🎓 Bildungseinrichtungen und E-Learning
- 🏢 Unternehmens-Websites mit Fachbegriffen
- 📚 Dokumentations-Seiten und Wikis
- 🔬 Wissenschaftliche Publikationen

---

## ✨ Hauptfeatures

### � Auto-Linking Engine
- ✅ **Automatische Verlinkung** von Glossar-Begriffen in Posts/Pages
- ✅ **Konfigurierbar:** Erste oder alle Vorkommen verlinken
- ✅ **Synonyme:** Alternative Schreibweisen werden automatisch erkannt
- ✅ **Ausschlüsse:** Startseite, bestimmte Post-Types oder Seiten ausschließen
- ✅ **3-Stufen-Caching:** Objekt-Cache, WordPress-Cache, Transient-Cache
- ✅ **HTML-Schutz:** Keine Verlinkung innerhalb von HTML-Tags
- ✅ **Case-insensitive:** Groß-/Kleinschreibung optional

### 💬 Tooltip-System
- ✅ **Hover-Tooltips** für Desktop (smooth Animationen)
- ✅ **Click-Tooltips** für Mobile (touch-optimiert)
- ✅ **4 Designs:** Default, Dark, Light, Minimal
- ✅ **AJAX-Loading:** Content wird erst bei Bedarf geladen
- ✅ **Externe Links:** Tooltips können auf externe Seiten verweisen
- ✅ **Responsive:** Automatische Positionierung mit Viewport-Erkennung
- ✅ **Accessibility:** Screen-Reader-kompatibel, Tastatur-Navigation

### 🔍 Live-Search
- ✅ **Echtzeit-Suche** mit 300ms Debouncing
- ✅ **Auto-Complete** Dropdown
- ✅ **Synonym-Suche** inklusive
- ✅ **AJAX-basiert** ohne Seitenneuladung
- ✅ **Keyboard-Navigation** (Pfeiltasten)
- ✅ **Shortcode:** `[glossary_search]`

### 📊 Admin-Features
- ✅ **Dashboard-Widget** mit umfassenden Statistiken
- ✅ **Bulk-Aktionen:** Export, Duplizieren, Cache leeren, Synonyme normalisieren
- ✅ **Import/Export CSV** mit UTF-8 BOM für Excel
- ✅ **Meta-Boxes:** Synonyme, Verwandte Begriffe, SEO, Medien, Zusatzinfos
- ✅ **Medienverwaltung:** Bildergalerie, Videos, Anhänge pro Begriff
- ✅ **Quick-Edit & Bulk-Edit** für schnelle Änderungen
- ✅ **Settings-Page** mit 3 Tabs (Auto-Linking, Tooltips, Ausschlüsse)

### ♿ Barrierefreiheit
- ✅ **WCAG 2.1 Level AA:** 100% konform (50/50 Kriterien erfüllt)
- ✅ **Screen-Reader:** NVDA, JAWS, VoiceOver, Narrator kompatibel
- ✅ **Tastatur-Navigation:** Vollständig bedienbar
- ✅ **Kontrast:** 4.5:1 für Text, 3:1 für UI-Komponenten
- ✅ **ARIA-Attribute:** Korrekt implementiert
- ✅ **Focus-Indikatoren:** Deutlich sichtbar (2px)

### 🎨 Theme-Integration
- ✅ **MH Magazine Theme:** Vollständig optimierte Styles
- ✅ **Automatische Erkennung:** Lädt passende CSS für aktives Theme
- ✅ **Design-neutral:** Funktioniert mit jedem Theme
- ✅ **Responsive:** Mobile-First Ansatz
- ✅ **Anpassbar:** Einfache CSS-Überschreibung möglich

---

## � Installation

### Methode 1: WordPress Admin (empfohlen)

1. **Download:** Plugin-ZIP herunterladen
2. **Upload:** `Plugins → Installieren → Plugin hochladen`
3. **Aktivieren:** Plugin aktivieren
4. **Fertig:** Unter `easyGlossary` im Admin-Menü konfigurieren

### Methode 2: FTP/SFTP

1. **Upload:** Ordner nach `/wp-content/plugins/easyGlossary/` hochladen
2. **Aktivieren:** Im WordPress Admin unter `Plugins` aktivieren
3. **Konfigurieren:** Einstellungen unter `easyGlossary` anpassen

### Methode 3: WP-CLI

```bash
wp plugin install easyGlossary.zip --activate
```

---

## ⚙️ Konfiguration

### Schnellstart

1. **Ersten Begriff erstellen:**
   ```
   Admin → easyGlossary → Neuer Eintrag
   - Titel: Ihr Begriff
   - Excerpt: Kurzdefinition (für Tooltips)
   - Content: Ausführliche Beschreibung
   ```

2. **Auto-Linking aktivieren:**
   ```
   Admin → easyGlossary → Einstellungen → Auto-Linking
   ✓ Auto-Linking aktivieren
   ✓ Erlaubte Post-Types auswählen
   ```

3. **Tooltips konfigurieren:**
   ```
   Admin → easyGlossary → Einstellungen → Tooltips
   - Tooltip-Trigger: Hover (Desktop) / Click (Mobile)
   - Tooltip-Stil: Default / Dark / Light / Minimal
   ✓ Tooltips aktivieren
   ```

### Display-Optionen
- **Tooltip-Style**: Design anpassen
- **Link-Verhalten**: Popup vs. Seiten-Navigation
- **Auto-Linking**: Automatische Verknüpfung
- **Ausschlüsse**: Seiten/Posts ausschließen

## 📋 Verwendung

### Glossar-Begriffe hinzufügen
1. **Neuer Begriff** → Admin → easyGlossary → Neuer Begriff
2. **Titel**: Begriff-Name eingeben
3. **Definition**: Ausführliche Erklärung
4. **Kategorie**: Themenbereich zuweisen
5. **Synonyme**: Alternative Bezeichnungen

### Shortcodes
```php
// Einzelner Begriff
[glossary term="WordPress"]

// Glossar-Index (alle Begriffe)
[glossary_index]

// A-Z Navigation (NEU!)
[glossary_az]

// A-Z Navigation mit Optionen
[glossary_az show_empty="false" show_count="true"]
```

**Shortcode-Parameter für `[glossary_az]`:**
- `show_empty`: Zeigt auch Buchstaben ohne Einträge (Standard: false)
- `show_count`: Zeigt Anzahl der Einträge pro Buchstabe (Standard: true)

### Widgets
- **Glossar-Widget**: Neueste Begriffe
- **Suche-Widget**: Begriff-Suche
- **Kategorien-Widget**: Nach Themen
- **Tag-Cloud**: Begriffe-Wolke

## 🔧 Technische Details

### Systemanforderungen
- **WordPress**: 5.0+
- **PHP**: 7.4+
- **Tested up to**: WordPress 6.5

### Developer-Integration
```php
// Begriff programmatisch hinzufügen
Easy_Glossary::add_term('API', 'Application Programming Interface');

// Begriff-Definition abrufen
$definition = Easy_Glossary::get_definition('API');

// Auto-Linking aktivieren
Easy_Glossary::enable_auto_linking($content);
```

## 🎯 SEO-Optimierung

### Structured Data
- **Schema.org Markup**: Automatische Implementierung
- **Rich Snippets**: Enhanced Search Results
- **FAQ-Schema**: Frage-Antwort-Format
- **Article-Schema**: Content-Enrichment

### Internal Linking
- **Automatic Cross-Linking**: Interne Verknüpfung
- **Link Juice Distribution**: SEO-Wert-Verteilung
- **Anchor Text Optimization**: Optimierte Anker-Texte
- **NoFollow Control**: Link-Attribute-Steuerung

## 📱 Frontend-Features

### Responsive Design
- **Mobile-First**: Optimiert für alle Geräte
- **Touch-Friendly**: Tablet-optimierte Tooltips
- **Fast Loading**: Performance-optimiert
- **Accessibility**: Barrierefreier Zugang

### User Experience
- **Instant Search**: Live-Suche in Begriffen
- **Keyboard Navigation**: Tastatur-Unterstützung
- **Print-Friendly**: Druckbare Glossar-Seiten
- **Social Sharing**: Begriff-Sharing

## 🔒 Sicherheit & Performance

### Sicherheit
- **Input Sanitization**: Sichere Eingabe-Verarbeitung
- **Capability Checks**: Berechtigungs-Kontrolle
- **Nonce Protection**: CSRF-Schutz
- **SQL Injection Prevention**: Prepared Statements

### Performance
- **Caching Ready**: Cache-Plugin-Kompatibilität
- **Lazy Loading**: Tooltips on-demand
- **Database Optimization**: Effiziente Queries
- **CDN Friendly**: Asset-Optimierung

## 📊 Analytics & Insights

### Nutzungsstatistiken
- **Begriff-Popularität**: Meist-gesuchte Begriffe
- **Tooltip-Interaktionen**: User-Engagement
- **Glossar-Seiten-Aufrufe**: Traffic-Analyse
- **Suchverhalten**: Query-Analytics

## 📚 Support & Dokumentation

- **Author**: PHIN IT Solutions
- **Website**: https://phin.network
- **Version**: 1.3.0
- **License**: GPL v2 or later

## 🔄 Changelog

### Version 1.3.0 (Aktuell)
- **NEU:** A-Z Navigation Shortcode `[glossary_az]`
- **NEU:** Interaktive Buchstaben-Filter im Archiv
- **NEU:** Design-neutrales CSS (Theme-kompatibel)
- **Verbessert:** Archive-Template mit optimierter Listendarstellung
- **Verbessert:** Nur Titel-Anzeige im Archiv, vollständige Beschreibung auf Einzelseite
- **Verbessert:** JavaScript-basierte Filter-Funktionalität

### Version 1.2.0
- **NEU:** Glossar-Index Shortcode `[glossary_index]`
- **NEU:** Glossar-Widget für die neuesten Begriffe
- **Verbessert:** Tooltips mit verbessertem Design und Funktionalität
- **Verbessert:** Auto-Linking Engine mit verbesserter Leistung

### Version 1.1.0
- **NEU:** A-Z Navigation im Glossar-Archiv
- **NEU:** Interaktive Buchstaben-Filter im Glossar-Archiv
- **Verbessert:** Glossar-Archiv-Template mit optimierter Listendarstellung
- **Verbessert:** Nur Titel-Anzeige im Glossar-Archiv, vollständige Beschreibung auf Einzelseite

### Version 1.0.0
- Initial release
- Glossar-Management-System
- Automatische Tooltip-Generierung
- Import/Export-Funktionen
- Modern Admin-Interface
- SEO-Optimierung
- Responsive Frontend

## 💡 Best Practices

### Content-Management
1. **Konsistente Definitionen**: Klare, präzise Erklärungen
2. **Kategorisierung**: Logische Struktur
3. **Synonyme nutzen**: Alle Varianten erfassen
4. **Regelmäßige Updates**: Begriffe aktuell halten

### SEO-Optimierung
1. **Keyword-Research**: Relevante Begriffe identifizieren
2. **Long-Tail-Keywords**: Spezifische Suchbegriffe
3. **Internal Linking**: Strategische Verknüpfung
4. **Content-Enrichment**: Wertvolle Zusatzinformationen

## 🤝 Contributing

- WordPress Coding Standards
- Accessibility Guidelines (WCAG)
- SEO Best Practices
- Performance-Optimierung

## 📄 Lizenz

GPL v2 or later - siehe [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html)

---

**Powered by PHIN IT Solutions** 📚
