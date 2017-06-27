<div class="col-sm-1">
<ul class="pagination pull-left" style="margin-top: 0px;">
	<li>{!! link_to_route('home', '< Past', ['day_offset' => $dayOffset-1], ['class' => 'item-title', 'style' => 'white-space: nowrap;']) !!}</li>
</ul>
</div>

<div class="col-sm-10">
<ul class="pagination" style="margin-top: 0px;">
<li></li>
</ul>
</div>

<div class="col-sm-1">
<ul class="pagination pull-right" style="margin-top: 0px;">
	<li>{!! link_to_route('home', 'Future >', ['day_offset' => $dayOffset+1], ['class' => 'item-title', 'style' => 'white-space: nowrap;']) !!}</li>
</ul>
</div>

<br style="clear: left;"/>
<!-- DISPLAY THE NEXT FOUR DAYS OF EVENTS --> 
<?php $today = \Carbon\Carbon::now('America/New_York'); ?>

<div class="row small-gutter">
	@for ($i = 0; $i < 4; $i++)  
	<?php
	 $offset = $i + $dayOffset;
	 $day = \Carbon\Carbon::parse($today)->addDay($offset);

	 ?>
		@include('events.day', ['day' => $day ])
	@endfor

</div>