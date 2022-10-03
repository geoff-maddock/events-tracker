# event costs by venue by year
select entities.name, count(*) as event_count, year(start_at), format(avg(events.door_price),2)
from events join entities on (events.venue_id = entities.id)
where
-- entities.name = 'Collision' and
events.door_price is not null and events.door_price != 0
group by entities.name, year(start_at)
having count(*) >= 2
order by year(start_at) desc, entities.name asc;