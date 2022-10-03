# event costs by tag by year
# counts events with each tag by year
select tags.name, count(*) as event_count, year(start_at), format(avg(events.door_price),2)
from events join event_tag on (events.id = event_tag.event_id)
join tags on (event_tag.tag_id = tags.id)
where
#tags.name = 'World Music' and
events.door_price is not null and events.door_price != 0
group by tags.name, year(start_at)
having count(*) > 2
order by year(start_at) desc, tags.name asc;