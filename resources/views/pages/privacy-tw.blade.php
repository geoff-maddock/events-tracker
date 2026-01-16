@extends('layouts.app-tw')

@section('title', 'Privacy Policy')

@section('content')
<div class="max-w-4xl mx-auto">
    <h1 class="text-3xl font-bold text-foreground mb-8">Privacy Policy</h1>

    <div class="card-tw p-6 space-y-8">
        <section>
            <h2 class="text-xl font-semibold text-foreground mb-4 flex items-center gap-2">
                <span class="bg-primary text-primary-foreground w-8 h-8 rounded-full flex items-center justify-center text-sm">1</span>
                General Policy
            </h2>
            <ul class="space-y-2 text-muted-foreground ml-10">
                <li class="flex items-start gap-2">
                    <i class="bi bi-check-circle text-green-500 mt-1"></i>
                    <span>We respect your privacy and strive to keep your data safe and secure.</span>
                </li>
                <li class="flex items-start gap-2">
                    <i class="bi bi-check-circle text-green-500 mt-1"></i>
                    <span>We don't use your data for any purpose other than your use and interaction with this site.</span>
                </li>
                <li class="flex items-start gap-2">
                    <i class="bi bi-check-circle text-green-500 mt-1"></i>
                    <span>We don't share your data with any 3rd parties. Sensitive data such as passwords are encrypted and not available to users or administrators.</span>
                </li>
                <li class="flex items-start gap-2">
                    <i class="bi bi-check-circle text-green-500 mt-1"></i>
                    <span>We display no advertisements and share no data with advertisers.</span>
                </li>
            </ul>
        </section>

        <section>
            <h2 class="text-xl font-semibold text-foreground mb-4 flex items-center gap-2">
                <span class="bg-primary text-primary-foreground w-8 h-8 rounded-full flex items-center justify-center text-sm">2</span>
                Types and Purpose of Collected Information
            </h2>
            <ul class="space-y-4 text-muted-foreground ml-10">
                <li>
                    <strong class="text-foreground">Personal information.</strong>
                    Your name, email address, bio, event data and other information you provide when signing up or sharing event data via a form. This data is collected to identify you as a user of the app to administrators as well as other site users.
                </li>
                <li>
                    <strong class="text-foreground">Facebook Data.</strong>
                    When you auth using the FB integration, we collect the account ID for future authentications. When you grant access to your attended events, and choose to import that data, we store public data related to FB events you are attending. This data is collected to allow you to log in more easily as well as share your event information more easily.
                </li>
                <li>
                    <strong class="text-foreground">Settings and Account information.</strong>
                    We store data such as your notification preferences, time zone, theme choice and other settings data you submit while using the site. This data is collected to improve the overall user experience and retain your preferences.
                </li>
            </ul>
        </section>

        <section>
            <h2 class="text-xl font-semibold text-foreground mb-4 flex items-center gap-2">
                <span class="bg-primary text-primary-foreground w-8 h-8 rounded-full flex items-center justify-center text-sm">3</span>
                How You Can Request Deletion of Data
            </h2>
            <p class="text-muted-foreground ml-10">
                You can request the deletion of any or all of your data by emailing the administrator at
                <a href="mailto:{{ Config::get('app.admin') }}" class="text-primary hover:underline">{{ Config::get('app.admin') }}</a>
                and they will follow up within two business days.
            </p>
        </section>

        <div class="border-t border-border pt-6 mt-8">
            <p class="text-muted-foreground">
                Direct any other questions to <strong class="text-foreground">{{ Config::get('app.admin') }}</strong>
            </p>
        </div>
    </div>
</div>
@stop
