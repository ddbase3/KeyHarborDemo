# KeyHarborDemo

KeyHarborDemo is a reference consumer for `CredentialFoundation`.

It demonstrates how a BASE3 plugin can:

- publish several credential-protected services from one discoverable provider
- authenticate a real HTTP bearer request
- authenticate a real HMAC-SHA256 request
- keep service grants separate from authentication
- expose useful HTTP status codes without leaking credential secrets
- test timestamp and replay protection
- remain independent of KeyHarbor implementation classes

The plugin depends on framework APIs and `CredentialFoundation`. The active implementation of `IApiCredentialService` is supplied by the final runtime composition, normally KeyHarbor.

## Services

One provider class publishes three services:

```text
keyharbordemo:ping
keyharbordemo:echo
keyharbordemo:report
```

Each service can be granted independently to a credential.

The provider is:

```text
KeyHarborDemo\Provider\KeyHarborDemoServiceProvider
```

It implements:

```text
CredentialFoundation\Api\ICredentialServiceProvider
```

## Administration UI

Base3IliasLab receives a third subtab under `API Keys`:

```text
API Keys
├── My API Keys
├── Administration
└── Demo
```

The HTML test interface requires `system/admin` permission.

The JSON endpoint performs credential authentication from real HTTP headers. Host-level routing and session requirements still depend on the active BASE3 host integration.

## Bearer request

Create a bearer credential and grant at least one demo service.

Example:

```bash
curl \
  -H 'Authorization: Bearer b3k_PUBLIC_SECRET' \
  'https://example.test/index.php?name=keyharbordemoadmindisplay&out=json&service_id=keyharbordemo%3Aping'
```

A successful response contains the normalized authentication result and the service payload:

```json
{
  "ok": true,
  "authentication": {
    "authenticated": true,
    "failure_code": "",
    "credential_id": "...",
    "user_id": 123,
    "service_id": "keyharbordemo:ping",
    "expires_at": null
  },
  "payload": {
    "message": "pong",
    "server_time": 1785140000
  }
}
```

## HMAC request

HMAC requests use these headers:

```text
Authorization: Bearer <full KeyHarbor token>
X-BASE3-Timestamp: <Unix timestamp>
X-BASE3-Nonce: <unique nonce>
X-BASE3-Signature: <lowercase HMAC-SHA256 hex>
```

The canonical request is:

```text
HTTP_METHOD
REQUEST_PATH
RAW_QUERY_STRING
UNIX_TIMESTAMP
NONCE
SHA256_HEX_OF_RAW_BODY
```

KeyHarborDemo uses `GET`, so the raw body is empty.

The HMAC key is the secret segment of the one-time KeyHarbor token:

```text
b3k_<public-id>_<secret>
                    ^^^^^^
```

The browser demo performs signing with Web Crypto and never sends the secret separately. The full token is sent in the Authorization header, while the HMAC signature proves possession of its secret segment.

## Failure tests

The UI includes two deliberate HMAC failures:

### Replay pair

The same timestamp, nonce, URL, and signature are sent twice.

Expected result:

```text
first request: authenticated
second request: replay_detected
```

### Stale timestamp

The request is signed with a timestamp two hours in the past.

Expected result:

```text
invalid_timestamp
```

## Grant test

Create a credential with only:

```text
keyharbordemo:ping
```

Then call:

```text
keyharbordemo:report
```

Expected result:

```text
HTTP 403
service_not_granted
```

No separate demo-only authorization mechanism exists. The result comes from the normal `IApiCredentialService` grant check.

## Structure

```text
KeyHarborDemo/
├── assets/keyharbordemo/
│   ├── keyharbordemo.css
│   └── keyharbordemo.js
├── lang/Administration/
│   ├── de.ini
│   └── en.ini
├── src/
│   ├── Display/KeyHarborDemoAdminDisplay.php
│   ├── Provider/KeyHarborDemoServiceProvider.php
│   ├── Service/DemoRequestAuthenticator.php
│   └── KeyHarborDemoPlugin.php
├── test/
├── tpl/Display/KeyHarborDemoAdminDisplay.php
├── LICENSE
├── README.md
└── VERSION
```

## Deployment

Install the plugin and refresh the class map:

```bash
unzip -o keyharbor-demo-step-7.zip
composer du
```

No database migration is required.

## Security notes

- Tokens are not persisted by the demo UI.
- The token field uses password display by default.
- Server responses never contain token or secret material.
- HMAC replay protection is provided by the active `IApiCredentialService` implementation.
- Grant checks are performed after credential lifecycle validation.
- The demo endpoint is intended as reference and verification tooling, not as a business API.

## Accesscontrol path

The JSON demo endpoint is authenticated by `SelectedAccesscontrol` before the display runs. The display reads the resulting user id from `IAccesscontrol`; it no longer performs a second bearer or HMAC validation. This avoids consuming the same HMAC nonce twice in one request and demonstrates the same user context that downstream usermanager checks receive.
