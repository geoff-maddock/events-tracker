# count all events by year
SELECT count(*), year(start_at)
FROM events
GROUP BY year(start_at)
order by year(start_at) desc;
