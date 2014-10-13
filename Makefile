.PHONY:
	dev

test:
	vendor/bin/phpunit

dev:
	while inotifywait -e close_write,moved_to,create -r src tests; do vendor/bin/phpunit; done
