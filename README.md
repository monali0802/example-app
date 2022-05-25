## About application

I have done this:

- Firstly setup laravel project in local using

    -   composer create-project laravel/laravel example-app

For you download from git then perform below steps:

- then go to  example-app folder using 

    -   cd example-app

- then check app is running using 

    -   php artisan serve

- Make command to perform the operation using

    -   php artisan make:command GetCakeDetail

- If you want to check with joining date as well then I need joining date in employee detail file. Ex. [name,birthdate, joiningdate]. For this purpose use below command

    -   php artisan employee:file \emp2.txt

- If you don't want to check with joining date the use below command

    -   php artisan employee:file \emp.txt
