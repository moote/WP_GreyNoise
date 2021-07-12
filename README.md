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

### Testing

In order to simulate differnt IPs hitting the site, you can add the URL parameter `wpg_ip` with a test IP, and the plugin will log that e.g:

`https://my-wp-site.com/wp-admin/admin.php?page=wp_greynoise_dash?wpg_ip=103.123.234.37`

You can get IPs to test from GreyNoise's 'Today' page (https://www.greynoise.io/viz/query/?gnql=last_seen%3A1d), here are some examples:

```
103.123.234.37   malicious
167.248.133.77   benign
162.156.111.156  unseen
34.127.93.20     unknown
159.151.211.12   malicious, cve
```

**NOTE:** This functionality would need to be removed from any production version and is only provided as a convenience here for testing.

### Caveats

- Each new IP (not in log), is queried via the GreyNoise API; there is a delay loading the page as this happens. Subsequent hits are not looked up using the API to improve user load times. It would be better to implement the API calls as an async. process via cron.
- The plugin doesn't do anything with the data, it just provides the site admin with a log. Automation here would be a good idea, see below.

### Ideas for Improvement

Some ideas for improvement that are out of scope for this proof of concept:

- Queue IPs for processing by an async. cron job (not Wordpress' fake cron) to improve performance and stop page load delay for new users. 
- Add automated integration with firewall sofware / hardware, passing malicious IPs to them for blocking.
- Add alerts for admins when malicious IPs are acessing, and/or hits pass a acertain threshold.
- Add a filter to the dash to show only malicious IPs.
