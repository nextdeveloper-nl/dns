# DNS Module — Feature Overview

**Purpose:** This document explains what the DNS module does, how it's built, and why — for anyone (human or Claude) picking up this codebase who needs the full picture before touching code, writing docs, or building a UI on top of it.

---

## 1. What this module is

The DNS module gives PlusClouds customers managed DNS hosting through **two backends behind one API**:

- **PowerDNS** — a self-hosted, PlusClouds-operated tier. Zones live on PlusClouds' own PowerDNS server fleet.
- **CloudFlare** — an enterprise tier for customers who already have (or want) CloudFlare's edge/CDN/DNSSEC features. Customers connect **their own** CloudFlare account; PlusClouds never holds zones in a PlusClouds-owned CloudFlare account.

A zone's `provider` field is the only thing that changes which backend actually serves it — every other API endpoint, model, and validation rule is identical regardless of which one a customer picked. This is the module's central design decision: **one product, two interchangeable backends**, not two separate products bolted together.

---

## 2. Why two backends, and why this split

PowerDNS gives PlusClouds a cost-effective default that works for every customer without any setup on their part. CloudFlare gives customers who need it (usually for DDoS protection, a CDN, or advanced DNSSEC/anycast) a path to those features without PlusClouds having to build or operate any of that infrastructure itself — the customer's own CloudFlare account does the heavy lifting, PlusClouds just becomes a convenient single place to manage records alongside the rest of their infrastructure.

Because CloudFlare zones live in the *customer's* account, this module never needs to worry about CloudFlare billing, plan tiers, or rate limits on PlusClouds' side — the customer's own CloudFlare token carries all of that. The trade-off: a customer must have (or create) their own CloudFlare account and generate an API token before they can use that tier. PowerDNS has no such prerequisite.

---

## 3. Architecture: one interface, two adapters, one dispatcher

```
                        ┌─────────────────────────┐
   DnsZonesService  ───▶│   DnsProviderManager     │
   DnsRecordsService───▶│  (resolves by provider)  │
                        └───────────┬─────────────┘
                                    │
                    ┌───────────────┴───────────────┐
                    ▼                                ▼
        PowerDnsProviderService            CloudFlareProviderService
        (implements DnsProviderInterface)  (implements DnsProviderInterface
                    │                        + DnssecCapableInterface
                    │                        + ProxyCapableInterface)
                    ▼                                ▼
        AgentCommandsService::dispatch()    Direct HTTPS call to
        → NATS → pdns-agent → PowerDNS's    CloudFlare's REST API v4,
          own local REST API                using the customer's own token
```

`DnsProviderInterface` (`src/Contracts/DnsProviderInterface.php`) defines every zone/record operation both backends must support: `createZone`, `deleteZone`, `syncZone`, `createRecord`, `updateRecord`, `deleteRecord`, `listRecords`. `DnsProviderManager` (`src/Services/DnsProviderManager.php`) is the only thing that knows which adapter to call — it reads the zone's `provider` column and looks up the matching adapter class from `config('dns.providers')`. This mirrors the interface + driver + dispatcher pattern used (currently unfinished) in `NextDeveloper\IAAS\Services\HypervisorsV2` for hypervisor selection — it's the one place in this codebase's history that formalized this pattern, so the DNS module completes it rather than reinventing something else.

Two capability interfaces mark backend-specific extras that don't apply to both providers:

- **`DnssecCapableInterface`** — only `CloudFlareProviderService` implements this. Calling `DnsProviderManager::enableDnssec()` on a PowerDNS zone throws (PowerDNS *can* do DNSSEC, but that's not wired up in v1).
- **`ProxyCapableInterface`** — CloudFlare's "orange cloud" proxy/CDN toggle. PowerDNS has no equivalent concept at all.

`DnsProviderManager` checks `instanceof` before calling either of these, so calling an unsupported capability on the wrong provider fails cleanly with a clear error rather than a silent no-op or a crash.

---

## 4. How PowerDNS zones actually get created (async, agent-mediated)

This is the part that's easy to get wrong reading the code casually, so it's worth spelling out end to end:

1. `DnsZonesService::create()` inserts the `DnsZones` row with `status = 'provisioning'`, then calls `DnsProviderManager::createZone()`, which resolves to `PowerDnsProviderService::createZone()`.
2. `PowerDnsProviderService` does **not** talk to PowerDNS directly. It calls `AgentCommandsService::dispatch('dns', $server->agent_uuid, 'zone.create', [...])` — the same generic agent-command-over-NATS mechanism the platform already uses for `vm.agent` (confirmed by reading `NextDeveloper\Events\Services\AgentCommandsService`; it's parameterized by `agentType`, not hardcoded to VMs). This publishes to `agent.dns.{server_uuid}.cmd` and returns immediately — it does not wait for the result.
3. **pdns-agent** (a Go daemon, forked from `vm.agent`, running on the PowerDNS server identified by `DnsServers.agent_uuid`) receives the command and calls PowerDNS's own local REST API (`POST /servers/localhost/zones`). It never touches PowerDNS's backend database directly, so PowerDNS keeps owning its own consistency, AXFR, and NOTIFY mechanics.
4. pdns-agent publishes the result to `agent.dns.{server_uuid}.evt`.
5. `php artisan dns:pdns-agent-listen` (`src/Console/Commands/ListenPdnsAgentEvents.php`) — a long-running listener, mirroring `NextDeveloper\IAAS\Console\Commands\ListenVmAgentEvents` — picks up the result and flips `DnsZones.status` from `provisioning` to `active` or `failed` (with `last_error` populated on failure).

**Practical consequence for anything built on top of this API:** creating a PowerDNS zone or record does not mean it's live yet. The UI (or any caller) must poll or re-fetch the resource and watch `status` — `provisioning` means "command sent, no result yet," not "done." CloudFlare zones don't have this problem (see below).

## 5. How CloudFlare zones get created (synchronous, no agent)

`CloudFlareProviderService::createZone()` calls CloudFlare's REST API v4 directly over HTTPS using the zone's `DnsProviderCredentials.api_token_enc` (see §7), and updates `DnsZones.status` to `active` (or `failed`, with the CloudFlare error message in `last_error`) **within the same request** — no NATS round-trip, no agent, no polling needed. This is possible because CloudFlare is an external SaaS API call, not PlusClouds-owned infrastructure the platform has to relay commands to.

---

## 6. Data model

| Model | Purpose |
|---|---|
| `DnsServers` | One row per PowerDNS instance (primary/secondary). `agent_uuid`/`agent_api_key` identify it to `AgentCommandsService`/NATS. |
| `DnsProviderCredentials` | A customer's own CloudFlare API token, `encrypted`-cast at rest (`api_token_enc`), never returned by the API (`$hidden` on the model, and never listed in the transformer). |
| `DnsZones` | A zone. `provider` (`powerdns`\|`cloudflare`) selects the backend; exactly one of `dns_server_id` / `dns_provider_credential_id` is set to match (enforced by a DB check constraint, `dns_zones_provider_ref_check`). `external_id` holds CloudFlare's zone ID — always null for PowerDNS zones, which are addressed by name instead. |
| `DnsRecords` | A/AAAA/CNAME/MX/TXT/SRV/NS/CAA record within a zone. `is_proxied` is CloudFlare-only (silently ignored by the PowerDNS adapter). `external_id` holds CloudFlare's record ID, same null-for-PowerDNS rule as zones. |

**Known v1 limitation, by design, not oversight:** both `DnsZonesQueryFilter`'s uniqueness handling and pdns-agent's `internal/modules/powerdns` package treat each `DnsRecords` row as the entire PowerDNS *rrset* for its `(name, type)` pair. Round-robin/multi-value records sharing a name+type (e.g. two A records for the same hostname) aren't merged into one rrset on the PowerDNS side yet — doing that correctly requires fetching the existing rrset before every REPLACE, which is a real follow-up, not implemented. CloudFlare has no such limitation since it addresses every record by its own unique `external_id`.

---

## 7. Security

- **CloudFlare tokens are bring-your-own and encrypted at rest.** `DnsProviderCredentials.api_token_enc` uses Laravel's `encrypted` model cast — the column never holds plaintext, and the field is `$hidden` on the model so it can never leak through the API transformer, even by accident.
- **Token verification happens immediately, not just on first use.** `DnsProviderCredentialsService::create()`/`update()` calls CloudFlare's `/user/tokens/verify` endpoint right away and records `status` (`active`/`invalid`) plus `last_verify_error`, so a customer finds out a token is bad the moment they paste it in, not the first time they try to create a zone.
- **PowerDNS zones authenticate purely through server identity.** There's no per-zone credential for PowerDNS — the owning `DnsServers.agent_uuid`/`agent_api_key` pair is what NATS/the agent-command layer checks, matching the same auth pattern already used for `vm.agent`.

---

## 8. Authorization

Two roles, mirroring the S3 module's `S3ManagerRole`/`S3UserRole` pattern exactly:

- **`DnsManagerRole`** — full CRUD on zones/records/provider-credentials for the whole account; read-only on `dns_servers` (PowerDNS infrastructure is admin-managed, customers never create/edit server rows directly).
- **`DnsUserRole`** — same operations, scoped further to records the user personally owns (`iam_user_id`), not just the account.

---

## 9. What's genuinely new here vs. reused

To be explicit about what required new engineering versus what's an application of existing platform patterns:

**New:**
- `DnsProviderInterface` / `DnsProviderManager` / the two adapters
- The 4 database tables and their models/services/controllers
- `pdns-agent`'s `internal/modules/powerdns` package (PowerDNS REST API calls)

**Reused as-is:**
- `AgentCommandsService`/`NatsService` (agent command dispatch — already generic, just given `agentType = 'dns'`)
- `ListenVmAgentEvents`'s pattern for `ListenPdnsAgentEvents` (event listener reconciling status from command results)
- pdns-agent's transport/protocol/executor/system/services modules, copied from `vm.agent` (a deliberate fork, not a shared-library extraction — see the DNS positioning plan for why)
- The entire module skeleton (Models/Services/Controllers/Transformers/Requests/Roles), following the `NextDeveloper/S3` package template exactly
