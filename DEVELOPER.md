# Developer Documentation - Circular Year Planner

Detta dokument är för utvecklare som vill anpassa, utöka eller bidra till pluginen.

## Arkitektur

### Översikt

Pluginen följer WordPress best practices och använder en objektorienterad approach med singleton-mönster.

```
Circular Year Planner
├── Huvudklass (Singleton)
│   ├── Event Post Type Handler
│   ├── Settings Manager
│   ├── REST API Handler
│   └── Shortcode Handler
├── Frontend (JavaScript + SVG)
│   └── CircularCalendar Class
└── Assets (CSS + JS)
```

### Klassstruktur

#### `Circular_Year_Planner` (huvudklass)
- **Fil:** `circular-year-planner.php`
- **Ansvar:** Orkestrerar hela pluginen, laddar beroenden, hanterar hooks
- **Pattern:** Singleton

#### `CYP_Event_Post_Type`
- **Fil:** `includes/class-event-post-type.php`
- **Ansvar:** Hanterar Custom Post Type för händelser
- **Funktioner:**
  - Registrerar post type
  - Meta boxes för händelsedata
  - Admin-kolumner
  - Data-validering

#### `CYP_Settings`
- **Fil:** `includes/class-settings.php`
- **Ansvar:** Administrera plugin-inställningar
- **Funktioner:**
  - Inställningssida i admin
  - Händelsetyp-hantering
  - Verksamhetsår-konfiguration
  - Färgschema-val

#### `CYP_REST_API`
- **Fil:** `includes/class-rest-api.php`
- **Ansvar:** REST API endpoints
- **Endpoints:**
  - `GET /wp-json/cyp/v1/events`
  - `GET /wp-json/cyp/v1/settings`

#### `CYP_Shortcode`
- **Fil:** `includes/class-shortcode.php`
- **Ansvar:** Shortcode-rendering
- **Shortcode:** `[circular_year_planner]`

#### `CircularCalendar` (JavaScript)
- **Fil:** `assets/js/circular-calendar.js`
- **Ansvar:** SVG-rendering och interaktivitet
- **Funktioner:**
  - Ritar cirkulär kalender
  - Hanterar events (klick, hover)
  - Navigation mellan år

## Datamodell

### Post Type: `cyp_event`

**Post Meta Fields:**

| Meta Key | Type | Description |
|----------|------|-------------|
| `_cyp_start_date` | string | Startdatum (YYYY-MM-DD) |
| `_cyp_end_date` | string | Slutdatum (YYYY-MM-DD) |
| `_cyp_event_type` | string | Index till händelsetyp |

**OBS:** Verksamhetsår beräknas automatiskt från startdatum och sparas inte som meta-data.

### Options

| Option Key | Type | Description |
|------------|------|-------------|
| `cyp_event_types` | array | Array av händelsetyper med namn och färg |
| `cyp_fiscal_year_start` | string | Start för verksamhetsår (MM-DD) |
| `cyp_color_scheme` | string | Färgschema (default, dark, blue, green) |

**Exempel på `cyp_event_types`:**
```php
[
    ['name' => 'Program', 'color' => '#4A90E2'],
    ['name' => 'Kampanj', 'color' => '#E24A90'],
    ['name' => 'Utbildning', 'color' => '#90E24A'],
]
```

## Hooks & Filters

### Actions

```php
// Körs när pluginen initieras
do_action('cyp_init');

// Körs efter att en händelse sparats
do_action('cyp_event_saved', $post_id, $post_data);

// Körs efter att inställningar sparats
do_action('cyp_settings_saved', $settings);
```

### Filters

```php
// Filtrera händelsedata innan den returneras via API
apply_filters('cyp_event_data', $event_data, $post_id);

// Filtrera inställningar
apply_filters('cyp_settings', $settings);

// Filtrera shortcode-attribut
apply_filters('cyp_shortcode_atts', $atts);

// Filtrera SVG-attribut
apply_filters('cyp_svg_attributes', $attributes);
```

## Anpassning

### Lägg till egen händelsetyp programmatiskt

```php
add_filter('cyp_event_types', function($types) {
    $types[] = [
        'name' => 'Styrelsearbete',
        'color' => '#FF5733'
    ];
    return $types;
});
```

### Modifiera händelsedata

```php
add_filter('cyp_event_data', function($event_data, $post_id) {
    $event_data['custom_field'] = get_post_meta($post_id, '_custom_field', true);
    return $event_data;
}, 10, 2);
```

### Anpassa kalenderutseende

```php
add_filter('cyp_svg_attributes', function($attributes) {
    $attributes['class'] = 'my-custom-class';
    return $attributes;
});
```

## JavaScript API

### Initiera kalender manuellt

```javascript
const calendar = new CircularCalendar('.cyp-calendar-container');
```

### Ladda om händelser

```javascript
calendar.loadEvents();
```

### Ändra verksamhetsår

```javascript
calendar.fiscalYear = '2024/2025';
calendar.loadEvents();
```

### Custom event handlers

```javascript
// Lägg till egen logik när en händelse klickas
jQuery(document).on('click', '.cyp-event-arc', function() {
    const eventId = jQuery(this).data('event-id');
    // Din custom logik här
});
```

## REST API

### Hämta händelser

**Endpoint:** `GET /wp-json/cyp/v1/events`

**Parametrar:**
- `fiscal_year` (string) - Filtrera på verksamhetsår
- `types` (string) - Kommaseparerad lista av händelsetyp-index

**Exempel:**
```bash
curl "https://example.com/wp-json/cyp/v1/events?fiscal_year=2024/2025&types=0,1"
```

**Response:**
```json
[
    {
        "id": 123,
        "title": "Årsmöte",
        "description": "Årligt styrelsemöte",
        "start_date": "2024-03-15",
        "end_date": "2024-03-15",
        "event_type": "0",
        "event_type_name": "Program",
        "event_type_color": "#4A90E2",
        "fiscal_year": "2024/2025"  // Beräknas automatiskt från start_date
    }
]
```

### Hämta inställningar

**Endpoint:** `GET /wp-json/cyp/v1/settings`

**Response:**
```json
{
    "event_types": [
        {"name": "Program", "color": "#4A90E2"},
        {"name": "Kampanj", "color": "#E24A90"}
    ],
    "fiscal_year_start": "01-01",
    "color_scheme": "default"
}
```

## CSS Customization

### Anpassa färger

```css
/* Ändra månadstext-färg */
.cyp-month-label {
    fill: #your-color;
}

/* Ändra hover-effekt på händelser */
.cyp-event-arc:hover {
    opacity: 1;
    filter: brightness(1.2);
}

/* Anpassa detaljpanel */
.cyp-event-details {
    background: #your-background;
    border: 2px solid #your-border;
}
```

### Responsiv anpassning

```css
@media (max-width: 480px) {
    .cyp-calendar-container {
        padding: 5px;
    }
    
    #cyp-circular-calendar {
        max-width: 100%;
    }
}
```

## Debugging

### Aktivera debug-läge

```php
// I wp-config.php
define('CYP_DEBUG', true);
```

### Console logging

```javascript
// I circular-calendar.js är console.error redan aktiverat
// Lägg till fler logs vid behov:
console.log('Events loaded:', this.events);
```

### Vanliga problem

**Problem:** Kalendern renderas inte
- Kontrollera att jQuery laddas korrekt
- Se till att REST API är tillgängligt
- Kontrollera browser-konsolen för fel

**Problem:** Händelser syns inte
- Verifiera att händelserna har korrekt datum
- Kontrollera att verksamhetsåret matchar
- Se API-response i Network-tab

**Problem:** Svenska veckonummer är felaktiga
- Kontrollera `getWeekNumber()` funktionen
- ISO 8601-standard används

## Testing

### Manuell testning

1. Skapa händelser med olika datum
2. Testa alla händelsetyper
3. Byt verksamhetsår
4. Testa navigation
5. Verifiera responsiv design

### Testdata

Använd följande SQL för att skapa testdata:

```sql
-- Infogas via WordPress admin eller via WP-CLI
-- Se SNABBSTART.md för manuell skapande av händelser
```

## Bidra till projektet

### Kodstil

- Följ WordPress Coding Standards
- Använd phpcs för PHP
- ESLint för JavaScript
- Kommentera komplexa funktioner

### Git Workflow

1. Fork projektet
2. Skapa feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit dina ändringar (`git commit -m 'Add some AmazingFeature'`)
4. Push till branch (`git push origin feature/AmazingFeature`)
5. Öppna en Pull Request

### Commit-meddelanden

Använd konventionella commit-meddelanden:
- `feat:` ny funktionalitet
- `fix:` buggfix
- `docs:` dokumentation
- `style:` formatering
- `refactor:` kod-omstrukturering
- `test:` tester
- `chore:` underhåll

## Performance

### Optimering

- Händelser cachas efter första laddningen
- SVG renderas endast vid behov
- Lazy loading av händelsedetaljer
- Minimera DOM-operationer

### Best Practices

- Använd `posts_per_page => -1` med försiktighet
- Cacha API-responses när möjligt
- Optimera SVG-path-beräkningar

## Säkerhet

### Nonce-validering

All data som sparas valideras med nonce (v1.0.15+):

```php
// Korrekt nonce-verifiering med wp_unslash
if (!isset($_POST['cyp_event_details_nonce']) || 
    !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cyp_event_details_nonce'])), 'cyp_save_event_details')) {
    return;
}
```

**Viktigt:** Alla `$_POST` och `$_GET` variabler måste:
1. Unslashas med `wp_unslash()` innan sanitering
2. Saniteras med lämplig funktion
3. Escapas vid output

### Sanitering

All input saniteras korrekt:
- `sanitize_text_field()` för text
- `sanitize_hex_color()` för färger
- `intval()` eller `absint()` för nummer
- `esc_url()` för URLs

**Exempel:**
```php
$value = sanitize_text_field(wp_unslash($_POST['field_name']));
```

### Output Escaping

All output escapas (WP Plugin Check-kompatibel):
- `esc_html()` för HTML-text
- `esc_attr()` för HTML-attribut
- `esc_url()` för URLs
- `wp_kses_post()` för HTML-innehåll

**Exempel:**
```php
<div data-year="<?php echo esc_attr($year); ?>">
    <?php echo esc_html($title); ?>
</div>
```

### Capability-kontroll

```php
if (!current_user_can('manage_options')) {
    wp_die('Otillåten åtkomst');
}
```

### WordPress Plugin Check Compliance

Pluginen uppfyller alla krav från WordPress Plugin Check (v1.0.15):
- ✅ Nonce verification med unslashing
- ✅ Input sanitization
- ✅ Output escaping
- ✅ Sanitization callbacks för register_setting()
- ✅ Tested up to senaste WordPress-version
- ✅ Ingen manual textdomain-laddning (auto-load sedan WP 4.6)

## Versionering

Projektet följer [Semantic Versioning](https://semver.org/):
- MAJOR: Inkompatibla API-ändringar
- MINOR: Ny funktionalitet (bakåtkompatibel)
- PATCH: Buggfixar

---

**Lycka till med utvecklingen! 🚀**

