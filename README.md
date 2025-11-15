# payment-api

```
docker-compose up --scale messenger-worker=3
```
This command starts the Docker containers defined in the `docker-compose.yml` file and scales the `messenger-worker` service to run 3 instances concurrently.

# Start all services
docker-compose up -d

docker-compose down

docker-compose up -d --build php-application

Rebuild

docker-compose -f docker-compose.yml up -d --build --force-recreate


# Check messenger worker logs
docker-compose logs -f messenger-worker

# Scale messenger workers
docker-compose up -d --scale messenger-worker=2

# Restart only messenger workers
docker-compose restart messenger-worker


# Access the PHP application
http://localhost:7000