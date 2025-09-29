# Kinetic EV Website

A modern electric vehicle company website with booking system, dealership finder, and admin portal.

## 🌐 Environment Information

- **Production Domain**: `dev.kineticev.in`
- **Application Root**: `php/` directory
- **Backend Technology**: PHP with MySQL
- **Frontend Build**: Node.js + npm

## 📚 Documentation

📋 **[PHP_FILES_CATEGORIZATION.md](./PHP_FILES_CATEGORIZATION.md)** - Comprehensive understanding of the PHP codebase structure

🚀 **[PRODUCTION_DEPLOYMENT.md](./PRODUCTION_DEPLOYMENT.md)** - Production deployment requirements and guidelines

## 🏗️ Project Architecture

### Directory Structure
```
K2/
├── php/                    # 🎯 Application Root (All backend code)
│   ├── admin/             # Admin portal & management
│   ├── api/               # RESTful API endpoints
│   ├── components/        # Reusable PHP components
│   ├── email-templates/   # Email template files
│   ├── logs/              # Application logs
│   └── vendor/            # Composer dependencies
├── src/                   # Frontend source code
│   ├── scss/              # SCSS stylesheets
│   ├── scripts/           # JavaScript modules
│   ├── public/            # Static assets
│   └── dist/              # Compiled assets (auto-generated)
└── scripts/               # Build & deployment scripts
```

### Symlink Configuration
The PHP application uses symlinks to serve frontend assets:

```bash
php/-/    ⟷  src/public/     # Static assets (images, files)
php/css/  ⟷  src/dist/css/   # Compiled CSS files
php/js/   ⟷  src/dist/js/    # Compiled JavaScript files
```

## 🛠️ Development Workflow

### Prerequisites
- Node.js (v14+)
- npm or yarn
- PHP (v8.0+)
- Composer
- MySQL/MariaDB

### Setup Instructions

1. **Install Dependencies**
   ```bash
   npm install           # Install Node.js dependencies
   composer install     # Install PHP dependencies
   ```

2. **Build Frontend Assets**
   ```bash
   npm run dev          # Development build with watching
   npm run build        # Production build
   ```

   ⚠️ **Note**: Always check if build process is already running before starting `npm run dev`

### Frontend Development

#### CSS Development
- **Location**: Write SCSS in `src/scss/`
- **Entry Point**: Import all SCSS files in `src/scss/main.scss`
- **Output**: Compiled to `src/dist/css/` and symlinked to `php/css/`

#### JavaScript Development
- **Location**: Write JavaScript in `src/scripts/`
- **Entry Point**: Import all JS modules in `src/scripts/index.js`
- **Output**: Compiled to `src/dist/js/` and symlinked to `php/js/`
- **Scope**: Normal website (excludes admin portal at `php/admin/`)

### Backend Development
- **Location**: All PHP code in `php/` directory
- **Database**: Configuration in `php/config.php`
- **Admin Portal**: Separate interface at `php/admin/`
- **API Endpoints**: RESTful APIs in `php/api/`

## 🔧 Available Scripts

```bash
npm run dev          # Start development build with file watching
npm run build        # Create production build
npm run clean        # Clean dist directory
```

## 🌟 Key Features

### Public Website
- **Homepage**: Product showcase and information
- **Booking System**: Test drive and purchase booking
- **Dealership Finder**: Location-based dealer search
- **Contact Forms**: Customer inquiry system
- **Product Comparison**: Feature comparison tools

### Admin Portal
- **Dashboard**: Overview and analytics
- **User Management**: Customer and booking management
- **Dealership Management**: Dealer network administration
- **Content Management**: Website content updates

### API Services
- **Customer APIs**: Contact, booking, and verification
- **Location Services**: Dealership finder and mapping
- **Payment Integration**: Secure payment processing
- **Notification System**: Email and SMS notifications

## 🔒 Security Features

- **Input Validation**: Comprehensive form validation
- **SQL Injection Protection**: Prepared statements
- **XSS Prevention**: Output sanitization
- **Admin Authentication**: Secure admin panel access
- **OTP Verification**: Two-factor authentication
- **HTTPS Enforcement**: SSL/TLS encryption

## 📱 Responsive Design

- **Mobile-First**: Optimized for mobile devices
- **Progressive Enhancement**: Works across all browsers
- **Touch-Friendly**: Mobile-optimized interactions
- **Fast Loading**: Optimized assets and caching

## 🚀 Deployment

### Development
```bash
npm run dev          # Start development server
```

### Production
1. Run production build: `npm run build`
2. Follow deployment guide: [PRODUCTION_DEPLOYMENT.md](./PRODUCTION_DEPLOYMENT.md)
3. Verify all symlinks are properly configured
4. Test functionality on production environment

## 📞 Support

For technical support and documentation:
- **Codebase Structure**: See [PHP_FILES_CATEGORIZATION.md](./PHP_FILES_CATEGORIZATION.md)
- **Deployment Guide**: See [PRODUCTION_DEPLOYMENT.md](./PRODUCTION_DEPLOYMENT.md)
- **Development Issues**: Check application logs in `php/logs/`

---

**Last Updated**: September 2, 2025  
**Environment**: dev.kineticev.in  
**Version**: Production Ready

