# PHP Files Categorization - K2 Project

**Last Updated**: September 25, 2025  
**Total PHP Files**: ~50+ files

## 🔄 **Recent Updates**
- **Sept 25, 2025**: Enhanced SalesforceService.php with duplicate prevention and improved date handling

---

## 📁 **Configuration Files**

### Root Configuration
| File | Location | Purpose |
|------|----------|---------|
| `config.php` | `/php/` | Main application configuration |
| `test-config.php` | `/` | Test environment configuration |
| `production-config.php` | `/` | Production environment configuration |
| `production-timezone-guard.php` | `/php/` | Timezone protection for production |

### Admin Configuration
| File | Location | Purpose |
|------|----------|---------|
| `config.php` | `/php/admin/` | Admin panel configuration |

---

## 🌐 **Public Pages (Frontend)**

### Main Pages
| File | Location | Purpose |
|------|----------|---------|
| `index.php` | `/php/` | Homepage - "The Legend Is Reborn" |
| `about-us.php` | `/php/` | About us page |
| `contact-us.php` | `/php/` | Contact form page |
| `book-now.php` | `/php/` | Vehicle booking page |
| `choose-variant.php` | `/php/` | Variant selection page |
| `thank-you.php` | `/php/` | Post-purchase thank you page |

### Product Pages
| File | Location | Purpose |
|------|----------|---------|
| `product-info.php` | `/php/` | Product information page |
| `range-x.php` | `/php/` | Range-X model page |
| `see-comparison.php` | `/php/` | Vehicle comparison page |

### Utility Pages
| File | Location | Purpose |
|------|----------|---------|
| `dealership-finder-pincode.php` | `/php/` | Find dealership by pincode |
| `dealership-map.php` | `/php/` | Dealership location mapping |

### Legal Pages
| File | Location | Purpose |
|------|----------|---------|
| `privacy-policy.php` | `/php/` | Privacy policy |
| `terms.php` | `/php/` | Terms and conditions |
| `delivery-policy.php` | `/php/` | Delivery policy |
| `refund-policy.php` | `/php/` | Refund policy |

---

## 🔧 **API Endpoints**

### Payment APIs
| File | Location | Purpose |
|------|----------|---------|
| `process-payment.php` | `/php/api/` | Payment processing endpoint |
| `check-status.php` | `/php/api/` | Payment status verification |

### Form Processing APIs
| File | Location | Purpose |
|------|----------|---------|
| `save-contact.php` | `/php/api/` | Contact form submission |
| `submit-test-drive.php` | `/php/api/` | Test drive request submission |

### OTP & Verification APIs
| File | Location | Purpose |
|------|----------|---------|
| `generate-otp.php` | `/php/api/` | OTP generation |
| `verify-otp.php` | `/php/api/` | OTP verification |

### Utility APIs
| File | Location | Purpose |
|------|----------|---------|
| `distance-check.php` | `/php/api/` | Distance calculation service |

---

## 🏗️ **Core System Classes**

### Database Layer
| File | Location | Purpose |
|------|----------|---------|
| `DatabaseHandler.php` | `/php/` | Main database operations |
| `DatabaseMigration.php` | `/php/` | Database schema migrations |
| `DatabaseUtils.php` | `/php/` | Database utility functions |

### Service Layer
| File | Location | Purpose |
|------|----------|---------|
| `EmailHandler.php` | `/php/` | Email sending service |
| `FileEmailHandler.php` | `/php/` | File-based email operations |
| `SalesforceService.php` | `/php/` | **ENHANCED** - Salesforce Web-to-Lead integration with duplicate prevention |
| `SmsService.php` | `/php/` | SMS sending service |
| `OtpService.php` | `/php/` | OTP generation and validation |
| `DealershipFinder.php` | `/php/` | Dealership location service |

### Utility Classes
| File | Location | Purpose |
|------|----------|---------|
| `Logger.php` | `/php/` | Application logging system |

---

## 🎨 **UI Components**

### Layout Components
| File | Location | Purpose |
|------|----------|---------|
| `layout.php` | `/php/components/` | Main layout wrapper |
| `head.php` | `/php/components/` | HTML head section |
| `header.php` | `/php/components/` | Site header |
| `footer.php` | `/php/components/` | Site footer |

### Feature Components
| File | Location | Purpose |
|------|----------|---------|
| `modals.php` | `/php/components/` | Modal dialogs |
| `scripts.php` | `/php/components/` | JavaScript includes |
| `google-maps-script.php` | `/php/components/` | Google Maps integration |

### Admin Components
| File | Location | Purpose |
|------|----------|---------|
| `admin-header.php` | `/php/components/` | Admin panel header |
| `admin-footer.php` | `/php/components/` | Admin panel footer |

### Migration Component
| File | Location | Purpose |
|------|----------|---------|
| `migrate.php` | `/php/components/` | Database migration interface |

---

## 🔐 **Admin Panel**

### Admin Core
| File | Location | Purpose |
|------|----------|---------|
| `index.php` | `/php/admin/` | Admin dashboard |
| `AdminHandler.php` | `/php/admin/` | Admin operations handler |
| `api.php` | `/php/admin/` | Admin API endpoints |

### Authentication
| File | Location | Purpose |
|------|----------|---------|
| `login.php` | `/php/admin/` | Admin login page |
| `logout.php` | `/php/admin/` | Admin logout handler |
| `reset-password.php` | `/php/admin/` | Password reset functionality |

### Admin Features
| File | Location | Purpose |
|------|----------|---------|
| `dealership.php` | `/php/admin/` | Dealership management |
| `dealership_form.php` | `/php/admin/` | Dealership form interface |

---

## 📧 **Email Templates**

### Customer Email Templates
| File | Location | Purpose |
|------|----------|---------|
| `contact-customer-email.tpl.php` | `/php/email-templates/` | Contact form confirmation |
| `test-ride-customer-email.tpl.php` | `/php/email-templates/` | Test ride booking confirmation |
| `transaction-success-customer.tpl.php` | `/php/email-templates/` | Payment success notification |
| `transaction-failure-customer.tpl.php` | `/php/email-templates/` | Payment failure notification |

### Admin Email Templates
| File | Location | Purpose |
|------|----------|---------|
| `contact-admin-email.tpl.php` | `/php/email-templates/` | Contact form admin notification |
| `test-ride-admin-email.tpl.php` | `/php/email-templates/` | Test ride admin notification |
| `transaction-success-admin.tpl.php` | `/php/email-templates/` | Payment success admin alert |
| `transaction-failure-admin.tpl.php` | `/php/email-templates/` | Payment failure admin alert |

---

## 🛠️ **Utility & Maintenance**

### Health Check
| File | Location | Purpose |
|------|----------|---------|
| `check-database-health.php` | `/php/` | Database connectivity check |

### Migration System
| File | Location | Purpose |
|------|----------|---------|
| `EmailNotificationsMigration.php` | `/php/` | Email system migration |

---

## 📊 **File Statistics**

| Category | File Count | Percentage |
|----------|------------|------------|
| **Public Pages** | 12 | 24% |
| **API Endpoints** | 7 | 14% |
| **Core Classes** | 8 | 16% |
| **UI Components** | 10 | 20% |
| **Admin Panel** | 6 | 12% |
| **Email Templates** | 8 | 16% |
| **Configuration** | 4 | 8% |
| **Utility** | 2 | 4% |
| **Total** | ~50 | 100% |

---

## 🔍 **Key Dependencies**

### External Dependencies (Composer)
- Located in `/php/vendor/` (managed by `composer.json`)

### Internal Dependencies
- **Layout System**: Most pages depend on `components/layout.php`
- **Database**: Most functionality depends on `DatabaseHandler.php`
- **Logging**: System-wide logging via `Logger.php`
- **Configuration**: All files reference configuration files
- **Email System**: Forms depend on `EmailHandler.php` and templates

---

## 📝 **Notes**

1. **Security**: `.htaccess` files present in `/php/` and `/php/admin/` for access control
2. **Logging**: Comprehensive logging system with `/php/logs/` directory
3. **Migration**: Database migration system in place for schema updates
4. **Email System**: Template-based email system with customer and admin variants
5. **API Structure**: RESTful API design in `/php/api/` directory
6. **Admin Panel**: Separate admin interface with authentication
7. **Configuration**: Environment-specific configuration files for test/production