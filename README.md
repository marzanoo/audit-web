# Work Area Audit Application - Floor 4 Wahana Makmur Sejati

## Overview

The Work Area Audit Application is designed to manage and streamline the audit process for the 4th floor of Wahana Makmur Sejati. It consists of a web application built with **Laravel** and an API for mobile applications, facilitating efficient audit management with three distinct user roles: **Admin**, **Steering Committee**, and **Auditor**. The application ensures secure authentication, user management, and audit workflows, integrated with an HRIS database for employee verification.

## Features

-   **Role-Based Access Control**:
    -   **Admin**: Full control over the application, including user configuration, form management, area and floor assignments, PIC (Person in Charge) management, and audit summary generation.
    -   **Steering Committee**: Can approve audits, view summaries, and download audit reports.
    -   **Auditor**: Responsible for filling out audit forms by selecting areas and PICs, then completing the audit process.
-   **Authentication**:
    -   **Web**: Uses session-based authentication.
    -   **Mobile API**: Uses JWT (JSON Web Token) for secure access.
-   **Account Creation**:
    -   Requires a valid **NIK** (employee ID) registered in the HRIS database.
    -   Account activation via **email OTP** (One-Time Password).
    -   One account is tied to one device (based on device ID), and one device can only use one account.
-   **Login**: Uses username and password created during account setup.
-   **Audit Workflow**:
    -   Auditors select an area and PIC, then fill out audit forms.
    -   Steering Committee reviews and approves audits.
    -   Admins manage configurations and generate reports.

## Tech Stack

-   **Backend**: Laravel (PHP)
-   **Frontend**: Laravel Blade (for web)
-   **API**: RESTful API with JWT authentication for mobile
-   **Database**: Integrated with HRIS database for employee NIK validation
-   **Authentication**: Session (web), JWT (API), Email OTP for account activation
-   **Deployment**: Managed via GitHub branches (`dev`, `staging`, `prod`)

## Branch Information

This README is tailored for the following branches:

-   **Dev**: Development environment for testing new features and bug fixes.
-   **Staging**: Pre-production environment for final testing before deployment to production.
-   **Prod**: Production environment for live usage.

## Setup Instructions

### Prerequisites

-   PHP >= 8.x
-   Composer
-   MySQL or compatible database
-   Node.js and npm (for frontend assets)
-   Access to HRIS database for NIK validation
-   Mail server configuration for OTP emails
-   GitHub repository access

### Installation

1. **Clone the Repository**:
    ```bash
    git clone <repository-url>
    git checkout <branch-name>  # dev, staging, or prod
    ```
2. **Install Dependencies**:
    ```bash
    composer install
    npm install
    npm run build
    ```
3. **Environment Configuration**:
    - Copy `.env.example` to `.env` and configure:
        - Database connection details
        - HRIS database integration
        - Mail server settings for OTP
        - JWT secret for API
    ```bash
    cp .env.example .env
    ```
4. **Generate Application Key**:
    ```bash
    php artisan key:generate
    ```
5. **Run Migrations**:
    ```bash
    php artisan migrate
    ```
6. **Seed Database** (if applicable):
    ```bash
    php artisan db:seed
    ```
7. **Start the Development Server**:
    ```bash
    php artisan serve
    ```
8. **API Testing**:
    - Use tools like Postman to test API endpoints.
    - Ensure JWT tokens are included in API requests.

### Branch-Specific Notes

-   **Dev**:
    -   Used for active development and testing.
    -   May contain experimental features or incomplete code.
    -   Run `php artisan migrate:fresh` to reset the database for testing.
-   **Staging**:
    -   Mirrors production setup for final testing.
    -   Use production-like database and mail server configurations.
    -   Test all features thoroughly before merging to `prod`.
-   **Prod**:
    -   Live environment; ensure all configurations are secure.
    -   Avoid running migrations that drop data (`migrate:fresh`).
    -   Monitor logs and performance post-deployment.

## Authentication Flow

1. **Account Creation**:
    - Users provide a valid NIK, verified against the HRIS database.
    - An OTP is sent to the registered email for account activation.
    - Users set a username and password, tied to their device ID.
2. **Login**:
    - Web: Session-based login with username and password.
    - Mobile: JWT token issued after successful login, tied to device ID.
3. **Device Restriction**:
    - One account per device, enforced via device ID checks.

## Usage

-   **Admin**:
    -   Configure users, forms, areas, floors, and PICs via the web interface.
    -   View and generate audit summaries.
-   **Steering Committee**:
    -   Review and approve audit submissions.
    -   Download audit reports in the web interface.
-   **Auditor**:
    -   Log in via mobile app, select area and PIC, and fill out audit forms.
    -   Submit forms for Steering Committee approval.

## Contributing

-   Create feature branches from `dev` for new features or bug fixes.
-   Submit pull requests to `dev` for review.
-   Ensure code passes tests and follows Laravel coding standards.
-   Merge to `staging` after thorough testing, then to `prod` after approval.

## License

This project is proprietary and intended for internal use by Wahana Makmur Sejati. Unauthorized distribution or use is prohibited.
