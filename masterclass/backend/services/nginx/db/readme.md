mysql -u root -p
-- Create the database
CREATE DATABASE nginx;

-- Create the user (if they don't already exist)
CREATE USER 'gurpreet'@'%' IDENTIFIED BY 'gurpreet';

-- Grant all privileges on the nginx database to the user
GRANT ALL PRIVILEGES ON nginx.* TO 'gurpreet'@'%';

-- Make the changes take effect
FLUSH PRIVILEGES;