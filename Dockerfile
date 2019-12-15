FROM debian

# Set up LLMP server
RUN apt update --assume-yes && apt upgrade --assume-yes
RUN apt install curl wget gnupg2 ca-certificates lsb-release apt-transport-https --assume-yes
RUN cd /tmp
RUN wget https://packages.sury.org/php/apt.gpg
RUN apt-key add apt.gpg
RUN echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" | tee /etc/apt/sources.list.d/php7.list
RUN apt update --assume-yes
RUN apt install lighttpd --assume-yes
RUN apt install libapache2-mod-php php php-cli php7.0-mbstring php-gd --assume-yes
RUN apt install php7.0-cgi php7.0-mysql --assume-yes
RUN apt install unzip telnet --assume-yes
RUN apt install libreadline-dev --assume-yes
RUN lighttpd-enable-mod fastcgi
RUN lighttpd-enable-mod fastcgi-php
RUN DEBIAN_FRONTEND=noninteractive apt install default-mysql-server default-mysql-client --assume-yes
RUN rm /var/www/html/index.lighttpd.html

# At this point, run mysql_secure_installation and set root password to 'lwt'
# Then mysql -u root and change the authentification method

# Install LWT
COPY . /tmp/lwt
RUN cd /tmp/lwt && cp -r * /var/www/html
RUN rm -r /tmp/lwt
#ADD http://downloads.sourceforge.net/project/lwt/lwt_v_1_6_1.zip /tmp/lwt.zip
#RUN cd /var/www/html && unzip /tmp/lwt.zip && rm /tmp/lwt.zip
RUN mv /var/www/html/connect_xampp.inc.php /var/www/html/connect.inc.php
RUN chmod -R 755 /var/www/html

EXPOSE 80

RUN printf "USE mysql;\nUPDATE user SET plugin='mysql_native_password' WHERE User='root';\nFLUSH PRIVILEGES;\nSET PASSWORD FOR 'root'@'localhost' = PASSWORD('lwt');" > /tmp/fix.sql
RUN /etc/init.d/mysql start && mysql -u root --password="" < /tmp/fix.sql

CMD /etc/init.d/mysql start && /etc/init.d/lighttpd start && /bin/bash

# docker build -t csalg/lwt_fork:latest .
# docker run -itdp 8010:80 csalg/lwt_fork
