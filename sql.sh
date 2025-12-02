for file in /var/www/forum/database/*.sql; do 
    echo "Importing $file..."
    mysql -u superadmin -p'F0rum@G@teOf13Ve!N$' forum < "$file"
done
