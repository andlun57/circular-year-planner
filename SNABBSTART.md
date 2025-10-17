# Snabbstartsguide - Circular Year Planner

## Steg 1: Installation

1. Kopiera hela mappen `circular-year-planner` till din WordPress-installation:
   ```
   /wp-content/plugins/circular-year-planner/
   ```

2. Logga in på WordPress admin-panel

3. Gå till **Plugins** och aktivera "Circular Year Planner"

## Steg 2: Grundinställningar

1. I WordPress-menyn, klicka på **Årsplanering → Inställningar**

2. **Konfigurera händelsetyper:**
   - Lägg till dina händelsetyper (t.ex.):
     - Program (Blå färg: #4A90E2)
     - Kampanj (Rosa färg: #E24A90)
     - Utbildning (Grön färg: #90E24A)
     - Möte (Orange färg: #E2904A)
   - Klicka på färgfältet för att välja en annan färg
   - Använd "Lägg till händelsetyp" för fler typer

3. **Välj verksamhetsår:**
   - Om ditt verksamhetsår börjar i januari: välj "Januari (kalenderår)"
   - Om det börjar i juli: välj "Juli"
   - Osv.

4. **Välj färgschema:**
   - Standard (ljus) - rekommenderas för de flesta webbplatser
   - Mörk - för mörka teman
   - Blå/Grön - för alternativa färgteman

5. Klicka **Spara inställningar**

## Steg 3: Skapa din första händelse

1. Klicka på **Årsplanering → Lägg till händelse**

2. Fyll i formuläret:
   - **Titel:** "Årsmöte 2024"
   - **Beskrivning:** "Årligt styrelsemöte med genomgång av verksamhetsplan"
   - **Startdatum:** 2024-03-15
   - **Slutdatum:** 2024-03-15
   - **Händelsetyp:** Välj "Möte"
   - **Verksamhetsår:** Beräknas automatiskt från startdatum!

3. Klicka **Publicera**

## Steg 4: Lägg till fler händelser

Skapa några fler händelser för att se kalendern i aktion:

**Exempel 1 - Marknadsföringskampanj:**
- Titel: "Vårens kampanj"
- Startdatum: 2024-04-01
- Slutdatum: 2024-04-30
- Typ: Kampanj

**Exempel 2 - Utbildningsprogram:**
- Titel: "Ledarskapsutbildning"
- Startdatum: 2024-05-10
- Slutdatum: 2024-05-12
- Typ: Utbildning

**Exempel 3 - Årlig aktivitet:**
- Titel: "Sommarfest"
- Startdatum: 2024-06-15
- Slutdatum: 2024-06-15
- Typ: Program

## Steg 5: Visa kalendern på din webbplats

1. **Skapa eller redigera en sida:**
   - Gå till **Sidor → Lägg till ny** (eller redigera en befintlig sida)
   - Ge sidan namnet "Årsplanering" eller liknande

2. **Lägg till shortcode:**
   - I innehållsredigeraren, lägg till:
     ```
     [circular_year_planner]
     ```

3. **Publicera sidan**

4. **Besök sidan** för att se din cirkulära kalender!

## Steg 6: Utforska funktioner

### Navigation
- Använd **pilknapparna** (‹ ›) för att navigera mellan år
- Klicka på en **händelse** för att se detaljerad information

### Legenden
- Överst på kalendern ser du en legend med alla händelsetyper och deras färger

### Anpassa visningen

**Visa endast specifikt år:**
```
[circular_year_planner year="2024/2025"]
```

**Visa endast vissa händelsetyper:**
```
[circular_year_planner types="0,1"]
```
(0 = första händelsetypen, 1 = andra händelsetypen, osv.)

**Anpassa storlek:**
```
[circular_year_planner width="1000" height="1000"]
```

## Tips & Tricks

### Planera ett helt verksamhetsår

1. Börja med **återkommande aktiviteter** (årsmöten, rapporter, etc.)
2. Lägg till **säsongsbaserade kampanjer**
3. Fyll i **utbildningar och events**
4. Markera **viktiga deadlines**

### Färgkodning

Använd färger strategiskt:
- **Blå** - Administrativa aktiviteter
- **Grön** - Utbildning och utveckling
- **Rosa/Röd** - Marknadsföring och kampanjer
- **Orange** - Möten och workshops

### Verksamhetsår

Om din organisation använder ett verksamhetsår som inte följer kalenderåret:
- Skolår: Välj "Juli" eller "Augusti" som start
- Räkenskapsår: Välj lämplig månad
- Kalenderår: Välj "Januari"

## Felsökning

### Kalendern visas inte
- Kontrollera att pluginen är aktiverad
- Se till att shortcode är korrekt inskriven
- Kontrollera att du har publicerade händelser

### Händelser saknas
- Kontrollera att händelserna är **Publicerade** (inte Utkast)
- Se till att händelserna har rätt verksamhetsår inställt
- Verifiera datum (start- och slutdatum måste vara ifyllda)

### Färger syns inte
- Gå till Inställningar och kontrollera att händelsetyperna har färger
- Spara inställningar igen om de verkar saknas

## Nästa steg

När du känner dig bekväm med grundfunktionerna:
- Utforska REST API för integrationer
- Anpassa CSS för att matcha din webbplats design
- Dela kalendern med ditt team
- Exportera/importera händelser (kommer i framtida version)

## Support

Om du stöter på problem eller har frågor, kontakta plugin-utvecklaren.

---

**Lycka till med din årsplanering! 📅**

