# event costs by year
select year(start_at), format(avg(events.door_price),2)
from events
where
events.door_price is not null and events.door_price > 0.00
group by year(start_at)
order by year(start_at) desc;