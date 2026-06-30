# Use an official PHP image with Apache pre-installed
FROM php:8.2-apache

# Copy all your project files into the Apache web root
COPY . /var/www/html/

# Expose port 80 for the web server
EXPOSE 80
