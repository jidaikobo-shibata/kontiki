[![MIT](https://custom-icon-badges.herokuapp.com/badge/license-MIT-8BB80A.svg?logo=law&logoColor=white)](https://github.com/jidaikobo-shibata/kontiki/blob/main/LICENSE)

# jidaikobo/kontiki

Kontiki v1 is installed as an updateable Composer library. A stable v1 release
has not been published yet.

```bash
composer require jidaikobo/kontiki:1.0.0-alpha.1
php vendor/bin/kontiki install
```

The exact alpha version is intentional. Upgrade to later prereleases only
after reviewing and testing them against a copy of the site.

Database migrations are explicit and are never run by `composer update`:

```bash
php vendor/bin/kontiki status
php vendor/bin/kontiki migrate
```

Use `--project-dir` when running the command outside the site's Composer root,
and `--environment` for an environment other than `production`.

## Requirements

- PHP 8.2 or later
- Composer 2
- SQLite PDO extension

## License

This project is licensed under the [MIT License](https://opensource.org/licenses/MIT), see the [LICENSE file](https://github.com/jidaikobo-shibata/kontiki/blob/main/LICENSE) for details

## Author

- [jidaikobo-shibata](https://github.com/jidaikobo-shibata/)
