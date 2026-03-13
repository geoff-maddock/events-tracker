<script>
    /**
     * FullCalendar eventContent callback for multi-day events.
     *
     * For event segments that are not the first day (isStart is false),
     * displays "Ends at [time]" instead of the start time so it is clear
     * the event is carrying over from a previous day.
     *
     * For start segments, explicitly renders the FullCalendar-formatted
     * time (info.timeText) and title to ensure the event is always visible.
     */
    function calendarEventContent(info) {
        var frame = document.createElement('div');
        frame.className = 'fc-event-main-frame';

        var timeEl = document.createElement('div');
        timeEl.className = 'fc-event-time';

        if (!info.isStart && info.event.end) {
            // Continuation day: show "Ends at [end time]"
            var end = info.event.end;
            var hours = end.getHours();
            var minutes = end.getMinutes();
            var meridiem = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12 || 12;
            timeEl.textContent = 'Ends at ' + hours + ':' + String(minutes).padStart(2, '0') + ' ' + meridiem;
        } else if (info.timeText) {
            // Start day: use FullCalendar's pre-formatted start time
            timeEl.textContent = info.timeText;
        }

        var titleContainer = document.createElement('div');
        titleContainer.className = 'fc-event-title-container';
        var titleEl = document.createElement('div');
        titleEl.className = 'fc-event-title fc-sticky';
        titleEl.textContent = info.event.title;
        titleContainer.appendChild(titleEl);

        if (timeEl.textContent) {
            frame.appendChild(timeEl);
        }
        frame.appendChild(titleContainer);

        return { domNodes: [frame] };
    }
</script>
