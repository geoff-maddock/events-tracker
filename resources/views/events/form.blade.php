<div class="row">

    <div class="form-group col-md-6">
        {!! Form::label('primary_link','Primary Link:') !!}
        {!! Form::text('primary_link', null, ['class' =>'form-control']) !!}
        {!! $errors->first('primary_link','<span class="help-block">:message</span>') !!}
    </div>
    <div class="form-group col-md-3">
    <label></label>
    <button type="button" class="btn btn-info form-control" id='import-link'>Import</button>
    </div>
</div>

<div class="row">
 
	<div class="form-group col-md-12 {{$errors->has('name') ? 'has-error' : '' }}">
	{!! Form::label('name','Name') !!}
	{!! Form::text('name', null, ['class' =>'form-control']) !!}
	{!! $errors->first('name','<span class="help-block">:message</span>') !!}
	</div>

</div>

<div class="row">
 
	<div class="form-group col-md-12 {{$errors->has('slug') ? 'has-error' : '' }}">
	{!! Form::label('slug','Slug') !!}
	{!! Form::text('slug', null, ['placeholder' => 'Unique name for this event (will validate)', 'class' =>'form-control']) !!}
	{!! $errors->first('slug','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row">
 
	<div class="form-group col-md-12 {{$errors->has('slug') ? 'has-error' : '' }}">
	{!! Form::label('short','Short Description') !!}
	{!! Form::text('short', null, ['class' =>'form-control']) !!}
	{!! $errors->first('short','<span class="help-block">:message</span>') !!}
	</div>
</div>


<div class="row">
 
	<div class="form-group col-md-12">
	{!! Form::label('description','Description') !!}
	{!! Form::textarea('description', null, ['class' =>'form-control']) !!}
	{!! $errors->first('description','<span class="help-block">:message</span>') !!}
	</div>
</div>


<div class="row">

	<div class="form-group col-md-3 {{$errors->has('event_type_id') ? 'has-error' : '' }}">
	{!! Form::label('event_type_id','Event type:') !!}
	{!! Form::select('event_type_id', $eventTypes, (isset($event->event_type_id) ? $event->event_type_id : NULL), ['class' =>'form-control']) !!}
	{!! $errors->first('event_type_id','<span class="help-block">:message</span>') !!}
	</div>

	<div class="form-group col-md-3">
	{!! Form::label('venue_id','Venue') !!}
	{!! Form::select('venue_id', $venues, (isset($event->venue_id) ? $event->venue_id : NULL), ['class' =>'form-control select2']) !!}
	{!! $errors->first('venue_id','<span class="help-block">:message</span>') !!}
	</div>

	<div class="form-group col-md-3">
	{!! Form::label('promoter_id','Promoter') !!}
	{!! Form::select('promoter_id', $promoters, (isset($event->promoter_id) ? $event->promoter_id : NULL), ['class' =>'form-control select2']) !!}
	{!! $errors->first('promoter_id','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row">
	<div class="form-group col-md-3">
	{!! Form::label('soundcheck_at','Soundcheck At:') !!}
	{!! Form::dateTimeLocal('soundcheck_at', (isset($event->soundcheck_at)) ? $event->soundcheck_at->format('Y-m-d\\TH:i') : NULL, ['class' =>'form-control']) !!}
	{!! $errors->first('soundcheck_at','<span class="help-block">:message</span>') !!}
	</div>

	<div class="form-group col-md-3"> 
	{!! Form::label('door_at','Door At:') !!}
	{!! Form::dateTimeLocal('door_at', (isset($action) && isset($event->door_at)) ? $event->door_at->format('Y-m-d\\TH:i') : NULL, ['class' =>'form-control']) !!}
	{!! $errors->first('door_at','<span class="help-block">:message</span>') !!}
	</div>

</div>

<div class="row">
	<div class="form-group col-md-3 {{$errors->has('start_at') ? 'has-error' : '' }}">
	{!! Form::label('start_at','Start At:') !!}
	{!! Form::dateTimeLocal('start_at', (isset($action) && isset($event->start_at)) ?  $event->start_at->format('Y-m-d\\TH:i') : Carbon\Carbon::now()->format('Y-m-d\\TH:i'), ['class' =>'form-control']) !!}
	{!! $errors->first('start_at','<span class="help-block">:message</span>') !!}
	</div>

	<div class="form-group col-md-3">
	{!! Form::label('end_at','End At:') !!}
	{!! Form::dateTimeLocal('end_at', (isset($action) && isset($event->end_at)) ? $event->end_at->format('Y-m-d\\TH:i') : NULL, ['class' =>'form-control']) !!}
	{!! $errors->first('end_at','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row">
 

	<div class="form-group col-md-3">
	{!! Form::label('presale_price','Presale Price:') !!}
	{!! Form::text('presale_price', null, ['class' =>'form-control']) !!}
	{!! $errors->first('presale_price','<span class="help-block">:message</span>') !!}
	</div>

	<div class="form-group col-md-3">
	{!! Form::label('door_price','Door Price:') !!}
	{!! Form::text('door_price', null, ['class' =>'form-control']) !!}
	{!! $errors->first('door_price','<span class="help-block">:message</span>') !!}
	</div>

	<div class="form-group col-md-3">
	{!! Form::label('min_age','Min Age:') !!}
	{!! Form::select('min_age', [ '0' => 'All Ages', '18' => '18', '21' => '21'], (isset($event->min_age) ? $event->min_age : NULL), ['class' =>'form-control']) !!}
	{!! $errors->first('min_age','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row">


	<div class="form-group col-md-6 {{$errors->has('ticket_link') ? 'has-error' : '' }}">
	{!! Form::label('ticket_link','Ticket Link:') !!}
	{!! Form::text('ticket_link', null, ['class' =>'form-control']) !!}
	{!! $errors->first('ticket_link','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row">
	<div class="form-group col-md-3 {{$errors->has('visibility_id') ? 'has-error' : '' }}">
	{!! Form::label('visibility_id','Visibility:') !!}
	{!! Form::select('visibility_id', $visibilities, (isset($event->visibility_id) ? $event->visibility_id : NULL), ['class' =>'form-control']) !!}
	{!! $errors->first('visibility_id','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row">
	<div class="form-group col-md-3">
	{!! Form::label('series_id','Series:') !!}
	{!! Form::select('series_id', $seriesList, (isset($event->series_id) ? $event->series_id : NULL), ['class' =>'form-control select2']) !!}
	{!! $errors->first('series_id','<span class="help-block">:message</span>') !!}
	</div>
</div>


<div class="row">
	<div class="form-group col-md-6">
	{!! Form::label('entity_list','Related Entities:') !!}
	{!! Form::select('entity_list[]', $entities, null, ['id' => 'entity_list', 'class' =>'form-control select2', 'data-placeholder' => 'Choose a related artist, producer, dj', 'data-tags' =>'false', 'multiple']) !!}
	{!! $errors->first('entities','<span class="help-block">:message</span>') !!}
	</div>
</div>


<div class="row">
	<div class="form-group col-md-6">
	{!! Form::label('tag_list','Tags:') !!}
	{!! Form::select('tag_list[]', $tags, old('tag_list'), ['id' => 'tag_list',
	'class' =>'form-control select2',
	'data-placeholder' => 'Choose a tag',
	'data-tags' =>'true',
	'multiple' => 'true']) !!}
	{!! $errors->first('tags','<span class="help-block">:message</span>') !!}
	</div>
</div>


<div class="row">
	<div class="form-group col-md-3">
	{!! Form::label('attending','Attending') !!}
	@if (isset($event))
	  {{ $event->attending }}
	@else
	  0
	@endif
	</div>
</div>


<div class="form-group">
{!! Form::submit(isset($action) && $action == 'update' ? 'Update Event' : 'Add Event', null, ['class' =>'btn btn-primary']) !!}
</div>


@section('footer')
	<script>

		$('#import-link').click(function(e){

			// get the id out of the link
			var str = $('#primary_link').val();
			var spl = str.split("/");
			var event_id = spl[4];

			// get the content of the link
			console.log(str);
			console.log(event_id); // should be the position of the event

			// login
			FB.login(function(response) {
				// try to pull info from the fb object
				FB.api('/'+event_id, function(response) {
					if (!response || response.error) {
						handleError(response.error);
					} else {
						// process the response and try to set the event form fields
						if (response.name)
						{
							$('#name').val(response.name);
							$('#slug').val(response.name);
							console.log('set name');
						};

						if (response.description)
						{
							$('#description').val(response.description);
							console.log('set description');

							$('#short').val(response.description.slice(0,100));
							console.log('set short description');
						};

						// scrape some more data from description
						let adult = "21+";

						if (response.description.indexOf(adult) > 0)
						{
                            $('#min_age option[value=21]').attr('selected', 'selected');
                            console.log('set ages to 21+');
						} else {
                            console.log('adult '+response.description.indexOf(adult)+' '+adult);
						}

                        let amount = response.description.match(/\$(\d+)/);

						if (amount)
						{
                            $('#door_price').val(amount[1]);
                            console.log('set price '+amount[1]);
						};

						if (response.start_time)
						{
							start_trim = response.start_time.slice(0,-5);
							$('#start_at').val(start_trim);
							console.log('set start at '+start_trim);
						};
						if (response.end_time)
						{
							end_trim = response.end_time.slice(0,-5);
							$('#end_at').val(end_trim);
							console.log('set end at'+end_trim);
						};
						if (response.place)
						{
							venue = capitalizeNth(response.place.name,0);
							venue_val = $('#venue_id').find("option:contains('"+venue+"')").val();
							$('#venue_id option[value='+venue_val+']').attr('selected', 'selected');
							console.log('venue_val '+venue_val);
							console.log('set venue '+venue);
						};

						// default to public visibility
						$('#visibility_id option[value=3]').attr('selected', 'selected');
					}
		 		 console.log(response);
                    App.init();
				});
			});


		})

        function capitalizeNth(text, n) {
            return text.slice(0,n) + text.charAt(n).toUpperCase() + text.slice(n+1)
        }

		function handleError(error) {
			console.log('Error code:'+error.code);
			console.log(error.message);
		}

		function eventPhotos(event){
			// get the photos from the api
		}
	</script>
@endsection