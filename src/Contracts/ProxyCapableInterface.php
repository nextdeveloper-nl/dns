<?php

namespace NextDeveloper\DNS\Contracts;

use NextDeveloper\DNS\Database\Models\DnsRecords;

/**
 * Optional capability - CloudFlare's proxy ("orange cloud") toggle. PowerDNS
 * has no equivalent, so PowerDnsProviderService does not implement this.
 */
interface ProxyCapableInterface
{
    public function setProxied(DnsRecords $record, bool $proxied): void;
}
