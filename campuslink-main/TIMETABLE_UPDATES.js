// Add these functions to student-timetable.php JavaScript section

// Load and render timetable dynamically
async function loadTimetable(weekNumber = null) {
    const tbody = document.getElementById('timetable-body');
    tbody.innerHTML = '<tr><td colspan="21" style="text-align: center; padding: 40px;"><i class="fa-solid fa-spinner fa-spin"></i><p style="margin-top: 10px;">Loading...</p></td></tr>';
    
    try {
        const url = `api/timetable.php?action=getSchedule&studentID=${studentID}${weekNumber ? '&weekNumber=' + weekNumber : ''}`;
        const response = await fetch(url);
        const result = await response.json();
        
        if (result.success && result.data) {
            renderTimetable(result.data);
        } else {
            tbody.innerHTML = '<tr><td colspan="21" style="text-align: center; padding: 40px; color: #e74c3c;">' + (result.message || 'No schedule found') + '</td></tr>';
        }
    } catch (error) {
        console.error('Error:', error);
        tbody.innerHTML = '<tr><td colspan="21" style="text-align: center; padding: 40px; color: #e74c3c;">Error loading. Please refresh.</td></tr>';
    }
}

// Render timetable
function renderTimetable(scheduleData) {
    const startHour = 8, endHour = 18, totalSlots = (endHour - startHour) * 2;
    const daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
    const scheduleMatrix = {};
    
    scheduleData.forEach(row => {
        const day = row.day;
        if (!scheduleMatrix[day]) scheduleMatrix[day] = {};
        
        const [sH, sM] = row.startTime.split(':');
        const startIndex = ((parseInt(sH) - startHour) * 2) + (sM === '30' ? 1 : 0);
        const [eH, eM] = row.endTime.split(':');
        const endIndex = ((parseInt(eH) - startHour) * 2) + (eM === '30' ? 1 : 0);
        const durationSlots = endIndex - startIndex;
        
        if (durationSlots <= 0 || startIndex < 0 || endIndex > totalSlots) return;
        
        let isBlocked = false;
        for (let i = 0; i < durationSlots; i++) {
            if (scheduleMatrix[day][startIndex + i]) { isBlocked = true; break; }
        }
        
        if (!isBlocked) {
            scheduleMatrix[day][startIndex] = { info: row, colspan: durationSlots };
            for (let i = 1; i < durationSlots; i++) scheduleMatrix[day][startIndex + i] = 'occupied';
        }
    });
    
    const tbody = document.getElementById('timetable-body');
    tbody.innerHTML = '';
    
    daysOfWeek.forEach(day => {
        const tr = document.createElement('tr');
        const dayTd = document.createElement('td');
        dayTd.className = 'day-column';
        dayTd.textContent = day.substring(0, 3);
        tr.appendChild(dayTd);
        
        for (let i = 0; i < totalSlots; i++) {
            if (scheduleMatrix[day] && scheduleMatrix[day][i]) {
                const slot = scheduleMatrix[day][i];
                if (slot === 'occupied') continue;
                
                const td = document.createElement('td');
                td.className = 'time-slot';
                td.colSpan = slot.colspan;
                const typeClass = 'is-' + (slot.info.classType || 'Lecture');
                const suffix = (slot.info.classType === 'Lecture') ? '(L)' : (slot.info.classType === 'Practical' ? '(P)' : '(T)');
                
                td.innerHTML = `<div class="class-container ${typeClass}">
                    <span class="subject-code">${slot.info.courseID} ${suffix}</span>
                    <span class="subject-name">${slot.info.courseName}</span>
                    <span class="subject-loc"><i class="fa-solid fa-location-dot"></i> ${slot.info.facilityID}</span>
                    <span class="subject-prof">${slot.info.staffName}</span>
                </div>`;
                tr.appendChild(td);
            } else {
                const td = document.createElement('td');
                td.className = 'time-slot empty';
                tr.appendChild(td);
            }
        }
        tbody.appendChild(tr);
    });
}

// MODIFY populateWeekSelector to add event listener for week change
// Add after creating options:
selector.addEventListener('change', function() {
    currentWeek = parseInt(this.value);
    loadTimetable(currentWeek);
});

// MODIFY selectCurrentWeek to load timetable after selection:
// Add at the end:
loadTimetable(currentWeek);
