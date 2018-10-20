---
title: API
---

Once installed and running, AzuraCast exposes an API that allows you to monitor and interact with your stations. You can perform the following functions and more from the JSON REST API:

- View now-playing data and recent song history for all stations
- View general station information
- Submit song requests (if allowed by the station)
- Start, stop and restart stations individually

## Per-Install API Documentation

Each AzuraCast installation includes documentation for the API at the exact version it's currently using. If you're interacting with an AzuraCast instance and you're not sure what API endpoints it exposes, you can visit `azuracast.site.name/api/` to view the installation-specific documentation.

## Latest Version

Documentation for the latest version of the API can be found [on the main AzuraCast site](http://azuracast.com/api/index.html).

## API Authentication

If you're accessing sensitive information or modifying the server, you will be required to authenticate your API requests with an authorization key.

You can create an API key from the AzuraCast web interface, by clicking the user menu in the top right and clicking "My API Keys". Any API keys you create will share the same permissions that you have as a user.

The preferred method of authenticating is to send the following header along with your API request:

```
Authorization: Bearer your_api_key_here
```

You can also include the API key in the `X-API-Key` header if desired.