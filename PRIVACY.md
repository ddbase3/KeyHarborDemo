# KeyHarborDemo Privacy and Data Processing

This document describes the data-processing behavior of KeyHarborDemo itself. The plugin is a reference consumer for credential-protected services. It does not implement credential persistence, credential issuance, account storage, or a message transport.

This document is technical documentation and is not a legal privacy notice.

## Purpose and scope

KeyHarborDemo provides:

- an administrator-only browser test interface
- three credential-protected demo services
- bearer request testing
- HMAC-SHA256 request signing in the browser
- replay and stale-timestamp verification requests

The plugin is intended to demonstrate the CredentialFoundation consumer boundary and the runtime access-control path.

## No plugin-specific persistent storage

KeyHarborDemo has no:

- database repository
- database migration
- state-store writes
- settings store
- file persistence
- background job
- message queue

It does not persist the pasted credential token or the demo response.

The active credential implementation and host runtime may persist their own authentication, replay, state, logging, or access-control data independently.

## Browser token handling

The demo page contains a password input for the credential token.

The provided JavaScript:

- reads the token from that input when a demo action is executed
- does not write the token to local storage
- does not write the token to session storage
- does not write the token to cookies
- does not write the token to IndexedDB
- does not copy the token to the clipboard

The token remains in the DOM input while the page is open unless the user clears or replaces it. The UI allows the user to toggle the field between password and text display.

Browser extensions, password tools, screen capture, browser memory, and the local device environment are outside the plugin's control.

## Bearer request data

For bearer mode, the browser sends the full credential token in the Authorization header:

```text
Authorization: Bearer <token>
```

The token is not placed into the URL by KeyHarborDemo.

## HMAC browser processing

For HMAC mode, the browser parses the secret segment from the pasted token and uses Web Crypto locally.

The browser processes:

- full token
- secret segment
- request method
- request path
- raw query string
- Unix timestamp
- nonce
- SHA-256 body digest
- HMAC-SHA256 signature

The secret segment is used locally to calculate the signature. It is not transmitted as a separate field.

The full token is still sent in the Authorization header together with the timestamp, nonce, and signature headers.

## Demo query parameters

The JSON endpoint uses GET query parameters.

The following values can appear in the request URL:

- `service_id`
- `message` for the echo service

The echo message is arbitrary user input. It is not required to contain personal data, and users should avoid entering sensitive content.

Because the echo value is in the query string, it can be visible in systems outside KeyHarborDemo, including depending on configuration:

- browser history
- web-server access logs
- reverse-proxy logs
- monitoring systems
- request tracing

This makes the echo endpoint unsuitable for confidential test content.

## Same-origin credentials

The browser request uses:

```text
credentials: same-origin
```

As a result, the browser may include same-origin cookies according to normal browser behavior. The plugin does not create those cookies.

Credential identity for the demo endpoint is established through the active access-control and credential-access services. The host runtime determines the broader session and authentication environment.

## Administrator test interface

Rendering the HTML test interface requires system administrator permission.

The page exposes:

- service ids and descriptions
- token input
- authentication-mode selector
- echo message input
- result display
- replay and stale-timestamp test controls

The token is never rendered by the server into the page. It is supplied by the user after page load.

## Credential-protected JSON endpoint

The JSON endpoint does not apply the administrator check used by the HTML page. It is a credential-protected service endpoint.

The display calls `ICredentialAccess::authorizeService()` for the requested demo service after the runtime has already established the credential identity.

A successful response can include:

- credential id
- current user id
- service id
- expiration timestamp
- demo payload

These values are returned to the authenticated caller and can be personal or security-relevant metadata.

## Echo service data

The echo service returns the provided `message` query value unchanged as its demo payload after authorization.

KeyHarborDemo does not store the echo message. The surrounding infrastructure may still record the URL as described above.

## Report service data

The report payload is static sample data plus the current generation timestamp. It does not read business or user records.

## Replay and stale-timestamp tests

The replay test intentionally sends the same signed request twice. The stale-timestamp test intentionally sends an old timestamp.

KeyHarborDemo itself does not persist those test values. The active credential implementation can create temporary replay-protection state as part of authentication.

## Logging

On an unexpected JSON endpoint exception, KeyHarborDemo writes an error log containing:

- exception class
- exception message

The plugin does not intentionally add these values to its log context:

- Authorization header
- credential token
- HMAC secret
- HMAC signature
- nonce
- echo message
- complete JSON response

Exception messages can still contain implementation-specific details. Operators should review the active logger and general error handling.

## HTTP infrastructure logs

KeyHarborDemo does not control web-server, reverse-proxy, firewall, CDN, or host-runtime logs.

Particular care is required for the demo echo endpoint because its caller-provided message is placed in the query string.

Authorization headers should also be protected from infrastructure logging even though the demo plugin does not log them itself.

## Network communication

The browser code constructs its service URL from the server-provided link and performs same-origin `fetch()` requests.

No analytics, telemetry, advertising, or third-party API endpoint is implemented by this plugin.

## Retention and deletion

KeyHarborDemo has no own persistent records and therefore has no plugin-specific deletion workflow.

Retention can still exist in surrounding systems such as:

- credential implementation storage
- HMAC replay state
- host session storage
- web-server access logs
- application logs
- reverse-proxy logs
- browser history

Those systems must define their own retention and deletion policies.

## Data minimization recommendations

When using the demo:

- use test credentials where practical
- do not paste production credentials into an untrusted browser environment
- do not enter personal or confidential text into the echo field
- keep the administrator interface restricted
- avoid logging Authorization headers at infrastructure level
- use HTTPS or another secure browser context for Web Crypto and credential transport
- remove or disable reference tooling in deployments where it is not operationally required

## Security boundary

The plugin demonstrates two separate checks:

1. The access-control layer establishes the credential identity.
2. The JSON endpoint authorizes the requested demo service grant.

It does not add a second credential validation step inside the display. This avoids consuming an HMAC nonce twice and mirrors the intended consumer architecture.

## Operator checklist

If KeyHarborDemo is enabled outside a development environment, verify:

- administrator access to the HTML test page
- credential protection of the JSON endpoint
- HTTPS and secure browser context
- Authorization-header handling in proxies and logs
- query-string logging policy for the echo endpoint
- active credential implementation and replay behavior
- whether reference tooling should remain installed after integration testing
