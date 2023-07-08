#FROM mysql:latest
FROM docker.sysdomain.local/mysql:5.7.12

# Create tables upon databse initialization
COPY sql/*.sql /docker-entrypoint-initdb.d/

# Copy database configuration
COPY han_db.cnf /etc/mysql/conf.d/
