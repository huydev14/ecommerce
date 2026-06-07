# E-commerce Platform

An e-commerce platform with product management, shopping cart, order processing, authentication, authorization, shipping integration, and payment processing.

**Website:** [ecommerce.giahuy.tech](https://ecommerce.giahuy.tech)  

## Tech Stack

- **Backend:** Laravel, RESTful API, JWT, OAuth 2.0, Queue
- **Frontend:** Vue.js, Tailwind CSS, scss
- **Database & Storage:** MySQL, Redis
- **Integrations:** Cloudinary, GHN API, VNPAY API, Microsoft Graph API
- **DevOps:** Docker, GitHub Actions, VPS Hosting

## ERD

![ERD Diagram](https://res.cloudinary.com/trgiahuy-ecommerce/image/upload/f_auto/q_auto/documents/erd-diagram_pe7a03.png)

## Features

- Product, category, brand, variant, unit, tax, warehouse, stock, and stock movement management.
- JWT authentication for customer, using Redis to blacklist revoked tokens. Integration OAuth 2.0 (Google and Microsoft).
- Shopping cart with fast read/write operations using Redis Hashes.
- Dynamic RBAC system for flexible user role and permission management.
- OTP verification emails and order notification emails processed through Mail Queue.
- Excel/CSV import optimized with chunking, bulk insert, queue processing, and real-time progress tracking.
- Giaohangnhanh API integration for address selection, shipping fee calculation, and order creation.
- VNPAY payment gateway integration with IPN/Webhook for automatic order status updates.
- Caching for high-traffic APIs, add rate limiting to protect against brute-force and excessive requests.
- Product image optimization with Cloudinary.
- Localization support for Vietnamese and English interfaces.
- Configured for Hosting deployment with GitHub Actions, Docker


