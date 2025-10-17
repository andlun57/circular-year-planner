# Circular Year Planner - Projektöversikt

## 📋 Sammanfattning

Circular Year Planner är en WordPress-plugin som visualiserar verksamhetsår och händelser i en cirkulär kalender. Inspirerad av Plandisc och Yearo, ger pluginen en intuitiv översikt över årliga aktiviteter, kampanjer, möten och projekt.

## 🎯 Huvudfunktioner

### ✨ Cirkulär Visualisering
- **Månadsring** - Visar alla 12 månader runt kanten
- **Veckoring** - Svenska veckonummer (ISO 8601)
- **Händelseringar** - Separata ringar för varje händelsetyp
- **Centralt år** - Verksamhetsår visas i mitten

### 🎨 Anpassningsbar
- **Händelsetyper** - Skapa egna kategorier med valfria färger
- **Verksamhetsår** - Börja vilket månad som helst (t.ex. skolår juli-juni)
- **Färgscheman** - Ljus, mörk, blå eller grön
- **Storlekar** - Anpassningsbara dimensioner

### 🖱️ Interaktiv
- **Klickbara händelser** - Visa detaljer i popup
- **Navigation** - Bläddra mellan år
- **Hover-effekter** - Visuell feedback
- **Responsiv** - Fungerar på mobil, tablet och desktop

### 🔧 Tekniskt
- **REST API** - Integrering med externa system
- **Shortcode** - Enkel inbäddning på sidor
- **Custom Post Type** - Strukturerad datahantering
- **SVG-baserad** - Skalbar och prestanda-optimerad

## 📁 Projektstruktur

```
circular-year-planner/
│
├── circular-year-planner.php          # Huvudfil med plugin-header
│
├── includes/                          # PHP-klasser
│   ├── class-event-post-type.php     # Händelse-hantering
│   ├── class-settings.php            # Admin-inställningar
│   ├── class-rest-api.php            # REST API endpoints
│   └── class-shortcode.php           # Shortcode-rendering
│
├── assets/                            # Frontend-resurser
│   ├── css/
│   │   ├── admin.css                 # Admin-styling
│   │   └── frontend.css              # Kalender-styling
│   └── js/
│       ├── admin.js                  # Admin-funktionalitet
│       └── circular-calendar.js      # SVG-rendering & interaktion
│
├── README.md                          # Huvuddokumentation
├── SNABBSTART.md                      # Snabbstartsguide
├── INSTALLATION.md                    # Detaljerade installationsinstruktioner
├── DEVELOPER.md                       # Utvecklardokumentation
├── CHANGELOG.md                       # Versionshistorik
├── LICENSE.txt                        # Licensvillkor
└── PROJECT-OVERVIEW.md               # Denna fil
```

## 🔄 Dataflöde

```
┌─────────────────┐
│  WordPress      │
│  Admin          │
└────────┬────────┘
         │
         ├─> Skapa händelse (Custom Post Type)
         │   └─> Spara metadata (start, slut, typ, år)
         │
         ├─> Konfigurera inställningar
         │   └─> Spara till wp_options
         │
         └─> Publicera sida med shortcode
             │
             ▼
    ┌────────────────┐
    │  Frontend      │
    └────────┬───────┘
             │
             ├─> Shortcode renderar container
             │
             ├─> JavaScript initieras
             │   │
             │   ├─> Hämta inställningar (REST API)
             │   │
             │   └─> Hämta händelser (REST API)
             │       │
             │       └─> Rita SVG-kalender
             │           │
             │           ├─> Månadsring
             │           ├─> Veckoring
             │           ├─> Händelseringar
             │           └─> Interaktivitet
             │
             └─> Användare ser cirkulär kalender
```

## 🛠️ Teknisk Stack

| Layer | Teknologi |
|-------|-----------|
| **Backend** | PHP 7.4+ |
| **Framework** | WordPress 5.8+ |
| **Database** | MySQL 5.6+ |
| **API** | WordPress REST API |
| **Frontend** | JavaScript (ES6) |
| **Library** | jQuery (bundled with WP) |
| **Graphics** | SVG |
| **Styling** | CSS3 |

## 📊 Datamodell

### Custom Post Type: `cyp_event`

```
wp_posts
├── post_title         → Händelsenamn
├── post_content       → Beskrivning
├── post_type          → 'cyp_event'
└── post_status        → 'publish'

wp_postmeta
├── _cyp_start_date    → 'YYYY-MM-DD'
├── _cyp_end_date      → 'YYYY-MM-DD'
└── _cyp_event_type    → '0' (index)

Note: Verksamhetsår beräknas automatiskt från start_date och sparas inte.
```

### Options

```
wp_options
├── cyp_event_types       → JSON array av typer
├── cyp_fiscal_year_start → 'MM-DD'
└── cyp_color_scheme      → 'default|dark|blue|green'
```

## 🎨 UI/UX Design

### Färgpalett (Standard)

| Element | Färg | Användning |
|---------|------|-----------|
| Primary | #0073aa | Knappar, länkar |
| Success | #46b450 | Bekräftelser |
| Warning | #ffb900 | Varningar |
| Danger | #dc3232 | Fel |
| Text | #333333 | Huvudtext |
| Background | #ffffff | Bakgrund |

### Typografi

- **Rubrik:** 24px, font-weight 600
- **Brödtext:** 14px, line-height 1.6
- **Små texter:** 12px
- **Font:** System fonts stack för bästa prestanda

### Interaktion

- **Hover:** Opacity förändras, färg ljusare
- **Active:** Visuell feedback omedelbart
- **Loading:** Spinner visas under datahämtning
- **Transitions:** 0.2-0.3s ease för smooth känsla

## 📱 Responsivitet

### Breakpoints

| Device | Width | Adjustments |
|--------|-------|-------------|
| Desktop | 1024px+ | Full storlek |
| Tablet | 768px-1023px | Minskad padding |
| Mobile | <768px | Kompakt layout, mindre text |

### Mobile-optimeringar

- Touch-vänliga klickytor (min 44x44px)
- Swipe-navigation (framtida feature)
- Kompakt detaljpanel
- Mindre SVG-storlek

## 🔐 Säkerhet

### Implementerade åtgärder

✅ **Nonce-validering** - Alla formulär
✅ **Capability-kontroll** - Behörighetsverifiering
✅ **Input-sanitering** - All användardatabas
✅ **Output-escaping** - XSS-skydd
✅ **SQL-injection-skydd** - WordPress API används
✅ **CSRF-skydd** - Nonce-tokens

### Best Practices

- Aldrig `eval()` eller liknande
- Inga direkta SQL-queries
- Validering server-side
- Säker hantering av filer

## 🚀 Prestanda

### Optimeringar

- **Lazy loading** - Händelser laddas vid behov
- **Caching** - Browser-cache för assets
- **Minimal DOM** - Effektiv SVG-rendering
- **Debouncing** - För resize-events
- **Asset minification** - (framtida)

### Metrics (Målsättningar)

- **Initial load:** <2s
- **Interaction:** <100ms
- **Asset size:** <100KB total
- **API response:** <500ms

## 🌍 Internationalisering

### Nuvarande språk
- 🇸🇪 Svenska (primär)

### Framtida språk (planerat)
- 🇬🇧 Engelska
- 🇳🇴 Norska
- 🇩🇰 Danska
- 🇫🇮 Finska

### Översättningsbara strängar

Alla texter är omslutna med WordPress translation functions:
- `__()` - Översätt sträng
- `_e()` - Echo översatt sträng
- `esc_html__()` - Översätt och escapa

## 📈 Utvecklingsroadmap

### Version 1.0.15 ✅ (Nuvarande)
- Grundläggande funktionalitet
- Cirkulär kalender
- Händelsehantering
- Admin-inställningar
- WP Plugin Check-kompatibel
- Förbättrad säkerhet (escaping, nonce verification, sanitization)

### Version 1.1.0 (Q1 2025)
- Import/Export av händelser
- Fler färgscheman
- Förbättrad mobilanpassning
- Performance-optimeringar

### Version 1.2.0 (Q2 2025)
- Google Calendar-integration
- E-postnotifikationer
- Händelsemallar
- Sökfunktion

### Version 2.0.0 (Q4 2025)
- Flerspråksstöd
- Dashboard med analytics
- Team collaboration
- Avancerad filtrering

## 🧪 Testning

### Manuell testning

✅ Cross-browser testing
✅ Responsiv design testing
✅ Funktionalitetstester
✅ Användartester

### Automatiserad testning (framtida)

- PHPUnit för backend
- Jest för JavaScript
- Selenium för E2E
- Accessibility testing

## 📚 Dokumentation

| Dokument | Målgrupp | Innehåll |
|----------|----------|----------|
| README.md | Alla | Översikt och grundläggande info |
| SNABBSTART.md | Slutanvändare | Steg-för-steg guide |
| INSTALLATION.md | Administratörer | Detaljerad installation |
| DEVELOPER.md | Utvecklare | API, hooks, anpassning |
| CHANGELOG.md | Alla | Versionshistorik |

## 🤝 Support & Community

### Support-kanaler

- 📧 E-post: [Din e-post]
- 💬 Forum: [Om du har ett]
- 🐛 Issues: GitHub Issues
- 📖 Dokumentation: Se markdown-filer

### Bidra till projektet

1. Läs DEVELOPER.md
2. Fork projektet
3. Skapa feature branch
4. Gör dina ändringar
5. Skicka Pull Request

## 📊 Statistik

### Kodmängd (uppskattad)

| Språk | Filer | Rader |
|-------|-------|-------|
| PHP | 5 | ~1,250 |
| JavaScript | 2 | ~550 |
| CSS | 2 | ~390 |
| Markdown | 7 | ~2,500 |
| **Total** | **16** | **~4,700** |

### Utvecklingstid

- **Initial utveckling:** 8-12 timmar
- **Dokumentation:** 4-6 timmar
- **Testning:** 2-3 timmar
- **Total:** ~15-20 timmar

## 🎓 Lärdomar & Insikter

### Tekniska utmaningar

1. **SVG-beräkningar** - Komplexa arc-paths för händelser
2. **Svenska veckonummer** - ISO 8601-standard implementation
3. **Flexibelt verksamhetsår** - Måste hantera alla startmånader
4. **Responsivitet** - SVG-skalning på olika skärmar

### Lösningar

1. **Arc-paths:** Matematiska funktioner för polära koordinater
2. **Veckonummer:** Algoritm baserad på ISO-standard
3. **Verksamhetsår:** Dynamisk beräkning från inställningar
4. **Responsivitet:** viewBox och CSS för skalning

## 🔮 Framtida funktioner (Wishlist)

- 📊 Export till PDF/PNG
- 🔔 Push-notifikationer
- 👥 Användarroller och teamarbete
- 🎨 Drag-and-drop händelseskapande
- 📱 Native mobilapp
- 🔗 Zapier-integration
- 📈 Analytics dashboard
- 🗓️ Återkommande händelser
- 🏷️ Taggning och kategorisering
- 💾 Automatisk backup

## 📝 Licens & Copyright

**Copyright © 2024 Circular Year Planner**

GPL v2 eller senare - Se LICENSE.txt för detaljer.

## 👏 Credits

- **Inspiration:** Plandisc, Yearo
- **Framework:** WordPress
- **Icons:** WordPress Dashicons
- **Color picker:** WordPress Color Picker

---

**Skapad med ❤️ för bättre årsplanering**

Version 1.0.15 | Senast uppdaterad: 2024-10-17

