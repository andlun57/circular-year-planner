# Changelog

Alla betydande ändringar i detta projekt dokumenteras i denna fil.

## [1.0.17] - 2025-10-20

### Fixat
- 🐛 Korrigerad datumformatering i frontend som gav felaktiga månadsnamn (t.ex. "Septe09ber").

### Tekniskt
- Bytt ut global regex-ersättning mot token-baserad formattering i `formatDate()` för att undvika att siffror injiceras i redan insatta månadsnamn. Stöd för escaping med backslash.

## [1.0.16] - 2024-12-19

### Tillagt
- 📅 Streckad linje som markerar dagens datum i kalendern
- 🔄 Automatisk fyllning av slutdatum när startdatum anges
- 📊 Radiell stapling av händelser som inträffar samma vecka
- 🌍 Svensk översättning för automatisk slutdatum-funktionalitet

### Förbättrat
- 🎯 Dagens datum visas tydligt med röd streckad linje (2.5px bredd)
- ⚡ Snabbare inmatning av endagshändelser (slutdatum fylls automatiskt)
- 👁️ Bättre synlighet när flera händelser inträffar samma vecka
- 🧹 Renare formulär utan överflödig verksamhetsårsinformation

### Fixat
- 🐛 Slutdatum sätts nu korrekt även när fältet är tomt
- 🌍 Svenska översättningar kompilerade och fungerar korrekt
- 📝 Översättningstext uppdaterad för slutdatum-beskrivning

### Tekniskt
- Ny metod: `drawTodayMarker()` för dagens datum-markering
- Ny metod: `drawStackedEvents()` för radiell stapling av händelser
- Ny metod: `groupEventsByWeek()` för veckogruppering av händelser
- Ny metod: `drawEventsInWeek()` för staplad rendering inom veckor
- Förbättrad JavaScript-logik för automatisk slutdatum-fyllning
- Uppdaterade översättningsfiler (.po och .mo) för svenska
- Borttagen överflödig verksamhetsårsinformation från formulär

## [1.0.15] - 2024-10-18

### Tillagt
- 🎨 Textfärgval för varje händelsetyp
- ✨ Möjlighet att välja anpassad textfärg för bättre synlighet
- 🔄 Automatisk kontrastfärg om ingen textfärg anges
- 🎯 Textfärg appliceras både i kalendern och i händelsedetaljer
- 📊 Sorterbara kolumner i admin-listan för händelser

### Förbättrat
- 👁️ Bättre läsbarhet av händelsetext med anpassningsbara färger
- 🎨 Mer flexibel färghantering för händelsetyper
- 📝 Uppdaterad beskrivning i inställningar om automatisk kontrast
- 🔍 Alla kolumner i händelselistan kan nu sorteras (Händelsetyp, Startdatum, Slutdatum, Publiceringsdatum)

### Tekniskt
- Nytt fält: `text_color` i händelsetyper
- Uppdaterad `sanitize_event_types()` för att hantera textfärg
- Uppdaterad REST API för att inkludera `event_type_text_color`
- JavaScript använder custom textfärg eller fallback till `getContrastColor()`
- Nya översättningar: "Background", "Text", "Auto"
- Uppdaterade CSS-stilar för färgväljare med etiketter
- Ny metod: `set_sortable_columns()` för att markera kolumner som sorterbara
- Ny metod: `handle_custom_column_sorting()` för att hantera sortering av meta-fält

## [1.0.15] - 2024-10-17

### Förbättrat
- 📊 Dynamisk ringstorlek för 6+ händelsetyper
- 🎯 Ringarna skalas automatiskt proportionellt
- ✨ Samma totalutrymme behålls oavsett antal händelsetyper
- 📏 Tunna hjälplinjer mellan månaderna genom alla händelseringar
- 🎯 Extra markerad årsskifteslinje (vertikalt uppåt) mellan sista och första månaden
- 👁️ Bättre visuell avgränsning av månader och år
- 📜 GPL v2 licens tillagd för WordPress Plugin Directory kompatibilitet
- 📝 readme.txt skapad enligt WordPress standard
- 🌍 Engelsk översättning inkluderad (en_US)
- 🇸🇪 Svenska översättningsfiler skapade (sv_SE)

### Tekniskt
- Beräknar ringhöjd dynamiskt: 225px totalt / antal händelsetyper
- Maximal bredd (45px) för 1-5 händelsetyper
- Proportionell skalning för 6+ händelsetyper
- Ny metod: drawMonthDividers() för radiella månadsgränser
- Månadsgränser: stroke-width 1px, opacity 0.4
- Årsskifteslinje: stroke-width 2px, opacity 0.6 (extra markerad)
- Uppdaterade plugin headers med korrekt information
- POT-mall och PO/MO-filer för översättning
- Engelsk readme.txt för internationell publik

## [1.0.14] - 2024-10-16

### Förbättrat
- 🔢 Veckonummer visas nu varannan vecka (tidigare var fjärde vecka)
- 👁️ Bättre läsbarhet av veckonummer i kalendern

### Tekniskt
- Ändrat visningsintervall från `% 4 === 0` till `% 2 === 0`

## [1.0.13] - 2024-10-16

### Förbättrat
- 🔄 Texten vänds automatiskt på vänster sidan av cirkeln (90°-270°)
- 📖 All händelsetext är nu läsbar oavsett position
- ✨ Intelligent textorientering baserat på vinkel

### Tekniskt
- Beräknar mittpunktvinkel för varje händelse
- Inverterar textPath-riktning för text mellan 90° och 270°
- Normaliserar vinklar för korrekt orientering

## [1.0.12] - 2024-10-16

### Förbättrat
- 📏 Händelseringar gjorda 50% bredare (från 30px till 45px)
- 🎨 Ljusgrå bakgrund (#f5f5f5) på alla händelseringar
- ✨ Mer utrymme för händelsetext och bättre synlighet

### Tekniskt
- ringHeight ökad från 30px till 45px
- Ny metod: drawRingBackground() för ljusgrå bakgrundscirklar
- Bakgrundscirklar med pointer-events: none

## [1.0.11] - 2024-10-16

### Förbättrat
- 📅 Förkortad visning av verksamhetsår
- ✨ Kalenderår visas som "2025"
- ✨ Brutna verksamhetsår förkortas till "24/25" (istället för "2024/2025")
- 🎯 Mer kompakt och lättläst i både centrum och titel

### Tekniskt
- Förkortning i drawCenterYear() för centrum-etikett
- Förkortning i changeFiscalYear() för navigeringstitel
- Förkortning i shortcode render för initial visning
- Fullt verksamhetsår används fortfarande i data-attribut för API-anrop

## [1.0.10] - 2024-10-16

### Förbättrat
- 📏 Ökat avstånd mellan veckoring och händelseringar för veckonummer
- 🎯 Ca en halv händelsering (~15px) extra utrymme för veckonummer
- ✨ Optimerade avstånd mellan alla ringar

### Tekniskt
- Händelseringar flyttade från radius - 50 till radius - 70
- Ger ~45px utrymme för veckoringen och dess nummer
- Balanserad layout med kompakta yttre ringar och gott om plats för veckonummer

## [1.0.9] - 2024-10-16

### Förbättrat
- 📏 Minskat avstånd mellan månadsring och veckoring till ~5px
- 🎯 Mer kompakt och tätare layout
- ✨ Händelseringar justerade för att passa den nya layouten

### Tekniskt
- Veckoring flyttad från radius - 60 till radius - 25
- Endast 5px mellanrum mellan månadsring (inre kant: radius - 20) och veckoring (yttre kant: radius - 25)
- Händelseringar börjar nu vid radius - 50

## [1.0.8] - 2024-10-16

### Förbättrat
- 🔄 Månadsetiketter flyttade längst ut på kalendern
- 📊 Månadsring precis innanför etiketterna
- ✨ Tydligare visuell hierarki

### Tekniskt
- Månadsetikett: radius + 15px (längst ut)
- Månadsring: radius - 20px till radius (precis innanför etiketten)

## [1.0.7] - 2024-10-16

### Förbättrat
- 🔤 Textstorlek ökad till 9px för bättre läsbarhet
- ✨ Nu när textklippning är löst kan vi ha större, tydligare text

## [1.0.6] - 2024-10-16

### Fixat
- 🐛 **SLUTLIG FIX för textklippning** - textPath är nu 60° längre än händelsen
- ✨ All text visas nu komplett oavsett längd
- 🎯 Path för text sträcker sig 30° utanför händelsens båda sidor

### Tekniskt
- textPath använder extended arc (startAngle - 30° till endAngle + 30°)
- Text förblir centrerad på händelsen (startOffset 50%)
- Ger texten ~2x mer utrymme än själva händelselängden

## [1.0.5] - 2024-10-16

### Förbättrat
- 🔤 Textstorlek minskad till 8px för bättre utrymme
- ✍️ Text visas nu som inskriven (ej versaler) - mer naturligt
- 🎯 Förbättrad textklippning med explicit overflow på alla nivåer
- 📏 Minskad letter-spacing till 0.3px för kompaktare text

### Ändrat
- ❌ Tog bort text-transform: uppercase
- 🔧 Font-weight justerad till 600 istället för 700
- ✨ Explicit overflow: visible på SVG, g-element och paths

### Tekniskt
- textPath side="left" för konsistent rendering
- Inline style overflow: visible på text-element
- SVG-element har explicit overflow-attribut

## [1.0.4] - 2024-10-16

### Förbättrat
- 📝 Text visas nu komplett även när den är längre än händelsefältet
- 🔤 Minskad textstorlek till 9px för bättre läsbarhet
- ✨ Ingen textklippning vid långa händelsenamn

### Fixat
- 🐛 Text klipptes av när händelsenamnet var längre än fältet
- 🔧 Lagt till overflow: visible på alla SVG-textelement

### Tekniskt
- SVG overflow: visible på text, textPath och g-element
- textPath method="stretch" och spacing="auto" för bättre rendering
- Font-size reducerad från 10px till 9px

## [1.0.3] - 2024-10-16

### Förbättrat
- 🎨 Händelsetext visas nu korrekt i cirkeln för ALLA händelser
- 📝 Förbättrad SVG textPath-implementering med native DOM-metoder
- 🔲 Ramar runt varje händelsetypsring för tydlig visuell separation
- ✨ Text renderas oavsett händelselängd

### Fixat
- 🐛 Löste problem där text inte visades i cirkeln
- 🔧 Förbättrad SVG-elementhantering

### Tekniskt
- Ny metod: `drawRingBorder()` för ramar runt händelsetypscirklar
- Förbättrad `drawEventText()` med native SVG createElement
- Använder document.createElementNS för korrekt SVG-rendering

## [1.0.2] - 2024-10-16

### Tillagt
- ✨ Händelsetext längs händelsebågen (initial implementation)
- 📏 Endagshändelser renderas som hela veckan för bättre synlighet
- 🎨 Automatisk kontrastfärgberäkning för text (svart/vit beroende på bakgrund)
- 💡 Informationstext i detaljpanel för endagshändelser

### Tekniskt
- Ny metod: `drawEventText()` för att rita text längs bågar
- Ny metod: `createTextArc()` för att skapa textpaths
- Ny metod: `getContrastColor()` för färgkontrast
- Förbättrad logik i `drawEvent()` för endagshändelser

## [1.0.1] - 2024-10-16

### Förbättrat
- ✨ Verksamhetsår beräknas nu automatiskt från händelsens startdatum
- 🎯 Användaren behöver inte längre ange verksamhetsår manuellt
- 📊 Verksamhetsår visas som beräknad information i admin-gränssnittet
- 🔧 Förbättrad användarupplevelse med mindre manuell inmatning

### Ändrat
- Tog bort verksamhetsår-fältet från händelseformuläret
- Verksamhetsår-kolumnen borttagen från admin-listan
- REST API beräknar nu verksamhetsår automatiskt vid datahämtning

### Tekniskt
- Ny metod: `calculate_fiscal_year()` i CYP_Event_Post_Type
- Statisk metod: `get_event_fiscal_year()` för extern användning
- Uppdaterad REST API-logik för automatisk beräkning

## [1.0.0] - 2024-10-16

### Tillagt
- ✨ Cirkulär kalendervisning med SVG
- ✨ Custom Post Type för händelser
- ✨ Anpassningsbara händelsetyper med färgval
- ✨ Flexibelt verksamhetsår (kan börja vilken månad som helst)
- ✨ Svenska veckonummer enligt ISO 8601
- ✨ REST API endpoints för händelser och inställningar
- ✨ Shortcode `[circular_year_planner]` med parametrar
- ✨ Administrativt gränssnitt för inställningar
- ✨ Interaktiv kalender med klickbara händelser
- ✨ Detaljpanel för händelser
- ✨ Navigation mellan år
- ✨ Färgscheman: Standard, Mörk, Blå, Grön
- ✨ Responsiv design
- ✨ Legend för händelsetyper
- 📚 Omfattande dokumentation (README, Snabbstart)

### Funktionalitet
- Månadsringar visar hela året
- Veckoring med numrering
- Separata händelseringar per typ
- Verksamhetsår i centrum
- Automatisk beräkning av verksamhetsår
- Färgkodade händelser
- Hover-effekter och klickbara element

### Tekniskt
- WordPress 5.8+ kompatibel
- PHP 7.4+ krävs
- Vanilla JavaScript + jQuery
- SVG-baserad rendering
- REST API integration
- Säker nonce-validering
- AJAX-baserad datahämtning

---

Formatering: [version] - datum
Kategorier: Tillagt, Ändrat, Borttaget, Fixat, Säkerhet
