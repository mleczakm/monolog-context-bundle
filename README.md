# Monolog Context Bundle

Symfony bundle that appends useful context to every [Monolog](https://github.com/Seldaek/monolog) log record:
the authenticated user, request details, parsed browser/User-Agent, IP geo-location, and your own static tags.
It's the successor to [monolog-sentry-bundle](https://github.com/mleczakm/monolog-sentry-bundle)'s user/tag
appending features, decoupled from Sentry so it works with any Monolog handler.

## Installation

```bash
composer require mleczakm/monolog-context-bundle
```

If you don't use Symfony Flex, register the bundle in `config/bundles.php`:

```php
return [
    // ...
    Mleczakm\MonologContextBundle\MonologContextBundle::class => ['all' => true],
];
```

## What it adds

| Feature | Config key | Log location | Requires |
|---|---|---|---|
| Authenticated user (identifier, roles, email) | `user` | `context.user` | `symfony/security-bundle`, enabled by default |
| Request id, method, URI, route, IP | `request` | `context.request` | enabled by default |
| Parsed browser name/version/platform | `browser` | `context.browser` | opt-in |
| IP geo-location (country/city) | `geo_location` | `context.geo` | opt-in |
| Static tags (app version, server, commit...) | `tags` | `extra.tags` | opt-in |

## Configuration

Defaults do the useful thing: user and request context are on, everything else is off.

```yaml
# config/packages/monolog_context.yaml
monolog_context:
    user: true    # append the authenticated user's identifier/roles/email
    request: true # append request id/method/uri/route/ip

    browser:
        enabled: true
        parser: phpuseragent # "native", "phpuseragent", or a service id implementing ParserInterface
        cache: cache.app     # optional: a Psr\SimpleCache\CacheInterface service id

    geo_location:
        enabled: true
        database_path: '%kernel.project_dir%/var/geoip/GeoLite2-City.mmdb'
        # or plug in your own resolver instead of the built-in MaxMind one:
        # resolver: App\Logging\MyGeoLocationResolver

    tags:
        app_version: '%env(APP_VERSION)%'
        commit: '%env(APP_REVISION)%'
        environment: '%env(APP_ENV)%'
```

### User context

Requires `symfony/security-bundle`. Reads the current `Symfony\Component\Security\Core\User\UserInterface` from
`TokenStorageInterface` and appends `identifier`, `roles`, `class`, and `email` (when the user object has a
`getEmail()` method).

### Request context

Appends a request id (adopted from an incoming `X-Request-Id` header, or generated as a UUID v4 otherwise) plus
method, URI, matched route and client IP - handy for correlating logs across services or the same request's log
lines.

### Browser parsing

Install one of:

```bash
composer require donatj/phpuseragentparser # "phpuseragent" parser, recommended
```

or use `parser: native`, which relies on PHP's `get_browser()` and a configured
[browscap.ini](https://browscap.org/) - slower, no extra dependency. Parsing costs roughly 0.1-1ms per request;
set `cache` to a PSR-16 cache service to avoid re-parsing the same User-Agent repeatedly.

You can also implement `Mleczakm\MonologContextBundle\UserAgent\ParserInterface` yourself and reference your
service id as `parser`.

### IP geo-location

The built-in resolver uses [MaxMind's GeoIP2 reader](https://github.com/maxmind/GeoIP2-php):

```bash
composer require geoip2/geoip2
```

Download a free `GeoLite2-City.mmdb` database from
[MaxMind](https://dev.maxmind.com/geoip/geolite2-free-geolocation-data) (registration required) and point
`database_path` at it. To use a different provider, implement
`Mleczakm\MonologContextBundle\Ip\GeoLocationResolverInterface` and set `resolver` to your service id.

### Tags

Arbitrary static `key: value` pairs merged into `extra.tags` on every record - useful for things a Sentry/ELK
dashboard likes to filter or group by, such as `symfony_version`, `commit`, or `environment`.

## License

MIT, see [LICENSE](LICENSE).
