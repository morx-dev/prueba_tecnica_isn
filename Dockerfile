# =========================================================================
# Configuración de Dockerfile para la imagen de Apache con PHP 7.4
# Define el entorno de ejecución, habilita módulos requeridos por el MVC
# y ajusta el DocumentRoot hacia la carpeta pública del proyecto.
# =========================================================================

# Imagen base oficial con PHP 7.4 y servidor web Apache integrado
# (la prueba exige PHP 7, no PHP 8)
FROM php:7.4-apache

# Habilita el módulo mod_rewrite de Apache indispensable para el enrutador de URLs amigables
RUN a2enmod rewrite

# Instala las extensiones nativas PDO y PDO MySQL requeridas para la persistencia de datos
RUN docker-php-ext-install pdo pdo_mysql

# Redefine la variable de entorno del DocumentRoot de Apache hacia la carpeta public/ del proyecto
ENV APACHE_DOCUMENT_ROOT /var/www/html/public

# IMPORTANTE: comillas DOBLES, no simples, para que bash expanda ${APACHE_DOCUMENT_ROOT}
# antes de que sed reciba la expresion. Con comillas simples, sed recibia el texto
# literal "${APACHE_DOCUMENT_ROOT}" sin resolver, y el DocumentRoot nunca cambiaba de verdad.
RUN sed -ri -e "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/sites-available/*.conf \
    && sed -ri -e "s!/var/www/!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/apache2.conf

# Configura los permisos y directivas de directorio en Apache (AllowOverride All)
# para garantizar el funcionamiento correcto de las reglas de reescritura del archivo .htaccess
RUN { \
    echo '<Directory /var/www/html/public/>'; \
    echo '    Options Indexes FollowSymLinks'; \
    echo '    AllowOverride All'; \
    echo '    Require all granted'; \
    echo '</Directory>'; \
    } >> /etc/apache2/apache2.conf