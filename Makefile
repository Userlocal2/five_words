PROJECT_NAME ?= SEPA
VERSION = 2.1.*
PROJECT_NAMESPACE ?= Gate
REGISTRY_IMAGE ?= $(PROJECT_NAMESPACE)/$(PROJECT_NAME)


help:
	@echo "make dev_build      - Create & setup development virtual environment"
	@echo "make dev_rebuild    - Destroy and Create & setup development virtual environment"
	@echo "make dev_update     - Update dev environment"
	@echo "make dev_clean_all  - Remove dev environment"
	@echo "make dev_clean_db   - Clean database in dev environment"
	@echo "make test_run       - Run tests"
	@echo "make docker_push    - Rebuild docker and push to hub"
	@exit 0


#ENVIRONMENT CREATE/UPDATE TOOLS
dev_build:
	# создаем новое окружение в через docker-compose
	docker-compose up -d --build



dev_rebuild:
	# создаем новое окружение в через docker-compose
	docker-compose rm -f -v
	docker-compose down --rmi all -v
	docker-compose up -d --build



dev_update:
	# обновление тестового окружения если необходимо
	docker-compose run app composer update


dev_clean_all:
	# чистим dev окружение
	docker-compose rm -f -v
	docker-compose down --rmi all -v
	rm -f ./config/app_local.php
	rm -f ./config/database.php
	rm -f composer.lock
	rm -fr ./vendor/*


dev_clean_db:
	docker-compose down -v
	docker-compose up -d --build
	docker-compose down


#TESTING TOOLS
test_run:
	#запуск тестов в тестовом окружении
	docker-compose run app vendor/phpunit/phpunit/phpunit


#DOCKER BUILD IMAGE
docker_push:
	# Билд и пуш нового образа app контейнера в docker hub репозиторий для последующего использования в проектах
	# TODO: необходимо параметризовать команду для создания версии через аргументы
	# TODO: необходимо доделать обновление docker-compose при выпуске новой версии.
	cd ./docker/images && docker build -t userlocal2/php8-science-arm:1.0.0 .
	cd ./docker/images && docker push userlocal2/php8-science-arm:1.0.0


