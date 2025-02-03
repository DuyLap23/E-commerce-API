E-commerce API

📌 Overview

Ecommerce API is a RESTful API built using Laravel 10x to manage an online store, including product listings, orders, payments, and user authentication. This API is designed to be used with a frontend (Vue.js or any other framework).

🚀 Features

User Authentication (JWT)

Product Management (CRUD operations)

Categories & Tags

Shopping Cart & Checkout

Order Management

Payment Integration (VNPay)

Admin Dashboard

API Documentation (Swagger)

🛠️ Tech Stack

Backend: Laravel 10x (MVC Pattern)

Database: MySQL

Authentication: JWT (JSON Web Token)

Payment Gateway: VNPay

API Documentation: Swagger

🚀 Installation & Setup

1️⃣ Clone Repository

git clone https://github.com/your-repo/e-commerce-api.git
cd e-commerce-api

2️⃣ Install Dependencies

composer install

3️⃣ Setup Environment Variables

cp .env.example .env

Edit .env file with your database credentials:

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ecommerce_db
DB_USERNAME=root
DB_PASSWORD=

4️⃣ Generate Application Key

php artisan key:generate

5️⃣ Run Migrations & Seeders

php artisan migrate --seed

6️⃣ Serve the Application

php artisan serve

API will be available at http://127.0.0.1:8000

🔑 Authentication

Register

POST /api/auth/register

Login

POST /api/auth/login

Response:

{
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vMTI3LjAuMC4xOjgwMDAvYXBpL2F1dGgvbG9naW4iLCJpYXQiOjE3Mzg1NzEyODgsImV4cCI6MTczODU3NDg4OCwibmJmIjoxNzM4NTcxMjg4LCJqdGkiOiJTblE1b29lWU12Y2J1RkMzIiwic3ViIjoiMiIsInBydiI6IjIzYmQ1Yzg5NDlmNjAwYWRiMzllNzAxYzQwMDg3MmRiN2E1OTc2ZjcifQ.ey6hXIKWtrXoWYWk23nKXu3Po3SC2PobcNCnK7J-YAU",
    "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VyX2lkIjoyLCJyYW5kb20iOiIxMjc2NTQwMDM3MTczODU3MTI4OCIsImV4cCI6MTczODU4MTM2OH0.wq730y4CvTvZez1sGqdvVEFPnbnqtxPO8JJZ7jpHx58",
    "token_type": "bearer",
    "expires_in": 3600
}

Use the token in the Authorization header for authenticated requests:

Authorization: Bearer your-jwt-token

📚 API Endpoints

http://localhost:8000/api/documentation
