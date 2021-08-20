# proxymarket
This is library for working with the Proxy.Market API

# Usage

Creating object with your token
```php
$p = new \Proxymarket\Proxymarket(YOUR_TOKEN);
```

**Getting proxy list**

```php
$list = $p->listProxy($type = 'all', $page = 1, $pageSize = 0, $sort = 0);
```
`$type` - type of the choosen proxy. It may be `all`, `ipv4` and `ipv6`

`$page` - page number

`$pageSize` - page size

`$sort` - type of the sort: newest at the top; 1 - oldest at the top

It returns an array of associative arrays

**Buying the proxy**

```php
$result = $p->buyProxy($count, $type = 'ipv4', $duration = 30, $country = 'ru', $promocode = '', $subnet = null, $speed = null);
```
`$count` - count of proxies 

`$type` - type of the proxy. It may be `ipv4` or `ipv6`

`$duration`:

- available durations for the ipv4: `30, 60, 90, 180, 360` days

- available durations for the ipv6: `3, 7, 14, 30, 60, 90, 180, 360` days

`$country` - available country `ru`

`$promocode` - Proxy market promocode (string)

`$subnet` - not null only for the ipv6. Available value:  `32, 29` (int)

`$speed` - available speeds(only for ipv6): 1 - 1mb/s, 2 - 5mb/s, 3 - 15mb/s 
