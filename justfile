

reset:
	php bin/console doctrine:schema:drop --force -q
	php bin/console doctrine:schema:create -q
	php bin/console doctrine:fixtures:load --append -q


deploy-cheapo-preview:
	git pull
	composer install --dev
	php bin/console doctrine:schema:drop --force -q
	php bin/console doctrine:schema:create -q
	php bin/console doctrine:fixtures:load --append -q
	php bin/console cache:clear


deploy:
	git pull
	composer dump-env prod
	composer install --no-dev --optimize-autoloader
	APP_ENV=prod APP_DEBUG=0 php bin/console cache:clear


run:
    symfony server:start --no-tls


check:
    vendor/bin/rector process src --dry-run -c tools/reactor.php
    vendor/bin/phpstan analyse -c tools/phpstan.neon
    #vendor/bin/psalm --threads=8 --config='tests/psalm.xml' --show-info=true
    #phpcs --_all inline since cant give config as parameter


