window.fbAsyncInit = function() {

    FB.init({
      appId      : facebookAppId, // should be replaced with FB app id
      autoLogAppEvents : true,
      status     : true,
      cookie     : true,
      xfbml      : true,
      version    : 'v2.12'
    });
    FB.AppEvents.logPageView();
    console.log('fbAsyncInit complete')
  };