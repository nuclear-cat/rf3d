up , start: docker-up
down , stop: docker-down

assets-install:
	cd public/theme/base-2018/ && yarn prod

docker-up:
		docker-compose up -d
docker-down:
		docker-compose down --remove-orphans

db-dump:
		docker-compose run --rm rf3d-db mysql -u root -p db < /var/www/app/database.sql