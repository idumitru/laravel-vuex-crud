At the time of this writing we're using dev env on windows with the following setup

- install composer from https://getcomposer.org/download/
composer v1.6.5

- install npm from https://nodejs.org/en/
npm 10.4.1

You need to have a php installation with minimum v7.1.3 (required by laravel). During composer install it should be able to automatically detect the location of your php.exe

You may need to restart your dev environment (either the editor or logoff/logon) if you can't access the composer or npm in your command line


Create your dev location ex:
c:/laravel-dev

1. install laravel from https://laravel.com/docs/5.6/installation

cd laravel-dev

composer global require "laravel/installer"

laravel new blog

- this will create a new folder called blog where the laravel framework will be installed. You may change 'blog' to any name you desire


- switch to the new location
cd blog

2. install additional npm packages

npm install bootstrap-vue --save-dev

3. you can use the standard auth install from laravel

php artisan make:auth

4. create your database and modify .env to reflect your connection details and run the the initial migration

php artisan migrate

5. startup dev env 

php artisan serve

6. Point your browser to http://127.0.0.1:8000/ and you should see the new laravel app you just created
