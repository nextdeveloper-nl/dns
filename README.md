# NextDeveloper DNS

DNS module for the PlusClouds cloud platform - unified zone/record management
across two backends:

- **PowerDNS** (self-hosted) - managed through **pdns-agent**, a NATS-connected
  Go agent forked from `vm.agent`, talking to PowerDNS's own local REST API.
- **CloudFlare** (enterprise) - customers connect their own CloudFlare API
  token; this module calls CloudFlare's REST API v4 directly, no agent involved.

Both sit behind one `DnsProviderInterface` contract, resolved per-zone by
`DnsProviderManager` based on `DnsZones.provider` - mirrors the interface +
driver + dispatcher pattern in `NextDeveloper\IAAS\Services\HypervisorsV2`.

## Resources

- `DnsServers` - individual PowerDNS instances (primary/secondary), identified
  by their pdns-agent's `agent_uuid`.
- `DnsProviderCredentials` - a customer's own CloudFlare API token, encrypted
  at rest, never exposed back through the API.
- `DnsZones` - a DNS zone, backed by either a `DnsServers` row or a
  `DnsProviderCredentials` row depending on `provider`.
- `DnsRecords` - A/AAAA/CNAME/MX/TXT/SRV/NS/CAA records within a zone.

## Command dispatch

PowerDNS operations go through the platform's existing generic agent-command
layer (`NextDeveloper\Events\Services\AgentCommandsService::dispatch('dns', ...)`
- the same mechanism vm.agent already uses, just with `agent_type = 'dns'`),
publishing to `agent.dns.{server_uuid}.cmd` over NATS. Results come back on
`agent.dns.{server_uuid}.evt` and are picked up by
`php artisan dns:pdns-agent-listen`, which flips `DnsZones`/`DnsRecords`
status from `provisioning` to `active`/`failed`.

CloudFlare operations are synchronous HTTP calls - no agent, no NATS.
