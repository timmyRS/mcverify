# mcverify

A simple REST API for linking your user's Minecraft: Java Edition accounts.

[Try it!](https://mcverify.de/)

## Using the REST API

You may use `https://api.mcverify.de/` but you can obviously also set up your own instance.

### Starting a Challenge

    GET /start?service=...

Starts a challenge for your user to fulfil. The service parameter is used in the kick message, e.g. "Thanks! You may now return to _service_." The return will `plain`ly be the address the user needs to connect to.

### Getting a Challenge's status

    GET /status/...

Returns the status of the given challenge as `application/json` which will be an empty object if the challenge was not yet completed or an object with `username` and `uuid` if it was.
