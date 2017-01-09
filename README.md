# wp-checksum

Wp-cli subcommand for verifying checksums of themes and plugins. It checks the md5 sum of all files inside each plugin and theme and compares against what the same plugin/theme looks like on the WordPress repo. The core wp-cli command has this functionality for core, this sub command brings the same for plugins and themes.

wp-checksum checks for added, removed or modified files and prints out info about files that does not match the original file as it exists in the .org repositories.

## Backend api
wp-checksum uses a backend API (https://api.wpessentials.io) to retreive the checksums for known plugins and themes from our database. Read more about how the api and hourly rate limits work in the section "Backend api and rate limits" below.


### Known issues
  - Does not ignore minor changes in a plugin/themes readme.txt file. This may lead to false positives
  - A locally developed plugin or theme may trigger false positives if a plugin or theme in the official repository has the same name.
  - Does not know how to verify premium plugins.
  - Some plugins and themes does not have all historic versions available and therefore can't be checked (but who uses old plugin versions anyway?)
  - Some plugins add files into it's own installation folder. This will trigger false positives

## Installation

### As a wp-cli package

```bash
wp package install wp-checksum
```

### Via composer
wp-checksum can also be installed manually or via compser.

```bash
$ composer require eriktorsner/wp-checksum
```

To activate wp-checksum, you need to edit (or create) your wp-cli.yml file to make sure it includes the vendor/autoload.php file.

```bash
require:
    - vendor/autoload.php
path: /path/to/my/wordpress/installation
```

## Running wp-checksum

```bash
### SYNOPSIS

  wp checksum <command>

### SUBCOMMANDS

  all           Verify integrity of plugins and themes by comparing file checksums
  apikey        Get or set the api key stored in WordPress options table
  plugin        Verify integrity of all plugins by comparing file checksums
  quota         Print API rate limits for current api key
  register      Register email address for the current api key to increase hourly quota.
  theme         Verify integrity of all themes by comparing file checksums
```

### Global options
  - **--format** Optional. How to format the output. Table (default), json, csv or yaml
  - **--apikey** Optional. Specify key to override the default key

### wp checksum all|theme|plugin

The base functionality of wp-checksum. Verifies local checksums for everything (all), all plugins (plugin) or all themes (theme). For the plugin and theme sub commands, you can optionally specify a slug to just verify a specific plugin or theme.


#### OPTIONS
  - **slug** Optional. Name of a specific plugin or theme to check. Leave blank to check all installed
  - **--format** Optional. How to format the output. Table (default), json, csv or yaml
  - **--details** Optional. Set this flag to output details about all modified/added/deleted files
  - **--apikey** Optional. Specify key to override the default key

### wp checksum quota

Displays the current api rate limit usage.

#### OPTIONS
  - **--apikey** Optional. Specify key to override the default key

### wp checksum apikey &lt;action&gt; [&lt;apikey&gt;]

Get or set the api key stored in the current WordPress installation.

#### OPTIONS
  - **action** Mandatory. Get (print) or set (store) default key
  - **apikey** Mandatory for action=set. Specify key to store as default key in WP options table

### wp checksum register &lt;email&gt;

Connect your email address to the default (or specified via --apikey) key to raise your hourly api rate limit.

#### OPTIONS
  - **email** Mandatory. Get (print) or set (store) default key


## Examples

```bash
# Check themes and plugins, format output as table (default(
$ wp checksum all
```
```bash
# Only check themes, format as json and include details
$ wp checksum theme --format=json --details
```
```bash
# Check a specific theme, format as yaml and include details
$ wp checksum theme twentysixteen --format=yaml --details
```
```bash
# Only check plugins, omit all output except the actual data table
$ wp checksum plugin --quiet
```
```bash
# Check a specific plugin
$ wp checksum plugin jetpack
```
```bash
# Check the current API rate limit for the current api key
$ wp checksum quota
```
```bash
# Check the current API rate limit for a specific api key
$ wp checksum quota --apikey=ABC123
```
```bash
# Register email address to trigger validation email
$ wp checksum register me@example.com
```
```bash
# See the default api key
$ wp checksum apikey get
```
```bash
# Set a new default api key
$ wp checksum apikey set ABC123
```

## Output
By default, wp-checksum will output a table with information with the number of changes detected printed out;

In this example, there are two premium plugins and two plugins from the repository. The premium plugins can't be checked. One of the checked plugins has issues, the other one is fine:

```bash
$ wp checksum plugin --quiet
+--------+-----------------------------------+-----------+---------+------------------+--------+
| Type   | Slug                              | Status    | Version | Result           | Issues |
+--------+-----------------------------------+-----------+---------+------------------+--------+
| plugin | homepage-control                  | Checked   | 2.0.2   | Changes detected | 2      |
| plugin | storefront-designer               | Unchecked | 1.8.4   |                  |        |
| plugin | storefront-woocommerce-customiser | Unchecked | 1.9.1   |                  |        |
| plugin | wp-cfm                            | Checked   | 1.4.5   | Ok               | 0      |
+--------+-----------------------------------+-----------+---------+------------------+--------+
```

To get more details, the --details switch can be used:
```bash
$ wp checksum plugin --quiet --details
+--------+-----------------------------------+-----------+---------+------------------+----------------------------------------------------+
| Type   | Slug                              | Status    | Version | Result           | Issues                                             |
+--------+-----------------------------------+-----------+---------+------------------+----------------------------------------------------+
| plugin | homepage-control                  | Checked   | 2.0.2   | Changes detected | {"index.php":"MODIFIED","warez_virus.php":"ADDED"} |
| plugin | storefront-designer               | Unchecked | 1.8.4   |                  |                                                    |
| plugin | storefront-woocommerce-customiser | Unchecked | 1.9.1   |                  |                                                    |
| plugin | wp-cfm                            | Checked   | 1.4.5   | Ok               |                                                    |
+--------+-----------------------------------+-----------+---------+------------------+----------------------------------------------------+
```

Naturally, getting detailed output makes a whole lot more sense when using yaml, or json:

```json
[{
	"Type": "plugin",
	"Slug": "homepage-control",
	"Status": "Checked",
	"Version": "2.0.2",
	"Result": "Changes detected",
	"Issues": {
		"index.php": "MODIFIED",
		"warez_virus.php": "ADDED"
	}
}, {
	"Type": "plugin",
	"Slug": "storefront-designer",
	"Status": "Unchecked",
	"Version": "1.8.4",
	"Result": null,
	"Issues": null
}, {
	"Type": "plugin",
	"Slug": "storefront-woocommerce-customiser",
	"Status": "Unchecked",
	"Version": "1.9.1",
	"Result": null,
	"Issues": null
}, {
	"Type": "plugin",
	"Slug": "wp-cfm",
	"Status": "Checked",
	"Version": "1.4.5",
	"Result": "Ok",
	"Issues": null
}]
```
## Parameters in wp-cli.yml

Default values for parameters **apikey**, **details** and **format** can be entered into the wp-cli.yml file. Add a section named checksum: 

```yaml

checksum:
  details: yes
  format: json
  apikey: ABC123
``` 

## Specifying the api key

The api key can be specified in multiple ways. wp-checksum will try to locate an api key in the following order:
  1. Passed in via the --apikey command line parameter
  2. Specified in the wp-cli.yml file
  3. Specified in the environment variable WP_CHKSM_APIKEY
  4. Found in the options table in the current WordPress installation

If no api key is found in any of the above locations, wp-checksum will attempt to create an anonymous api key and store it in the WordPres options table. Creating an anonymous api key might fail if too many new keys are generated at the same time from the same source IP address.

## Backend api and hourly rate limits

The backed api and database are work in progress and requires a fair amount of work. In order to minimize various kinds of abuse, the api has an hourly rate limit. The first time you use wp-checksum, an anonymous api key is generated and stored in the WordPress options table. The anonymous key grants up 30 requests api per hour (subject to change). If you register and validate your email address, your hourly limit is raised to 100 requests per hour (subject to change). If you need to go beyond 100 requests per hour, you are welcome to subscribe to the service and paying a (small) montly fee. If you do that, also know that you are supporting a project that I think can do a lot of good for the WordPress community. Thanks in advance.

I've previously announced a plan to release the code for the backend api as open source. While I havent completely abandoned that plan it's not going to be a high priority in the short term (2017). The main reasons is that the backend API has grown a lot more complex that it initially was and it's simply not feasable to maintain that service and support other users as well.

## Change log
### Version 0.2.0
Changed default behaviour. The naked command "wp checksum" previously was a short for for sub command "wp checksum all". Now the naked command just displays usage information.
