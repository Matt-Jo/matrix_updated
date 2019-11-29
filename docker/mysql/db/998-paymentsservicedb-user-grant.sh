echo '*****************************'
echo $MYSQL_DB
echo $MYSQL_ROOT_PASSWORD
mysql -u root -p$MYSQL_ROOT_PASSWORD -e \
"GRANT ALL PRIVILEGES ON $PAYMENTS_API_DB.* TO $MYSQL_USER@'%' IDENTIFIED BY '$MYSQL_PASSWORD' WITH GRANT OPTION;"
echo '*****************************'
