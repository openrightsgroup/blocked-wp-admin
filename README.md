# blocked-wp-admin
Admin plugin for Wordpress, showing the site's status on blocked.org.uk

# Installation

Ensure that the admin email address for your wordpress site is correct.

1. Download a release from the [Release](https://github.com/openrightsgroup/blocked-wp-admin/releases/) page.  Select the '*-deploy.zip' package for upload to your wordpress site.  The source code zip/tar.gz files contain a version-suffixed folder name, which will prevent clean upgrades.
2. Log in to your wordpress installation as an administrator, go to Plugins ➔ Add New.  Click "Upload plugin", and select the downloaded zip file.
3. On the next screen, click the "Activate Plugin" button.
4. Go to the "Settings" ➔ "BlockedWP Admin" page
5. Click the "Register {your site name}" button.

Whenever you visit the "Settings" ➔ "BlockedWP Admin" page, you will be able to see the most recent scan results for your site.  
Your site will be tested weekly by the blocked.org.uk platform.

# Upgrading

The plugin should upgrade cleanly when using one of the `*-deploy.zip` packages from the releases page.  The site registration and API credentials will 
still be stored in your wordpress database, so there is no need to re-register when the new version is installed.  

If you have one of the older (pre-0.8) alpha releases installed, you should uninstall and delete the plugin before installing the deployment package.

# Bug reporting

If you experience any problems, please create an issue on the github repository at https://github.com/openrightsgroup/blocked-wp-admin/issues.  Please provide as much detail as you can, without sharing your personal information. 
This is an early release, to the user experience is quite rudimentary.  Feature requests and ideas are welcome!
