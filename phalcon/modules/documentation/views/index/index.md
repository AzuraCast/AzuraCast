# Documentation

***
### Features:
* Bootstrap file
* Config file
* [CLI](https://github.com/mruz/base-app/wiki/CLI) and Console file
* HMVC support
* [Volt](http://docs.phalconphp.com/en/latest/reference/volt.html), Markdown templates
* Frontend/Backend/Cli/Documentation modules
* Environment
 * _development_ - display debug, always compile template files, always minify assets
 * _testing_ - log debug, only checks for changes in the children templates, checks for changes and minify assets
 * _staging_ - log debug, notify admin, only checks for changes in the children templates, checks for changes and minify assets
 * _production_ - log debug, notify admin, don't check for differences, don't create missing files, compiled and minified files must exist before!
* Library
 * [Arr](https://github.com/mruz/base-app/wiki/Arr)
 * [Auth](https://github.com/mruz/base-app/wiki/Auth)
 * [Email](https://github.com/mruz/base-app/wiki/Email)
 * [I18n](https://github.com/mruz/base-app/wiki/I18n)
 * Markdown
 * [Tool](https://github.com/mruz/base-app/wiki/Tool)
 * Payment
     * [PayPal](http://www.paypal.com)
     * [dotpay](http://www.dotpay.pl)
* User
 * Models
 * Auth schema mysql
* Twitter Bootstrap 3.2.0

***

### Configuration:
1. Use */auth-schema-mysql.sql* to create required tables
2. Set *base_uri* and other settings in `/app/common/config/config.ini` config file:

```ini
[app]
domain = "example.com"
base_uri = "/"
static_uri = "/"
admin = "admin@example.com"
```
<br />
Enter the settings to connect to the database:
```ini
[database]
host     = "localhost"
username = "baseapp"
password = "password"
dbname   = "baseapp"
```
<br />
Change default hash keys. It is **very important** for safety reasons:
```ini
[auth]
hash_key = "secret_key"

[crypt]
key = "secret_key"
```
<br />
Prepare the application for the first run:
```bash
# go to /path/base-app/private
php index.php prepare chmod
```
***

### Requirements:
* Phalcon 1.3.2+

***

### Links:
* [Phalcon PHP](https://phalconphp.com)
* [Base-app](https://github.com/mruz/base-app)
* [Demo](http://base-app.mruz.pl)
* [Twitter Bootstrap](http://getbootstrap.com)

***

### Example volt usage:
Access to `auth` in the views:
```django
{% if auth.logged_in() %}
    {{ auth.get_user().username }}
{% endif %}
```
<br />
Easy translation with `__()` function:
```django
{% if auth.logged_in('admin') %}
    {{ __('Hello :user', [':user' : auth.get_user().username]) }}
{% endif %}
```
<br />
Use static classes:
```django
{# access to some model #}
{% set user = users__findFirst(1) %}
{{ user.username }}

{# access to class in the library #}
{{ arr__get(_POST, 'username') }}
```
<br />
Debug variables:
```php
{{ dump('string', 1, 2.5, TRUE, NULL, ['key': 'value']) }}
```