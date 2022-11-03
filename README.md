#Science Project 
Five Five-letter words, all letters unique

###for develop:
Run next command twice on first start for creation of all folders(set permissions) and creation app.php from app.php.default
```
    composer install
```
###for prod:
```
    composer install --no-dev
```

###Run a script to search for five five-letter words:
```
    php bin/cake.php five_words
```



###Required for stable work(PHP 8.1):
 - php-dev
 - php-intl
 - php-mbstring
 - php-sqlite
 - php-mysql
 - php-curl