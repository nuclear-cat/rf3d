up , start: docker-up
down , stop: docker-down

assets-install:
	cd public/theme/base-2018/ && yarn prod

docker-up:
		docker-compose up -d
docker-down:
		docker-compose down --remove-orphans