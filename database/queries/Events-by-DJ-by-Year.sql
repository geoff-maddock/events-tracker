# count of events by dj
select entities.name, count(*) as event_count, year(events.start_at)
from events join entity_event on (events.id = entity_event.event_id)
join entities on (entity_event.entity_id = entities.id)
join entity_role on (entity_role.entity_id = entities.id)
join roles on (roles.id = entity_role.role_id)
where roles.id = 4 # dj
group by entities.name, year(events.start_at)
order by year(events.start_at) desc, event_count desc, entities.name asc;
