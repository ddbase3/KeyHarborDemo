# KeyHarborDemo FAQ

## What is KeyHarborDemo?

KeyHarborDemo is a reference and verification plugin for credential-protected BASE3 services. It demonstrates how a consumer plugin can publish multiple services through CredentialFoundation and how those services can be called with bearer or HMAC-SHA256 credentials.

The plugin is intended for development, integration testing, and architecture examples. It is not a business API.

## Does KeyHarborDemo implement credential storage?

No. It does not generate, persist, rotate, revoke, or delete credentials. It consumes the credential-access contracts supplied by the active runtime.

## Which services does the plugin expose?

`KeyHarborDemoServiceProvider` publishes three independently grantable services:

```text
keyharbordemo:ping
keyharbordemo:echo
keyharbordemo:report
```

The provider implements `ICredentialServiceProvider`.

## What does the ping service return?

The ping service returns a small payload containing:

- `message: pong`
- the current server time

It is useful for checking whether credential authentication and the specific service grant both succeed.

## What does the echo service return?

The echo service returns the caller-supplied `message` query parameter after the credential has been authorized for `keyharbordemo:echo`.

Because the value is sent in a URL query string, it should not be used for confidential or personal information in real testing environments.

## What does the report service return?

The report service returns a static example report with a generated timestamp and sample counters. It demonstrates that a separate service grant can protect another operation without introducing a second authorization mechanism.

## Does the HTML demo UI require administrator permission?

Yes. Rendering the interactive demo page requires:

```php
Permission::for('system', 'admin')
```

This protects the test interface itself.

## Does the JSON service endpoint also require system administrator permission?

Not directly. The JSON endpoint demonstrates credential-based access. Request authentication is established before the display runs, and the display then calls `ICredentialAccess::authorizeService()` for the requested demo service.

The credential owner becomes the request user through the active access-control composition.

## Why is authentication not performed again inside the JSON display?

The display intentionally does not parse and validate the bearer or HMAC credential a second time. The access-control layer has already established the request identity.

This is especially important for HMAC requests because replay protection consumes the nonce during authentication. Validating the same request twice would incorrectly look like a replay on the second check.

## What does the JSON endpoint return after successful authorization?

The response contains:

- `ok`
- a normalized authentication result
- the current access-control user id
- the selected demo payload

The authentication result can include credential id, user id, service id, and expiration timestamp.

## Which HTTP status codes are used?

The demo maps service-related authorization failures to HTTP 403:

- `service_not_found`
- `service_not_granted`

Other credential authentication failures are returned as HTTP 401.

Unexpected server errors return HTTP 500.

## How does the browser send a bearer request?

The browser sends the full token in the Authorization header:

```text
Authorization: Bearer <token>
```

The request uses `fetch()` with `credentials: same-origin` and `cache: no-store`.

## How does the browser create HMAC requests?

The browser extracts the secret segment from the pasted KeyHarbor token and uses Web Crypto to calculate:

- SHA-256 body digest
- HMAC-SHA256 signature

It generates a nonce using `crypto.randomUUID()` when available, otherwise `crypto.getRandomValues()`.

The request headers contain:

```text
Authorization: Bearer <full token>
X-BASE3-Timestamp: <Unix timestamp>
X-BASE3-Nonce: <nonce>
X-BASE3-Signature: <signature>
```

## Is the HMAC secret sent separately?

No. The browser derives the HMAC key from the secret segment of the full token and uses it locally for signing. The secret is not sent in a separate header or query parameter.

The full token is still sent in the Authorization header because the server also needs the credential public id and secret for credential validation.

## Does the demo store the pasted token in the browser?

The provided JavaScript does not use local storage, session storage, IndexedDB, or cookies for the token.

The token remains in the password input field while the page is open. The user can toggle its visual display between password and text modes.

## Does the demo copy the token to the clipboard?

No. The demo UI does not implement a copy action. The token is simply pasted into the browser field by the user.

## Does the browser send host cookies with demo requests?

The JavaScript uses `credentials: same-origin`, so the browser can include same-origin cookies according to normal browser rules.

The demo service itself authorizes the credential service grant through the credential-access boundary. Deployments should still understand how their host access-control composition treats session identity and explicit credentials.

## What is the replay test?

The replay test deliberately sends two HMAC requests with the same:

- timestamp
- nonce
- URL
- signature

The first request should succeed if the credential and grant are valid. The second should fail with `replay_detected` when the active credential implementation provides replay protection.

## What is the stale-timestamp test?

The stale test signs a request with a timestamp two hours in the past. A normal KeyHarbor configuration should reject it with `invalid_timestamp`.

## Does KeyHarborDemo persist replay data?

No. It only sends the test request. Replay-state persistence belongs to the active credential implementation.

## Can the echo message contain personal data?

Technically yes, because it is arbitrary caller input. The demo does not require or need personal data, and users should avoid entering it.

The echo message is placed into the URL query string. Query strings are commonly visible to browser history, reverse proxies, access logs, and monitoring systems depending on deployment configuration.

## Does the demo create database tables?

No. It has no migration provider and does not require its own database schema.

## Does the demo run background jobs?

No.

## Does the demo send email or notifications?

No.

## Does the demo contact external services?

No direct external service call is implemented. The browser sends requests back to the configured same-origin demo service URL.

## What does the plugin log?

On an unexpected JSON endpoint failure, the demo logs:

- exception class
- exception message

It does not intentionally log the Authorization header, token, HMAC signature, nonce, or response payload.

Infrastructure outside the plugin may have separate HTTP access or error logs.

## Should this plugin be enabled in production?

It is designed as reference and verification tooling. Whether it is appropriate to keep installed in a production runtime is a deployment decision.

If enabled, the HTML test UI should remain restricted to administrators and the credential-protected JSON endpoint should be treated as a real authenticated endpoint.

## Where can I find privacy and data-processing details?

See [PRIVACY.md](../PRIVACY.md).
