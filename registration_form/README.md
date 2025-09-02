# Registration Form Setup

## Steps to Run

1. Open XAMPP Control Panel
2. Start **Apache** and **MySQL** services

3. Navigate to:
```bash
C:\xampp\htdocs\
```

4. Create folder:
```
registration_form
```

5. Inside the folder, insert the project files

## Database Setup

1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Create a new database called `customer_db`
3. Create a table called `customers` with these fields:
   - `id` (INT, AUTO_INCREMENT, PRIMARY KEY)
   - `name` (VARCHAR 30)
   - `email` (VARCHAR 30)
   - `age` (INT)
   - `gender` (VARCHAR 10)
   - `color` (VARCHAR 15)
   - `message` (VARCHAR 255)


4. Access your registration form: `http://localhost/registration_form/form.html`
