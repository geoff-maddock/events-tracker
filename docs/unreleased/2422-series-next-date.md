### A series' next date: two-period schedules, short months and leap days (#2422)

Four faults in how a series works out its next date:

- **Biweekly and Bimonthly** stepped one week or one month, the same as Weekly and Monthly. They now step two. Which week or month is "on" comes from the series' latest event (before today, not cancelled), and from the founding date until it has one: the week or month the series last ran in was on, so the next is two after it. The weekday and the time of day stay the series' own, so one night moved to another day doesn't move the series.
- **"Last X of the month"** could skip a month: from a date on the 29th to 31st, the step ran past a shorter month. A last-Saturday series was being offered 26 December when 28 November was still to come.
- **A founding date on the 29th to 31st** had the same fault on the first step of any monthly schedule.
- **A yearly series founded on 29 February** moved to 28 February for good after the first year. It is now counted from the founding date each time, so leap years get the 29th back.

Checked against production (read-only): of 45 active scheduled series, one gets a different next date, the last-Saturday series above.
