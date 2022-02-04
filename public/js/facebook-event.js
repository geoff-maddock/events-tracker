$(document).ready(function(){
    function slugify(text)
    {
        return text.toString().toLowerCase()
            .replace(/\s+/g, '-')           // Replace spaces with -
            .replace(/[^\w\-]+/g, '')       // Remove all non-word chars
            .replace(/\-\-+/g, '-')         // Replace multiple - with single -
            .replace(/^-+/, '')             // Trim - from start of text
            .replace(/-+$/, '');            // Trim - from end of text
    };

// queries the primary link to get data - FB API only for now - and parses to set form inputs
    $('#import-link').click(function(e){

        // get the id out of the link
        var str = $('#primary_link').val();
        var spl = str.split("/");
        var event_id = spl[4];

        // get the content of the link
        console.log(str);
        console.log('event_id:'+event_id); // should be the position of the event

        if (str == undefined || str == '')
        {
            $('#primary_link').attr('placeholder','You must enter a valid FB event link to import an event.');
            throw new Error('Error: The import link was empty.');
        }

        if (event_id == undefined || event_id == '')
        {
            $('#importlink').after('<small>The link does not contain a FB event ID.</small>');
            throw new Error('Error:  Could not detect the FB event.');
        }

        // check that there is a login first
        FB.getLoginStatus(function(response) {
            if (response.status === 'connected') {
                console.log('Facebook-event.js. Already Logged in.');
                const accessToken = response.authResponse.accessToken;
                // set the token in the session
                document.cookie = "fb-token="+accessToken+"; path=/";
            }
            else {
               // FB.login();
                console.log('FB.login - trying to get all scopes')
                FB.login(function(response) {
                    // handle the response
                    // set the token in the session
                    const accessToken = response.authResponse.accessToken;
                    document.cookie = "fb-token="+accessToken+"; path=/";
                }, {
                    scope: 'public_profile,email,user_events',
                    return_scopes: true
                })
            }
        });

        // let fields =  'description,end_time,id,name,place,start_time,cover,attending_count,interested_count,maybe_count,noreply_count';
        let fields = 'description,name,end_time,start_time,interested_count,ticket_uri';

        // try to pull info from the fb object
        FB.api('/'+event_id+'?fields='+fields, function(response) {
            if (!response || response.error) {
                $('#import-link').after('<span class="help-block" id="fb-api-error">The FB API did not return anything for that event link.</span>');
                $('#fb-api-error').delay(3000).fadeOut(200);
                handleError(response.error);
            } else {
                // process the response and try to set the event form fields
                if (response.name)
                {
                    $('#name').val(response.name);
                    $('#slug').val(slugify(response.name));
                    console.log('set name');
                };

                if (response.ticket_uri)
                {
                    $('#ticket_link').val(response.ticket_uri);
                    console.log('set ticket link');
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


    })

    function capitalizeNth(text, n) {
        return text.slice(0,n) + text.charAt(n).toUpperCase() + text.slice(n+1)
    }

    function handleError(error) {
        console.log('Error code:'+error.code);
        console.log(error.message);
    }

    console.log('facebook-event.js ready');
});