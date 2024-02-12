# Ambiente com múltiplos conteiners com aplições laravel + mysql + phpmyadmin + redis

Ambiente com nginx como proxy reverso para  múltiplos conteiners com aplições laravel + mysql + phpmyadmin + redis.

# Configuração de uma nova aplicação laravel
Cada nova aplicação laravel deve ser montada no nginx, assim como o último volume denominado PASTA_DA_NOVA_APLICACAO no exemplo abaixo:

```
nginx:
    image: nginx:1.25.3-alpine3.18
    restart: unless-stopped
    tty: true
    ports:
      - 80:80
      - 443:443
    volumes:
      - ./docker/nginx/nginx.conf:/etc/nginx/nginx.conf:ro
      - ./docker/nginx/conf.d/:/etc/nginx/conf.d/
      - ./xprob:/var/www/xprob
      - ./codigo-cassinov3/codigofonte:/var/www/cassinov3
      - NOME_DA_PASTA_DA_NOVA_APLICACAO:/var/www/NOME_DA_PASTA_DA_NOVA_APLICACAO
```

Deve ser gerado um novo arquivo de configuração do nginx e colocado na pasta docker/nginx/conf.d, precisamos colocar o root do novo server apontando para a pasta do volume da nova aplicação que foi colocada acima no nginx:

```
root /var/www/NOME_DA_PASTA_DA_NOVA_APLICACAO/public;
```

e também precisamos do nome do serviço da nova aplicação que será colocada no docker-compose.yaml, na linha fastcgi_pass:

```
fastcgi_pass nome_da_nova_aplicacao-app:9000;
```

Segue um exemplo de arquivo de configuração de um novo server nginx para a aplicação acima:

```
server {
    listen 80;
    index index.php index.html;
    server_name nome_da_nova_aplicacao.local.gd;

    error_log /var/log/nginx/error.log;
    access_log /var/log/nginx/access.log;

    error_page 404 /index.php;
    root /var/www/NOME_DA_PASTA_DA_NOVA_APLICACAO/public;

    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_pass nome_da_nova_aplicacao-app:9000;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_param PATH_INFO $fastcgi_path_info;

    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
        gzip_static on;
    }
    
}
```