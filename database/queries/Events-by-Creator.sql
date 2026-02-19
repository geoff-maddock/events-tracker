# count of events by creator
select users.name, count(*) as event_count
from events join users on (events.created_by = users.id)
group by users.name
order by event_count desc;
