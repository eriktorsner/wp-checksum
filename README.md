# wp-checksum

Wp-cli subcommand for verifying checksums of themes and plugins. It checks the md5 sum of all files inside each plugin and theme and compares against what the same plugin/theme looks like on the WordPress repo. The core wp-cli command has this functionality for core, this sub command brings the same for plugins and themes.

wp-checksum checks for added, removed or modified files.

### Known issues
  - Does not ignore minor changes in a plugin/themes readme.txt file. This may lead to false positives
  - A locally developed plugin or theme may trigger false positives if a plugin or theme in the official repository has the same name.
  - Does not know how to verify premium plugins.
  - Some plugins and themes does not have all historic versions available and therefore can't be checked (but who uses old plugin versions anyway?)
  - Some plugins add files into it's own installation folder. This will trigger false positives

## Installation

For now, wp-checksum is installed manually or via compser.

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

### Syntax
  wp checksum type slug --format=table|json|csv|yaml --details

### OPTIONS
  - **type** Optional. What should we do checksums on? plugin|theme. Omit to check everything
  - **slug** Optional. Name of a specific plugin or theme to check. Leave blank to check all installed
  - **--format** Optional. How to format the output. Table (default), json, csv or yaml
  - **--details** Optional. Set this flag to output details about all modified/added/deleted files


```bash
# Check themes and plugins, format output as table (default(
$ wp checksum theme
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

Default values for parameters **details** and **format** can be entered into the wp-cli.yml file. Add a section named checksum: 

```yaml

checksum:
  details: yes
  format: json
  

```  
### Backend and local cacheing

By default, wp-checksum uses an external API for fetching the original checksums for each theme and plugin. This can be  

