

reset:
	php bin/console doctrine:schema:drop --force -q
	php bin/console doctrine:schema:create -q
	php bin/console doctrine:fixtures:load --append -q
	just extendEvents


deploy:
	git pull
	composer dump-env prod
	composer install --no-dev --optimize-autoloader
	php bin/console asset-map:compile
	php bin/console doctrine:migrations:migrate --allow-no-migration --no-interaction
	APP_ENV=prod APP_DEBUG=0 php bin/console cache:clear


run:
    symfony server:start --no-tls


make:
    php bin/console make


extendEvents:
    php bin/console app:event:extent


translationsExtract:
    php bin/console translation:extract --force --format php de
    php bin/console translation:extract --force --format php en
    php bin/console translation:extract --force --format php cn


clearCache:
    composer dump-autoload
    php bin/console cache:clear


check:
    vendor/bin/rector process src --dry-run -c tools/rector.php
    vendor/bin/phpstan analyse -c tools/phpstan.neon
    #vendor/bin/psalm --threads=8 --config='tests/psalm.xml' --show-info=true
    #phpcs --_all inline since cant give config as parameter


