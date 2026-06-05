# E-commerce Platform

An e-commerce platform with product management, shopping cart, order processing, authentication, authorization, shipping integration, and payment processing.

**Website:** [ecommerce.giahuy.tech](https://ecommerce.giahuy.tech)  

## Tech Stack

- **Backend:** Laravel, RESTful API, JWT, OAuth 2.0
- **Frontend:** Vue.js, Tailwind CSS, scss
- **Database & Storage:** MySQL, Redis
- **Async Processing:** Queue, Mail Queue
- **Integrations:** Cloudinary, Giaohangnhanh API, Vnpay API
- **DevOps:** Docker, GitHub Actions, VPS Hosting

## ERD

![ERD Diagram](.github/images/erd-diagram.png)

## Features

- Product, category, brand, variant, unit, tax, warehouse, stock, and stock movement management.
- Shopping cart with fast read/write operations using Redis Hashes.
- JWT authentication for customer, using Redis to blacklist revoked tokens .session-based authentication for admin.
- Integration OAuth 2.0: Google and Microsoft.
- Dynamic RBAC system for flexible user role and permission management.
- OTP verification emails and order notification emails processed through Mail Queue.
- Excel/CSV import optimized with chunking, bulk insert, queue processing, and real-time progress tracking.
- Product image optimization with Cloudinary.
- Giaohangnhanh API integration for address selection, shipping fee calculation, and order creation.
- VNPAY payment gateway integration with IPN/Webhook for automatic order status updates.
- Caching for high-traffic APIs, add rate limiting to protect against brute-force and excessive requests.
- Localization support for Vietnamese and English interfaces.
- Configured for deployment with GitHub Actions, Docker, and a VPS environment


