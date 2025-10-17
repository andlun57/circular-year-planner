# Circular Year Planner - WordPress Plugin

En WordPress-plugin för att visualisera verksamhetsår och händelser i en cirkulär kalender, inspirerad av Plandisc och Yearo.

## Funktioner

✅ **Cirkulär Kalendervisning**
- Årets månader i yttersta ringen
- Veckonummer (svensk standard) i nästa ring
- Händelser visas i separata ringar per typ
- Verksamhetsår visas i mitten

✅ **Flexibel Händelsehantering**
- Anpassningsbara händelsetyper med egna färger
- Start- och slutdatum (utan tid)
- Stöd för olika verksamhetsår

✅ **Administrativa Inställningar**
- Skapa och redigera händelsetyper
- Välj när verksamhetsåret börjar (Jan-Dec)
- Anpassningsbart färgschema

✅ **Enkel Integration**
- Shortcode för att visa kalendern
- REST API för externa integrationer
- Responsiv design

## Installation

1. Ladda upp plugin-mappen till `/wp-content/plugins/`
2. Aktivera pluginen via WordPress admin-panel
3. Gå till "Årsplanering" → "Inställningar" för att konfigurera

## Användning

### Konfigurera händelsetyper

1. Gå till **Årsplanering → Inställningar**
2. Lägg till händelsetyper (t.ex. "Program", "Kampanj", "Utbildning", "Möte")
3. Välj färg för varje händelsetyp
4. Spara inställningar

### Ställa in verksamhetsår

1. Gå till **Årsplanering → Inställningar**
2. Välj vilken månad verksamhetsåret börjar
3. Välj färgschema för kalendern

### Skapa händelser

1. Gå till **Årsplanering → Lägg till händelse**
2. Fyll i:
   - Titel
   - Beskrivning (valfritt)
   - Startdatum
   - Slutdatum
   - Händelsetyp
   - *Verksamhetsår beräknas automatiskt från startdatum*
3. Publicera

### Visa kalendern

Använd shortcode i valfri sida eller inlägg:

```
[circular_year_planner]
```

#### Shortcode-parametrar

- `year` - Visa specifikt verksamhetsår
  ```
  [circular_year_planner year="2024/2025"]
  ```

- `types` - Visa endast vissa händelsetyper (index, 0-baserat)
  ```
  [circular_year_planner types="0,1"]
  ```

- `width` och `height` - Anpassa storlek
  ```
  [circular_year_planner width="1000" height="1000"]
  ```

## Funktionalitet

### Cirkulär Kalender

Kalendern består av flera koncentriska ringar:

1. **Längst ut** - Månadsetiketter (Jan, Feb, Mar...)
2. **Månadsring** - Markerar månadens utsträckning
3. **Veckorring** - Veckonummer enligt svensk standard (ISO 8601)
4. **Händelseringar** - En ring per händelsetyp med händelsetext längs bågen
5. **Centrum** - Visar aktuellt verksamhetsår (t.ex. "2025" eller "24/25")

**Speciella funktioner:**
- 📏 **Endagshändelser** renderas som hela veckan för bättre synlighet
- 📝 **Händelsenamn** visas direkt i cirkeln och följer bågens form (9px storlek)
- ✍️ **Text visas som inskriven** - behåller kapitalisering (ej versaler)
- 🎨 **Automatisk textfärg** (svart eller vit) för bästa kontrast
- 🔲 **Ramar runt händelsetypscirklar** för tydlig visuell separation
- 📏 **Ingen textklippning** - långa händelsenamn visas helt

### Interaktivitet

- Klicka på en händelse för att se detaljer
- Navigera mellan år med pilknapparna
- Legenden visar alla händelsetyper med färger

### REST API

Pluginen exponerar två API-endpoints:

#### Hämta händelser
```
GET /wp-json/cyp/v1/events
```

Parametrar:
- `fiscal_year` - Filtrera på verksamhetsår
- `types` - Filtrera på händelsetyper (kommaseparerad lista)

#### Hämta inställningar
```
GET /wp-json/cyp/v1/settings
```

## Teknisk Information

### Filstruktur

```
circular-year-planner/
├── circular-year-planner.php    # Huvudfil
├── includes/
│   ├── class-event-post-type.php   # Custom Post Type
│   ├── class-settings.php          # Admin-inställningar
│   ├── class-rest-api.php          # REST API
│   └── class-shortcode.php         # Shortcode-hantering
├── assets/
│   ├── css/
│   │   ├── admin.css              # Admin-styling
│   │   └── frontend.css           # Frontend-styling
│   └── js/
│       ├── admin.js               # Admin JavaScript
│       └── circular-calendar.js   # Cirkulär kalender-logik
└── README.md
```

### Krav

- WordPress 5.8 eller senare
- PHP 7.4 eller senare

### Teknologier

- **Backend**: PHP, WordPress REST API
- **Frontend**: JavaScript (vanilla + jQuery), SVG
- **Styling**: CSS3

## Anpassning

### Färgscheman

Pluginen stödjer flera färgscheman:
- Standard (ljus)
- Mörk
- Blå
- Grön

Färgschemat väljs i inställningssidan och påverkar kalendervisningen.

### Custom CSS

Du kan lägga till egen CSS för att anpassa utseendet:

```css
.cyp-calendar-container {
    /* Dina anpassningar */
}
```

## Version

Version: 1.0.15

## Licens

Detta plugin är licensierat under GPL v2 eller senare.

## Support

För frågor och support, besök GitHub-repository:
https://github.com/andlun57/circular-year-planner

