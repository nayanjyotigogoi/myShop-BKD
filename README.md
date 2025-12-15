<p align="center">
  <h1 align="center">🛒 MyShop</h1>
</p>

<p align="center">
  A modern shop management system built to manage sales, discounts, stock, users, and reports using a clean API-driven architecture.
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-Backend-red" />
  <img src="https://img.shields.io/badge/Next.js-Frontend-black" />
  <img src="https://img.shields.io/badge/REST-API-blue" />
  <img src="https://img.shields.io/badge/Status-Active-success" />
</p>

## About MyShop

MyShop is a full-stack shop management application designed to handle real-world retail workflows. The system focuses on practical business logic where products can have different selling prices, discounts can be applied at the item level, and a single sale can include multiple items with or without discounts. The goal of the project is to digitize daily shop operations while keeping the system simple, scalable, and easy to use.

## Key Features

- Sales management with support for multiple items per sale  
- Item-level discount handling and actual selling price tracking  
- Product and stock management with automatic stock updates  
- Role-based access for admin and sales users  
- Sales summaries and basic reporting  
- Secure RESTful API architecture  

## Tech Stack

Backend: Laravel, PHP, MySQL, Eloquent ORM, REST APIs  
Frontend: Next.js, React, Tailwind CSS, JavaScript  
Tools: Git, GitHub, Postman, Curl, Vite, Turbopack  

## Project Structure

myShop/  
├── backend/        Laravel API  
│   ├── app/  
│   ├── routes/  
│   ├── database/  
│  
├── frontend/       Next.js application  
│   ├── app/  
│   ├── components/  
│   ├── services/  
│  
└── README.md  

## Setup Instructions

Clone the repository  
git clone https://github.com/your-username/myShop.git  
cd myShop  

Backend setup  
cd backend  
composer install  
cp .env.example .env  
php artisan key:generate  
php artisan migrate  
php artisan serve  

Frontend setup  
cd frontend  
npm install  
npm run dev  

## Business Logic

The system is designed to reflect real shop behavior where the MRP of a product may differ from the actual selling price. Discounts can be applied individually to each item within a sale, and a single sale can contain both discounted and non-discounted items. Stock quantities are updated automatically based on sales, ensuring accurate inventory tracking.

## Project Status

The project is actively under development with plans to add advanced reporting, analytics, invoice generation, and enhanced role-based permissions.

## License

This project is open-source and licensed under the MIT License.

## Author

Nayanjyoti Gogoi  
Full-Stack Laravel Developer & SAP ABAP Consultant  
Email: nayanjyoti2724@gmail.com  
LinkedIn: https://linkedin.com/in/nayanjyotigogoi  
Portfolio: https://nayanjyotigogoi.github.io/nayanjyotigogoi
