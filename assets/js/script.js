// File: script.js
// Location: /assets/js/
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
    const reportModal = document.getElementById('report-modal');
    const printModal = document.getElementById('print-modal');
    const tickerContainer = document.getElementById('event-ticker-container');

    let currentMonth = new Date().getMonth();
    let currentYear = new Date().getFullYear();
    let publishStartDate = null;
    let publishEndDate = null;
    let availableMonths = [];
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

    function updateNavButtonsState(year, month) {
        const currentDate = new Date(year, month, 1);
        prevMonthBtn.disabled = publishStartDate && currentDate <= publishStartDate;
        nextMonthBtn.disabled = publishEndDate && currentDate >= publishEndDate;
    }

    async function fetchEvents(year, month) {
        showLoading();
        updateNavButtonsState(year, month);
        try {
            const response = await fetch(`api/get_events.php?year=${year}&month=${month + 1}`);
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

    async function fetchTickerData() {
        if (!tickerContainer) return;
        try {
            const response = await fetch('api/get_ticker_data.php');
            const data = await response.json();
            if (data && data.show && data.text) {
                tickerContainer.style.backgroundColor = data.bgColor;
                tickerContainer.innerHTML = `<p class="ticker-text" style="animation-duration: ${data.speed}; color: ${data.color};">${data.text}</p>`;
                tickerContainer.classList.remove('hidden');
            } else {
                 tickerContainer.classList.add('hidden');
            }
        } catch (error) { console.error('Fetch ticker error:', error); }
    }

    async function fetchNavigation() {
        try {
            const response = await fetch(`api/get_navigation.php`);
            const data = await response.json();
            if (data.settings) {
                if (data.settings.publish_start) {
                    const [y, m] = data.settings.publish_start.split('-');
                    publishStartDate = new Date(y, m - 1, 1);
                }
                if (data.settings.publish_end) {
                    const [y, m] = data.settings.publish_end.split('-');
                    publishEndDate = new Date(y, m - 1, 1);
                }
            }
            if (data.navigation) {
                availableMonths = data.navigation;
                renderNavigation(data.navigation);
            }
        } catch (error) { console.error('Fetch navigation error:', error); }
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
            allEventsContainer.innerHTML = '<p style="text-align:center;">ไม่พบกิจกรรมในช่วงเวลาที่เผยแพร่</p>';
            hideLoading(); return;
        }
        for (const monthInfo of availableMonths) {
            try {
                const response = await fetch(`api/get_events.php?year=${monthInfo.year}&month=${monthInfo.month}`);
                if (!response.ok) continue;
                const data = await response.json();
                const eventList = data.event_list_for_table || [];
                if (eventList.length > 0) {
                    const monthGroup = document.createElement('div');
                    monthGroup.className = 'all-events-month-group';
                    monthGroup.innerHTML = `<h2>${monthInfo.label}</h2>`;
                    const table = document.createElement('table');
                    table.className = 'all-events-table';
                    table.innerHTML = `<thead><tr><th style="width:25%">วันที่</th><th>ชื่อกิจกรรม</th><th style="width:20%">หน่วยงาน</th><th style="width:1%"><i class="fas fa-cog"></i></th></tr></thead><tbody></tbody>`;
                    const tbody = table.querySelector('tbody');
                    eventList.forEach(event => {
                        const row = tbody.insertRow();
                        row.insertCell().textContent = event.formatted_dates;
                        row.insertCell().textContent = event.event_name;
                        row.insertCell().textContent = event.responsible_unit || '';
                        const actionCell = row.insertCell();
                        actionCell.style.textAlign = 'center';
                        actionCell.innerHTML = `<a href="export_ical.php?id=${event.id}" class="ical-export-btn" title="เพิ่มไปยังปฏิทิน" download><i class="fas fa-calendar-plus"></i></a>`;
                    });
                    monthGroup.appendChild(table);
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
            monthGroup.innerHTML = `<h2>${monthInfo.label}</h2>`;
            const grid = document.createElement('div');
            grid.id = `calendar-grid-${monthInfo.year}-${monthInfo.month}`;
            grid.className = 'calendar-grid';
            monthGroup.appendChild(grid);
            allEventsContainer.appendChild(monthGroup);
            try {
                const response = await fetch(`api/get_events.php?year=${monthInfo.year}&month=${monthInfo.month}`);
                if (!response.ok) continue;
                const data = await response.json();
                renderSingleCalendar(monthInfo.year, monthInfo.month - 1, data, grid);
            } catch (error) { console.error(`Failed to load calendar for ${monthInfo.label}`, error); }
        }
        hideLoading();
    }

    function renderNavigation(navData) {
        navigationContainer.innerHTML = '';

        if (siteSettings.nav_menu_style === 'dropdown') {
            const dropdown = document.createElement('select');
            dropdown.id = 'month-nav-dropdown';
            const allOption = document.createElement('option');
            allOption.value = 'all';
            allOption.textContent = 'ทั้งหมด';
            dropdown.appendChild(allOption);
            navData.forEach(item => {
                const option = document.createElement('option');
                option.value = `${item.year}-${item.month}`;
                option.textContent = item.label;
                dropdown.appendChild(option);
            });
            navigationContainer.appendChild(dropdown);
            dropdown.addEventListener('change', handleNavigationChange);
        } else {
            const allButton = document.createElement('button');
            allButton.className = 'nav-button';
            allButton.id = 'all-months-btn';
            allButton.textContent = 'ทั้งหมด';
            navigationContainer.appendChild(allButton);
            navData.forEach(item => {
                const button = document.createElement('button');
                button.className = 'nav-button';
                button.dataset.year = item.year;
                button.dataset.month = item.month;
                button.textContent = item.label;
                navigationContainer.appendChild(button);
            });
            navigationContainer.addEventListener('click', handleNavigationChange);
        }
    }

    function handleNavigationChange(e) {
        let target = e.target;
        let value;

        if (target.tagName === 'SELECT') { // Dropdown
            value = target.value;
        } else if (target.classList.contains('nav-button')) { // Button
            document.querySelectorAll('.nav-button.active').forEach(btn => btn.classList.remove('active'));
            target.classList.add('active');
            value = target.id === 'all-months-btn' ? 'all' : `${target.dataset.year}-${target.dataset.month}`;
        } else {
            return; // Not a navigation element
        }

        searchBox.value = '';
        searchResultsContainer.classList.add('hidden');

        isAllViewActive = (value === 'all');

        if (isAllViewActive) {
            renderAllViews();
        } else {
            allEventsContainer.classList.add('hidden');
            const isTableView = tableViewBtn.classList.contains('active');
            calendarContainer.classList.toggle('hidden', isTableView);
            tableViewContainer.classList.toggle('hidden', !isTableView);

            const [year, month] = value.split('-');
            currentYear = parseInt(year);
            currentMonth = parseInt(month) - 1;
            fetchEvents(currentYear, currentMonth);
        }
    }


    function renderCalendar(year, month, data) {
        monthYearHeader.textContent = `${thaiMonths[month]} ${year + 543}`;
        renderSingleCalendar(year, month, data, calendarGrid);
    }

    function renderSingleCalendar(year, month, data, targetGrid) {
        targetGrid.innerHTML = '';
        const eventsForCalendar = data.events_for_calendar || {};
        const weekStartDay = siteSettings.week_start_day === 'monday' ? 1 : 0;
        const dayHeaders = weekStartDay === 1 ? ['จ', 'อ', 'พ', 'พฤ', 'ศ', 'ส', 'อา'] : ['อา', 'จ', 'อ', 'พ', 'พฤ', 'ศ', 'ส'];
        dayHeaders.forEach(day => {
            const dayHeader = document.createElement('div');
            dayHeader.classList.add('calendar-day', 'day-header'); dayHeader.textContent = day; targetGrid.appendChild(dayHeader);
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
                    const textSpan = document.createElement('span');
                    textSpan.textContent = eventText; eventDiv.appendChild(textSpan);
                    eventDiv.addEventListener('click', () => { window.location.href = `export_ical.php?id=${event.id}`; });
                    dayCell.appendChild(eventDiv);
                });
            }
            targetGrid.appendChild(dayCell);
        }
    }
    
    function renderTable(year, month, data) {
        tableMonthYearHeader.textContent = `กิจกรรมเดือน ${thaiMonths[month]} ${year + 543}`;
        tableBody.innerHTML = '';
        const eventList = data.event_list_for_table || [];
        if (eventList.length === 0) { tableBody.innerHTML = '<tr><td colspan="4">ไม่มีกิจกรรมในเดือนนี้</td></tr>'; return; }
        eventList.forEach(event => {
            const row = tableBody.insertRow();
            row.insertCell().textContent = event.formatted_dates;
            row.insertCell().textContent = event.event_name;
            row.insertCell().textContent = event.responsible_unit || '';
            const actionCell = row.insertCell();
            actionCell.classList.add('action-cell');
            actionCell.innerHTML = `<a href="export_ical.php?id=${event.id}" class="ical-export-btn" title="เพิ่มไปยังปฏิทิน" download><i class="fas fa-calendar-plus"></i></a>`;
        });
    }

    async function performSearch(query) {
         if (query.length < 2) {
            searchResultsContainer.classList.add('hidden');
            const isAllView = isAllViewActive;
            if (!isAllView) {
                const isTableView = tableViewBtn.classList.contains('active');
                calendarContainer.classList.toggle('hidden', isTableView);
                tableViewContainer.classList.toggle('hidden', !isTableView);
            } else { allEventsContainer.classList.remove('hidden'); }
            return;
        }
        showLoading();
        try {
            const response = await fetch(`api/search.php?q=${encodeURIComponent(query)}`);
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
            html += `<li>
                <h4>${isHoliday ? '<i class="fas fa-star"></i> ' : ''}${formatResponsibleUnit(item.name, item.responsible_unit)}</h4>
                <p><strong>วันที่:</strong> ${item.dates ? item.dates : 'N/A'}</p>
                ${item.notes ? `<p class="notes"><strong>หมายเหตุ:</strong> ${item.notes}</p>` : ''}
            </li>`;
        });
        html += '</ul>';
        searchResultsContainer.innerHTML = html;
    }
    
    prevMonthBtn.addEventListener('click', () => { currentMonth--; if (currentMonth < 0) { currentMonth = 11; currentYear--; } fetchEvents(currentYear, currentMonth); });
    nextMonthBtn.addEventListener('click', () => { currentMonth++; if (currentMonth > 11) { currentMonth = 0; currentYear++; } fetchEvents(currentYear, currentMonth); });
    calendarViewBtn.addEventListener('click', () => { tableViewContainer.classList.add('hidden'); calendarContainer.classList.remove('hidden'); tableViewBtn.classList.remove('active'); calendarViewBtn.classList.add('active'); if (isAllViewActive) renderAllViews(); });
    tableViewBtn.addEventListener('click', () => { calendarContainer.classList.add('hidden'); tableViewContainer.classList.remove('hidden'); calendarViewBtn.classList.remove('active'); tableViewBtn.classList.add('active'); if (isAllViewActive) renderAllViews(); });
    let searchTimeout;
    searchBox.addEventListener('input', () => { clearTimeout(searchTimeout); searchTimeout = setTimeout(() => { performSearch(searchBox.value); }, 300); });
    
    function openModal(modal) { modal.classList.remove('hidden'); }
    function closeModal(modal) { modal.classList.add('hidden'); }
    document.querySelectorAll('.modal-close-btn').forEach(btn => { btn.addEventListener('click', () => closeModal(btn.closest('.modal-overlay'))); });
    document.getElementById('report-problem-btn').addEventListener('click', () => openModal(reportModal));
    document.getElementById('submit-report-btn').addEventListener('click', async () => {
        const message = document.getElementById('report-message').value, statusMsg = document.getElementById('report-status-msg');
        if (!message.trim()) { statusMsg.textContent = 'กรุณากรอกข้อความ'; statusMsg.style.color = 'red'; return; }
        const formData = new FormData();
        formData.append('message', message);
        try {
            const response = await fetch('api/submit_report.php', { method: 'POST', body: formData });
            const result = await response.json();
            statusMsg.textContent = result.message;
            statusMsg.style.color = result.success ? 'green' : 'red';
            if (result.success) {
                document.getElementById('report-message').value = '';
                setTimeout(() => closeModal(reportModal), 2000);
            }
        } catch (error) { statusMsg.textContent = 'เกิดข้อผิดพลาดในการส่ง'; statusMsg.style.color = 'red'; }
    });

    document.getElementById('print-btn').addEventListener('click', () => openModal(printModal));
    printModal.querySelectorAll('.print-option-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const printType = btn.dataset.printType;
            let url = 'print.php?';
            if (isAllViewActive) {
                if (printType === 'calendar') url += 'view=all_calendars';
                else if (printType === 'table') url += 'view=all_tables';
                else url += 'view=all_mixed';
            } else {
                url += `year=${currentYear}&month=${currentMonth + 1}`;
                if (printType === 'calendar') url += '&view=calendar';
                else if (printType === 'table') url += '&view=table';
                else url += '&view=mixed';
            }
            window.open(url, '_blank');
            closeModal(printModal);
        });
    });

    async function initialize() {
        await fetchTickerData();
        await fetchNavigation();
        const today = new Date();
        let initialYear = today.getFullYear(), initialMonth = today.getMonth();
        const todayForCompare = new Date(initialYear, initialMonth, 1);
        if (publishStartDate && todayForCompare < publishStartDate) { initialYear = publishStartDate.getFullYear(); initialMonth = publishStartDate.getMonth(); } 
        else if (publishEndDate && todayForCompare > publishEndDate) { initialYear = publishEndDate.getFullYear(); initialMonth = publishEndDate.getMonth(); }
        currentYear = initialYear; currentMonth = initialMonth;
        
        if (siteSettings.nav_menu_style === 'dropdown') {
            const dropdown = document.getElementById('month-nav-dropdown');
            if (dropdown) {
                const initialValue = `${currentYear}-${currentMonth + 1}`;
                const targetOption = dropdown.querySelector(`option[value="${initialValue}"]`);
                if (targetOption) {
                    dropdown.value = initialValue;
                } else if (dropdown.options.length > 1) {
                    dropdown.selectedIndex = 1; // Select first month if today is not available
                }
                dropdown.dispatchEvent(new Event('change'));
            }
        } else {
            const initialButton = navigationContainer.querySelector(`.nav-button[data-year="${currentYear}"][data-month="${currentMonth + 1}"]`);
            if (initialButton) {
                initialButton.click();
            } else {
                const allBtn = document.getElementById('all-months-btn');
                if (allBtn) allBtn.click();
            }
        }
    }
    initialize();
});

