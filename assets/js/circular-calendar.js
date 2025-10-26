/**
 * Circular Calendar JavaScript för Circular Year Planner
 */

(function($) {
    'use strict';
    
    class CircularCalendar {
        constructor(container) {
            this.container = $(container);
            this.svg = this.container.find('#cyp-circular-calendar');
            this.fiscalYear = this.container.data('fiscal-year');
            this.eventTypes = this.container.data('event-types');
            this.width = parseInt(this.container.data('width')) || 800;
            this.height = parseInt(this.container.data('height')) || 800;
            
            // Ensure fiscalYear is set and is a string
            if (!this.fiscalYear) {
                console.warn('No fiscal year provided, using current year');
                this.fiscalYear = new Date().getFullYear().toString();
            } else {
                // Convert to string to ensure string methods work
                this.fiscalYear = String(this.fiscalYear);
            }
            
            this.centerX = this.width / 2;
            this.centerY = this.height / 2;
            this.outerRadius = Math.min(this.width, this.height) / 2 - 40;
            
            this.events = [];
            this.settings = {};
            
            this.init();
        }
        
        init() {
            this.loadSettings().then(() => {
                this.loadEvents();
            });
            
            this.setupEventHandlers();
        }
        
        async loadSettings() {
            try {
                const response = await fetch(cyplData.restUrl + 'settings');
                if (!response.ok) {
                    throw new Error('Failed to load settings: ' + response.status);
                }
                this.settings = await response.json();
                this.renderLegend();
            } catch (error) {
                console.error('Error loading settings:', error);
                // Set default settings if loading fails
                this.settings = {
                    event_types: [],
                    fiscal_year_start: '01-01',
                    color_scheme: 'default'
                };
            }
        }
        
        async loadEvents() {
            this.container.find('.cyp-loading').show();
            
            try {
                let url = cyplData.restUrl + 'events?fiscal_year=' + encodeURIComponent(this.fiscalYear);
                if (this.eventTypes) {
                    url += '&types=' + encodeURIComponent(this.eventTypes);
                }
                
                const response = await fetch(url);
                if (!response.ok) {
                    throw new Error('Failed to load events: ' + response.status);
                }
                this.events = await response.json();
                
                this.render();
            } catch (error) {
                console.error('Error loading events:', error);
                // Render anyway with empty events
                this.events = [];
                this.render();
            } finally {
                this.container.find('.cyp-loading').hide();
            }
        }
        
        render() {
            this.svg.empty();
            
            // Se till att SVG inte klipper innehåll
            this.svg.attr('overflow', 'visible');
            this.svg.css('overflow', 'visible');
            
            // Applicera färgschema
            if (this.settings.color_scheme && this.settings.color_scheme !== 'default') {
                this.container.attr('data-color-scheme', this.settings.color_scheme);
            } else {
                this.container.removeAttr('data-color-scheme');
            }
            
            // Skapa SVG-grupper
            const g = this.createSVGElement('g', {
                transform: `translate(${this.centerX}, ${this.centerY})`,
                overflow: 'visible'
            });
            
            this.svg.append(g);
            
            // Rita cirklar och etiketter
            this.drawMonthRing(g);
            this.drawWeekRing(g);
            this.drawEventRings(g);
            this.drawMonthDividers(g); // Rita månadsgränser sist så de syns ovanpå
            this.drawTodayMarker(g); // Rita dagens datum-markering
            this.drawCenterYear(g);
        }
        
        drawMonthRing(g) {
            const monthRadius = this.outerRadius;
            const months = this.getMonthsInFiscalYear();
            const totalDays = this.getTotalDaysInYear();
            
            let dayOffset = 0;
            
            months.forEach(month => {
                const daysInMonth = this.getDaysInMonth(month.year, month.month);
                const startAngle = (dayOffset / totalDays) * 360 - 90;
                const endAngle = ((dayOffset + daysInMonth) / totalDays) * 360 - 90;
                
                // Månadsetikett längst ut
                const labelAngle = (startAngle + endAngle) / 2;
                const labelRadius = monthRadius + 15;
                const labelX = Math.cos(this.degToRad(labelAngle)) * labelRadius;
                const labelY = Math.sin(this.degToRad(labelAngle)) * labelRadius;
                
                const text = this.createSVGElement('text', {
                    x: labelX,
                    y: labelY,
                    class: 'cypl-month-label',
                    'text-anchor': 'middle',
                    'dominant-baseline': 'middle'
                });
                text.text(this.getMonthName(month.month));
                g.append(text);
                
                // Rita månadsbåge precis innanför etiketten
                const arc = this.createArc(monthRadius - 20, monthRadius, startAngle, endAngle);
                const path = this.createSVGElement('path', {
                    d: arc,
                    class: 'cypl-month-arc',
                    fill: 'none',
                    stroke: '#ddd',
                    'stroke-width': '2'
                });
                g.append(path);
                
                dayOffset += daysInMonth;
            });
        }
        
        drawMonthDividers(g) {
            const months = this.getMonthsInFiscalYear();
            const totalDays = this.getTotalDaysInYear();
            
            // Beräkna var händelseringarna börjar och slutar
            const eventTypes = this.settings.event_types || [];
            const numTypes = eventTypes.length;
            
            if (numTypes === 0) return; // Inga händelseringar att rita genom
            
            const baseRadius = this.outerRadius - 70;
            const baseRingHeight = 45;
            const maxRingsForBaseHeight = 5;
            const totalSpace = baseRingHeight * maxRingsForBaseHeight;
            const ringHeight = numTypes <= maxRingsForBaseHeight 
                ? baseRingHeight 
                : totalSpace / numTypes;
            
            // Yttre radie för händelseringarna (där första ringen börjar)
            const outerEventRadius = baseRadius;
            // Innersta radie för händelseringarna
            const innerRadius = baseRadius - numTypes * ringHeight;
            
            let dayOffset = 0;
            
            months.forEach((month, index) => {
                const daysInMonth = this.getDaysInMonth(month.year, month.month);
                const startAngle = (dayOffset / totalDays) * 360 - 90;
                
                // Rita radiell linje vid månadsgränsen (utom för första månaden)
                if (index > 0) {
                    const angleRad = this.degToRad(startAngle);
                    const x1 = Math.cos(angleRad) * outerEventRadius;
                    const y1 = Math.sin(angleRad) * outerEventRadius;
                    const x2 = Math.cos(angleRad) * innerRadius;
                    const y2 = Math.sin(angleRad) * innerRadius;
                    
                    const line = this.createSVGElement('line', {
                        x1: x1,
                        y1: y1,
                        x2: x2,
                        y2: y2,
                        class: 'cypl-month-divider',
                        stroke: '#999',
                        'stroke-width': '1',
                        opacity: '0.4',
                        'pointer-events': 'none'
                    });
                    g.append(line);
                }
                
                dayOffset += daysInMonth;
            });
            
            // Rita årsskiftet (mellan sista och första månaden) som en extra markerad linje
            const yearStartAngle = -90; // Vertikalt uppåt
            const yearStartRad = this.degToRad(yearStartAngle);
            const x1 = Math.cos(yearStartRad) * outerEventRadius;
            const y1 = Math.sin(yearStartRad) * outerEventRadius;
            const x2 = Math.cos(yearStartRad) * innerRadius;
            const y2 = Math.sin(yearStartRad) * innerRadius;
            
            const yearLine = this.createSVGElement('line', {
                x1: x1,
                y1: y1,
                x2: x2,
                y2: y2,
                class: 'cypl-year-divider',
                stroke: '#666',
                'stroke-width': '2',
                opacity: '0.6',
                'pointer-events': 'none'
            });
            g.append(yearLine);
        }
        
        drawTodayMarker(g) {
            // Beräkna var händelseringarna börjar och slutar
            const eventTypes = this.settings.event_types || [];
            const numTypes = eventTypes.length;
            
            if (numTypes === 0) return; // Inga händelseringar att rita genom
            
            const baseRadius = this.outerRadius - 70;
            const baseRingHeight = 45;
            const maxRingsForBaseHeight = 5;
            const totalSpace = baseRingHeight * maxRingsForBaseHeight;
            const ringHeight = numTypes <= maxRingsForBaseHeight 
                ? baseRingHeight 
                : totalSpace / numTypes;
            
            // Yttre radie för händelseringarna (där första ringen börjar)
            const outerEventRadius = baseRadius;
            // Innersta radie för händelseringarna
            const innerRadius = baseRadius - numTypes * ringHeight;
            
            // Hämta dagens datum
            const today = new Date();
            
            // Kontrollera om dagens datum är inom det aktuella verksamhetsåret
            const fiscalStart = this.settings.fiscal_year_start || '01-01';
            const [startMonth, startDay] = fiscalStart.split('-').map(Number);
            const startYear = this.parseFiscalYear(this.fiscalYear);
            
            const fiscalStartDate = new Date(startYear, startMonth - 1, startDay);
            const fiscalEndDate = new Date(startYear + 1, startMonth - 1, startDay - 1);
            
            // Om dagens datum inte är inom verksamhetsåret, rita inte linjen
            if (today < fiscalStartDate || today > fiscalEndDate) {
                return;
            }
            
            // Beräkna dagens position i verksamhetsåret
            const totalDays = this.getTotalDaysInYear();
            const dayOfYear = this.getDayOfYear(today);
            const angle = (dayOfYear / totalDays) * 360 - 90;
            
            // Rita streckad linje vid dagens datum
            const angleRad = this.degToRad(angle);
            const x1 = Math.cos(angleRad) * outerEventRadius;
            const y1 = Math.sin(angleRad) * outerEventRadius;
            const x2 = Math.cos(angleRad) * innerRadius;
            const y2 = Math.sin(angleRad) * innerRadius;
            
            const todayLine = this.createSVGElement('line', {
                x1: x1,
                y1: y1,
                x2: x2,
                y2: y2,
                class: 'cypl-today-marker',
                stroke: '#d63638',
                'stroke-width': '2.5',
                'stroke-dasharray': '8,4',
                opacity: '0.8',
                'pointer-events': 'none'
            });
            g.append(todayLine);
        }
        
        drawWeekRing(g) {
            const weekRadius = this.outerRadius - 25; // Flytta upp närmare månadsringen
            const weeks = this.getWeeksInFiscalYear();
            const totalDays = this.getTotalDaysInYear();
            
            weeks.forEach(week => {
                const startDay = this.getDayOfYear(week.startDate);
                const endDay = this.getDayOfYear(week.endDate);
                
                const startAngle = (startDay / totalDays) * 360 - 90;
                const endAngle = (endDay / totalDays) * 360 - 90;
                
                // Rita veckobåge
                const arc = this.createArc(weekRadius - 20, weekRadius, startAngle, endAngle);
                const path = this.createSVGElement('path', {
                    d: arc,
                    class: 'cypl-week-arc',
                    fill: 'none',
                    stroke: '#eee',
                    'stroke-width': '1'
                });
                g.append(path);
                
                // Veckonummer (visa varannan vecka för att undvika trängsel)
                if (week.weekNumber % 2 === 0) {
                    const labelAngle = (startAngle + endAngle) / 2;
                    const labelRadius = weekRadius - 30;
                    const labelX = Math.cos(this.degToRad(labelAngle)) * labelRadius;
                    const labelY = Math.sin(this.degToRad(labelAngle)) * labelRadius;
                    
                    const text = this.createSVGElement('text', {
                        x: labelX,
                        y: labelY,
                        class: 'cypl-week-label',
                        'text-anchor': 'middle',
                        'dominant-baseline': 'middle'
                    });
                    text.text('v' + week.weekNumber);
                    g.append(text);
                }
            });
        }
        
        drawEventRings(g) {
            const eventTypes = this.settings.event_types || [];
            const numTypes = eventTypes.length;
            
            if (numTypes === 0) return;
            
            // Ge mer utrymme för veckonummer - ca en halv händelsering (15px) extra mellanrum
            const baseRadius = this.outerRadius - 70;
            
            // Beräkna ringhöjd proportionellt - 5 ringar = 45px per ring (totalt 225px)
            // Om fler än 5 ringar, behåll samma totalutrymme (225px)
            const baseRingHeight = 45;
            const maxRingsForBaseHeight = 5;
            const totalSpace = baseRingHeight * maxRingsForBaseHeight; // 225px
            const ringHeight = numTypes <= maxRingsForBaseHeight 
                ? baseRingHeight 
                : totalSpace / numTypes;
            
            eventTypes.forEach((type, index) => {
                const innerRadius = baseRadius - (index + 1) * ringHeight;
                const outerRadius = baseRadius - index * ringHeight;
                
                // Rita ljusgrå bakgrund för hela ringen
                this.drawRingBackground(g, innerRadius, outerRadius);
                
                // Rita ram runt denna händelsetypsring
                this.drawRingBorder(g, innerRadius, outerRadius, type.color);
                
                // Filtrera händelser för denna typ
                const typeEvents = this.events.filter(event => {
                    return parseInt(event.event_type) === index;
                });
                
                // Gruppera händelser per vecka och stapla dem radiellt
                this.drawStackedEvents(g, typeEvents, innerRadius, outerRadius);
            });
        }
        
        drawStackedEvents(g, events, innerRadius, outerRadius) {
            if (events.length === 0) return;
            
            // Gruppera händelser per vecka
            const weekGroups = this.groupEventsByWeek(events);
            
            // Rita staplade händelser för varje vecka
            Object.keys(weekGroups).forEach(weekKey => {
                const weekEvents = weekGroups[weekKey];
                this.drawEventsInWeek(g, weekEvents, innerRadius, outerRadius);
            });
        }
        
        groupEventsByWeek(events) {
            const weekGroups = {};
            
            events.forEach(event => {
                let startDate = new Date(event.start_date);
                let endDate = new Date(event.end_date);
                
                // Om händelsen bara är en dag, visa hela veckan istället
                const isOneDay = event.start_date === event.end_date;
                if (isOneDay) {
                    // Hitta veckans start (måndag) och slut (söndag)
                    const dayOfWeek = startDate.getDay();
                    const daysToMonday = (dayOfWeek === 0 ? 6 : dayOfWeek - 1); // Söndag = 0, måste bli 6
                    
                    startDate = new Date(startDate);
                    startDate.setDate(startDate.getDate() - daysToMonday);
                    
                    endDate = new Date(startDate);
                    endDate.setDate(endDate.getDate() + 6); // Hela veckan (måndag-söndag)
                }
                
                // Skapa veckonyckel baserat på veckans startdatum
                const weekKey = this.getWeekKey(startDate);
                
                if (!weekGroups[weekKey]) {
                    weekGroups[weekKey] = [];
                }
                
                weekGroups[weekKey].push({
                    ...event,
                    calculatedStartDate: startDate,
                    calculatedEndDate: endDate
                });
            });
            
            return weekGroups;
        }
        
        getWeekKey(date) {
            // Skapa en unik nyckel för veckan baserat på måndagen
            const monday = new Date(date);
            const dayOfWeek = monday.getDay();
            const daysToMonday = (dayOfWeek === 0 ? 6 : dayOfWeek - 1);
            monday.setDate(monday.getDate() - daysToMonday);
            
            return monday.toISOString().split('T')[0]; // YYYY-MM-DD format
        }
        
        drawEventsInWeek(g, events, innerRadius, outerRadius) {
            if (events.length === 0) return;
            
            // Sortera händelser efter startdatum för konsistent stapling
            events.sort((a, b) => new Date(a.start_date) - new Date(b.start_date));
            
            // Beräkna hur många staplar vi behöver
            const numStacks = events.length;
            
            // Beräkna stapelbredd (dela ringhöjden mellan alla händelser)
            const stackHeight = (outerRadius - innerRadius) / numStacks;
            
            // Rita varje händelse i sin egen stapel
            events.forEach((event, stackIndex) => {
                const stackInnerRadius = innerRadius + (stackIndex * stackHeight);
                const stackOuterRadius = innerRadius + ((stackIndex + 1) * stackHeight);
                
                this.drawEvent(g, event, stackInnerRadius, stackOuterRadius);
            });
        }
        
        drawRingBackground(g, innerRadius, outerRadius) {
            // Rita en fullständig cirkel med ljusgrå bakgrund
            const backgroundCircle = this.createSVGElement('circle', {
                cx: 0,
                cy: 0,
                r: outerRadius,
                fill: '#f5f5f5',
                class: 'cypl-ring-background'
            });
            g.append(backgroundCircle);
            
            // Rita en vit cirkel innanför för att skapa ring-effekten
            const innerCircle = this.createSVGElement('circle', {
                cx: 0,
                cy: 0,
                r: innerRadius,
                fill: '#ffffff',
                class: 'cypl-ring-background-inner'
            });
            g.append(innerCircle);
        }
        
        drawRingBorder(g, innerRadius, outerRadius, color) {
            // Rita yttre cirkel
            const outerCircle = this.createSVGElement('circle', {
                cx: 0,
                cy: 0,
                r: outerRadius,
                fill: 'none',
                stroke: color,
                'stroke-width': '1.5',
                opacity: '0.3',
                class: 'cypl-ring-border'
            });
            g.append(outerCircle);
            
            // Rita inre cirkel
            const innerCircle = this.createSVGElement('circle', {
                cx: 0,
                cy: 0,
                r: innerRadius,
                fill: 'none',
                stroke: color,
                'stroke-width': '1.5',
                opacity: '0.3',
                class: 'cypl-ring-border'
            });
            g.append(innerCircle);
        }
        
        drawEvent(g, event, innerRadius, outerRadius) {
            const totalDays = this.getTotalDaysInYear();
            
            // Använd beräknade datum om de finns (från grupperingen), annars beräkna dem här
            let startDate, endDate;
            if (event.calculatedStartDate && event.calculatedEndDate) {
                startDate = event.calculatedStartDate;
                endDate = event.calculatedEndDate;
            } else {
                startDate = new Date(event.start_date);
                endDate = new Date(event.end_date);
                
                // Om händelsen bara är en dag, visa hela veckan istället
                const isOneDay = event.start_date === event.end_date;
                if (isOneDay) {
                    // Hitta veckans start (måndag) och slut (söndag)
                    const dayOfWeek = startDate.getDay();
                    const daysToMonday = (dayOfWeek === 0 ? 6 : dayOfWeek - 1); // Söndag = 0, måste bli 6
                    
                    startDate = new Date(startDate);
                    startDate.setDate(startDate.getDate() - daysToMonday);
                    
                    endDate = new Date(startDate);
                    endDate.setDate(endDate.getDate() + 6); // Hela veckan (måndag-söndag)
                }
            }
            
            const startDay = this.getDayOfYear(startDate);
            const endDay = this.getDayOfYear(endDate);
            
            const startAngle = (startDay / totalDays) * 360 - 90;
            const endAngle = (endDay / totalDays) * 360 - 90;
            
            // Rita händelsebåge
            const arc = this.createArc(innerRadius, outerRadius, startAngle, endAngle);
            const pathId = 'event-path-' + event.id;
            
            const path = this.createSVGElement('path', {
                d: arc,
                class: 'cypl-event-arc',
                fill: event.event_type_color,
                'data-event-id': event.id,
                id: pathId,
                overflow: 'visible'
            });
            
            // Klickhändelse
            path.on('click', () => this.showEventDetails(event));
            
            g.append(path);
            
            // Lägg till text längs bågen
            this.drawEventText(g, event, innerRadius, outerRadius, startAngle, endAngle, pathId);
        }
        
        drawEventText(g, event, innerRadius, outerRadius, startAngle, endAngle) {
            const midRadius = (innerRadius + outerRadius) / 2;
            const arcLength = Math.abs(endAngle - startAngle);
            
            // Beräkna mittpunkt för att avgöra om texten är på vänster sidan
            const midAngle = (startAngle + endAngle) / 2;
            // Normalisera vinkeln till 0-360
            const normalizedAngle = ((midAngle + 90) % 360 + 360) % 360;
            
            // Om texten är mellan 90° och 270° (vänster sidan), vänd den
            const isLeftSide = normalizedAngle > 90 && normalizedAngle < 270;
            
            // Utöka path-längden för att få plats med all text
            let textStartAngle, textEndAngle;
            if (isLeftSide) {
                // Vänd path-riktningen för vänster sida
                textStartAngle = endAngle + 30;
                textEndAngle = startAngle - 30;
            } else {
                // Normal riktning för höger sida
                textStartAngle = startAngle - 30;
                textEndAngle = endAngle + 30;
            }
            
            const textPathId = 'text-path-' + event.id;
            const textArc = this.createTextArc(midRadius, textStartAngle, textEndAngle);
            
            // Skapa defs om det inte finns
            let defs = this.svg.find('defs');
            if (defs.length === 0) {
                defs = document.createElementNS('http://www.w3.org/2000/svg', 'defs');
                this.svg.prepend(defs);
            }
            
            // Skapa path i defs - längre än själva händelsen
            const textPath = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            textPath.setAttribute('id', textPathId);
            textPath.setAttribute('d', textArc);
            textPath.setAttribute('fill', 'none');
            $(defs).append(textPath);
            
            // Skapa text-elementet
            const text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
            text.setAttribute('class', 'cypl-event-text');
            // Använd custom text color om den finns, annars automatisk kontrastfärg
            const textColor = event.event_type_text_color || this.getContrastColor(event.event_type_color);
            text.setAttribute('fill', textColor);
            text.setAttribute('font-size', '9px');
            text.setAttribute('font-weight', '600');
            text.setAttribute('pointer-events', 'none');
            
            // Skapa textPath-elementet
            const textPathEl = document.createElementNS('http://www.w3.org/2000/svg', 'textPath');
            textPathEl.setAttributeNS('http://www.w3.org/1999/xlink', 'xlink:href', '#' + textPathId);
            textPathEl.setAttribute('startOffset', '50%');
            textPathEl.setAttribute('text-anchor', 'middle');
            textPathEl.textContent = event.title;
            
            text.appendChild(textPathEl);
            g.append(text);
        }
        
        createTextArc(radius, startAngle, endAngle) {
            // Skapa en enkel arc-path för text
            const startAngleRad = this.degToRad(startAngle);
            const endAngleRad = this.degToRad(endAngle);
            
            const x1 = Math.cos(startAngleRad) * radius;
            const y1 = Math.sin(startAngleRad) * radius;
            const x2 = Math.cos(endAngleRad) * radius;
            const y2 = Math.sin(endAngleRad) * radius;
            
            // Räkna ut arc-längd och sweep-riktning
            let arcLength = endAngle - startAngle;
            let sweepFlag = 1; // Medurs
            
            if (arcLength < 0) {
                // Om vi går baklänges, använd absolut längd och moturs
                arcLength = Math.abs(arcLength);
                sweepFlag = 0; // Moturs
            }
            
            const largeArc = arcLength > 180 ? 1 : 0;
            
            return `M ${x1} ${y1} A ${radius} ${radius} 0 ${largeArc} ${sweepFlag} ${x2} ${y2}`;
        }
        
        getContrastColor(hexColor) {
            // Konvertera hex till RGB
            const r = parseInt(hexColor.substr(1, 2), 16);
            const g = parseInt(hexColor.substr(3, 2), 16);
            const b = parseInt(hexColor.substr(5, 2), 16);
            
            // Beräkna ljusstyrka (YIQ-formeln)
            const yiq = ((r * 299) + (g * 587) + (b * 114)) / 1000;
            
            // Returnera vit eller svart beroende på bakgrundsfärg
            return (yiq >= 128) ? '#000000' : '#FFFFFF';
        }
        
        drawCenterYear(g) {
            // Förkorta verksamhetsår om det är brutet
            let displayYear = String(this.fiscalYear);
            if (displayYear.includes('/')) {
                // Brutet verksamhetsår: "2024/2025" -> "24/25"
                const years = displayYear.split('/');
                displayYear = years[0].slice(-2) + '/' + years[1].slice(-2);
            }
            
            const text = this.createSVGElement('text', {
                x: 0,
                y: 0,
                class: 'cypl-center-year',
                'text-anchor': 'middle',
                'dominant-baseline': 'middle'
            });
            text.text(displayYear);
            g.append(text);
        }
        
        renderLegend() {
            const legend = this.container.find('.cyp-calendar-legend');
            legend.empty();
            
            const eventTypes = this.settings.event_types || [];
            
            eventTypes.forEach(type => {
                const item = $('<div class="cypl-legend-item"></div>');
                const color = $('<div class="cypl-legend-color"></div>').css('background-color', type.color);
                const label = $('<span class="cypl-legend-label"></span>').text(type.name);
                
                item.append(color, label);
                legend.append(item);
            });
        }
        
        showEventDetails(event) {
            const detailsPanel = this.container.find('.cyp-event-details');
            const content = detailsPanel.find('.cyp-event-content');
            
            // Get translated strings
            const i18n = cyplData.i18n || {};
            
            const isOneDay = event.start_date === event.end_date;
            const dateInfo = isOneDay 
                ? `<div class="cypl-event-meta-item">
                       <span class="cypl-event-meta-label">${i18n.date || 'Date'}:</span>
                       <span>${this.formatDate(event.start_date)}</span>
                   </div>
                   <div class="cypl-event-meta-item cypl-info-note">
                       <span class="cypl-event-meta-label">${i18n.display || 'Display'}:</span>
                       <span>${i18n.wholeWeek || 'Whole week (for visibility)'}</span>
                   </div>`
                : `<div class="cypl-event-meta-item">
                       <span class="cypl-event-meta-label">${i18n.startDate || 'Start Date'}:</span>
                       <span>${this.formatDate(event.start_date)}</span>
                   </div>
                   <div class="cypl-event-meta-item">
                       <span class="cypl-event-meta-label">${i18n.endDate || 'End Date'}:</span>
                       <span>${this.formatDate(event.end_date)}</span>
                   </div>`;
            
            // Använd custom text color om den finns, annars automatisk kontrastfärg
            const badgeTextColor = event.event_type_text_color || this.getContrastColor(event.event_type_color);
            
            const html = `
                <h3>${event.title}</h3>
                <div class="cypl-event-meta">
                    <div class="cypl-event-meta-item">
                        <span class="cypl-event-meta-label">${i18n.type || 'Type'}:</span>
                        <span class="cypl-event-type-badge" style="background-color: ${event.event_type_color}; color: ${badgeTextColor};">
                            ${event.event_type_name}
                        </span>
                    </div>
                    ${dateInfo}
                    <div class="cypl-event-meta-item">
                        <span class="cypl-event-meta-label">${i18n.fiscalYear || 'Fiscal Year'}:</span>
                        <span>${event.fiscal_year}</span>
                    </div>
                </div>
                ${event.description ? `<div class="cypl-event-description"><strong>${i18n.description || 'Description'}:</strong><br>${event.description}</div>` : ''}
            `;
            
            content.html(html);
            detailsPanel.addClass('active');
            
            // Lägg till overlay
            if (!$('.cyp-overlay').length) {
                $('body').append('<div class="cypl-overlay"></div>');
            }
            $('.cyp-overlay').addClass('active');
        }
        
        setupEventHandlers() {
            // Stäng detaljer
            this.container.find('.cyp-close-details').on('click', () => {
                this.container.find('.cyp-event-details').removeClass('active');
                $('.cyp-overlay').removeClass('active');
            });
            
            $(document).on('click', '.cyp-overlay', () => {
                this.container.find('.cyp-event-details').removeClass('active');
                $('.cyp-overlay').removeClass('active');
            });
            
            // Årsnavigering
            this.container.find('.cyp-prev-year').on('click', () => {
                this.changeFiscalYear(-1);
            });
            
            this.container.find('.cyp-next-year').on('click', () => {
                this.changeFiscalYear(1);
            });
        }
        
        changeFiscalYear(direction) {
            const currentYear = this.parseFiscalYear(this.fiscalYear);
            const newYear = currentYear + direction;
            
            this.fiscalYear = this.formatFiscalYear(newYear);
            
            // Förkorta för visning
            let displayYear = this.fiscalYear;
            if (displayYear.includes('/')) {
                const years = displayYear.split('/');
                displayYear = years[0].slice(-2) + '/' + years[1].slice(-2);
            }
            
            this.container.find('.cyp-year-title').text(displayYear);
            this.loadEvents();
        }
        
        parseFiscalYear(fiscalYear) {
            // Hanterar både "2024" och "2024/2025"
            if (!fiscalYear) {
                console.error('fiscalYear is undefined or null');
                return new Date().getFullYear();
            }
            const yearStr = String(fiscalYear).split('/')[0];
            return parseInt(yearStr);
        }
        
        formatFiscalYear(year) {
            const fiscalStart = this.settings.fiscal_year_start || '01-01';
            
            if (fiscalStart === '01-01') {
                return year.toString();
            } else {
                return year + '/' + (year + 1);
            }
        }
        
        // Hjälpfunktioner
        getMonthsInFiscalYear() {
            const fiscalStart = this.settings.fiscal_year_start || '01-01';
            const [startMonth] = fiscalStart.split('-').map(Number);
            
            const startYear = this.parseFiscalYear(this.fiscalYear);
            const months = [];
            
            for (let i = 0; i < 12; i++) {
                const month = ((startMonth - 1 + i) % 12) + 1;
                const year = startYear + Math.floor((startMonth - 1 + i) / 12);
                months.push({ month, year });
            }
            
            return months;
        }
        
        getWeeksInFiscalYear() {
            const fiscalStart = this.settings.fiscal_year_start || '01-01';
            const [startMonth, startDay] = fiscalStart.split('-').map(Number);
            const startYear = this.parseFiscalYear(this.fiscalYear);
            
            const fiscalStartDate = new Date(startYear, startMonth - 1, startDay);
            const fiscalEndDate = new Date(startYear + 1, startMonth - 1, startDay - 1);
            
            const weeks = [];
            let currentDate = new Date(fiscalStartDate);
            
            // Hitta första måndagen
            while (currentDate.getDay() !== 1) {
                currentDate.setDate(currentDate.getDate() - 1);
            }
            
            while (currentDate <= fiscalEndDate) {
                const weekStart = new Date(currentDate);
                const weekEnd = new Date(currentDate);
                weekEnd.setDate(weekEnd.getDate() + 6);
                
                weeks.push({
                    weekNumber: this.getWeekNumber(weekStart),
                    startDate: weekStart,
                    endDate: weekEnd
                });
                
                currentDate.setDate(currentDate.getDate() + 7);
            }
            
            return weeks;
        }
        
        getWeekNumber(date) {
            const d = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()));
            const dayNum = d.getUTCDay() || 7;
            d.setUTCDate(d.getUTCDate() + 4 - dayNum);
            const yearStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1));
            return Math.ceil((((d - yearStart) / 86400000) + 1) / 7);
        }
        
        getDaysInMonth(year, month) {
            return new Date(year, month, 0).getDate();
        }
        
        getTotalDaysInYear() {
            const fiscalStart = this.settings.fiscal_year_start || '01-01';
            const [startMonth, startDay] = fiscalStart.split('-').map(Number);
            const startYear = this.parseFiscalYear(this.fiscalYear);
            
            const start = new Date(startYear, startMonth - 1, startDay);
            const end = new Date(startYear + 1, startMonth - 1, startDay);
            
            return Math.floor((end - start) / (1000 * 60 * 60 * 24));
        }
        
        getDayOfYear(date) {
            const fiscalStart = this.settings.fiscal_year_start || '01-01';
            const [startMonth, startDay] = fiscalStart.split('-').map(Number);
            const startYear = this.parseFiscalYear(this.fiscalYear);
            
            const start = new Date(startYear, startMonth - 1, startDay);
            const target = new Date(date);
            
            const diff = target - start;
            return Math.floor(diff / (1000 * 60 * 60 * 24));
        }
        
        getMonthName(month) {
            // Use localized month names from PHP if available, otherwise fallback to English
            const months = cyplData.monthNames || ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            return months[month - 1];
        }
        
        formatDate(dateString) {
            // Parse the date (expects YYYY-MM-DD format)
            const parts = dateString.split('-');
            if (parts.length !== 3) return dateString;
            
            const year = parts[0];
            const month = parseInt(parts[1], 10);
            const day = parseInt(parts[2], 10);
            
            // Get WordPress date format
            const format = cyplData.dateFormat || 'F j, Y';

            // Month replacements - use translated names from PHP
            const monthNames = cyplData.monthNamesFull || ['January', 'February', 'March', 'April', 'May', 'June',
                               'July', 'August', 'September', 'October', 'November', 'December'];
            const monthNamesShort = cyplData.monthNames || ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                                                           'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

            // Prepare token map (PHP date format tokens we support)
            const tokenValues = {
                'F': monthNames[month - 1],              // Full month name
                'M': monthNamesShort[month - 1],         // Short month name
                'm': String(month).padStart(2, '0'),     // Month with leading zero
                'n': String(month),                      // Month without leading zero
                'd': String(day).padStart(2, '0'),       // Day with leading zero
                'j': String(day),                        // Day without leading zero
                'Y': year,                                // 4-digit year
                'y': year.slice(-2)                      // 2-digit year
            };

            // Build formatted string by scanning the format and replacing tokens only
            // Supports escaping with backslash like PHP's date() (e.g., \'F')
            let result = '';
            let escaping = false;
            for (let i = 0; i < format.length; i++) {
                const ch = format[i];
                if (escaping) {
                    result += ch;
                    escaping = false;
                    continue;
                }
                if (ch === '\\') { // escape next character
                    escaping = true;
                    continue;
                }
                if (Object.prototype.hasOwnProperty.call(tokenValues, ch)) {
                    result += tokenValues[ch];
                } else {
                    result += ch;
                }
            }

            return result;
        }
        
        degToRad(degrees) {
            return degrees * (Math.PI / 180);
        }
        
        createArc(innerRadius, outerRadius, startAngle, endAngle) {
            const startAngleRad = this.degToRad(startAngle);
            const endAngleRad = this.degToRad(endAngle);
            
            const x1 = Math.cos(startAngleRad) * outerRadius;
            const y1 = Math.sin(startAngleRad) * outerRadius;
            const x2 = Math.cos(endAngleRad) * outerRadius;
            const y2 = Math.sin(endAngleRad) * outerRadius;
            const x3 = Math.cos(endAngleRad) * innerRadius;
            const y3 = Math.sin(endAngleRad) * innerRadius;
            const x4 = Math.cos(startAngleRad) * innerRadius;
            const y4 = Math.sin(startAngleRad) * innerRadius;
            
            const largeArc = endAngle - startAngle > 180 ? 1 : 0;
            
            return `
                M ${x1} ${y1}
                A ${outerRadius} ${outerRadius} 0 ${largeArc} 1 ${x2} ${y2}
                L ${x3} ${y3}
                A ${innerRadius} ${innerRadius} 0 ${largeArc} 0 ${x4} ${y4}
                Z
            `;
        }
        
        createSVGElement(tag, attrs) {
            const elem = $(document.createElementNS('http://www.w3.org/2000/svg', tag));
            if (attrs) {
                Object.keys(attrs).forEach(key => {
                    elem.attr(key, attrs[key]);
                });
            }
            return elem;
        }
    }
    
    // Initiera alla kalendrar på sidan
    $(document).ready(function() {
        $('.cyp-calendar-container').each(function() {
            new CircularCalendar(this);
        });
    });
    
})(jQuery);

