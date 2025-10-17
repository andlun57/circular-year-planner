# Installation - Circular Year Planner

## Metod 1: Manuell installation (Rekommenderad)

### Steg 1: Förbered filer

1. Se till att du har alla filer i mappen `circular-year-planner/`
2. Verifiera att mappstrukturen är korrekt:
   ```
   circular-year-planner/
   ├── circular-year-planner.php
   ├── includes/
   ├── assets/
   ├── README.md
   └── ...
   ```

### Steg 2: Ladda upp till WordPress

**Via FTP/SFTP:**
1. Anslut till din webbserver
2. Navigera till `/wp-content/plugins/`
3. Ladda upp hela mappen `circular-year-planner/`
4. Kontrollera att filerna har rätt behörigheter (755 för mappar, 644 för filer)

**Via cPanel File Manager:**
1. Logga in på cPanel
2. Öppna File Manager
3. Navigera till `public_html/wp-content/plugins/`
4. Ladda upp hela mappen `circular-year-planner/`

**Via WP-CLI:**
```bash
wp plugin install /path/to/circular-year-planner.zip --activate
```

### Steg 3: Aktivera plugin

1. Logga in på WordPress admin (`/wp-admin`)
2. Gå till **Plugins → Installed Plugins**
3. Hitta "Circular Year Planner"
4. Klicka på **Activate**

### Steg 4: Verifiera installation

1. I WordPress-menyn ska du nu se **Årsplanering**
2. Klicka på **Årsplanering → Inställningar**
3. Du ska se inställningssidan med förinställda händelsetyper

## Metod 2: Skapa ZIP och ladda upp via WordPress

### Steg 1: Skapa ZIP-fil

**På Mac/Linux:**
```bash
cd /path/to/parent-directory
zip -r circular-year-planner.zip circular-year-planner/ -x "*.DS_Store" -x "*__MACOSX*"
```

**På Windows:**
1. Högerklicka på mappen `circular-year-planner`
2. Välj "Send to → Compressed (zipped) folder"

### Steg 2: Ladda upp via WordPress

1. Logga in på WordPress admin
2. Gå till **Plugins → Add New**
3. Klicka på **Upload Plugin**
4. Välj `circular-year-planner.zip`
5. Klicka **Install Now**
6. Klicka **Activate Plugin**

## Systemkrav

### Minimikrav

| Krav | Version |
|------|---------|
| WordPress | 5.8 eller senare |
| PHP | 7.4 eller senare |
| MySQL | 5.6 eller senare |
| jQuery | Medföljer WordPress |

### Rekommenderat

| Krav | Version |
|------|---------|
| WordPress | 6.0+ |
| PHP | 8.0+ |
| MySQL | 8.0+ |
| HTTPS | Aktiverat |

### Webbläsarstöd

- Chrome 90+ ✅
- Firefox 88+ ✅
- Safari 14+ ✅
- Edge 90+ ✅
- Internet Explorer ❌ (ej stödd)

## Första konfiguration

### 1. Grundinställningar

Efter aktivering, gå till **Årsplanering → Inställningar**:

1. **Händelsetyper** - Standardtyper är förinställda:
   - Program (Blå)
   - Kampanj (Rosa)
   - Utbildning (Grön)
   - Möte (Orange)

2. **Verksamhetsår** - Standardvärde: Januari (kalenderår)

3. **Färgschema** - Standardvärde: Standard (ljus)

### 2. Skapa första händelsen

1. Gå till **Årsplanering → Lägg till händelse**
2. Fyll i formuläret
3. Klicka **Publicera**

### 3. Visa kalendern

1. Skapa en ny sida: **Sidor → Lägg till ny**
2. Lägg till shortcode: `[circular_year_planner]`
3. Publicera sidan
4. Besök sidan för att se kalendern

## Felsökning vid installation

### Problem: Plugin syns inte i listan

**Lösning:**
- Kontrollera att mappen heter exakt `circular-year-planner`
- Verifiera att filen `circular-year-planner.php` finns i rooten av plugin-mappen
- Kontrollera att alla filer laddades upp korrekt

### Problem: Aktiveringen misslyckas

**Lösning:**
- Kontrollera PHP-versionen: `php -v` eller i WordPress under **Verktyg → Site Health**
- Se i debug-loggen: Aktivera `WP_DEBUG` i `wp-config.php`
  ```php
  define('WP_DEBUG', true);
  define('WP_DEBUG_LOG', true);
  ```
- Kontrollera filbehörigheter

### Problem: "The plugin does not have a valid header"

**Lösning:**
- Kontrollera att `circular-year-planner.php` innehåller korrekt plugin-header
- Se till att filen inte är korrupt
- Ladda upp filen igen

### Problem: White screen efter aktivering

**Lösning:**
1. Inaktivera plugin via FTP:
   - Byt namn på mappen till `circular-year-planner-disabled`
2. Kontrollera PHP-versionen (måste vara 7.4+)
3. Kolla error-loggen i WordPress eller på servern
4. Återaktivera och testa igen

## Upgrade från framtida versioner

När nya versioner släpps:

1. **Backup först!**
   - Backup av databasen
   - Backup av plugin-filen

2. **Inaktivera plugin** (ej nödvändigt men rekommenderat)

3. **Ersätt filer:**
   - Ta bort gamla filer
   - Ladda upp nya filer

4. **Återaktivera plugin**

5. **Verifiera funktionalitet**

## Avinstallation

### Komplett borttagning

1. **Inaktivera plugin** i WordPress admin

2. **Ta bort plugin:**
   - Gå till **Plugins → Installed Plugins**
   - Klicka **Delete** under Circular Year Planner

3. **Radera data manuellt** (om pluginens avinstallation inte gör det):

```sql
-- Ta bort händelser
DELETE FROM wp_posts WHERE post_type = 'cyp_event';
DELETE FROM wp_postmeta WHERE post_id NOT IN (SELECT ID FROM wp_posts);

-- Ta bort inställningar
DELETE FROM wp_options WHERE option_name LIKE 'cyp_%';
```

**OBS:** Detta tar bort ALL data! Gör backup först.

## Migrering mellan sajter

### Export

1. Exportera händelser via **Verktyg → Exportera**
2. Välj "Händelser" som content-typ
3. Ladda ner XML-filen

### Import

1. På nya sajten: **Verktyg → Importera → WordPress**
2. Ladda upp XML-filen
3. Importera innehåll
4. Gå till **Årsplanering → Inställningar** och konfigurera samma inställningar

## Support vid installation

Om du stöter på problem:

1. Kontrollera systemkraven
2. Se över felsökningen ovan
3. Aktivera WP_DEBUG och kontrollera loggen
4. Kontakta support med:
   - WordPress-version
   - PHP-version
   - Felmeddelande (om något)
   - Steg du tog innan felet uppstod

---

**Lycka till med installationen! 🚀**

