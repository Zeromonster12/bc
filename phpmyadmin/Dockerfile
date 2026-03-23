FROM phpmyadmin:latest

ENV PMA_HOST=projectlinker-projectlinker-akbhhp
ENV PMA_PORT=3306

RUN echo 'session.gc_maxlifetime = 3600' >> /usr/local/etc/php/conf.d/docker-phpmyadmin.ini

EXPOSE 80