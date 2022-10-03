# counts events with each tag by year
select tags.name, COUNT(*), year(start_at)
from events join event_tag on (events.id = event_tag.event_id)
join tags on (event_tag.tag_id = tags.id)
group by tags.name, year(start_at)
order by year(start_at) desc, tags.name asc;