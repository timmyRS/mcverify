# mcverify

A simple REST API for linking your users' Minecraft: Java Edition accounts. Powered by [Phpcraft](https://github.com/timmyrs/Phpcraft).

[Try it!](https://mcverify.de/)

## Using the REST API

You may use `https://api.mcverify.de/` but you can obviously also set up your own instance.

All endpoints respond using JSON (`application/json`).

### Starting a Challenge

    GET /start?service=...

Starts a challenge for your user to fulfil. The service parameter is used in the kick message, e.g. "Thanks! You may now return to _service_."

	{
		"address": "abcd.mcverify.de", // The address the user is to connect to.
		"versions": "1.8 - 1.13.2"     // The Minecraft version range that is supported.
	}

### Getting a Challenge's status

    GET /status/...

Returns an empty object until the challenge was completed:

	{
		"username": "timmyRS",                         // The in-game name of the Minecraft account.
		"uuid": "e0603b59-2edc-45f7-acc7-b0cccd6656e1" // The unique user ID of the Mojang account.
	}
