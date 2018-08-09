# sitecheck

A poor man's Nagios. Check on the status of websites assuming they have an endpoint that returns an object of the form:
```json
{
	"<key>": "<true|false>"
	// ...
}
```
where `key` could be `web`, `db`, etc.

## config
`sitecheck.yml` in top-level of form:
```yaml
version: 1.0
endpoints: 
	- <endpoint1>
	// etc.
```

## commands
`artisan sitecheck:site` check an arbitrary endpoint
`artisan sitecheck:config` check all sites in config 
`artisan sitecheck:summary` show a summary of recent changes

Call any command with `--help` for more details
