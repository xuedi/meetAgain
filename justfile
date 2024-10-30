
reset:
	php bin/console doctrine:schema:drop --force -q
	php bin/console doctrine:schema:create -q
	php bin/console doctrine:fixtures:load --append -q

deploy-cheapo-preview:
	git pull
	composer install --dev
	php bin/console cache:clear
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
    symfony server:start

check:
    vendor/bin/rector process src --dry-run