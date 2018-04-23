# GSuite Provision

This is a lightweight WordPress plugin that allows you to identify a trusted GSuite managed domain and automatcally log in your users if they have a valid account in that domain. This plugin provides just-in-time user provisioning and maps real names to WordPress's display names, if applicable.

This plugin uses Composer to manage dependencies on the Google API client, so you will need ssh access to your host in order to install the dependencies (or some other way to run composer remotely).

## Setup Instructions

1. Install and activate the plugin in WordPress. In the plugin root, do `composer install` to add the Google API client library.
2. Go to the [Google Cloud Developer Console](https://console.cloud.google.com) and create a new project.
3. Add the Gmail API to it and go to the "Credentials" screen in the sidebar.
4. Go to "Create Credentials" -> "OAuth Client ID".
5. Select "Web Application" in the radio buttons and enter the following authorized redirect URI: `https://www.example.com/wp-content/plugins/gsuite-provision/lib/auth.php` (replace `www.example.com` with your site's hostname).
6. Click "Create" and copy the "Client ID" and "Client Secret". The client ID looks like `[number]-[hash].apps.googleusercontent.com` and the client secret is a shorter hash.
7. Log in as an administrator to your WordPress site and on the dashboard, to go "Settings" -> "General" and scroll down to the "GSuite Login Settings" section.
8. Enter the domain you want to allow users to log in from. Do not include an '@' before the domain. Triple-check the domain, since this is the main security mechanism of this plugin.
9. Select the role you want new users to be provisioned with. Note that if you change this later, existing users will not be reassigned to a new role.
10. Enter the client ID and client secret you obtained from Google's console.
11. Log out, and you should see your login screen now offers a GSuite login button instead of the standard username and password fields. You can click the link below to log in with a username and password.

## Security Considerations

This plugin grants automatic access to your site at the selected role to anyone who can authenticate via Google's single sign-on as long as their email domain matches what you enter. It is, therefore, not a wise solution for production sites. We developd this plugin at Alley to ease the hassle of granting accounts on internal development sites, wikis, and discussion boards to many people at different times.

The GSuite login mechanism is automatically disabled if no domain is set, or if the domain is set to "gmail.com" (since any member of the public can authenticate with a gmail.com domain).

Once provisioned, users are not treated differently than manually created users. They are assigned a randomly-generated password at provisioning time. If a user's GSuite account is disabled, you will need to manually remove them from WordPress as well. While no longer having a valid GSuite account would prevent them from logging in via that method, they could still have changed their own password to something they know before their account was disabled.

## Roadmap

There are several features that are not supported, but could be added to this plugin, including:

* Supporting multiple domains with granular role mapping.
* Fully disabling username/password login while GSuite is active.
* Some kind of account information sync to detect, e.g., disabled users.
