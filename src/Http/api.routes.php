<?php

Route::prefix('dns')->group(function () {

Route::prefix('servers')->group(
    function () {
        Route::get('/', 'DnsServers\DnsServersController@index');
        Route::get('/actions', 'DnsServers\DnsServersController@getActions');

        Route::get('{dns_servers}/tags ', 'DnsServers\DnsServersController@tags');
        Route::post('{dns_servers}/tags ', 'DnsServers\DnsServersController@saveTags');

        Route::get('/{dns_servers}/{subObjects}', 'DnsServers\DnsServersController@relatedObjects');
        Route::get('/{dns_servers}', 'DnsServers\DnsServersController@show');

        Route::post('/', 'DnsServers\DnsServersController@store');
        Route::post('/{dns_servers}/do/{action}', 'DnsServers\DnsServersController@doAction');

        Route::patch('/{dns_servers}', 'DnsServers\DnsServersController@update');
        Route::delete('/{dns_servers}', 'DnsServers\DnsServersController@destroy');
    }
);

Route::prefix('provider-credentials')->group(
    function () {
        Route::get('/', 'DnsProviderCredentials\DnsProviderCredentialsController@index');
        Route::get('/actions', 'DnsProviderCredentials\DnsProviderCredentialsController@getActions');

        Route::get('{dns_provider_credentials}/tags ', 'DnsProviderCredentials\DnsProviderCredentialsController@tags');
        Route::post('{dns_provider_credentials}/tags ', 'DnsProviderCredentials\DnsProviderCredentialsController@saveTags');

        Route::get('/{dns_provider_credentials}/{subObjects}', 'DnsProviderCredentials\DnsProviderCredentialsController@relatedObjects');
        Route::get('/{dns_provider_credentials}', 'DnsProviderCredentials\DnsProviderCredentialsController@show');

        Route::post('/', 'DnsProviderCredentials\DnsProviderCredentialsController@store');
        Route::post('/{dns_provider_credentials}/do/{action}', 'DnsProviderCredentials\DnsProviderCredentialsController@doAction');
        Route::post('/{dns_provider_credentials}/verify', 'DnsProviderCredentials\DnsProviderCredentialsController@verify');

        Route::patch('/{dns_provider_credentials}', 'DnsProviderCredentials\DnsProviderCredentialsController@update');
        Route::delete('/{dns_provider_credentials}', 'DnsProviderCredentials\DnsProviderCredentialsController@destroy');
    }
);

Route::prefix('zones')->group(
    function () {
        Route::get('/', 'DnsZones\DnsZonesController@index');
        Route::get('/actions', 'DnsZones\DnsZonesController@getActions');

        Route::get('{dns_zones}/tags ', 'DnsZones\DnsZonesController@tags');
        Route::post('{dns_zones}/tags ', 'DnsZones\DnsZonesController@saveTags');

        Route::get('/{dns_zones}/{subObjects}', 'DnsZones\DnsZonesController@relatedObjects');
        Route::get('/{dns_zones}', 'DnsZones\DnsZonesController@show');

        Route::post('/', 'DnsZones\DnsZonesController@store');
        Route::post('/{dns_zones}/do/{action}', 'DnsZones\DnsZonesController@doAction');
        Route::post('/{dns_zones}/sync', 'DnsZones\DnsZonesController@sync');

        Route::patch('/{dns_zones}', 'DnsZones\DnsZonesController@update');
        Route::delete('/{dns_zones}', 'DnsZones\DnsZonesController@destroy');
    }
);

Route::prefix('records')->group(
    function () {
        Route::get('/', 'DnsRecords\DnsRecordsController@index');
        Route::get('/actions', 'DnsRecords\DnsRecordsController@getActions');

        Route::get('{dns_records}/tags ', 'DnsRecords\DnsRecordsController@tags');
        Route::post('{dns_records}/tags ', 'DnsRecords\DnsRecordsController@saveTags');

        Route::get('/{dns_records}/{subObjects}', 'DnsRecords\DnsRecordsController@relatedObjects');
        Route::get('/{dns_records}', 'DnsRecords\DnsRecordsController@show');

        Route::post('/', 'DnsRecords\DnsRecordsController@store');
        Route::post('/{dns_records}/do/{action}', 'DnsRecords\DnsRecordsController@doAction');

        Route::patch('/{dns_records}', 'DnsRecords\DnsRecordsController@update');
        Route::delete('/{dns_records}', 'DnsRecords\DnsRecordsController@destroy');
    }
);

});
