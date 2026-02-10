# SPARK'26 - Docker Setup Guide

This guide explains how to run the SPARK'26 application using Docker with three instances on ports 10015, 10016, and 10017.

## Prerequisites

- Docker Desktop installed and running
- Docker Compose installed

## Getting Started

### 1. Navigate to the project directory

```bash
cd c:\Users\harik\OneDrive\Desktop\Git\spark
```

### 2. Build and Start the Containers

```bash
docker-compose up --build
```

This command will:
- Build the PHP application Docker image
- Start 3 PHP application instances (spark-app-1, spark-app-2, spark-app-3)
- Start MySQL database (spark-db)
- Start PhpMyAdmin for database management
- Create necessary networks and volumes

### 3. Access the Applications

Once all containers are running, access your SPARK'26 application at:

- **Instance 1**: http://localhost:10015
- **Instance 2**: http://localhost:10016
- **Instance 3**: http://localhost:10017
- **PhpMyAdmin**: http://localhost:8080

### 4. Database Credentials

When prompted for database login:
- **Username**: spark_user
- **Password**: spark_password
- **Database**: spark

For root access:
- **Username**: root
- **Password**: root_password

## Project Structure for Docker

The Docker setup includes:

```
.
├── Dockerfile                 # PHP application container definition
├── docker-compose.yml         # Multi-container orchestration
├── .dockerignore              # Files to exclude from Docker image
├── config.docker.php          # Docker-specific database config
├── .env.docker                # Docker environment variables
└── assets/schema/spark.sql    # Initial database schema
```

## Common Docker Commands

### Start containers in the background
```bash
docker-compose up -d
```

### Stop all containers
```bash
docker-compose down
```

### View logs from all containers
```bash
docker-compose logs -f
```

### View logs from a specific service
```bash
docker-compose logs -f spark-app-1
```

### Stop and remove all containers (including volumes)
```bash
docker-compose down -v
```

### Rebuild containers
```bash
docker-compose build --no-cache
```

### Access a running container shell
```bash
docker exec -it spark-app-1 bash
```

### Check container status
```bash
docker-compose ps
```

## Troubleshooting

### Port Already in Use
If ports 10015, 10016, 10017, or 3306 are already in use, modify the port mappings in `docker-compose.yml`:

```yaml
ports:
  - "10025:80"  # Change 10025 to an available port
```

### Database Connection Issues
1. Ensure the spark-db container is running:
   ```bash
   docker-compose ps
   ```

2. Check database logs:
   ```bash
   docker-compose logs spark-db
   ```

3. Verify the database schema was imported:
   ```bash
   docker-compose exec spark-db mysql -u spark_user -pspark_password spark -e "SHOW TABLES;"
   ```

### Application Not Connecting to Database
1. The database configuration in `db.php` uses localhost for the host
2. In Docker, update `db.php` to use the service name `spark-db` instead
3. Or use the provided `config.docker.php` for Docker environments

## Updating the Application

To update the application files while containers are running:

1. Edit files locally (they're volume-mounted)
2. Changes should be reflected immediately in the containers
3. For PHP files, simply refresh the browser
4. For configuration changes, you may need to restart containers:
   ```bash
   docker-compose restart
   ```

## Production Considerations

For production deployment:
- Use environment variables for sensitive data
- Implement proper logging and monitoring
- Use managed database services instead of containerized DB
- Implement container health checks
- Use a reverse proxy/load balancer for the three instances
- Enable HTTPS/SSL
- Set resource limits on containers
