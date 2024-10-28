
reset:
	php bin/console doctrine:schema:drop --force -q
	php bin/console doctrine:schema:create -q
	php bin/console doctrine:fixtures:load --append -q

run:
    symfony server:start

check:
    vendor/bin/rector process src --dry-run