#Sepa Project
For develop project require two db (default and test)

###for develop:
Run next command twice on first start for creation of all folders(set permissions) and creation app.php from app.php.default
```
    composer install
```
###for prod:
```
    composer install --no-dev
```

###Required for stable work(PHP 7.2):
 - php-dev
 - php-intl
 - php-mbstring
 - php-sqlite
 - php-mysql
 - php-curl


##For tests:
```
    vendor/bin/phpunit
```

##Migrate and Seed DB:
```
    cd path/to/project
    bash init.sh
```

#Documentation:
##Table prefixes
<p>Add Prefix for all Tables in Plugins</p>
Rule: Plugin `Currency` Model `Rates` => Table currency_rates.

But you may specify your custom table prefix or disable it
by passing param $tablePrefix to model (false for disable or 'some_custom_pref' for custom prefix)

#Important
In Tests method setUp in parent(or in parent of parent) may reset global EventManager</br>
that is why control the calling of it(re-init all from our config/model_config.php) or prevent parent setUp call
