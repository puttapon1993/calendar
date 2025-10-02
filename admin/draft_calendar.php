<?php
// File: draft_calendar.php
// Location: /admin/
require_once 'session_check.php'; // Ensures only logged-in users can access
require_once '../config.php';

// Fetch settings to pass to JavaScript
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
    $settings_raw = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (PDOException $e) {
    $settings_raw = [];
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ปฏิทินฉบับร่าง (สำหรับเจ้าหน้าที่)</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo filemtime('../assets/css/style.css'); ?>">
    <style>
        /* Dynamic Styles from Admin Settings */
        body {
            background-color: <?php echo htmlspecialchars($settings_raw['site_bg_color'] ?? '#ecf0f1'); ?>;
        }
        .calendar-day.has-event { background-color: <?php echo htmlspecialchars($settings_raw['event_date_bg_color'] ?? '#d9edf7'); ?>; }
        .calendar-day.is-holiday { background-color: <?php echo htmlspecialchars($settings_raw['holiday_bg_color'] ?? '#f2dede'); ?>; }
        .calendar-day.is-saturday { background-color: <?php echo htmlspecialchars($settings_raw['saturday_bg_color'] ?? '#f0f8ff'); ?>; }
        .calendar-day.is-sunday { background-color: <?php echo htmlspecialchars($settings_raw['sunday_bg_color'] ?? '#fff0f0'); ?>; }
        .calendar-day:not(.has-event):not(.is-holiday):not(.is-saturday):not(.is-sunday) {
             background-color: <?php echo htmlspecialchars($settings_raw['no_event_date_bg_color'] ?? '#fafafa'); ?>;
        }
        .calendar-day.is-today { 
            box-shadow: inset 0 0 0 2px #3498db; 
        }

        /* Styles for this page only */
        .draft-warning-banner {
            background-color: #e74c3c;
            color: #fff;
            padding: 0.5rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .draft-warning-banner p {
            margin: 0.5rem;
        }
        .nav-button .fa-eye-slash {
            font-size: 0.9em;
            color: #e74c3c;
            opacity: 0.7;
            margin-left: 5px;
        }
        .unpublished-text {
            font-size: 0.8em;
            color: #555;
            font-style: italic;
            font-weight: normal;
        }
        .search-results-list .fa-star {
            color: #f39c12;
            margin-right: 5px;
        }
        /* Button styles to match main site buttons */
        .button {
             text-decoration: none;
             padding: 0.6rem 1.2rem;
             border-radius: 4px;
             border: none;
             cursor: pointer;
             font-size: 0.9rem;
             transition: opacity 0.2s ease;
             display: inline-flex;
             align-items: center;
             gap: 8px;
        }
        .button-secondary {
            background-color: #f0f0f0;
            color: #000;
            border: 1px solid #ccc;
        }
        .button-secondary:hover {
            opacity: 0.8;
        }
    </style>
</head>
<body>
    <div class="site-container">
        <div class="draft-warning-banner">
            <p>ข้อมูลในหน้านี้เป็นมุมมองฉบับร่างสำหรับเจ้าหน้าที่เท่านั้น เพื่อให้เห็นข้อมูลกิจกรรมทั้งหมดที่มีการบันทึกเข้ามาในฐานข้อมูล<br>
            ซึ่งอาจมีข้อมูลบางส่วนที่ยังไม่ถูกเผยแพร่สู่สาธารณะในหน้าเว็บหลัก</p>
        </div>

        <div id="navigation-container">
            <!-- Navigation will be loaded here by JavaScript -->
        </div>

        <main class="main-content">
            <div class="controls-toolbar">
                <div class="view-switcher">
                    <button id="calendar-view-btn" class="active">📅 มุมมองปฏิทิน</button>
                    <button id="table-view-btn">📄 มุมมองตาราง</button>
                </div>
                 <div class="search-container">
                    <input type="search" id="search-box" placeholder="ค้นหากิจกรรมและวันสำคัญ...">
                </div>
                <div class="actions-group">
                    <a href="dashboard.php" class="button button-secondary"><i class="fas fa-arrow-left"></i> กลับสู่หน้าจัดการ</a>
                </div>
            </div>

            <div id="search-results-container" class="hidden"></div>
            <div id="all-events-container" class="hidden"></div>
            
            <div id="calendar-container">
                <div class="calendar-header">
                    <button id="prev-month-btn">&lt;</button>
                    <h2 id="month-year-header"></h2>
                    <button id="next-month-btn">&gt;</button>
                </div>
                <div id="calendar-grid" class="calendar-grid"></div>
            </div>

            <div id="table-view-container" class="hidden">
                 <h2 id="table-month-year-header"></h2>
                 <table id="event-table">
                     <thead>
                         <tr>
                             <th>วันที่</th>
                             <th>ชื่อกิจกรรม/วันสำคัญ</th>
                             <th>หน่วยงาน</th>
                         </tr>
                     </thead>
                     <tbody></tbody>
                 </table>
            </div>

            <div id="loading-spinner" class="hidden">
                <div class="spinner"></div>
            </div>
        </main>
    </div>

    <script>
        const siteSettings = <?php echo json_encode($settings_raw); ?>;
        
        document.addEventListener('DOMContentLoaded', function() {
            const monthYearHeader = document.getElementById('month-year-header');
            const calendarGrid = document.getElementById('calendar-grid');
            const prevMonthBtn = document.getElementById('prev-month-btn');
            const nextMonthBtn = document.getElementById('next-month-btn');
            const loadingSpinner = document.getElementById('loading-spinner');
            const navigationContainer = document.getElementById('navigation-container');
            const calendarViewBtn = document.getElementById('calendar-view-btn');
            const tableViewBtn = document.getElementById('table-view-btn');
            const calendarContainer = document.getElementById('calendar-container');
            const tableViewContainer = document.getElementById('table-view-container');
            const allEventsContainer = document.getElementById('all-events-container');
            const tableBody = document.querySelector('#event-table tbody');
            const tableMonthYearHeader = document.getElementById('table-month-year-header');
            const searchBox = document.getElementById('search-box');
            const searchResultsContainer = document.getElementById('search-results-container');

            let currentMonth = new Date().getMonth();
            let currentYear = new Date().getFullYear();
            let availableMonths = [];
            let publishSettings = {};
            let isAllViewActive = false;
            const thaiMonths = ["มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"];

            function showLoading() { loadingSpinner.classList.remove('hidden'); }
            function hideLoading() { loadingSpinner.classList.add('hidden'); }

            function formatResponsibleUnit(name, unit) {
                if (!name) return ''; if (!unit) return name;
                const format = siteSettings.responsible_unit_format || 'parenthesis';
                switch (format) {
                    case 'parenthesis': return `${name} (${unit})`;
                    case 'dash': return `${name} - ${unit}`;
                    case 'slash': return `${name} / ${unit}`;
                    case 'colon': return `${name} : ${unit}`;
                    case 'hide': return name;
                    default: return `${name} (${unit})`;
                }
            }
            
            async function fetchEvents(year, month) {
                showLoading();
                try {
                    // NOTE: This now points to the main get_events.php, which correctly filters by hidden status.
                    const response = await fetch(`../api/get_events.php?year=${year}&month=${month + 1}`);
                    if (!response.ok) throw new Error('Network response was not ok');
                    const data = await response.json();
                    renderCalendar(year, month, data);
                    renderTable(year, month, data);
                } catch (error) {
                    console.error('Fetch error:', error);
                    calendarGrid.innerHTML = '<p class="error-message">ไม่สามารถโหลดข้อมูลกิจกรรมได้</p>';
                } finally {
                    hideLoading();
                }
            }

            async function fetchNavigation() {
                try {
                    const response = await fetch(`../api/get_draft_navigation.php`);
                    const data = await response.json();
                    if (data.navigation) {
                        availableMonths = data.navigation;
                        publishSettings = data.settings || {};
                        renderNavigation(data.navigation);
                    }
                } catch (error) {
                    console.error('Fetch navigation error:', error);
                }
            }
            
            function renderAllViews() {
                const isCalendarView = calendarViewBtn.classList.contains('active');
                isCalendarView ? renderAllCalendars() : renderAllTables();
            }

            async function renderAllTables() {
                showLoading();
                [calendarContainer, tableViewContainer, searchResultsContainer].forEach(el => el.classList.add('hidden'));
                allEventsContainer.innerHTML = '';
                allEventsContainer.classList.remove('hidden');
                if (!availableMonths || availableMonths.length === 0) {
                    allEventsContainer.innerHTML = '<p style="text-align:center;">ไม่พบกิจกรรมในฐานข้อมูล</p>';
                    hideLoading(); return;
                }
                for (const monthInfo of availableMonths) {
                    try {
                        const response = await fetch(`../api/get_events.php?year=${monthInfo.year}&month=${monthInfo.month}`);
                        if (!response.ok) continue;
                        const data = await response.json();
                        
                        let headerText = `<h2>${monthInfo.label}`;
                        if (!monthInfo.is_published) {
                            headerText += ' <span class="unpublished-text">(ยังไม่เผยแพร่สาธารณะ)</span>';
                        }
                        headerText += '</h2>';

                        const monthGroup = document.createElement('div');
                        monthGroup.className = 'all-events-month-group';
                        monthGroup.innerHTML = headerText;

                        const table = document.createElement('table');
                        table.className = 'all-events-table';
                        table.innerHTML = `<thead><tr><th style="width:25%">วันที่</th><th>ชื่อกิจกรรม</th><th style="width:20%">หน่วยงาน</th></tr></thead><tbody></tbody>`;
                        const tbody = table.querySelector('tbody');

                        const eventList = data.event_list_for_table || [];
                        if (eventList.length > 0) {
                             eventList.forEach(event => {
                                const row = tbody.insertRow();
                                row.insertCell().textContent = event.formatted_dates;
                                row.insertCell().textContent = event.event_name;
                                row.insertCell().textContent = event.responsible_unit || '';
                            });
                            monthGroup.appendChild(table);
                            allEventsContainer.appendChild(monthGroup);
                        } else {
                            // Optionally, still show the month header but with a message
                            monthGroup.innerHTML += '<p>ไม่มีกิจกรรมในเดือนนี้</p>';
                            allEventsContainer.appendChild(monthGroup);
                        }

                    } catch (error) { console.error(`Failed to load table for ${monthInfo.label}`, error); }
                }
                hideLoading();
            }

            async function renderAllCalendars() {
                showLoading();
                [calendarContainer, tableViewContainer, searchResultsContainer].forEach(el => el.classList.add('hidden'));
                allEventsContainer.classList.remove('hidden');
                allEventsContainer.innerHTML = '';
                for (const monthInfo of availableMonths) {
                    const monthGroup = document.createElement('div');
                    monthGroup.className = 'all-events-month-group';
                    let headerText = `<h2>${monthInfo.label}`;
                     if (!monthInfo.is_published) {
                        headerText += ' <span class="unpublished-text">(ยังไม่เผยแพร่สาธารณะ)</span>';
                    }
                    headerText += '</h2>';
                    monthGroup.innerHTML = headerText;

                    const grid = document.createElement('div');
                    grid.className = 'calendar-grid';
                    monthGroup.appendChild(grid);
                    allEventsContainer.appendChild(monthGroup);
                    try {
                        const response = await fetch(`../api/get_events.php?year=${monthInfo.year}&month=${monthInfo.month}`);
                        if (!response.ok) continue;
                        const data = await response.json();
                        renderSingleCalendar(monthInfo.year, monthInfo.month - 1, data, grid);
                    } catch (error) { console.error(`Failed to load calendar for ${monthInfo.label}`, error); }
                }
                hideLoading();
            }

            function renderNavigation(navData) {
                navigationContainer.innerHTML = '';
                const allButton = document.createElement('button');
                allButton.className = 'nav-button'; allButton.id = 'all-months-btn'; allButton.textContent = 'ทั้งหมด';
                navigationContainer.appendChild(allButton);
                navData.forEach(item => {
                    const button = document.createElement('button');
                    button.className = 'nav-button';
                    button.dataset.year = item.year;
                    button.dataset.month = item.month;
                    let buttonHTML = item.label;
                    if (!item.is_published) {
                        button.classList.add('unpublished');
                        buttonHTML += ' <i class="fas fa-eye-slash"></i>';
                    }
                    button.innerHTML = buttonHTML;
                    navigationContainer.appendChild(button);
                });
            }

            function renderCalendar(year, month, data) {
                let headerText = `${thaiMonths[month]} ${year + 543}`;
                const monthDate = new Date(year, month, 15); // Use mid-month to avoid timezone issues
                let isPublished = true;

                if (publishSettings.publish_start_date) {
                    const startDate = new Date(publishSettings.publish_start_date + '-01');
                    if (monthDate < startDate) isPublished = false;
                }
                if (publishSettings.publish_end_date) {
                    const endDate = new Date(publishSettings.publish_end_date + '-01');
                    endDate.setMonth(endDate.getMonth() + 1, 0); // End of the month
                    if (monthDate > endDate) isPublished = false;
                }

                if(!isPublished) {
                    headerText += ' <span class="unpublished-text">(ยังไม่เผยแพร่สาธารณะ)</span>';
                }
                monthYearHeader.innerHTML = headerText;
                renderSingleCalendar(year, month, data, calendarGrid);
            }

            function renderSingleCalendar(year, month, data, targetGrid) {
                targetGrid.innerHTML = '';
                const eventsForCalendar = data.events_for_calendar || {};
                const weekStartDay = siteSettings.week_start_day === 'monday' ? 1 : 0;
                const dayHeaders = weekStartDay === 1 ? ['จ', 'อ', 'พ', 'พฤ', 'ศ', 'ส', 'อา'] : ['อา', 'จ', 'อ', 'พ', 'พฤ', 'ศ', 'ส'];
                dayHeaders.forEach(day => {
                    const dayHeader = document.createElement('div');
                    dayHeader.classList.add('calendar-day', 'day-header');
                    dayHeader.textContent = day;
                    targetGrid.appendChild(dayHeader);
                });
                const firstDayOfMonth = new Date(year, month, 1).getDay(), daysInMonth = new Date(year, month + 1, 0).getDate();
                const startOffset = (firstDayOfMonth - weekStartDay + 7) % 7;
                for (let i = 0; i < startOffset; i++) { targetGrid.appendChild(document.createElement('div')).classList.add('calendar-day', 'empty'); }
                for (let day = 1; day <= daysInMonth; day++) {
                    const dayCell = document.createElement('div');
                    dayCell.classList.add('calendar-day');
                    const dayNumber = document.createElement('span');
                    dayNumber.classList.add('day-number'); dayNumber.textContent = day; dayCell.appendChild(dayNumber);
                    const dayOfWeek = new Date(year, month, day).getDay();
                    if (dayOfWeek === 6) dayCell.classList.add('is-saturday');
                    if (dayOfWeek === 0) dayCell.classList.add('is-sunday');
                    const today = new Date();
                    if (today.getFullYear() === year && today.getMonth() === month && day === today.getDate()) { dayCell.classList.add('is-today'); }
                    const dayKey = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                    if (data.holidays && data.holidays[dayKey]) {
                        dayCell.classList.add('is-holiday');
                        const holidayDiv = document.createElement('div');
                        holidayDiv.classList.add('event-item', 'holiday-item'); holidayDiv.textContent = data.holidays[dayKey]; dayCell.appendChild(holidayDiv);
                    }
                    if (eventsForCalendar[dayKey]) {
                        dayCell.classList.add('has-event');
                        eventsForCalendar[dayKey].forEach(event => {
                            const eventDiv = document.createElement('div');
                            eventDiv.classList.add('event-item');
                            let eventText = formatResponsibleUnit(event.event_name, event.responsible_unit);
                            if (siteSettings.truncate_event_names === '1' && eventText.length > 25) { eventText = eventText.substring(0, 22) + '...'; }
                            eventDiv.textContent = eventText;
                            dayCell.appendChild(eventDiv);
                        });
                    }
                    targetGrid.appendChild(dayCell);
                }
            }
            
            function renderTable(year, month, data) {
                let headerText = `กิจกรรมเดือน ${thaiMonths[month]} ${year + 543}`;
                const monthDate = new Date(year, month, 15);
                let isPublished = true;
                if (publishSettings.publish_start_date) {
                    const startDate = new Date(publishSettings.publish_start_date + '-01');
                    if (monthDate < startDate) isPublished = false;
                }
                if (publishSettings.publish_end_date) {
                    const endDate = new Date(publishSettings.publish_end_date + '-01');
                    endDate.setMonth(endDate.getMonth() + 1, 0); // End of the month
                    if (monthDate > endDate) isPublished = false;
                }

                if(!isPublished) {
                    headerText += ' <span class="unpublished-text">(ยังไม่เผยแพร่สาธารณะ)</span>';
                }
                tableMonthYearHeader.innerHTML = headerText;
                tableBody.innerHTML = '';
                const eventList = data.event_list_for_table || [];
                if (eventList.length === 0) { tableBody.innerHTML = '<tr><td colspan="3">ไม่มีกิจกรรมในเดือนนี้</td></tr>'; return; }
                eventList.forEach(event => {
                    const row = tableBody.insertRow();
                    row.insertCell().textContent = event.formatted_dates;
                    row.insertCell().textContent = event.event_name;
                    row.insertCell().textContent = event.responsible_unit || '';
                });
            }

            async function performSearch(query) {
                 if (query.length < 2) {
                    searchResultsContainer.classList.add('hidden');
                    const isAllView = document.querySelector('#all-months-btn')?.classList.contains('active');
                    if (!isAllView) {
                        const isTableView = tableViewBtn.classList.contains('active');
                        calendarContainer.classList.toggle('hidden', isTableView);
                        tableViewContainer.classList.toggle('hidden', !isTableView);
                    } else { allEventsContainer.classList.remove('hidden'); }
                    return;
                }
                showLoading();
                try {
                    const response = await fetch(`../api/draft_search.php?q=${encodeURIComponent(query)}`);
                    const results = await response.json();
                    renderSearchResults(results);
                } catch (error) {
                    console.error('Search error:', error);
                    searchResultsContainer.innerHTML = '<p class="error-message">การค้นหาล้มเหลว</p>';
                } finally { hideLoading(); }
            }

            function renderSearchResults(results) {
                [calendarContainer, tableViewContainer, allEventsContainer].forEach(el => el.classList.add('hidden'));
                searchResultsContainer.classList.remove('hidden');
                
                if (!results || results.length === 0) {
                    searchResultsContainer.innerHTML = `<h3>ผลการค้นหา (0 รายการ)</h3><p>ไม่พบรายการที่ตรงกัน</p>`;
                    return;
                }
                
                let html = `<h3>ผลการค้นหา (${results.length} รายการ)</h3><ul class="search-results-list">`;
                results.forEach(item => {
                    const isHoliday = item.type === 'holiday';
                    html += `<li class="${isHoliday ? 'holiday-result' : ''}">
                        <h4>${isHoliday ? '<i class="fas fa-star"></i> ' : ''}${formatResponsibleUnit(item.name, item.responsible_unit)}</h4>
                        <p><strong>วันที่:</strong> ${item.dates ? item.dates : 'N/A'}</p>
                        ${item.notes ? `<p class="notes"><strong>หมายเหตุ:</strong> ${item.notes}</p>` : ''}
                    </li>`;
                });
                html += '</ul>';
                searchResultsContainer.innerHTML = html;
            }
            
            navigationContainer.addEventListener('click', (e) => {
                const target = e.target.closest('.nav-button');
                if (!target) return;
                searchBox.value = ''; searchResultsContainer.classList.add('hidden');
                document.querySelectorAll('.nav-button.active').forEach(btn => btn.classList.remove('active'));
                target.classList.add('active');
                isAllViewActive = (target.id === 'all-months-btn');
                if (isAllViewActive) {
                    renderAllViews();
                } else {
                    allEventsContainer.classList.add('hidden');
                    const isTableView = tableViewBtn.classList.contains('active');
                    calendarContainer.classList.toggle('hidden', isTableView);
                    tableViewContainer.classList.toggle('hidden', !isTableView);
                    currentYear = parseInt(target.dataset.year);
                    currentMonth = parseInt(target.dataset.month) - 1;
                    fetchEvents(currentYear, currentMonth);
                }
            });
            prevMonthBtn.addEventListener('click', () => { currentMonth--; if (currentMonth < 0) { currentMonth = 11; currentYear--; } fetchEvents(currentYear, currentMonth); });
            nextMonthBtn.addEventListener('click', () => { currentMonth++; if (currentMonth > 11) { currentMonth = 0; currentYear++; } fetchEvents(currentYear, currentMonth); });
            calendarViewBtn.addEventListener('click', () => { tableViewContainer.classList.add('hidden'); calendarContainer.classList.remove('hidden'); tableViewBtn.classList.remove('active'); calendarViewBtn.classList.add('active'); if (isAllViewActive) renderAllViews(); });
            tableViewBtn.addEventListener('click', () => { calendarContainer.classList.add('hidden'); tableViewContainer.classList.remove('hidden'); calendarViewBtn.classList.remove('active'); tableViewBtn.classList.add('active'); if (isAllViewActive) renderAllViews(); });
            let searchTimeout;
            searchBox.addEventListener('input', () => { clearTimeout(searchTimeout); searchTimeout = setTimeout(() => { performSearch(searchBox.value); }, 300); });

            async function initialize() {
                await fetchNavigation();
                const today = new Date();
                currentYear = today.getFullYear();
                currentMonth = today.getMonth();
                const initialButton = navigationContainer.querySelector(`.nav-button[data-year="${currentYear}"][data-month="${currentMonth + 1}"]`);
                if (initialButton) { initialButton.click(); } 
                else if (availableMonths.length > 0) { navigationContainer.querySelector('.nav-button:not(#all-months-btn)').click(); } 
                else { document.getElementById('all-months-btn').click(); }
            }
            initialize();
        });
    </script>
</body>
</html>

