FROM php:8.2-apache

# Instalar ffmpeg para conversao/fragmentacao se necessario
RUN apt-get update && apt-get install -y --no-install-recommends \
    ffmpeg \
    && rm -rf /var/lib/apt/lists/*

# Habilitar mod_rewrite do Apache
RUN a2enmod rewrite

# Ajustar configuracoes do PHP para upload de arquivos de audio ate 150MB
RUN echo "upload_max_filesize = 150M" > /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 160M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "memory_limit = 512M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "max_execution_time = 600" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "max_input_time = 600" >> /usr/local/etc/php/conf.d/uploads.ini

# Copiar arquivos do projeto para o diretorio web do Apache
COPY . /var/www/html/

# Ajustar permissoes para o Apache gravar na pasta uploads
RUN mkdir -p /var/www/html/uploads \
    && chown -R www-data:www-data /var/www/html/uploads \
    && chmod -R 777 /var/www/html/uploads

# Expor a porta 80
EXPOSE 80

CMD ["apache2-foreground"]