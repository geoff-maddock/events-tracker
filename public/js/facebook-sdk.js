window.fbAsyncInit = function() {

    FB.init({
      appId      : facebookAppId, // should be replaced with FB app id
      status     : true,
      cookie     : true,
      xfbml      : true,
      version    : 'v2.12'
    });
    FB.AppEvents.logPageView();
    console.log('fbAsyncInit complete')
    console.log('appid: ' + facebookAppId)
  };

  (function(d, s, id){
     var js, fjs = d.getElementsByTagName(s)[0];
     if (d.getElementById(id)) {return;}
     js = d.createElement(s); js.id = id;
     js.src = "//connect.facebook.net/en_US/sdk.js";
     fjs.parentNode.insertBefore(js, fjs);
   }(document, 'script', 'facebook-jssdk'));
