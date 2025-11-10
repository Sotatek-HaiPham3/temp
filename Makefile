ifndef u
u:=sotatek
endif

ifndef env
env:=dev
endif

OS:=$(shell uname)

docker-start:
	cp laravel-echo-server.json.example laravel-echo-server.json
	@if [ $(OS) = "Linux" ]; then\
		sed -i -e "s/localhost:8000/web:8000/g" laravel-echo-server.json; \
		sed -i -e "s/\"redis\": {}/\"redis\": {\"host\": \"redis\"}/g" laravel-echo-server.json; \
	else\
		sed -i '' -e "s/localhost:8000/web:8000/g" laravel-echo-server.json; \
		sed -i '' -e "s/\"redis\": {}/\"redis\": {\"host\": \"redis\"}/g" laravel-echo-server.json; \
	fi
	docker-compose up -d

docker-start-build:
	cp laravel-echo-server.json.example laravel-echo-server.json
	@if [ $(OS) = "Linux" ]; then\
		sed -i -e "s/localhost:8000/web:8000/g" laravel-echo-server.json; \
		sed -i -e "s/\"redis\": {}/\"redis\": {\"host\": \"redis\"}/g" laravel-echo-server.json; \
	else\
		sed -i '' -e "s/localhost:8000/web:8000/g" laravel-echo-server.json; \
		sed -i '' -e "s/\"redis\": {}/\"redis\": {\"host\": \"redis\"}/g" laravel-echo-server.json; \
	fi
	docker-compose up -d --build

docker-stop:
	docker-compose stop

docker-restart:
	docker-compose down
	make docker-start
	make docker-init-db-full
	make docker-link-storage

create-env:
	cp .env.example .env
	sed -i -e "s/DB_HOST=db/DB_HOST=127.0.0.1/g" .env
	sed -i -e "s/REDIS_HOST=redis/REDIS_HOST=127.0.0.1/g" .env

docker-connect: 
	docker exec -it gamelancer-matching-api bash

init-app:
# 	cp .env.example .env
	composer install
	php artisan key:generate
	php artisan passport:keys
	php artisan migrate
	php artisan db:seed
	php artisan storage:link

docker-init:
	docker exec -it gamelancer-matching-api make init-app
# 	rm -rf node_modules
# 	npm install

init-db-full:
	make autoload
	php artisan migrate:fresh
	php artisan db:seed

docker-init-db-full:
	docker exec -it gamelancer-matching-api make init-db-full

fake-users:
	php artisan fake-users:run
	php artisan mattermost-user-create

docker-fake-users:
	docker exec -it gamelancer-matching-api make fake-users

docker-link-storage:
	docker exec -it gamelancer-matching-api php artisan storage:link

init-db:
	make autoload
	php artisan migrate:fresh

start:
	php artisan serve

log-daily:
	tail -f "./storage/logs/laravel-$(shell date +"%Y-%m-%d").log"

log:
	tail -f storage/logs/laravel.log

test-js:
	npm test

test-php:
	vendor/bin/phpcs --standard=phpcs.xml && vendor/bin/phpmd app text phpmd.xml

build:
	npm run dev

watch:
	docker exec -it gamelancer-matching-api npm run watch

docker-watch:
	docker exec -it gamelancer-matching-api make watch

autoload:
	composer dump-autoload

cache:
	php artisan cache:clear && php artisan view:clear

docker-cache:
	docker exec gamelancer-matching-api make cache

route:
	php artisan route:list

create-table:
	# Ex: make create-alter n=create_users_table t=users
	docker exec -it gamelancer-matching-api php artisan make:migration $(n) --create=$(t)

model:
	php artisan make:model Models/$(n) -m

create-model:
	# Ex: make create-model n=Test
	# Result: app/Models/Test.php
	#         database/migrations/2018_01_05_102531_create_tests_table.php
	docker exec -it gamelancer-matching-api php artisan make:model Models/$(n) -m

create-alter:
	# Ex: make create-alter n=add_votes_to_users_table t=users
	docker exec -it gamelancer-matching-api php artisan make:migration $(n) --table=$(t)

deploy:
	ssh $(u)@$(h) "mkdir -p $(dir)"
	rsync -avhzL --delete \
				--no-perms --no-owner --no-group \
				--exclude .git \
				--exclude .idea \
				--exclude .env \
				--exclude laravel-echo-server.json \
				--exclude oauth-public.key \
				--exclude storage/*.key \
				--exclude node_modules \
				--exclude /vendor \
				--exclude bootstrap/cache \
				--exclude storage/logs \
				--exclude storage/framework \
				--exclude storage/app \
				--exclude public/storage \
				--exclude .env.example \
				. $(u)@$(h):$(dir)/

deploy-dev:
	make deploy h=192.168.1.206 dir=/var/www/gamelancer-matching-api

localization:
	docker exec -it gamelancer-matching-api php artisan localization:sort

swagger:
	docker exec -it gamelancer-matching-api php artisan l5-swagger:generate

games-generate:
	docker exec -it gamelancer-matching-api php artisan games:generate

deploy-155:
	make deploy u=ubuntu h=3.101.43.155 dir=/home/ubuntu/gamelancer-matching-api
