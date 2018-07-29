# sitecheck

A poor man's Nagios. Check on the status of websites assuming they have an endpoint that returns an object of the form:
```json
{
	"<key>": "<true|false>"
	// ...
}
```