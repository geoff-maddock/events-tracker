### A series' next date: two-period schedules, short months and leap days (#2422)

Four faults in how a series works out its next date:

- **Biweekly and Bimonthly** stepped one week or one month, the same as Weekly and Monthly. They now step two. The "on" week or month is counted from the series' latest event (up to today), and from the founding date until it has one, so a moved or skipped night resets the rhythm. The time of day still comes from the series.
- **"Last X of the month"** could skip a month: from a date on the 29th to 31st, the step ran past a shorter month. A last-Saturday series was being offered 26 December when 28 November was still to come.
- **A founding date on the 29th to 31st** had the same fault on the first step of any monthly schedule.
- **A yearly series founded on 29 February** moved to 28 February for good after the first year. It is now counted from the founding date each time, so leap years get the 29th back.

Checked against production (read-only): of 45 active scheduled series, two get a different next date. One is the last-Saturday series above. The other is a Biweekly series whose latest event was a Sunday in September while its schedule says Monday; its schedule setting looks out of date and is worth a look.
