# =========================================================================
# Configuración de Dockerfile para la imagen de Apache con PHP 8.2
# Define el entorno de ejecución, habilita módulos requeridos por el MVC
# y ajusta el DocumentRoot hacia la carpeta pública del proyecto.
# =========================================================================

# Imagen base oficial con PHP 8.2 y servidor web Apache integrado
FROM php:8.2-apache

# Habilita el módulo mod_rewrite de Apache indispensable para el enrutador de URLs amigables
RUN a2enmod rewrite

# Instala las extensiones nativas PDO y PDO MySQL requeridas para la persistencia de datos
RUN docker-php-ext-install pdo pdo_mysql

# Redefine la variable de entorno del DocumentRoot de Apache hacia la carpeta public/ del proyecto
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -s 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -s 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf

# Configura los permisos y directivas de directorio en Apache (AllowOverride All)
# para garantizar el funcionamiento correcto de las reglas de reescritura del archivo .htaccess
RUN echo '<Directory /var/www/html/public/>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' >> /etc/apache2/apache2.conf