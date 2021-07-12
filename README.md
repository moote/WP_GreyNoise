# WP_GreyNoise
A WordPress plugin that leverages the GreyNoise API to test for malicious visitors based on IP address.

## WARNING!

**This plugin is a proof of concept only, it should not be used in production under any circumstances!**

### Requirements

- PHP >=7.4
- Wordpress >= 4.0
- You will need a Greynoise API key, a trial key is available by signing up here: https://www.greynoise.io/viz/signup

### Installation / Usage

- Download latest zip from https://github.com/moote/WP_GreyNoise/archive/refs/heads/main.zip.
- Place zip in your plugin directory, and unzip.
- Plugin will now be available in the admin 'Plugins' section, click 'Activate'.
- Goto `Admin > Settings > WP GreyNoise`, enter your GreyNoise API key, and check the 'Enable GreyNoise?' checkbox to activate GreyNoise lookups.
- The plugin will now start ckecing the IP addresses of all visitors. You can stop / start at any time by unchecking / checking the 'Enable GreyNoise?' checkbox.
- View the logs at `Admin > WP GreyNoise`; here you can view and delete logs.

### Caveats

- Each new IP (not in log), is queried via the GreyNoise API; there is a delay loading the page as this happens. Subsequent hits are not looked up using the API to improve user load times. I would be better to implement the API calls as an async process via cron.
-  The plugin doesn't do anything with the data, it just provides the site admin with a log, automation here would be a good idea, see below.
-  

### Ideas for Improvement

- 
