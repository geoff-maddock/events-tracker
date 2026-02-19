# count of events by entity 
select entities.name, count(*) as event_count
from events join entity_event on (events.id = entity_event.event_id)
join entities on (entity_event.entity_id = entities.id)
group by entities.name
order by event_count desc, entities.name asc;
