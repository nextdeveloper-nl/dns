# DNS Module — UI Specification

**Purpose:** This document gives a Claude instance (or any frontend developer) everything needed to build the DNS module UI. It covers every screen, the API endpoint behind it, the exact fields returned, the forms to build, and the business rules to enforce. Read [`dns-module-overview.md`](../dns-module-overview.md) first for the *why* behind the architecture this spec builds on.

---

## 1. System Context

The DNS module manages zones/records across two backends behind one API. The API prefix for all endpoints is `/dns/`.

All IDs in API requests and responses are UUIDs, named `*_id` (not `*_uuid`).

There are two user personas:

- **Platform Admin** — can see and manage the PowerDNS server fleet (`dns_servers`); can see zones/records across all accounts (via `system-admin` role bypass — see `DnsManagerRole::checkUpdatePolicy`/`checkDeletePolicy`).
- **Customer** — sees only their own zones, records, and provider credentials, scoped by `iam_account_id` (`DnsManagerRole`) or further by `iam_user_id` (`DnsUserRole`).

There is no `*-perspective` split in this module (unlike S3) — all endpoints are scoped automatically by the active role's `apply()` query scope, so the same endpoint serves both personas differently based on who's logged in.

---

## 2. Navigation Structure

```
DNS Module
├── Dashboard (zone/record counts, health summary)
├── Zones
│   ├── List
│   └── Zone Detail
│       ├── Records (tab)
│       ├── DNSSEC (tab, CloudFlare zones only)
│       └── Sync action
├── Provider Credentials (CloudFlare tokens)
│   ├── List
│   └── Create / Verify
└── [Admin Only]
    └── Servers (PowerDNS fleet)
        ├── List
        └── Server Detail
```

---

## 3. API Base URLs

| Resource | Endpoint | Notes |
|---|---|---|
| Servers | `GET /dns/servers` | Read-only for customers (`DnsManagerRole`/`DnsUserRole` both only grant `dns_servers:read`) |
| Provider Credentials | `GET/POST/PATCH/DELETE /dns/provider-credentials` | Full CRUD, scoped to the caller's account/user |
| Verify Credential | `POST /dns/provider-credentials/{id}/verify` | Re-checks the token against CloudFlare on demand |
| Zones | `GET/POST/PATCH/DELETE /dns/zones` | Full CRUD |
| Sync Zone | `POST /dns/zones/{id}/sync` | Reconciles records against the provider |
| Records | `GET/POST/PATCH/DELETE /dns/records` | Full CRUD |

All list endpoints support query-string filtering by any field (see each resource's `*QueryFilter` class for the exact filter names). All single-record endpoints: `GET /dns/{resource}/{uuid}`.

Actions (non-CRUD operations) are dispatched via: `POST /dns/{resource}/{uuid}/do/{action}` — except **Sync**, which has its own dedicated route (`POST /dns/zones/{id}/sync`) rather than going through the generic action dispatcher, since it's a first-class operation on every zone, not an optional add-on action.

---

## 4. Dashboard

There is no dedicated dashboard/stats endpoint in v1 (unlike S3's `account-stats`) — build this screen by aggregating client-side from the list endpoints below, or treat it as a follow-up once real usage patterns are known. Suggested content once built:

- Zone count by `status` (`provisioning` / `active` / `failed`) — surfaces stuck PowerDNS provisions immediately.
- Zone count by `provider` (`powerdns` / `cloudflare`).
- Any `DnsServers` with `agent_status != 'online'` or a stale `agent_last_seen_at` (admin view only) — an unhealthy PowerDNS server silently breaks every zone.

---

## 5. Zones

### 5.1 Zone List

**Endpoint:** `GET /dns/zones`

**Columns:**

| Field | Label | Notes |
|---|---|---|
| `name` | Domain | Link to zone detail |
| `provider` | Provider | Pill: `powerdns` (blue) / `cloudflare` (orange) |
| `status` | Status | Pill: `provisioning` (amber, spinner icon), `active` (green), `failed` (red) |
| `last_error` | — | Show as a tooltip/icon on the status pill when `status = failed` — don't add a separate column |
| `is_dnssec_enabled` | DNSSEC | Badge if true |
| `soa_primary_ns` | Nameserver | Only meaningful once `status = active` |
| `created_at` | Created | |

**Filters (available in UI):**
- Provider (`powerdns`, `cloudflare`)
- Status (`provisioning`, `active`, `failed`)
- Name (partial match — `DnsZonesQueryFilter::name()` does `ilike '%value%'`)

**Polling note:** any zone showing `status = provisioning` should be re-fetched periodically (e.g. every 3–5s while any row is in that state) until it flips to `active`/`failed` — PowerDNS zone creation is asynchronous (see overview doc §4). CloudFlare zones never sit in `provisioning` for more than the initial request's duration.

### 5.2 Create Zone

**Endpoint:** `POST /dns/zones`

**Form fields:**

| Field | Input type | Validation |
|---|---|---|
| `name` | text | required; the domain name, e.g. `example.com` |
| `provider` | radio / select | required; `powerdns` or `cloudflare` — **cannot be changed after creation** |
| `dns_server_id` | select | **required when provider = powerdns**; list from `GET /dns/servers` |
| `dns_provider_credential_id` | select | **required when provider = cloudflare**; list from `GET /dns/provider-credentials?filter[provider]=cloudflare` — if empty, prompt to create one first (link to §6.2) |
| `soa_admin_email` | email | optional |
| `soa_ttl` | number | optional; default 3600 |

**Business rules:**
- Toggling `provider` in the form must swap which of `dns_server_id` / `dns_provider_credential_id` is shown and required — the API enforces exactly one is set for the chosen provider (`DnsZonesService::create()` throws otherwise) and a DB constraint backs this up.
- `provider` is immutable after creation — don't render it as editable on the edit form (§5.4).
- New zones start `status = provisioning` regardless of provider; the create response reflects this even for CloudFlare zones that will typically flip to `active` almost immediately. Don't assume the create response's `status` is final — re-fetch or rely on the list screen's polling behavior.

**Success:** Navigate to the new zone's detail page, which will show `provisioning` until the status settles.

### 5.3 Zone Detail

**Endpoint:** `GET /dns/zones/{id}`

**Overview panel:**
- Name, Provider, Status (with `last_error` shown prominently when `failed`)
- SOA fields: `soa_primary_ns`, `soa_admin_email`, `soa_serial`, `soa_refresh`, `soa_retry`, `soa_expire`, `soa_ttl`
- Created At, Updated At

**DNSSEC panel** (CloudFlare zones only — hide entirely for `provider = powerdns`, since `DnssecCapableInterface` isn't implemented for PowerDNS and calling it throws):
- `is_dnssec_enabled` toggle
- When enabled, show `dnssec_ds_records` (the DS record values the registrar needs) with a copy button — these need to be published at the domain registrar, outside this platform, so make that dependency explicit in the UI copy (e.g. "Add these DS records at your domain registrar to activate DNSSEC").
- Enabling calls a dedicated action (not yet a REST route in v1 — `DnsProviderManager::enableDnssec()` exists at the service layer; expose it via `POST /dns/zones/{id}/do/enable-dnssec` once an Action class wraps it, or call it through a future dedicated endpoint. **Not yet routed as of this spec — flag to backend if the UI needs it before that lands.**)

**Records tab:** see §7 — embeds the record list scoped to this zone (`GET /dns/records?filter[dns_zone_id]={zone_id}`).

**Actions:**
- **Sync** (`POST /dns/zones/{id}/sync`) — re-sends the zone's full current record set to the provider. Useful after a failed/partial create, or if someone suspects drift. For PowerDNS this is async like create (watch `status`); for CloudFlare it's a no-op today (`CloudFlareProviderService::syncZone()` does nothing — CloudFlare is treated as its own source of truth once records exist there). Label the button honestly for CloudFlare zones (e.g. disable it or note "not needed for CloudFlare zones") rather than implying it does something it doesn't.
- **Delete** (`DELETE /dns/zones/{id}`) — confirmation required. Deletes the provider-side zone *before* removing the local record (if the provider call fails, the zone stays visible for a retry rather than disappearing while still live) — see the note in §5.5.

### 5.4 Edit Zone

**Endpoint:** `PATCH /dns/zones/{id}`

**Editable fields:** `soa_admin_email`, `soa_ttl`, `tags`.

**Not editable:** `name`, `provider`, `dns_server_id`, `dns_provider_credential_id`, `status`, `external_id`, `dnssec_ds_records`, `soa_serial` (all system-managed or immutable — don't render as form inputs even though some may technically accept a PATCH value; only enforce what's actually validated server-side in `DnsZonesUpdateRequest`, but there's no reason to expose fields that have no effect or wrong semantics if edited).

### 5.5 Delete Zone

Show a confirmation dialog:
> "Deleting this zone will remove it from [PowerDNS / CloudFlare] as well as from PlusClouds. This cannot be undone."

Because the provider-side delete happens before the local row is removed (`DnsZonesService::delete()`), a failed provider call surfaces as a normal API error (e.g. a network problem reaching pdns-agent, or a CloudFlare API error) — show it and leave the zone in place rather than assuming success.

---

## 6. Provider Credentials

### 6.1 Provider Credentials List

**Endpoint:** `GET /dns/provider-credentials`

**Columns:**

| Field | Label | Notes |
|---|---|---|
| `name` | Label | Falls back to showing `provider` if blank |
| `provider` | Provider | Currently always `cloudflare` (only provider requiring a credential) |
| `cloudflare_account_id` | CloudFlare Account | |
| `status` | Status | Pill: `active` (green), `invalid` (red) |
| `last_verified_at` | Last Verified | Relative time |
| `last_verify_error` | — | Tooltip on the status pill when `invalid`, same pattern as zone `last_error` |

**The actual token (`api_token_enc`) is never returned by the API** — the model marks it `$hidden` and the transformer never includes it. Don't build any UI that expects to display or re-populate it; the edit form (§6.3) can only *replace* it, never show the current value.

### 6.2 Create Provider Credential

**Endpoint:** `POST /dns/provider-credentials`

**Form fields:**

| Field | Input type | Validation |
|---|---|---|
| `provider` | select (currently only one option) | required; `cloudflare` |
| `api_token` | password/text | required — **note the field name is `api_token` on create/update, not `api_token_enc`**; the API remaps it internally |
| `cloudflare_account_id` | text | required when provider = cloudflare |
| `name` | text | optional but encouraged — label what this token is for |

**Business rules:**
- Submitting this form triggers an **immediate verification call** to CloudFlare (`DnsProviderCredentialsService::create()` calls `verify()` right after saving) — the response already reflects `status`/`last_verify_error`. Surface this in the UI as part of the same submit flow (e.g. "Verifying token..." spinner) rather than a separate step, since it already happens synchronously server-side.
- If verification fails, still show the created credential (it exists, just marked `invalid`) with the error and a clear "Re-verify" action (§6.4) rather than silently discarding the submission.

### 6.3 Edit Provider Credential

**Endpoint:** `PATCH /dns/provider-credentials/{id}`

**Editable fields:** `api_token` (replaces the stored token entirely — re-verifies automatically, same as create), `cloudflare_account_id`, `name`.

There is no way to view the existing token — only replace it. Label the token field as empty/placeholder ("Leave blank to keep the current token") if the form supports partial updates, or require re-entry every time, whichever matches how the form is built; either is safe since the API only re-verifies when `api_token` is actually present in the request (`DnsProviderCredentialsService::update()` only calls `verify()` `if (array_key_exists('api_token_enc', $data))`).

### 6.4 Verify (manual re-check)

**Endpoint:** `POST /dns/provider-credentials/{id}/verify`

A simple button on the list row and detail page — re-runs the CloudFlare token check on demand (useful if a customer rotated permissions on the CloudFlare side without changing the token value itself, so nothing on PlusClouds' end would otherwise trigger a re-check).

### 6.5 Delete Provider Credential

Show a confirmation dialog. Warn if any zones currently reference this credential:
> "This credential is used by N zone(s). Deleting it will break record management for those zones until a new credential is assigned."

(There's no server-side block on deleting a referenced credential in v1 — this is a UI-side warning to prevent an easy mistake, not an enforced constraint. Don't imply the API will stop it.)

---

## 7. Records

Records are always viewed/created in the context of a zone (§5.3's Records tab) — there is no bare "all records across all zones" screen in the nav structure above, though the API technically supports listing without a zone filter for anyone building an admin-wide view.

### 7.1 Record List (within Zone Detail)

**Endpoint:** `GET /dns/records?filter[dns_zone_id]={zone_id}`

**Columns:**

| Field | Label | Notes |
|---|---|---|
| `type` | Type | A, AAAA, CNAME, MX, TXT, SRV, NS, CAA |
| `name` | Name | |
| `content` | Value | Monospace |
| `ttl` | TTL | Seconds |
| `priority` | Priority | Only meaningful for MX/SRV — hide the column entirely for other types, don't just show blank |
| `is_proxied` | Proxied | **CloudFlare zones only** — hide this column entirely for PowerDNS zones, don't show a disabled/false state, since the concept doesn't exist there |
| `status` | Status | Pill, same semantics as zone status (`provisioning`/`active`/`failed`) |

**Filters:** Type, Name (partial match).

### 7.2 Create Record

**Endpoint:** `POST /dns/records`

**Form fields:**

| Field | Input type | Validation |
|---|---|---|
| `dns_zone_id` | hidden | Set from context (the zone detail page) |
| `type` | select | required; A, AAAA, CNAME, MX, TXT, SRV, NS, CAA |
| `name` | text | required |
| `content` | text | required |
| `ttl` | number | optional; default 3600 |
| `priority` | number | **required when type is MX or SRV** — enforce this in the form even though it's the same rule the API validates (`required_if:type,MX,SRV`) |
| `is_proxied` | toggle | **only show for CloudFlare zones** |

**Business rules:**
- **v1 rrset limitation** (see overview doc §6): creating a second record with the same `(name, type)` in a PowerDNS zone REPLACEs the existing rrset rather than adding to it — round-robin/multi-value records aren't merged yet. If the UI allows creating what looks like "another A record with the same name," warn the customer this will replace the previous one for PowerDNS zones specifically (CloudFlare handles this correctly via its own per-record IDs, no warning needed there).
- New records start `status = provisioning` for PowerDNS zones (same async pattern as zone creation) and `active` almost immediately for CloudFlare (synchronous).

### 7.3 Edit Record

**Endpoint:** `PATCH /dns/records/{id}`

**Editable fields:** `name`, `content`, `ttl`, `priority`, `is_proxied` (CloudFlare only).

**Not editable:** `dns_zone_id`, `type` (changing the type of an existing record isn't a coherent "update" — the correct flow is delete + create; don't offer type as an editable field).

### 7.4 Delete Record

Confirmation dialog. Same delete-provider-first-then-local ordering as zones (§5.5) — a failed provider call leaves the record in place with a normal error, not a silent partial deletion.

---

## 8. Servers (Admin Only)

### 8.1 Server List

**Endpoint:** `GET /dns/servers`

**Columns:**

| Field | Label | Notes |
|---|---|---|
| `hostname` | Hostname | |
| `name` | Label | |
| `role` | Role | `primary` / `secondary` |
| `agent_status` | Agent | Pill: `online` (green), anything else (red/grey) |
| `agent_last_seen_at` | Last Seen | Relative time — flag visually if stale (e.g. > 2 minutes, matching the heartbeat interval the agent publishes on) |
| `health` | Health | |
| `pdns_version` | PowerDNS Version | |
| `agent_version` | Agent Version | |

**Note:** `agent_api_key` is never returned by the API (model-level `$hidden`, in addition to being excluded from the transformer) — there is no screen or field anywhere that should attempt to display or copy it. It's provisioned once out-of-band when a PowerDNS server is registered, not managed through this UI in v1.

### 8.2 Server Detail

**Endpoint:** `GET /dns/servers/{id}`

Read-only in v1 for both personas (`DnsManagerRole`/`DnsUserRole` both only grant `dns_servers:read` — there's no create/edit/delete screen to build). Show all list columns plus `health_summary` and the zones currently assigned to this server (`GET /dns/zones?filter[dns_server_id]={id}`).
