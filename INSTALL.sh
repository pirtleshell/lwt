#!/bin/sh

# Download necessary packages
apt_get_cmd=$(which apt-get)
yum_cmd=$(which yum)

packages="apache2 libapache2-mod-php php php-mbstring php-mysql mysql-server"

echo "Installing dependencies"
if [ ! -z $apt_get_cmd ]; then
    sudo apt-get update
    sudo apt-get install $packages
elif [ ! -z $yum_cmd ]; then
    sudo yum update
    sudo yum install $packages
else
    echo "Error: can't install package $packages"
    exit 1;
fi

# Enable extensions for Apache
echo 
echo "Enabling mbstring and mysqli (PHP extensions necessary for LWT)..."
sudo phpenmod -s apache2 mbstring
sudo phpenmod -s apache2 mysqli

# Database access settings
echo 
echo "LWT needs to know how to access the database. You can change this options later in 'connect.inc.php'."
read -p "Database User Name [lwt]: " -r user
user=${user:-lwt}
read -p "Database Password [abcxyz]: " -r passwd
passwd=${passwd:-abcxyz}
read -p "Database Name [learning-with-texts]: " -r db_name
db_name=${db_name:-learning-with-texts}

# Create a new MySQL user
echo 
echo "Creating the MySQL database user..."
sudo mysql -e "CREATE DATABASE $db_name"
sudo mysql -e "GRANT ALL PRIVILEGES ON $db_name.* TO $user@localhost IDENTIFIED BY '$passwd'"

# Database connection parameters
echo
echo "Saving parameters in 'connect.inc.php'"
cp connect_xampp.inc.php connect.inc.php
sed -i 's/$userid = "root";/$userid = "'$user'";/' connect.inc.php
sed -i 's/$passwd = "";/$passwd = "'$passwd'";/' connect.inc.php
sed -i 's/$dbname = "learning-with-texts";/$dbname = "'$db_name'";/' connect.inc.php

# Paste folder to server space
echo 
echo "You are all set! We need to copy the content of this folder to your server path."
echo "WARNING: IT MAY ERASE ANY FILE IN THE DESTINATION FOLDER!"
read -p "Save data under [/var/www/html/lwt]: " -r dest
dest=${dest:-/var/www/html/lwt}
sudo cp -r . $dest
# We need current folder name to add read/write/execute permissions
sudo chmod -R 755 $dest

# Restarting
echo
echo "LWT was successfully copied to the destination folder."
echo "Restarting server..."
sudo service apache2 restart
sudo service mysql restart
echo "You can access LWT at http://localhost/"${dest##*/}
