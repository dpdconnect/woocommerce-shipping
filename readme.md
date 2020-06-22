# Readme

## Developing with Docker

### Networking
When developing locally with docker, be sure to add both your WooCommerce environment and the API to the same network. Consider the following docker-compose configuration:

**docker-compose.yml:**
```yaml
version: '3.5'

services:

  wordpress:
    image: wordpress:php5.6-apache
    restart: always

  db:
    image: mysql:5.7
    restart: always
```

**docker-compose.override.yml:**
```yaml
version: '3.5'

services:

  wordpress:
    volumes:
      - ./app:/var/www/html
    ports:
      - 8089:80
    networks:
      - default
      - dpd
    environment:
      WORDPRESS_DB_HOST: db
      WORDPRESS_DB_USER: exampleuser
      WORDPRESS_DB_PASSWORD: examplepass
      WORDPRESS_DB_NAME: exampledb

  db:
    volumes:
      - ./mysql-data:/var/lib/mysql
    environment:
      MYSQL_DATABASE: exampledb
      MYSQL_USER: exampleuser
      MYSQL_PASSWORD: examplepass
      MYSQL_RANDOM_ROOT_PASSWORD: '1'

networks:
  dpd:
    external:
      name: dpd
```

### Plugin configuration
When configuring the module, in the _advanced settings_ section both the _Connect URL_ and _Callback URL_ need to be configured.
* Configure _Connect URL_ to use `http://app:5000` (this may differ depending on your Docker configuration for your DPD Connect environment).
* And configure _Callback URL_ to use `http://wordpress/wp-admin/`, which is accessible to the DPD Connect api once you have put both environments in the same network (see docker-compose above).
