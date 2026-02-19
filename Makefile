.PHONY: install lint test check clean

install:
	composer install

lint:
	find . -name '*.php' -not -path './vendor/*' -not -path './tests/*' | xargs -I{} php -l {}

test:
	./vendor/bin/phpunit --configuration phpunit.xml

check: lint test

clean:
	rm -rf vendor/
